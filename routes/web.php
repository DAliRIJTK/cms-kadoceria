<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BukuController;
use App\Http\Controllers\HalamanController;
use App\Http\Controllers\AreaInteraktifController;
use App\Http\Controllers\AudioLatarController;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']); 
});

Route::middleware('auth')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    Route::get('/dashboard', [BukuController::class, 'index'])->name('dashboard');

    Route::get('/dashboard', [BukuController::class, 'index'])->name('dashboard');
    Route::get('/buku/create', [BukuController::class, 'create'])->name('buku.create');
    Route::post('/buku', [BukuController::class, 'store'])->name('buku.store');

    Route::get('/buku/{id_buku}/halaman', [HalamanController::class, 'index'])->name('buku.halaman.index');
    Route::get('/buku/{id_buku}/halaman/proses-konversi', [HalamanController::class, 'processPdfFromStorage'])->name('buku.halaman.proses');
    
    Route::put('/halaman/{id_halaman}', [HalamanController::class, 'update'])->name('halaman.update');
    Route::delete('/halaman/{id_halaman}', [HalamanController::class, 'destroy'])->name('halaman.destroy');
    Route::post('/buku/{id_buku}/halaman/reorder', [HalamanController::class, 'reorder'])->name('buku.halaman.reorder');

    Route::get('/buku/{id}/preview', [BukuController::class, 'preview']); 

    Route::get('/buku/{id_buku}/halaman', [HalamanController::class, 'index']);           
    Route::post('/buku/{id_buku}/upload-pdf', [HalamanController::class, 'uploadPdf']); 
    Route::post('/buku/{id_buku}/halaman/reorder', [HalamanController::class, 'reorder']);

    Route::post('/halaman/{id_halaman}', [HalamanController::class, 'update']);            
    Route::delete('/halaman/{id_halaman}', [HalamanController::class, 'destroy']);

    Route::get('/halaman/{id_halaman}/area', [AreaInteraktifController::class, 'index']);      
    Route::post('/halaman/{id_halaman}/area', [AreaInteraktifController::class, 'store']); 
    Route::post('/area/{id_area}', [AreaInteraktifController::class, 'update']);
    Route::delete('/area/{id_area}', [AreaInteraktifController::class, 'destroy']);            

    Route::get('/audio-latar', [AudioLatarController::class, 'index']);
    Route::post('/audio-latar', [AudioLatarController::class, 'store']);
    Route::delete('/audio-latar/{id}', [AudioLatarController::class, 'destroy']);
});