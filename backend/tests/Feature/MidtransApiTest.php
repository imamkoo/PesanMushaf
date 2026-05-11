<?php

use App\Contracts\CreatesSnapPaymentToken;
use App\Models\District;
use App\Models\Registration;
use Illuminate\Support\Facades\Http;
use Mockery\MockInterface;

it('validates snap token request input', function () {
    $this->postJson('/api/midtrans/snap-token', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['registration_code']);
});

it('returns not found when registration code does not exist', function () {
    $this->postJson('/api/midtrans/snap-token', [
        'registration_code' => 'UNKNOWN-CODE',
    ])
        ->assertNotFound()
        ->assertJsonPath('success', false);
});

it('rejects snap token when payment is already successful', function () {
    $district = District::query()->create([
        'name' => 'Gambir',
        'code' => '310003',
    ]);

    $registrationResponse = $this->postJson('/api/register', [
        'district_id' => $district->id,
        'education_level' => 'SMA',
        'edition' => 'reguler',
        'name' => 'Raya Pratama',
        'phone_number' => '6281234567890',
        'school_name' => 'SMAN 1 Jakarta',
    ])->assertCreated();

    $code = $registrationResponse->json('data.registration_code');

    Registration::query()
        ->where('registration_code', $code)
        ->update(['payment_status' => 'success']);

    $this->postJson('/api/midtrans/snap-token', [
        'registration_code' => $code,
    ])
        ->assertStatus(422)
        ->assertJsonPath('success', false);
});

it('returns snap token and client key for a pending registration', function () {
    $this->mock(CreatesSnapPaymentToken::class, function (MockInterface $mock) {
        $mock->shouldReceive('createTokenForRegistration')
            ->once()
            ->andReturn('test-snap-token-abc');
    });

    $district = District::query()->create([
        'name' => 'Gambir',
        'code' => '310003',
    ]);

    $registrationResponse = $this->postJson('/api/register', [
        'district_id' => $district->id,
        'education_level' => 'SMA',
        'edition' => 'reguler',
        'name' => 'Raya Pratama',
        'phone_number' => '6281234567890',
        'school_name' => 'SMAN 1 Jakarta',
    ])->assertCreated();

    $code = $registrationResponse->json('data.registration_code');

    $this->postJson('/api/midtrans/snap-token', [
        'registration_code' => $code,
    ])
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.snap_token', 'test-snap-token-abc')
        ->assertJsonPath('data.order_id', $code)
        ->assertJsonPath('data.client_key', config('midtrans.client_key'));
});

it('rejects Midtrans notifications with an invalid signature', function () {
    $this->postJson('/api/midtrans/notification', [
        'order_id' => 'ANY',
        'status_code' => '200',
        'gross_amount' => '10000',
        'signature_key' => 'invalid',
    ])
        ->assertStatus(400)
        ->assertJsonPath('success', false);
});

it('updates registration to success on a valid settlement notification', function () {
    $serverKey = (string) config('midtrans.server_key');

    $district = District::query()->create([
        'name' => 'Gambir',
        'code' => '310003',
    ]);

    $registrationResponse = $this->postJson('/api/register', [
        'district_id' => $district->id,
        'education_level' => 'SMA',
        'edition' => 'reguler',
        'name' => 'Raya Pratama',
        'phone_number' => '6281234567890',
        'school_name' => 'SMAN 1 Jakarta',
    ])->assertCreated();

    $code = $registrationResponse->json('data.registration_code');
    $total = (string) $registrationResponse->json('data.financial.total_payment');

    $signature = hash('sha512', $code.'200'.$total.$serverKey);

    $this->postJson('/api/midtrans/notification', [
        'order_id' => $code,
        'status_code' => '200',
        'gross_amount' => $total,
        'transaction_status' => 'settlement',
        'signature_key' => $signature,
    ])
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    expect(Registration::query()->where('registration_code', $code)->value('payment_status'))->toBe('success');
});

it('syncs payment status via Midtrans Status API when settlement is returned', function () {
    $district = District::query()->create([
        'name' => 'Gambir',
        'code' => '310003',
    ]);

    $registrationResponse = $this->postJson('/api/register', [
        'district_id' => $district->id,
        'education_level' => 'SMA',
        'edition' => 'reguler',
        'name' => 'Raya Pratama',
        'phone_number' => '6281234567890',
        'school_name' => 'SMAN 1 Jakarta',
    ])->assertCreated();

    $code = $registrationResponse->json('data.registration_code');

    $statusUrl = 'https://api.sandbox.midtrans.com/v2/'.rawurlencode($code).'/status';

    Http::fake([
        $statusUrl => Http::response([
            'order_id' => $code,
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
        ], 200),
    ]);

    $this->postJson('/api/midtrans/sync-status', [
        'registration_code' => $code,
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.payment_status', 'success');

    expect(Registration::query()->where('registration_code', $code)->value('payment_status'))->toBe('success');
});
