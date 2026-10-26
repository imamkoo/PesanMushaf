<?php

use App\Models\District;
use App\Models\Registration;
use Illuminate\Support\Facades\Http;

function enablePaymentSuccessWhatsAppForTests(?string $testTo = '6281112223333'): void
{
    config()->set('whatsapp.enabled', true);
    config()->set('whatsapp.registration_success.enabled', false);
    config()->set('whatsapp.payment_success.enabled', true);
    config()->set('whatsapp.test_to', $testTo);
    config()->set('whatsapp.green_api.url', 'https://7105.api.greenapi.com');
    config()->set('whatsapp.green_api.id_instance', '12345');
    config()->set('whatsapp.green_api.api_token', 'token-abc');
    config()->set('whatsapp.green_api.timeout', 5);
    config()->set('whatsapp.green_api.verify_ssl', false);
}

it('sends payment success WhatsApp on a valid Midtrans settlement webhook', function () {
    enablePaymentSuccessWhatsAppForTests();

    Http::fake([
        'https://7105.api.greenapi.com/*' => Http::response([
            'idMessage' => 'msg-123',
        ], 200),
    ]);

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

    $registration = Registration::query()->where('registration_code', $code)->firstOrFail();

    expect($registration->payment_status)->toBe('success')
        ->and($registration->whatsapp_payment_notified_at)->not->toBeNull();

    Http::assertSent(function ($request) use ($code) {
        return $request->url() === 'https://7105.api.greenapi.com/waInstance12345/sendMessage/token-abc'
            && $request['chatId'] === '6281112223333@c.us'
            && str_contains((string) $request['message'], 'Kode pendaftaran: '.$code)
            && str_contains((string) $request['message'], 'Status: Lunas');
    });
});

it('sends payment success WhatsApp when sync status changes to success', function () {
    enablePaymentSuccessWhatsAppForTests();

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
        'https://7105.api.greenapi.com/*' => Http::response([
            'idMessage' => 'msg-456',
        ], 200),
    ]);

    $this->postJson('/api/midtrans/sync-status', [
        'registration_code' => $code,
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.payment_status', 'success');

    $registration = Registration::query()->where('registration_code', $code)->firstOrFail();

    expect($registration->payment_status)->toBe('success')
        ->and($registration->whatsapp_payment_notified_at)->not->toBeNull();

    Http::assertSent(function ($request) use ($code) {
        return $request->url() === 'https://7105.api.greenapi.com/waInstance12345/sendMessage/token-abc'
            && str_contains((string) $request['message'], 'Kode pendaftaran: '.$code);
    });
});

it('does not send payment success WhatsApp twice when webhook is followed by sync', function () {
    enablePaymentSuccessWhatsAppForTests();

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
    $statusUrl = 'https://api.sandbox.midtrans.com/v2/'.rawurlencode($code).'/status';

    Http::fake([
        $statusUrl => Http::response([
            'order_id' => $code,
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
        ], 200),
        'https://7105.api.greenapi.com/*' => Http::response([
            'idMessage' => 'msg-789',
        ], 200),
    ]);

    $this->postJson('/api/midtrans/notification', [
        'order_id' => $code,
        'status_code' => '200',
        'gross_amount' => $total,
        'transaction_status' => 'settlement',
        'signature_key' => $signature,
    ])->assertSuccessful();

    $this->postJson('/api/midtrans/sync-status', [
        'registration_code' => $code,
    ])->assertSuccessful();

    $greenApiRequests = collect(Http::recorded())
        ->filter(fn (array $call) => str_contains($call[0]->url(), 'api.greenapi.com'))
        ->count();

    expect($greenApiRequests)->toBe(1);
});

it('can resend payment success WhatsApp from the artisan command', function () {
    config()->set('whatsapp.enabled', false);

    $district = District::query()->create([
        'name' => 'Gambir',
        'code' => '310003',
    ]);

    $response = $this->postJson('/api/register', [
        'district_id' => $district->id,
        'education_level' => 'SMA',
        'edition' => 'reguler',
        'name' => 'Raya Pratama',
        'phone_number' => '6281234567890',
        'school_name' => 'SMAN 1 Jakarta',
    ])->assertCreated();

    $code = $response->json('data.registration_code');

    Registration::query()
        ->where('registration_code', $code)
        ->update([
            'payment_status' => 'success',
            'whatsapp_payment_notified_at' => now(),
        ]);

    enablePaymentSuccessWhatsAppForTests(testTo: null);

    Http::fake([
        'https://7105.api.greenapi.com/*' => Http::response([
            'idMessage' => 'msg-999',
        ], 200),
    ]);

    $this->artisan('app:send-payment-success-whatsapp', [
        'registration_code' => $code,
        '--to' => '6287770001112',
    ])
        ->expectsOutputToContain('berhasil dikirim')
        ->assertSuccessful();

    Http::assertSent(function ($request) {
        return $request->url() === 'https://7105.api.greenapi.com/waInstance12345/sendMessage/token-abc'
            && $request['chatId'] === '6287770001112@c.us';
    });
});
