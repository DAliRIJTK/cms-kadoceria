<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Route yang diakses oleh aplikasi mobile / klien eksternal.
| Prefix: /api  (otomatis dari RouteServiceProvider)
|
*/

Route::get('/buku', [ApiController::class, 'dataInformasiBuku']);
Route::get('/get/dataInformasiBuku', [ApiController::class, 'dataInformasiBuku']);
Route::get('/get/kontenBuku/{id?}', [ApiController::class, 'kontenBuku']);
Route::get('/get/detailBuku/{id?}', [ApiController::class, 'detailBuku']);
Route::post('/buku/{id}/generate', [ApiController::class, 'generateBundle']);