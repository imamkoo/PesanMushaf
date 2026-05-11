<?php

namespace App\Filament\Widgets;

use App\Models\Batch;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class BatchCapacityChartWidget extends ChartWidget
{
    protected ?string $heading = 'Kapasitas Batch per Jenjang';

    protected ?string $description = 'Total kapasitas vs total pendaftar yang sudah masuk batch, dikelompokkan per jenjang.';

    protected ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $levels = ['SD', 'SMP', 'SMA', 'UMUM'];

        $capacityRows = Batch::query()
            ->selectRaw("COALESCE(education_level, 'UMUM') as lvl, SUM(max_capacity) as total_capacity")
            ->groupBy(DB::raw("COALESCE(education_level, 'UMUM')"))
            ->pluck('total_capacity', 'lvl')
            ->toArray();

        $filledRows = Batch::query()
            ->leftJoin('registrations', 'registrations.batch_id', '=', 'batches.id')
            ->whereNull('registrations.deleted_at')
            ->selectRaw("COALESCE(batches.education_level, 'UMUM') as lvl, COUNT(registrations.id) as total_filled")
            ->groupBy(DB::raw("COALESCE(batches.education_level, 'UMUM')"))
            ->pluck('total_filled', 'lvl')
            ->toArray();

        $capacity = [];
        $filled = [];
        foreach ($levels as $level) {
            $capacity[] = (int) ($capacityRows[$level] ?? 0);
            $filled[] = (int) ($filledRows[$level] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Kapasitas',
                    'data' => $capacity,
                    'backgroundColor' => 'rgba(99, 102, 241, 0.35)',
                    'borderColor' => 'rgb(99, 102, 241)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Terisi',
                    'data' => $filled,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.55)',
                    'borderColor' => 'rgb(16, 185, 129)',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $levels,
        ];
    }
}
