<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\BatchResource;
use App\Models\Batch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BatchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $batches = Batch::query()
            ->with('district')
            ->withActiveRegistrationsCount()
            ->when(
                $request->filled('district_id'),
                fn ($query) => $query->where('district_id', $request->integer('district_id'))
            )
            ->when(
                $request->filled('education_level'),
                fn ($query) => $query->where('education_level', $request->string('education_level')->toString())
            )
            ->when(
                $request->boolean('only_available'),
                fn ($query) => $query->whereFullByOccupancy(false)
            )
            ->latest('id')
            ->get();

        return BatchResource::collection($batches);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function show(Batch $batch): BatchResource
    {
        $batch->loadMissing(['district'])->loadCount('registrations');

        return BatchResource::make($batch);
    }
}
