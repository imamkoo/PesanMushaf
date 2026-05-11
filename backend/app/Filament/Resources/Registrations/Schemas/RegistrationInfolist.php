<?php

namespace App\Filament\Resources\Registrations\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class RegistrationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas Peserta')
                    ->description('Data utama untuk verifikasi peserta.')
                    ->icon('heroicon-m-user')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nama Lengkap')
                            ->weight(FontWeight::Bold)
                            ->columnSpan(2),
                        TextEntry::make('phone_number')
                            ->label('Nomor WhatsApp')
                            ->icon('heroicon-m-phone')
                            ->copyable(),
                        TextEntry::make('email')
                            ->label('Email')
                            ->icon('heroicon-m-envelope')
                            ->placeholder('-'),
                        TextEntry::make('nik')
                            ->label('NIK (KTP)')
                            ->icon('heroicon-m-identification')
                            ->placeholder('-')
                            ->formatStateUsing(function (?string $state): string {
                                if (! $state) {
                                    return '-';
                                }

                                $digits = preg_replace('/\D/', '', $state) ?? '';
                                if (strlen($digits) < 4) {
                                    return $digits;
                                }

                                $tail = substr($digits, -4);
                                $prefix = str_repeat('•', strlen($digits) - 4);

                                return rtrim(chunk_split($prefix, 4, ' ')).' '.$tail;
                            }),
                        TextEntry::make('address')
                            ->label('Alamat')
                            ->placeholder('-')
                            ->columnSpan(2),
                    ]),

                Section::make('Pendaftaran & Batch')
                    ->description('Penempatan peserta dalam jilid / batch logistik.')
                    ->icon('heroicon-m-rectangle-stack')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('registration_code')
                            ->label('Kode Pendaftaran')
                            ->weight(FontWeight::Bold)
                            ->color('primary')
                            ->copyable()
                            ->columnSpan(2),
                        TextEntry::make('edition')
                            ->label('Edisi Mushaf')
                            ->badge()
                            ->color(fn (string $state): string => $state === 'vip' ? 'warning' : 'info')
                            ->formatStateUsing(fn (string $state): string => strtoupper($state)),
                        TextEntry::make('education_level')
                            ->label('Kategori Peserta')
                            ->badge()
                            ->color(fn (?string $state): string => match (strtoupper((string) $state)) {
                                'SD' => 'info',
                                'SMP' => 'warning',
                                'SMA' => 'success',
                                'UMUM' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('school_name')
                            ->label('Asal Sekolah / Instansi')
                            ->columnSpan(2),
                        TextEntry::make('district.name')
                            ->label('Kecamatan')
                            ->icon('heroicon-m-map-pin')
                            ->placeholder('-'),
                        TextEntry::make('batch.name')
                            ->label('Batch / Jilid')
                            ->icon('heroicon-m-rectangle-stack')
                            ->placeholder('Belum diassign'),
                        TextEntry::make('page_number')
                            ->label('Nomor Halaman')
                            ->numeric()
                            ->icon('heroicon-m-bookmark')
                            ->placeholder('-'),
                    ]),

                Section::make('Pembayaran')
                    ->description('Status dan rincian pembayaran.')
                    ->icon('heroicon-m-banknotes')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('payment_status')
                            ->label('Status Pembayaran')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'success' => 'success',
                                'failed' => 'danger',
                                default => 'warning',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'success' => '✅ Lunas',
                                'failed' => '❌ Batal',
                                default => '🕒 Pending',
                            }),
                        TextEntry::make('total_payment')
                            ->label('Total Tagihan')
                            ->money('IDR', divideBy: 1)
                            ->weight(FontWeight::Bold),
                        TextEntry::make('base_price')
                            ->label('Harga Dasar')
                            ->money('IDR', divideBy: 1)
                            ->placeholder('-'),
                        TextEntry::make('created_at')
                            ->label('Tanggal Daftar')
                            ->dateTime('d M Y H:i')
                            ->icon('heroicon-m-clock'),
                        TextEntry::make('updated_at')
                            ->label('Update Terakhir')
                            ->dateTime('d M Y H:i')
                            ->icon('heroicon-m-arrow-path'),
                    ]),
            ]);
    }
}
