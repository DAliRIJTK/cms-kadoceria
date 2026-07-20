<?php

use App\Http\Controllers\BukuController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HalamanController;
use App\Http\Controllers\AudioLatarController;
use App\Http\Controllers\BoundingBoxController;
use App\Http\Controllers\AudioController;

Route::get('/', function () {
    return redirect('/login');
});

Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', [BukuController::class, 'index'])->name('dashboard');
    Route::get('/buku', function () {
        return redirect()->route('dashboard');
    });

    // Buku Routes
    Route::get('/buku/tambahbuku', [BukuController::class, 'create'])->name('buku.create');
    Route::post('/buku', [BukuController::class, 'store'])->name('buku.store');
    Route::get('/buku/{buku}', [BukuController::class, 'show'])->name('buku.show');
    Route::get('/buku/{buku}/edit', [BukuController::class, 'edit'])->name('buku.edit');
    Route::match(['put', 'patch'], '/buku/{buku}', [BukuController::class, 'update'])->name('buku.update');
    Route::delete('/buku/{buku}', [BukuController::class, 'destroy'])->name('buku.destroy');
    Route::get('/buku-search', [BukuController::class, 'search'])->name('buku.search');
    Route::patch('/buku/{buku}/status', [BukuController::class, 'updateStatus'])->name('buku.updateStatus');

    // Halaman Management
    Route::get('/kelola-halaman', [HalamanController::class, 'management'])->name('halaman.management');
    Route::get('/halaman/{buku:judul_idn}/halaman{nomor_halaman}', [HalamanController::class, 'show'])->name('halaman.show');
    Route::get('/halaman/{buku:judul_idn}/halaman{nomor_halaman}/edit', [HalamanController::class, 'edit'])->name('halaman.edit');
    Route::patch('/halaman/{halaman}', [HalamanController::class, 'update'])->name('halaman.update');
    Route::delete('/halaman/bulk', [HalamanController::class, 'bulkDestroy'])->name('halaman.bulkDestroy');
    Route::delete('/halaman/{halaman}', [HalamanController::class, 'destroy'])->name('halaman.destroy');

    // Halaman Narasi
    Route::post('/halaman/{halaman}/narasi', [AudioController::class, 'storeNarasi'])->name('halaman.storeNarasi');
    Route::delete('/halaman/{halaman}/narasi', [AudioController::class, 'deleteNarasi'])->name('halaman.deleteNarasi');

    // Halaman Backsound (AudioLatar)
    Route::patch('/halaman/{halaman}/backsound', [HalamanController::class, 'setBacksound'])->name('halaman.setBacksound');
    Route::patch('/halaman/{halaman}/backsound/remove', [HalamanController::class, 'removeBacksound'])->name('halaman.removeBacksound');

    // Area Interaktif (Annotations)
    Route::post('/area-interaktif', [BoundingBoxController::class, 'store'])->name('halaman.storeAreaInteraktif');
    Route::patch('/area-interaktif/{area}', [BoundingBoxController::class, 'update'])->name('halaman.updateAreaInteraktif');
    Route::delete('/area-interaktif/{area}', [BoundingBoxController::class, 'destroy'])->name('halaman.deleteAreaInteraktif');
    Route::post('/area-interaktif/{area}/audio', [AudioController::class, 'storeAreaAudio'])->name('halaman.storeAreaAudio');
    Route::delete('/area-interaktif/{area}/audio', [AudioController::class, 'deleteAreaAudio'])->name('halaman.deleteAreaAudio');
    Route::get('/flipbook/{buku}', [HalamanController::class, 'flipbook'])->name('halaman.flipbook');

    // Audio Latar
    Route::get('/audio-latar', [AudioLatarController::class, 'index'])->name('audio-latar.index');
    Route::post('/audio-latar', [AudioLatarController::class, 'store'])->name('audio-latar.store');
    Route::delete('/audio-latar/{audioLatar}', [AudioLatarController::class, 'delete'])->name('audio-latar.delete');


});



require __DIR__.'/auth.php';
