<?php

namespace App\Filament\Resources\Registrations\Schemas;

use App\Models\PriceCategory;
use App\Models\SchoolSuggestion;
use App\Models\University;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class RegistrationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ringkasan Admin')
                    ->description('Info transaksi dan logistik yang paling sering dicek operator.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('registration_code')
                            ->label('Kode Booking')
                            ->disabled()
                            ->hiddenOn('create'),
                        Select::make('edition')
                            ->label('Kategori')
                            ->options(fn (): array => PriceCategory::query()
                                ->active()
                                ->orderBy('sort_order')
                                ->get()
                                ->mapWithKeys(fn (PriceCategory $c): array => [
                                    $c->slug => $c->name.' — Rp '.number_format($c->amount, 0, ',', '.'),
                                ])
                                ->all())
                            ->default('reguler')
                            ->live()
                            ->required()
                            ->native(false),
                        Select::make('payment_status')
                            ->label('Status Pembayaran')
                            ->options([
                                'pending' => 'Pending',
                                'success' => 'Lunas',
                                'failed' => 'Batal',
                            ])
                            ->default('pending')
                            ->required(),
                        TextInput::make('page_number')
                            ->label('Nomor Halaman')
                            ->disabled()
                            ->hiddenOn('create'),
                        TextInput::make('total_payment')
                            ->label('Total Tagihan')
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated(false),
                    ]),

                Section::make('Identitas Peserta')
                    ->description('Data utama peserta untuk verifikasi admin.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required(),
                        TextInput::make('phone_number')
                            ->label('Nomor WhatsApp')
                            ->numeric()
                            ->required(),
                        Select::make('district_id')
                            ->label('Wilayah Kecamatan')
                            ->relationship('district', 'name')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required(),
                        Select::make('education_level')
                            ->label('Jenjang Peserta')
                            ->options([
                                'SD' => 'SD / Sederajat',
                                'SMP' => 'SMP / Sederajat',
                                'SMA' => 'SMA / Sederajat',
                                'UMUM' => 'Umum / Mahasiswa',
                            ])
                            ->live()
                            ->required(),
                        Select::make('school_name')
                            ->label('Asal Sekolah / Kampus / Instansi')
                            ->placeholder(fn (Get $get): string => $get('education_level') === 'UMUM'
                                ? 'Ketik nama kampus / instansi di sini...'
                                : 'Ketik nama sekolah di sini...')
                            ->helperText(fn (Get $get): string => $get('education_level') === 'UMUM'
                                ? 'Untuk peserta UMUM, isi nama kampus, universitas, atau instansi.'
                                : 'Untuk peserta sekolah, pilih sekolah dari katalog agar pengelompokan batch tetap rapi.')
                            ->searchable()
                            ->required()
                            ->columnSpan(2)
                            /**
                             * Tanpa ini, nilai "Daftarkan Baru" (teks bebas) gagal validasi Filament
                             * karena tidak termasuk dalam options() dari database.
                             */
                            ->getOptionLabelUsing(function (mixed $value): ?string {
                                if (blank($value)) {
                                    return null;
                                }

                                return (string) $value;
                            })
                            // Sumber katalog: SchoolSuggestion (per kecamatan + jenjang) untuk SD/SMP/SMA,
                            // University untuk jenjang UMUM. TIDAK pernah membaca dari Registration agar
                            // entri typo / dummy stress test tidak mencemari daftar saran.
                            ->options(function (Get $get) {
                                $distId = $get('district_id');
                                $level = $get('education_level');

                                if (! $level) {
                                    return [];
                                }

                                if ($level === 'UMUM') {
                                    return University::query()
                                        ->orderBy('name')
                                        ->limit(20)
                                        ->pluck('name', 'name')
                                        ->toArray();
                                }

                                if (! $distId) {
                                    return [];
                                }

                                return SchoolSuggestion::query()
                                    ->where('district_id', $distId)
                                    ->where('education_level', $level)
                                    ->orderBy('name')
                                    ->limit(20)
                                    ->pluck('name', 'name')
                                    ->toArray();
                            })
                            ->getSearchResultsUsing(function (string $search, Get $get) {
                                $distId = $get('district_id');
                                $level = $get('education_level');

                                if (! $level) {
                                    return [];
                                }

                                if ($level === 'UMUM') {
                                    $results = University::query()
                                        ->where('name', 'ilike', "%{$search}%")
                                        ->orderBy('name')
                                        ->limit(20)
                                        ->pluck('name', 'name')
                                        ->toArray();

                                    return array_merge([$search => "✨ Daftarkan Baru: \"{$search}\""], $results);
                                }

                                if (! $distId) {
                                    return [];
                                }

                                $results = SchoolSuggestion::query()
                                    ->where('district_id', $distId)
                                    ->where('education_level', $level)
                                    ->where('name', 'ilike', "%{$search}%")
                                    ->orderBy('name')
                                    ->limit(15)
                                    ->pluck('name', 'name')
                                    ->toArray();

                                return array_merge([$search => "✨ Daftarkan Baru: \"{$search}\""], $results);
                            })
                            ->searchDebounce(300),
                        TextInput::make('nik')
                            ->label('NIK (KTP)')
                            ->helperText('Wajib bagi peserta jenjang UMUM. 16 digit angka.')
                            ->length(16)
                            ->numeric()
                            ->required(fn (Get $get): bool => $get('education_level') === 'UMUM')
                            ->visible(fn (Get $get): bool => $get('education_level') === 'UMUM'),
                        Textarea::make('address')
                            ->label('Alamat Lengkap')
                            ->helperText('Jalan, RT/RW, kelurahan, kota - wajib bagi peserta jenjang UMUM.')
                            ->rows(3)
                            ->maxLength(500)
                            ->required(fn (Get $get): bool => $get('education_level') === 'UMUM')
                            ->visible(fn (Get $get): bool => $get('education_level') === 'UMUM')
                            ->columnSpan(2),
                    ]),

                Section::make('Kontrol Tambahan')
                    ->schema([
                        Toggle::make('exclude_from_school_suggestions')
                            ->label('Sembunyikan dari saran sekolah di website')
                            ->helperText('Aktifkan untuk nama yang diinput manual atau salah agar tidak muncul di dropdown pendaftaran publik.')
                            ->default(false)
                            ->inline(false),
                    ]),
            ]);
    }
}
