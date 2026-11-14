<?php

use App\Filament\Resources\ApiKeys\Tables\ApiKeysTable;
use App\Models\Batch;
use App\Models\District;
use App\Models\Mosque;
use App\Models\University;
use App\Models\User;

function makeDistrictWithBatch(): array
{
    $district = District::query()->create(['name' => 'Cakung', 'code' => '3172080']);

    $batch = Batch::query()->create([
        'district_id' => $district->id,
        'name' => 'Mushaf Reguler SMA Cakung 1',
        'batch_number' => '1',
        'education_level' => 'SMA',
        'max_capacity' => 603,
        'is_full' => false,
    ]);

    return [$district, $batch];
}

test('district detail resolves by numeric id and by slug', function () {
    [$district] = makeDistrictWithBatch();

    $this->getJson("/api/districts/{$district->id}")->assertOk()->assertJsonPath('data.slug', 'cakung');
    $this->getJson('/api/districts/cakung')->assertOk()->assertJsonPath('data.id', $district->id);
});

test('batch detail resolves by numeric id and by slug', function () {
    [, $batch] = makeDistrictWithBatch();

    $this->getJson("/api/batches/{$batch->id}")->assertOk()->assertJsonPath('data.slug', $batch->slug);
    $this->getJson("/api/batches/{$batch->slug}")->assertOk()->assertJsonPath('data.id', $batch->id);
});

test('unknown or malformed identifiers return json 404 instead of 500', function (string $path) {
    makeDistrictWithBatch();

    $this->get($path)
        ->assertNotFound()
        ->assertHeader('Content-Type', 'application/json');
})->with([
    'district slug unknown' => '/api/districts/tidak-ada',
    'district id unknown' => '/api/districts/999999',
    'district bigint overflow' => '/api/districts/99999999999999999999999',
    'district zero id' => '/api/districts/0',
    'batch slug unknown' => '/api/batches/tidak-ada',
    'mosque non numeric' => '/api/mosques/abc',
    'university non numeric' => '/api/universities/abc',
]);

test('mosque and university still resolve by numeric id', function () {
    $university = University::query()->create(['name' => 'Universitas Indonesia', 'type' => 'PTN', 'city' => 'Depok']);
    $mosque = Mosque::query()->create([
        'dmi_code' => 'M-1',
        'name' => 'Masjid Istiqlal',
        'district_name' => 'Sawah Besar',
        'city' => 'Jakarta Pusat',
        'province' => 'DKI Jakarta',
        'source' => 'dmi',
        'is_verified' => true,
        'is_active' => true,
    ]);

    $this->getJson("/api/universities/{$university->id}")->assertOk();
    $this->getJson("/api/mosques/{$mosque->id}")->assertOk();
});

test('admin edit pages still resolve records by id', function () {
    [$district, $batch] = makeDistrictWithBatch();
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get("/admin/districts/{$district->id}/edit")->assertOk();
    $this->actingAs($admin)->get("/admin/batches/{$batch->id}/edit")->assertOk();
});

test('api responses carry security headers', function () {
    $response = $this->get('/api/districts');

    $response->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none'")
        ->assertHeaderMissing('X-Powered-By')
        ->assertHeaderMissing('Strict-Transport-Security');
});

test('https responses carry hsts and web pages allow same-origin framing', function () {
    $this->get('https://localhost/api/districts')
        ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

    $this->get('/admin/login')
        ->assertOk()
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeaderMissing('Content-Security-Policy');
});

test('api key is masked in the admin table', function () {
    $masked = ApiKeysTable::mask('mushaf_abcdefghijklmnopqrstuvwxyz123456');

    expect($masked)
        ->toStartWith('mushaf_abc')
        ->toEndWith('3456')
        ->not->toContain('mnopqrst');

    expect(ApiKeysTable::mask(null))->toBe('—');
});

test('rate limit keys on the cloudflare client ip, not a spoofed forwarded-for', function () {
    $headers = ['Accept' => 'application/json', 'CF-Connecting-IP' => '203.0.113.10'];

    $first = $this->withHeaders($headers + ['X-Forwarded-For' => '198.51.100.1'])->postJson('/api/register', []);
    $second = $this->withHeaders($headers + ['X-Forwarded-For' => '198.51.100.2'])->postJson('/api/register', []);

    $first->assertStatus(422)->assertHeader('X-RateLimit-Remaining', '19');
    $second->assertStatus(422)->assertHeader('X-RateLimit-Remaining', '18');
});

test('spoofed forwarded-for is ignored when the edge header is absent', function () {
    $first = $this->withHeaders(['Accept' => 'application/json', 'X-Forwarded-For' => '198.51.100.3'])
        ->postJson('/api/register', []);
    $second = $this->withHeaders(['Accept' => 'application/json', 'X-Forwarded-For' => '198.51.100.4'])
        ->postJson('/api/register', []);

    $first->assertStatus(422)->assertHeader('X-RateLimit-Remaining', '19');
    $second->assertStatus(422)->assertHeader('X-RateLimit-Remaining', '18');
});

test('cf-connecting-ip is ignored when the origin is not behind cloudflare', function () {
    config(['app.trust_cf_connecting_ip' => false]);

    $first = $this->withHeaders(['Accept' => 'application/json', 'CF-Connecting-IP' => '203.0.113.20'])
        ->postJson('/api/register', []);
    $second = $this->withHeaders(['Accept' => 'application/json', 'CF-Connecting-IP' => '203.0.113.21'])
        ->postJson('/api/register', []);

    $first->assertStatus(422)->assertHeader('X-RateLimit-Remaining', '19');
    $second->assertStatus(422)->assertHeader('X-RateLimit-Remaining', '18');
});

test('browsing read endpoints does not consume the registration throttle budget', function () {
    foreach (range(1, 25) as $i) {
        $this->getJson('/api/price-categories')->assertOk();
    }

    $this->postJson('/api/register', [])->assertStatus(422);
});

test('registration throttle still limits to 20 per minute on its own bucket', function () {
    foreach (range(1, 20) as $i) {
        $this->postJson('/api/register', [])->assertStatus(422);
    }

    $this->postJson('/api/register', [])->assertStatus(429);
});
