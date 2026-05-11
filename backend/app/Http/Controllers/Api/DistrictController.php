<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\DistrictResource;
use App\Models\District;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DistrictController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $districts = District::query()
            ->withCount('batches')
            ->orderBy('name')
            ->get();

        return DistrictResource::collection($districts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function show(District $district): DistrictResource
    {
        $district->loadCount('batches');

        return DistrictResource::make($district);
    }
}
