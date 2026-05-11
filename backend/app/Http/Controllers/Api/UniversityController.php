<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\UniversityResource;
use App\Models\University;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UniversityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $universities = University::query()
            ->orderBy('name')
            ->get();

        return UniversityResource::collection($universities);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function show(University $university): UniversityResource
    {
        return UniversityResource::make($university);
    }
}
