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
        $success = Registration::query()->where('payment_status', 'success')->count();
        $pending = Registration::query()->where('payment_status', 'pending')->count();
        $failed = Registration::query()->where('payment_status', 'failed')->count();
        $batchFull = Batch::query()->whereFullByOccupancy(true)->count();

        $successPct = $total > 0 ? round(($success / $total) * 100) : 0;

        return [
            Stat::make('Total Pendaftaran', number_format($total, 0, ',', '.'))
                ->description('Sepanjang waktu')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Pendaftar Hari Ini', number_format($today, 0, ',', '.'))
                ->description('24 jam terakhir')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),

            Stat::make('Lunas', number_format($success, 0, ',', '.'))
                ->description($successPct.'% dari total')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Menunggu Bayar', number_format($pending, 0, ',', '.'))
                ->description('Status pending')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),

            Stat::make('Pembayaran Gagal', number_format($failed, 0, ',', '.'))
                ->description('Status failed')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('Batch Penuh', number_format($batchFull, 0, ',', '.'))
                ->description('Siap cetak')
                ->descriptionIcon('heroicon-m-lock-closed')
                ->color('gray'),
        ];
    }
}
