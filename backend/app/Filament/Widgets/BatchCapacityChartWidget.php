<?php

namespace App\Filament\Widgets;

use App\Models\Batch;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class BatchCapacityChartWidget extends ChartWidget
{
    protected ?string $heading = 'Kapasitas Batch per Jenjang';

    protected ?string $description = 'Total kapasitas vs total pendaftar per jenjang: SD, SMP, SMA, dan UMUM.';

    protected ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $levels = [
            'SD' => 'SD',
            'SMP' => 'SMP',
            'SMA' => 'SMA',
            'UMUM' => 'UMUM',
        ];

        $capacityBucketExpression = "
            CASE
                WHEN education_level = 'SD' THEN 'SD'
                WHEN education_level = 'SMP' THEN 'SMP'
                WHEN education_level = 'SMA' THEN 'SMA'
                WHEN education_level = 'UMUM' THEN 'UMUM'
                ELSE NULL
            END
        ";

        $filledBucketExpression = "
            CASE
                WHEN batches.education_level = 'SD' THEN 'SD'
                WHEN batches.education_level = 'SMP' THEN 'SMP'
                WHEN batches.education_level = 'SMA' THEN 'SMA'
                WHEN batches.education_level = 'UMUM' THEN 'UMUM'
                ELSE NULL
            END
        ";

        $capacityRows = Batch::query()
            ->selectRaw("{$capacityBucketExpression} as lvl, SUM(max_capacity) as total_capacity")
            ->whereNotNull('education_level')
            ->groupBy(DB::raw($capacityBucketExpression))
            ->pluck('total_capacity', 'lvl')
            ->toArray();

        $filledRows = Batch::query()
            ->leftJoin('registrations', 'registrations.batch_id', '=', 'batches.id')
            ->whereNotNull('batches.education_level')
            ->whereNull('registrations.deleted_at')
            ->selectRaw("{$filledBucketExpression} as lvl, COUNT(registrations.id) as total_filled")
            ->groupBy(DB::raw($filledBucketExpression))
            ->pluck('total_filled', 'lvl')
            ->toArray();

        $capacity = [];
        $filled = [];
        foreach ($levels as $bucket => $label) {
            $capacity[] = (int) ($capacityRows[$bucket] ?? 0);
            $filled[] = (int) ($filledRows[$bucket] ?? 0);
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
            'labels' => array_values($levels),
        ];
    }
}
