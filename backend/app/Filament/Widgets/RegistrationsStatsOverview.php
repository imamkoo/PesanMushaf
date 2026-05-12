<?php

namespace App\Filament\Widgets;

use App\Models\Batch;
use App\Models\Registration;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RegistrationsStatsOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Ringkasan Pendaftaran';

    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $total = Registration::query()->count();
        $today = Registration::query()->whereDate('created_at', today())->count();
        $umum = Registration::query()->whereUmum()->count();
        $nonUmum = Registration::query()->whereNonUmum()->count();
        $legacyRegistrations = Registration::query()->whereLegacyOrUnknownLevel()->count();
        $success = Registration::query()->where('payment_status', 'success')->count();
        $pending = Registration::query()->where('payment_status', 'pending')->count();
        $umumBatches = Batch::query()->whereUmum()->count();
        $nonUmumBatches = Batch::query()->whereNonUmum()->count();
        $batchFull = Batch::query()->whereFullByOccupancy(true)->count();

        $successPct = $total > 0 ? round(($success / $total) * 100) : 0;

        return [
            Stat::make('Total Pendaftaran', number_format($total, 0, ',', '.'))
                ->description('Hari ini: '.number_format($today, 0, ',', '.'))
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Pendaftar UMUM', number_format($umum, 0, ',', '.'))
                ->description($legacyRegistrations > 0
                    ? 'Data lama belum terkategori: '.number_format($legacyRegistrations, 0, ',', '.')
                    : 'NIK & alamat perlu terbaca jelas')
                ->descriptionIcon('heroicon-m-identification')
                ->color('danger'),

            Stat::make('Pendaftar Non-UMUM', number_format($nonUmum, 0, ',', '.'))
                ->description('SD / SMP / SMA')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('info'),

            Stat::make('Lunas', number_format($success, 0, ',', '.'))
                ->description($successPct.'% dari total')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Menunggu Bayar', number_format($pending, 0, ',', '.'))
                ->description('Belum lunas')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),

            Stat::make('Batch UMUM', number_format($umumBatches, 0, ',', '.'))
                ->description('Khusus peserta UMUM')
                ->descriptionIcon('heroicon-m-identification')
                ->color('danger'),

            Stat::make('Batch Non-UMUM', number_format($nonUmumBatches, 0, ',', '.'))
                ->description('Sekolah reguler per jenjang')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('primary'),

            Stat::make('Batch Penuh', number_format($batchFull, 0, ',', '.'))
                ->description('Siap cetak')
                ->descriptionIcon('heroicon-m-lock-closed')
                ->color('gray'),
        ];
    }
}
