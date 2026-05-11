<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolSuggestion;
use App\Models\University;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SchoolOptionController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'education_level' => ['required', Rule::in(['SD', 'SMP', 'SMA', 'UMUM'])],
        ]);

        if ($validated['education_level'] === 'UMUM') {
            $options = University::query()
                ->orderBy('name')
                ->limit(100)
                ->pluck('name')
                ->map(fn (string $name): array => [
                    'label' => $name,
                    'value' => $name,
                ])
                ->values();

            return response()->json(['data' => $options]);
        }

        if (empty($validated['district_id'])) {
            return response()->json(['data' => []]);
        }

        $districtId = (int) $validated['district_id'];
        $educationLevel = $validated['education_level'];

        // Catatan: sumber autocomplete sengaja terbatas pada katalog `school_suggestions`
        // saja. Mencantumkan distinct `registrations.school_name` membuat variasi
        // penulisan (typo, beda spasi/kapital) menyebar ke user lain dan memecah
        // clustering batch. Catalog dikelola admin via Filament.
        $names = SchoolSuggestion::query()
            ->where('district_id', $districtId)
            ->where('education_level', $educationLevel)
            ->orderBy('name')
            ->limit(100)
            ->pluck('name')
            ->filter(fn (?string $name) => filled($name))
            ->unique(fn (string $name): string => mb_strtolower(trim($name)))
            ->values();

        $options = $names->map(fn (string $name): array => [
            'label' => $name,
            'value' => $name,
        ]);

        return response()->json(['data' => $options]);
    }
}
