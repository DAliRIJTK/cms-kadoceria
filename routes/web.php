<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BukuController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HalamanController;
use App\Http\Controllers\AudioLatarController;

Route::get('/', function () {
    return redirect('/login');
});

Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', [BukuController::class, 'dashboard'])->name('dashboard');

    Route::resource('buku', BukuController::class);
    Route::get('/buku-search', [BukuController::class, 'search'])->name('buku.search');
    Route::patch('/buku/{buku}/status', [BukuController::class, 'updateStatus'])->name('buku.updateStatus');

    // Halaman Management
    Route::get('/halaman-management', [HalamanController::class, 'management'])->name('halaman.management');
    Route::get('/halaman/{halaman}', [HalamanController::class, 'show'])->name('halaman.show');
    Route::get('/halaman/{halaman}/edit', [HalamanController::class, 'edit'])->name('halaman.edit');
    Route::patch('/halaman/{halaman}', [HalamanController::class, 'update'])->name('halaman.update');
    Route::delete('/halaman/{halaman}', [HalamanController::class, 'destroy'])->name('halaman.destroy');
    Route::post('/halaman-reorder', [HalamanController::class, 'reorder'])->name('halaman.reorder');

    // Halaman Narasi
    Route::post('/halaman/{halaman}/narasi', [HalamanController::class, 'storeNarasi'])->name('halaman.storeNarasi');
    Route::delete('/halaman/{halaman}/narasi', [HalamanController::class, 'deleteNarasi'])->name('halaman.deleteNarasi');

    // Halaman Backsound (AudioLatar)
    Route::patch('/halaman/{halaman}/backsound', [HalamanController::class, 'setBacksound'])->name('halaman.setBacksound');
    Route::patch('/halaman/{halaman}/backsound/remove', [HalamanController::class, 'removeBacksound'])->name('halaman.removeBacksound');

    // Area Interaktif (Annotations)
    Route::post('/area-interaktif', [HalamanController::class, 'storeAreaInteraktif'])->name('halaman.storeAreaInteraktif');
    Route::patch('/area-interaktif/{area}', [HalamanController::class, 'updateAreaInteraktif'])->name('halaman.updateAreaInteraktif');
    Route::delete('/area-interaktif/{area}', [HalamanController::class, 'deleteAreaInteraktif'])->name('halaman.deleteAreaInteraktif');
    Route::post('/area-interaktif/{area}/audio', [HalamanController::class, 'storeAreaAudio'])->name('halaman.storeAreaAudio');
    Route::get('/flipbook/{buku}', [HalamanController::class, 'flipbook'])->name('halaman.flipbook');

    // Audio Latar
    Route::get('/audio-latar', [AudioLatarController::class, 'index'])->name('audio-latar.index');
    Route::post('/audio-latar', [AudioLatarController::class, 'store'])->name('audio-latar.store');
    Route::delete('/audio-latar/{audioLatar}', [AudioLatarController::class, 'delete'])->name('audio-latar.delete');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/api/buku', [BukuController::class, 'apiBooks']);
Route::get('/api/buku/{id}', [BukuController::class, 'apiBookDetail']);
Route::get('/api/get/dataInformasiBuku', [BukuController::class, 'apiDataInformasiBuku']);
Route::get('/api/get/kontenBuku', [BukuController::class, 'apiKontenBuku']);
Route::get('/api/get/detailBuku', [BukuController::class, 'apiDetailBuku']);

require __DIR__.'/auth.php';
