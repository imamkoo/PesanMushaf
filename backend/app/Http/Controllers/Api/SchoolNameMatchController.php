<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolSuggestion;
use App\Models\University;
use App\Support\SchoolNameNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class SchoolNameMatchController extends Controller
{
    private const MAX_CANDIDATES = 200;
    private const RESULT_LIMIT = 3;
    private const DEFAULT_THRESHOLD = 0.65;

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:120'],
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'education_level' => ['required', Rule::in(['SD', 'SMP', 'SMA', 'UMUM'])],
        ]);

        $threshold = (float) config('booking.school_match_threshold', self::DEFAULT_THRESHOLD);
        $candidates = $this->loadCandidates($validated);

        if ($candidates->isEmpty()) {
            return response()->json(['data' => []]);
        }

        $qNormalized = SchoolNameNormalizer::normalize($validated['q']) ?? '';
        if ($qNormalized === '') {
            return response()->json(['data' => []]);
        }

        $scored = $candidates
            ->map(function (array $row) use ($qNormalized): array {
                $score = $this->score($qNormalized, $row['normalized']);

                return [
                    'label' => $row['name'],
                    'value' => $row['name'],
                    'score' => $score,
                ];
            })
            ->filter(fn (array $row): bool => $row['score'] >= $threshold)
            ->sortByDesc('score')
            ->take(self::RESULT_LIMIT)
            ->values()
            ->all();

        return response()->json(['data' => $scored]);
    }

    /**
     * @param array{q: string, district_id?: int|null, education_level: string} $validated
     * @return Collection<int, array{name: string, normalized: string}>
     */
    private function loadCandidates(array $validated): Collection
    {
        $level = $validated['education_level'];

        if ($level === 'UMUM') {
            return University::query()
                ->orderBy('name')
                ->limit(self::MAX_CANDIDATES)
                ->get(['name'])
                ->map(fn (University $u): array => [
                    'name' => (string) $u->name,
                    'normalized' => SchoolNameNormalizer::normalize($u->name) ?? '',
                ])
                ->filter(fn (array $row): bool => $row['normalized'] !== '')
                ->values();
        }

        $districtId = $validated['district_id'] ?? null;
        if ($districtId === null) {
            return collect();
        }

        return SchoolSuggestion::query()
            ->where('district_id', $districtId)
            ->where('education_level', $level)
            ->orderBy('name')
            ->limit(self::MAX_CANDIDATES)
            ->get(['name', 'school_name_normalized'])
            ->map(function (SchoolSuggestion $suggestion): array {
                $normalized = $suggestion->school_name_normalized
                    ?? SchoolNameNormalizer::normalize($suggestion->name)
                    ?? '';

                return [
                    'name' => (string) $suggestion->name,
                    'normalized' => (string) $normalized,
                ];
            })
            ->filter(fn (array $row): bool => $row['normalized'] !== '')
            ->values();
    }

    private function score(string $qNormalized, string $candidateNormalized): float
    {
        if ($qNormalized === $candidateNormalized) {
            return 1.0;
        }

        similar_text($qNormalized, $candidateNormalized, $percent);
        $similarity = round($percent / 100, 4);

        // Boost ketika kandidat memuat seluruh string pencarian sebagai substring,
        // supaya prefix/partial typing tetap teratas.
        if (str_contains($candidateNormalized, $qNormalized)) {
            $similarity = max($similarity, 0.9);
        }

        return $similarity;
    }
}
