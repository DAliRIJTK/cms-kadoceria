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
    
    Route::get('/buku/create', [BukuController::class, 'create'])->name('buku.create');
    Route::post('/buku', [BukuController::class, 'store'])->name('buku.store');
    
    Route::get('/buku/{id_buku}', [BukuController::class, 'show'])->name('buku.show');
    
    Route::put('/buku/{id_buku}/status', [BukuController::class, 'updateStatus'])->name('buku.updateStatus');
    
    Route::get('/buku/{id_buku}/preview', [BukuController::class, 'preview'])->name('buku.preview'); 

    Route::get('/buku/{id_buku}/halaman', [HalamanController::class, 'index'])->name('buku.halaman.index');
    
    Route::get('/buku/{id_buku}/halaman/proses-konversi', [HalamanController::class, 'processPdfFromStorage'])->name('buku.halaman.proses');
    
    Route::post('/buku/{id_buku}/halaman/reorder', [HalamanController::class, 'reorder'])->name('buku.halaman.reorder');
    
    Route::get('/halaman/{id_halaman}/edit', [HalamanController::class, 'edit'])->name('halaman.edit'); 
    
    Route::put('/halaman/{id_halaman}', [HalamanController::class, 'update'])->name('halaman.update');
    
    Route::delete('/halaman/{id_halaman}', [HalamanController::class, 'destroy'])->name('halaman.destroy');

    Route::get('/halaman/{id_halaman}/area', [AreaInteraktifController::class, 'index'])->name('area.index');      
    Route::post('/halaman/{id_halaman}/area', [AreaInteraktifController::class, 'store'])->name('area.store'); 
    
    Route::post('/area/{id_area}', [AreaInteraktifController::class, 'update'])->name('area.update');
    Route::delete('/area/{id_area}', [AreaInteraktifController::class, 'destroy'])->name('area.destroy');            

    Route::get('/audio-latar', [AudioLatarController::class, 'index'])->name('audio.index');
    Route::post('/audio-latar', [AudioLatarController::class, 'store'])->name('audio.store');
    
    Route::post('/audio-latar/{id_audio_latar}', [AudioLatarController::class, 'update'])->name('audio.update');
    Route::delete('/audio-latar/{id_audio_latar}', [AudioLatarController::class, 'destroy'])->name('audio.destroy');
});