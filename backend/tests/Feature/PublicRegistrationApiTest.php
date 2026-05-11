<?php

use App\Models\Batch;
use App\Models\District;
use App\Models\Registration;
use App\Models\SchoolSuggestion;
use App\Models\University;
use App\Services\RegistrationService;
use Illuminate\Support\Str;

function makeDistrict(string $name, string $code): District
{
    return District::query()->create([
        'name' => $name,
        'code' => $code,
    ]);
}

function makeNik(): string
{
    $nik = '';
    for ($i = 0; $i < 16; $i++) {
        $nik .= (string) random_int(0, 9);
    }

    return $nik;
}

function postReg(District $district, string $level, string $school, ?string $phone = null, array $extra = [])
{
    $payload = array_merge([
        'district_id' => $district->id,
        'education_level' => $level,
        'edition' => 'reguler',
        'name' => 'Tester Reg',
        'phone_number' => $phone ?? '628' . random_int(1_000_000_000, 9_999_999_999),
        'school_name' => $school,
    ], $extra);

    return test()->postJson('/api/register', $payload);
}

function postVipWithDocs(District $district, string $level, ?string $phone = null, array $extra = [])
{
    $base = [
        'district_id' => $district->id,
        'education_level' => $level,
        'edition' => 'vip',
        'name' => 'Tester VIP',
        'phone_number' => $phone ?? '628' . random_int(1_000_000_000, 9_999_999_999),
        'school_name' => 'X-Inst',
    ];

    // NIK & alamat hanya wajib untuk jenjang UMUM. Untuk VIP SD/SMP/SMA kita
    // tidak mengirimkannya, supaya helper memantulkan kontrak API yang baru.
    if ($level === 'UMUM') {
        $base['nik'] = makeNik();
        $base['address'] = 'Jl. VIP Tester No. 1';
    }

    return test()->postJson('/api/register', array_merge($base, $extra));
}

it('allows the public status endpoint without an api key', function () {
    $this->getJson('/api/registrations/status')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['lookup']);
});

it('returns not found when the lookup does not match any registration', function () {
    $this->getJson('/api/registrations/status?lookup=UNKNOWN-CODE')
        ->assertNotFound()
        ->assertJson([
            'success' => false,
            'message' => 'Kode pendaftaran atau nomor WhatsApp tidak ditemukan.',
        ]);
});

it('can look up a registration status with the phone number only', function () {
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

    $registrationCode = $registrationResponse->json('data.registration_code');

    $this->getJson('/api/registrations/status?lookup=6281234567890')
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.0.registration_code', $registrationCode)
        ->assertJsonPath('data.0.district', 'Gambir');
});

it('returns every registration code when one WhatsApp number has multiple registrations', function () {
    $district = District::query()->create([
        'name' => 'Gambir',
        'code' => '310003',
    ]);

    $first = $this->postJson('/api/register', [
        'district_id' => $district->id,
        'education_level' => 'SMA',
        'edition' => 'reguler',
        'name' => 'Raya Pratama',
        'phone_number' => '6281999888777',
        'school_name' => 'SMAN 1 Jakarta',
    ])->assertCreated();

    $second = $this->postJson('/api/register', [
        'district_id' => $district->id,
        'education_level' => 'SMP',
        'edition' => 'reguler',
        'name' => 'Raya Pratama',
        'phone_number' => '6281999888777',
        'school_name' => 'SMPN 2 Jakarta',
    ])->assertCreated();

    $firstCode = $first->json('data.registration_code');
    $secondCode = $second->json('data.registration_code');

    $this->getJson('/api/registrations/status?lookup=6281999888777')
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment(['registration_code' => $firstCode])
        ->assertJsonFragment(['registration_code' => $secondCode]);
});

it('returns active price categories from the public api', function () {
    $this->getJson('/api/price-categories')
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment(['slug' => 'reguler'])
        ->assertJsonFragment(['slug' => 'vip']);
});

it('allows the public registration endpoint without an api key', function () {
    $this->postJson('/api/register', [])
        ->assertUnprocessable()
        ->assertJson([
            'success' => false,
            'message' => 'Data pendaftaran tidak valid.',
        ]);
});

it('allows public read-only master data endpoints without an api key', function () {
    $this->getJson('/api/districts')
        ->assertSuccessful()
        ->assertJsonStructure(['data']);

    $this->getJson('/api/batches')
        ->assertSuccessful()
        ->assertJsonStructure(['data']);
});

it('registers a participant and can look up the generated code', function () {
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
        'email' => 'raya@example.test',
    ])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => [
                'registration_code',
                'financial' => ['base_price', 'total_payment'],
            ],
        ])
        ->assertJsonPath('data.email', 'raya@example.test');

    $registrationCode = $registrationResponse->json('data.registration_code');

    $this->getJson("/api/registrations/status?lookup={$registrationCode}")
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.0.registration_code', $registrationCode)
        ->assertJsonPath('data.0.status', 'pending')
        ->assertJsonPath('data.0.district', 'Gambir');
});

it('returns school options from the master catalog when no registrations exist yet', function () {
    $district = District::query()->create([
        'name' => 'Senen',
        'code' => '319990',
    ]);

    SchoolSuggestion::query()->create([
        'district_id' => $district->id,
        'education_level' => 'SMP',
        'name' => 'SMP Negeri 5 Senen',
    ]);

    $this->getJson("/api/school-options?district_id={$district->id}&education_level=SMP")
        ->assertSuccessful()
        ->assertJsonPath('data.0.label', 'SMP Negeri 5 Senen')
        ->assertJsonPath('data.0.value', 'SMP Negeri 5 Senen');
});

it('returns school options sourced purely from the master catalog', function () {
    $district = District::query()->create([
        'name' => 'Gambir',
        'code' => '310003',
    ]);

    SchoolSuggestion::query()->create([
        'district_id' => $district->id,
        'education_level' => 'SMA',
        'name' => 'SMAN 1 Jakarta',
    ]);

    // Registrasi tidak boleh ikut tersurfacing di autocomplete walaupun
    // educational level / district-nya cocok.
    $this->postJson('/api/register', [
        'district_id' => $district->id,
        'education_level' => 'SMA',
        'edition' => 'reguler',
        'name' => 'Raya Pratama',
        'phone_number' => '6281234567890',
        'school_name' => 'SMAN 1 Jakarta',
    ])->assertCreated();

    $this->getJson("/api/school-options?district_id={$district->id}&education_level=SMA")
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.label', 'SMAN 1 Jakarta')
        ->assertJsonPath('data.0.value', 'SMAN 1 Jakarta');
});

it('does not list manual school names in public school options regardless of exclude flag', function () {
    $district = District::query()->create([
        'name' => 'Cengkareng',
        'code' => '310005',
    ]);

    $this->postJson('/api/register', [
        'district_id' => $district->id,
        'education_level' => 'SMA',
        'edition' => 'reguler',
        'name' => 'Peserta Manual',
        'phone_number' => '6281010101010',
        'school_name' => 'SMA cengkareng',
        'exclude_from_school_suggestions' => true,
    ])->assertCreated();

    $this->getJson("/api/school-options?district_id={$district->id}&education_level=SMA")
        ->assertSuccessful()
        ->assertJsonCount(0, 'data');

    // Bahkan ketika exclude_from_school_suggestions=false, nama hasil
    // registrasi tetap tidak boleh muncul karena autocomplete sekarang
    // murni dari katalog admin.
    $this->postJson('/api/register', [
        'district_id' => $district->id,
        'education_level' => 'SMA',
        'edition' => 'reguler',
        'name' => 'Peserta Resmi',
        'phone_number' => '6282020202020',
        'school_name' => 'SMAN 88 Jakarta',
    ])->assertCreated();

    $this->getJson("/api/school-options?district_id={$district->id}&education_level=SMA")
        ->assertSuccessful()
        ->assertJsonCount(0, 'data');
});

it('returns university options for umum participants', function () {
    University::query()->create([
        'name' => 'Universitas Indonesia',
        'type' => 'PTN',
        'city' => 'Depok',
    ]);

    $this->getJson('/api/school-options?education_level=UMUM')
        ->assertSuccessful()
        ->assertJsonPath('data.0.label', 'Universitas Indonesia')
        ->assertJsonPath('data.0.value', 'Universitas Indonesia');
});

/*
|--------------------------------------------------------------------------
| Validasi NIK & Alamat (hanya jenjang UMUM, baik VIP maupun Reguler)
|--------------------------------------------------------------------------
*/

it('does not require NIK and address for VIP with school-grade education level', function () {
    $district = makeDistrict('Cilandak', '3171030');

    $this->postJson('/api/register', [
        'district_id' => $district->id,
        'education_level' => 'SMA',
        'edition' => 'vip',
        'name' => 'Tester',
        'phone_number' => '6281234567890',
        'school_name' => 'SMAN Pelopor',
    ])
        ->assertCreated()
        ->assertJsonPath('success', true);
});

it('requires NIK and address when edition is VIP with UMUM', function () {
    $district = makeDistrict('Cilandak', '3171030');

    $this->postJson('/api/register', [
        'district_id' => $district->id,
        'education_level' => 'UMUM',
        'edition' => 'vip',
        'name' => 'Tester',
        'phone_number' => '6281234567890',
        'school_name' => 'Universitas VIP',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['nik', 'address']);
});

it('requires NIK and address when edition is reguler with UMUM', function () {
    $district = makeDistrict('Cilandak', '3171030');

    $this->postJson('/api/register', [
        'district_id' => $district->id,
        'education_level' => 'UMUM',
        'edition' => 'reguler',
        'name' => 'Tester',
        'phone_number' => '6281234567890',
        'school_name' => 'Universitas Tester',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['nik', 'address']);
});

it('does not require NIK and address for reguler non-UMUM levels', function () {
    $district = makeDistrict('Cilandak', '3171030');

    $this->postJson('/api/register', [
        'district_id' => $district->id,
        'education_level' => 'SMP',
        'edition' => 'reguler',
        'name' => 'Tester SMP',
        'phone_number' => '6281234567891',
        'school_name' => 'SMP Negeri 7 Cilandak',
    ])
        ->assertCreated()
        ->assertJsonPath('success', true);
});

it('rejects non-16-digit NIK on UMUM', function () {
    $district = makeDistrict('Cilandak', '3171030');

    $this->postJson('/api/register', [
        'district_id' => $district->id,
        'education_level' => 'UMUM',
        'edition' => 'vip',
        'name' => 'Tester',
        'phone_number' => '6281234567890',
        'school_name' => 'Universitas Anywhere',
        'nik' => '1234abc',
        'address' => 'Jl. Tester',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['nik']);
});

it('persists NIK and address back in the response when provided on UMUM', function () {
    $district = makeDistrict('Cilandak', '3171030');
    $nik = makeNik();

    $response = postVipWithDocs($district, 'UMUM', '6281234567000', [
        'nik' => $nik,
        'address' => 'Jl. Pegangsaan Timur No. 56',
    ])->assertCreated();

    expect($response->json('data.nik'))->toBe($nik)
        ->and($response->json('data.address'))->toBe('Jl. Pegangsaan Timur No. 56');
});

/*
|--------------------------------------------------------------------------
| Skenario clustering batch (VIP global & Reguler school > kecamatan > kota)
|--------------------------------------------------------------------------
*/

it('clusters VIP registrations into one global batch across district & level', function () {
    $a = makeDistrict('Cilandak', '3171030');
    $b = makeDistrict('Cengkareng', '3174070');

    $r1 = postVipWithDocs($a, 'SMA', '6281000000001')->assertCreated();
    $r2 = postVipWithDocs($b, 'UMUM', '6281000000002')->assertCreated();

    expect($r1->json('data.batch.id'))->toBe($r2->json('data.batch.id'))
        ->and($r1->json('data.batch.name'))->toStartWith('Mushaf VIP Jakarta');
});

it('clusters Reguler same-school registrations into the same batch', function () {
    $district = makeDistrict('Cilandak', '3171030');

    $r1 = postReg($district, 'SMP', 'SMPN 1 Cilandak', '6281000000010')->assertCreated();
    $r2 = postReg($district, 'SMP', 'SMPN 1 Cilandak', '6281000000011')->assertCreated();

    expect($r1->json('data.batch.id'))->toBe($r2->json('data.batch.id'));
});

it('extends Reguler batch to other schools within the same kecamatan', function () {
    $district = makeDistrict('Cilandak', '3171030');

    $r1 = postReg($district, 'SMP', 'SMPN 1 Cilandak', '6281000000020')->assertCreated();
    $r2 = postReg($district, 'SMP', 'SMPN 7 Cilandak', '6281000000021')->assertCreated();

    expect($r1->json('data.batch.id'))->toBe($r2->json('data.batch.id'));
});

it('extends Reguler batch to neighbouring kecamatan in the same kota', function () {
    $cilandak = makeDistrict('Cilandak', '3171030');
    $pasarMinggu = makeDistrict('Pasar Minggu', '3171040');

    $r1 = postReg($cilandak, 'SMP', 'SMPN 1 Cilandak', '6281000000030')->assertCreated();
    $r2 = postReg($pasarMinggu, 'SMP', 'SMPN 99 Pasar Minggu', '6281000000031')->assertCreated();

    expect($r1->json('data.batch.id'))->toBe($r2->json('data.batch.id'));
});

it('opens a new Reguler batch when registrant is in a different kota', function () {
    $cilandakJaksel = makeDistrict('Cilandak', '3171030');
    $cengkarengJakbar = makeDistrict('Cengkareng', '3174070');

    $r1 = postReg($cilandakJaksel, 'SMP', 'SMPN 1 Cilandak', '6281000000040')->assertCreated();
    $r2 = postReg($cengkarengJakbar, 'SMP', 'SMPN 9 Cengkareng', '6281000000041')->assertCreated();

    expect($r1->json('data.batch.id'))->not->toBe($r2->json('data.batch.id'));
});

it('keeps Reguler batches separate per education level', function () {
    $district = makeDistrict('Cilandak', '3171030');

    $smp = postReg($district, 'SMP', 'SMPN 1 Cilandak', '6281000000050')->assertCreated();
    $sma = postReg($district, 'SMA', 'SMAN 6 Cilandak', '6281000000051')->assertCreated();

    expect($smp->json('data.batch.id'))->not->toBe($sma->json('data.batch.id'));
});

/*
|--------------------------------------------------------------------------
| Format Smart Code (kecamatan & jenjang ASLI pendaftar)
|--------------------------------------------------------------------------
*/

it('generates VIP smart code using the registrant district and level', function () {
    $district = makeDistrict('Cengkareng', '3174070');

    $response = postVipWithDocs($district, 'SMA', '6281000000060')->assertCreated();

    expect($response->json('data.registration_code'))
        ->toMatch('/^3174070-VIP-SMA-MUSHAFV\d+-\d{3}-[A-Z0-9]+-[A-Z0-9]{4}$/');
});

it('generates Reguler smart code using the registrant district and level', function () {
    $district = makeDistrict('Cilandak', '3171030');

    $response = postReg($district, 'SMP', 'SMPN 1 Cilandak', '6281000000070')->assertCreated();

    expect($response->json('data.registration_code'))
        ->toMatch('/^3171030-REGULER-SMP-MUSHAF\d+-\d{3}-[A-Z0-9]+-[A-Z0-9]{4}$/');
});

it('uses the registrant district even when the batch was anchored elsewhere (kota extension)', function () {
    $cilandak = makeDistrict('Cilandak', '3171030');
    $pasarMinggu = makeDistrict('Pasar Minggu', '3171040');

    postReg($cilandak, 'SMP', 'SMPN 1 Cilandak', '6281000000080')->assertCreated();
    $response = postReg($pasarMinggu, 'SMP', 'SMPN 99 Pasar Minggu', '6281000000081')->assertCreated();

    expect($response->json('data.registration_code'))->toStartWith('3171040-REGULER-SMP-');
});

/*
|--------------------------------------------------------------------------
| Normalisasi school_name & autocomplete catalog-only
|--------------------------------------------------------------------------
*/

it('clusters Reguler registrations even when school name is spelled with minor variations', function () {
    $district = makeDistrict('Cilandak', '3171030');

    $r1 = postReg($district, 'SMP', 'SMP Negeri 1 Cilandak', '6281000099001')->assertCreated();
    $r2 = postReg($district, 'SMP', 'SMPN 1 Cilandak', '6281000099002')->assertCreated();
    $r3 = postReg($district, 'SMP', 'SMP N 1 Cilandak', '6281000099003')->assertCreated();

    expect($r1->json('data.batch.id'))->toBe($r2->json('data.batch.id'))
        ->and($r1->json('data.batch.id'))->toBe($r3->json('data.batch.id'));
});

it('does not surface registration-derived school names in autocomplete anymore', function () {
    $district = makeDistrict('Cilandak', '3171030');

    postReg($district, 'SMP', 'Sekolah Misterius Yang Tidak Di Katalog', '6281000099101', [
        'exclude_from_school_suggestions' => false,
    ])->assertCreated();

    $response = test()->getJson("/api/school-options?education_level=SMP&district_id={$district->id}")
        ->assertSuccessful();

    $names = collect($response->json('data'))->pluck('label')->all();

    expect($names)->not->toContain('Sekolah Misterius Yang Tidak Di Katalog');
});

it('still serves curated entries from school_suggestions in autocomplete', function () {
    $district = makeDistrict('Cilandak', '3171030');

    SchoolSuggestion::query()->create([
        'district_id' => $district->id,
        'education_level' => 'SMP',
        'name' => 'SMP Negeri 1 Cilandak',
    ]);

    $response = test()->getJson("/api/school-options?education_level=SMP&district_id={$district->id}")
        ->assertSuccessful();

    $names = collect($response->json('data'))->pluck('label')->all();

    expect($names)->toContain('SMP Negeri 1 Cilandak');
});

it('returns fuzzy match suggestions when user types a typo', function () {
    $district = makeDistrict('Cilandak', '3171030');

    SchoolSuggestion::query()->create([
        'district_id' => $district->id,
        'education_level' => 'SMP',
        'name' => 'SMP Negeri 1 Cilandak',
    ]);

    $response = test()->getJson(sprintf(
        '/api/school-options/match?education_level=SMP&district_id=%d&q=%s',
        $district->id,
        urlencode('SMPN1 CILANDA'),
    ))->assertSuccessful();

    $labels = collect($response->json('data'))->pluck('label')->all();

    expect($labels)->toContain('SMP Negeri 1 Cilandak');
});

it('returns empty fuzzy data when nothing crosses the similarity threshold', function () {
    $district = makeDistrict('Cilandak', '3171030');

    SchoolSuggestion::query()->create([
        'district_id' => $district->id,
        'education_level' => 'SMP',
        'name' => 'SMP Negeri 1 Cilandak',
    ]);

    $response = test()->getJson(sprintf(
        '/api/school-options/match?education_level=SMP&district_id=%d&q=%s',
        $district->id,
        urlencode('Universitas Padjadjaran'),
    ))->assertSuccessful();

    expect($response->json('data'))->toBe([]);
});

it('validates the fuzzy match endpoint input', function () {
    $district = makeDistrict('Cilandak', '3171030');

    test()->getJson("/api/school-options/match?education_level=SMP&district_id={$district->id}&q=a")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['q']);
});

/*
|--------------------------------------------------------------------------
| Roll-over kapasitas batch (603)
|--------------------------------------------------------------------------
*/

it('rolls over to a new Reguler batch when capacity is reached', function () {
    $district = makeDistrict('Cilandak', '3171030');

    // Buka batch pertama via 1 registrasi resmi
    $first = postReg($district, 'SMP', 'SMPN 1 Cilandak', '6281000000100')->assertCreated();
    $batchId = $first->json('data.batch.id');

    // Pre-fill batch 1 hingga 602 baris (1 sudah dipakai di atas) supaya page selanjutnya = 603
    $existing = Registration::where('batch_id', $batchId)->count();
    $needed = RegistrationService::BATCH_CAPACITY - 1 - $existing; // sisakan 1 slot

    if ($needed > 0) {
        $rows = [];
        $now = now();
        for ($i = 0; $i < $needed; $i++) {
            $rows[] = [
                'batch_id' => $batchId,
                'district_id' => $district->id,
                'education_level' => 'SMP',
                'name' => 'PreFill ' . $i,
                'phone_number' => '628200000' . str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                'edition' => 'reguler',
                'school_name' => 'SMPN 1 Cilandak',
                'registration_code' => 'PREFILL-' . Str::random(10) . '-' . $i,
                'page_number' => $existing + 1 + $i,
                'base_price' => 10000,
                'total_payment' => 10000,
                'payment_status' => 'pending',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        Registration::query()->insert($rows);
    }

    // Slot ke-603 (penutup) ditempati registrasi resmi → batch jadi penuh
    $closer = postReg($district, 'SMP', 'SMPN 1 Cilandak', '6281000000101')->assertCreated();
    expect($closer->json('data.batch.id'))->toBe($batchId)
        ->and($closer->json('data.page_number'))->toBe(RegistrationService::BATCH_CAPACITY);

    // Registrasi berikutnya HARUS membuka batch baru
    $next = postReg($district, 'SMP', 'SMPN 1 Cilandak', '6281000000102')->assertCreated();
    expect($next->json('data.batch.id'))->not->toBe($batchId)
        ->and($next->json('data.page_number'))->toBe(1);

    // Batch lama harus ditandai is_full
    expect(Batch::find($batchId)->is_full)->toBeTrue();
});

it('rolls over to a new global VIP batch when capacity is reached', function () {
    $district = makeDistrict('Cilandak', '3171030');

    $first = postVipWithDocs($district, 'SMA', '6281000000200')->assertCreated();
    $batchId = $first->json('data.batch.id');

    $needed = RegistrationService::BATCH_CAPACITY - 2; // sisakan 1 slot
    $rows = [];
    $now = now();
    for ($i = 0; $i < $needed; $i++) {
        $rows[] = [
            'batch_id' => $batchId,
            'district_id' => $district->id,
            'education_level' => 'SMA',
            'name' => 'VIP Filler ' . $i,
            'phone_number' => '628300000' . str_pad((string) $i, 5, '0', STR_PAD_LEFT),
            'edition' => 'vip',
            'school_name' => 'X',
            'registration_code' => 'VIPFILL-' . Str::random(10) . '-' . $i,
            'page_number' => 2 + $i,
            'base_price' => 20000,
            'total_payment' => 20000,
            'payment_status' => 'pending',
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
    Registration::query()->insert($rows);

    $closer = postVipWithDocs($district, 'UMUM', '6281000000201')->assertCreated();
    expect($closer->json('data.batch.id'))->toBe($batchId);

    $next = postVipWithDocs($district, 'SMA', '6281000000202')->assertCreated();
    expect($next->json('data.batch.id'))->not->toBe($batchId)
        ->and($next->json('data.batch.name'))->toStartWith('Mushaf VIP Jakarta')
        ->and($next->json('data.batch.batch_number'))->toBe('V2');
});
