<?php

namespace App\Console\Commands;

use App\Models\Registration;
use App\Models\SchoolSuggestion;
use App\Support\SchoolNameNormalizer;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class NormalizeSchoolNames extends Command
{
    protected $signature = 'app:normalize-school-names
        {--force : Tulis ulang semua baris meskipun school_name_normalized sudah terisi}
        {--chunk=500 : Ukuran chunk pemrosesan}';

    protected $description = 'Backfill kolom school_name_normalized di registrations & school_suggestions';

    public function handle(): int
    {
        $chunk = max(50, (int) $this->option('chunk'));
        $force = (bool) $this->option('force');

        $regCount = $this->normalize(
            label: 'registrations',
            builder: fn () => Registration::query()
                ->when(! $force, fn (Builder $q) => $q->whereNull('school_name_normalized')),
            apply: function (Registration $row): bool {
                $next = SchoolNameNormalizer::normalize($row->school_name);
                if ($next === $row->school_name_normalized) {
                    return false;
                }

                $row->school_name_normalized = $next;
                $row->saveQuietly();

                return true;
            },
            chunk: $chunk,
        );

        $sugCount = $this->normalize(
            label: 'school_suggestions',
            builder: fn () => SchoolSuggestion::query()
                ->when(! $force, fn (Builder $q) => $q->whereNull('school_name_normalized')),
            apply: function (SchoolSuggestion $row): bool {
                $next = SchoolNameNormalizer::normalize($row->name);
                if ($next === $row->school_name_normalized) {
                    return false;
                }

                $row->school_name_normalized = $next;
                $row->saveQuietly();

                return true;
            },
            chunk: $chunk,
        );

        $this->newLine();
        $this->info("Selesai. registrations diperbarui: {$regCount}, school_suggestions diperbarui: {$sugCount}.");

        return self::SUCCESS;
    }

    /**
     * @param  callable(): Builder  $builder
     * @param  callable(mixed): bool  $apply
     */
    private function normalize(string $label, callable $builder, callable $apply, int $chunk): int
    {
        $total = $builder()->count();
        $this->line("[{$label}] kandidat: {$total}");

        if ($total === 0) {
            return 0;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $updated = 0;
        $builder()->orderBy('id')->chunkById($chunk, function ($rows) use ($apply, &$updated, $bar): void {
            foreach ($rows as $row) {
                if ($apply($row)) {
                    $updated++;
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();

        return $updated;
    }
}
