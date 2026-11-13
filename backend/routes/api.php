<?php

use App\Http\Controllers\Api\BatchController;
use App\Http\Controllers\Api\DistrictController;
use App\Http\Controllers\Api\MidtransController;
use App\Http\Controllers\Api\MosqueController;
use App\Http\Controllers\Api\PriceCategoryController;
use App\Http\Controllers\Api\RegistrationController;
use App\Http\Controllers\Api\SchoolNameMatchController;
use App\Http\Controllers\Api\SchoolOptionController;
use App\Http\Controllers\Api\UniversityController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:20,1,register')->post('/register', [RegistrationController::class, 'store']);
Route::middleware('throttle:30,1,midtrans-snap')->post('/midtrans/snap-token', [MidtransController::class, 'createSnapToken']);
Route::middleware('throttle:30,1,midtrans-sync')->post('/midtrans/sync-status', [MidtransController::class, 'syncStatus']);
Route::post('/midtrans/notification', [MidtransController::class, 'notification']);
Route::middleware('throttle:120,1,price-categories')->get('/price-categories', [PriceCategoryController::class, 'index']);
Route::middleware('throttle:60,1,status-lookup')->get('/registrations/status', [RegistrationController::class, 'statusLookup']);
Route::middleware('throttle:60,1,status-code')->get('/registrations/{registrationCode}/status', [RegistrationController::class, 'status']);
Route::middleware('throttle:120,1,public-read')->group(function () {
    Route::apiResource('districts', DistrictController::class)->only(['index', 'show']);
    Route::apiResource('universities', UniversityController::class)->only(['index', 'show']);
    Route::apiResource('batches', BatchController::class)->only(['index', 'show']);
    Route::apiResource('mosques', MosqueController::class)->only(['index', 'show']);
    Route::get('/school-options', SchoolOptionController::class);
    Route::get('/school-options/match', SchoolNameMatchController::class);
});

// Endpoint internal lain tetap bisa ditempatkan di sini jika membutuhkan API key.
Route::middleware('check.api.key')->group(function () {
    //
});
