<?php

use App\Models\District;
use Illuminate\Support\Facades\Http;

function enableGreenApiForTests(?string $testTo = '6281112223333'): void
{
    config()->set('whatsapp.enabled', true);
    config()->set('whatsapp.registration_success.enabled', true);
    config()->set('whatsapp.test_to', $testTo);
    config()->set('whatsapp.green_api.url', 'https://7105.api.greenapi.com');
    config()->set('whatsapp.green_api.id_instance', '12345');
    config()->set('whatsapp.green_api.api_token', 'token-abc');
    config()->set('whatsapp.green_api.timeout', 5);
}

it('sends registration success WhatsApp to Green API after a new registration', function () {
    enableGreenApiForTests();

    Http::fake([
        'https://7105.api.greenapi.com/*' => Http::response([
            'idMessage' => 'msg-123',
        ], 200),
    ]);

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

    $registrationCode = $response->json('data.registration_code');

    Http::assertSent(function ($request) use ($registrationCode) {
        return $request->url() === 'https://7105.api.greenapi.com/waInstance12345/sendMessage/token-abc'
            && $request['chatId'] === '6281112223333@c.us'
            && str_contains((string) $request['message'], 'Kode pendaftaran: '.$registrationCode);
    });
});

it('keeps registration successful even when Green API fails', function () {
    enableGreenApiForTests();

    Http::fake([
        'https://7105.api.greenapi.com/*' => Http::response([
            'error' => 'gateway down',
        ], 500),
    ]);

    $district = District::query()->create([
        'name' => 'Gambir',
        'code' => '310003',
    ]);

    $this->postJson('/api/register', [
        'district_id' => $district->id,
        'education_level' => 'SMA',
        'edition' => 'reguler',
        'name' => 'Raya Pratama',
        'phone_number' => '6281234567890',
        'school_name' => 'SMAN 1 Jakarta',
    ])->assertCreated();
});

it('can resend registration success WhatsApp from the artisan command', function () {
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

    enableGreenApiForTests(testTo: null);

    Http::fake([
        'https://7105.api.greenapi.com/*' => Http::response([
            'idMessage' => 'msg-456',
        ], 200),
    ]);

    $code = $response->json('data.registration_code');

    $this->artisan('app:send-registration-whatsapp', [
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
