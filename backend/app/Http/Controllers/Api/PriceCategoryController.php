<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PriceCategory;
use Illuminate\Http\JsonResponse;

class PriceCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $rows = PriceCategory::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['slug', 'name', 'amount']);

        return response()->json([
            'success' => true,
            'message' => 'Daftar kategori harga.',
            'data' => $rows,
        ]);
    }
}
