<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BukuApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Route yang diakses oleh aplikasi mobile / klien eksternal.
| Prefix: /api  (otomatis dari RouteServiceProvider)
|
*/

Route::get('/buku', [BukuApiController::class, 'dataInformasiBuku']);
Route::get('/get/dataInformasiBuku', [BukuApiController::class, 'dataInformasiBuku']);
Route::get('/get/kontenBuku', [BukuApiController::class, 'kontenBuku']);
Route::get('/get/detailBuku', [BukuApiController::class, 'detailBuku']);