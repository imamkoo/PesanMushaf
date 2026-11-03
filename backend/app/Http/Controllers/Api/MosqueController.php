<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\MosqueResource;
use App\Models\District;
use App\Models\Mosque;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MosqueController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'min:2', 'max:120'],
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'city' => ['nullable', 'string', 'max:120'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:25'],
        ]);

        $query = Mosque::query()
            ->active()
            ->search($validated['q'] ?? null);

        if (! empty($validated['district_id'])) {
            $district = District::query()->findOrFail($validated['district_id']);

            $query->whereRaw(
                'LOWER(district_name) = ?',
                [Str::lower($district->name)]
            );
        }

        if (! empty($validated['city'])) {
            $query->whereRaw(
                'LOWER(city) = ?',
                [Str::lower($validated['city'])]
            );
        }

        $limit = $validated['limit'] ?? 10;

        $mosques = $query
            ->orderByDesc('is_verified')
            ->orderBy('name')
            ->limit($limit)
            ->get();

        return response()->json([
            'data' => MosqueResource::collection($mosques),
            'meta' => [
                'query' => $validated['q'] ?? null,
                'count' => $mosques->count(),
            ],
        ]);
    }

    public function show(Mosque $mosque): JsonResponse
    {
        abort_unless($mosque->is_active, 404);

        return response()->json([
            'data' => new MosqueResource($mosque),
        ]);
    }
}
