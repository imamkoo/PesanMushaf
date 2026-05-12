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
                Section::make('Ringkasan Admin')
                    ->description('Informasi operasional yang paling sering dicari admin.')
                    ->icon('heroicon-m-clipboard-document-list')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('registration_code')
                            ->label('Kode Booking')
                            ->weight(FontWeight::Bold)
                            ->color('primary')
                            ->copyable()
                            ->columnSpan(2),
                        TextEntry::make('payment_status')
                            ->label('Status Pembayaran')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'success' => 'success',
                                'failed' => 'danger',
                                default => 'warning',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'success' => 'Lunas',
                                'failed' => 'Batal',
                                default => 'Pending',
                            }),
                        TextEntry::make('total_payment')
                            ->label('Total Tagihan')
                            ->money('IDR', divideBy: 1)
                            ->weight(FontWeight::Bold),
                        TextEntry::make('batch.name')
                            ->label('Batch / Jilid')
                            ->icon('heroicon-m-rectangle-stack')
                            ->placeholder('Belum diassign'),
                        TextEntry::make('page_number')
                            ->label('Nomor Halaman')
                            ->numeric()
                            ->icon('heroicon-m-bookmark')
                            ->placeholder('-'),
                        TextEntry::make('edition')
                            ->label('Kategori')
                            ->badge()
                            ->color(fn (string $state): string => $state === 'vip' ? 'warning' : 'info')
                            ->formatStateUsing(fn (string $state): string => strtoupper($state)),
                        TextEntry::make('education_level')
                            ->label('Jenjang Peserta')
                            ->badge()
                            ->color(fn (?string $state): string => match (strtoupper((string) $state)) {
                                'SD' => 'info',
                                'SMP' => 'warning',
                                'SMA' => 'success',
                                'UMUM' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (?string $state): string => filled($state) ? (string) $state : 'Data Lama'),
                        TextEntry::make('created_at')
                            ->label('Tanggal Daftar')
                            ->dateTime('d M Y H:i')
                            ->icon('heroicon-m-clock'),
                        TextEntry::make('updated_at')
                            ->label('Update Terakhir')
                            ->dateTime('d M Y H:i')
                            ->icon('heroicon-m-arrow-path'),
                    ]),

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
                        TextEntry::make('district.name')
                            ->label('Kecamatan')
                            ->icon('heroicon-m-map-pin')
                            ->placeholder('-'),
                        TextEntry::make('school_name')
                            ->label('Asal Sekolah / Kampus / Instansi')
                            ->placeholder('Belum diisi')
                            ->columnSpan(2),
                    ]),

                Section::make('Dokumen & Identitas UMUM')
                    ->description('Tampilan khusus peserta UMUM agar NIK, alamat, dan instansi tidak tenggelam di data umum admin.')
                    ->icon('heroicon-m-identification')
                    ->visible(fn ($record): bool => $record->education_level === 'UMUM' || filled($record->nik) || filled($record->address))
                    ->columns(2)
                    ->schema([
                        TextEntry::make('school_name')
                            ->label('Kampus / Instansi')
                            ->placeholder('Belum diisi')
                            ->columnSpan(2),
                        TextEntry::make('nik')
                            ->label('NIK (KTP)')
                            ->icon('heroicon-m-identification')
                            ->placeholder('Belum diisi pada data lama')
                            ->copyable()
                            ->formatStateUsing(function (?string $state): string {
                                if (! $state) {
                                    return 'Belum diisi pada data lama';
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
                            ->label('Alamat Lengkap')
                            ->placeholder('Belum diisi pada data lama')
                            ->columnSpan(2),
                    ]),

                Section::make('Rincian Pembayaran')
                    ->description('Nilai dasar dan rincian biaya pendaftaran.')
                    ->icon('heroicon-m-banknotes')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('base_price')
                            ->label('Harga Dasar')
                            ->money('IDR', divideBy: 1)
                            ->placeholder('-'),
                    ]),
            ]);
    }
}
