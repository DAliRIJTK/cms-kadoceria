<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BookController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController;

Route::get('/', function () {
    return redirect('/login');
});

Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', function () {
        return redirect('/books');
    })->name('dashboard');

    Route::resource('books', BookController::class);
    Route::get('/books-search', [BookController::class, 'search'])->name('books.search');
    Route::patch('/books/{book}/status', [BookController::class, 'updateStatus'])->name('books.updateStatus');

    // Pages Management
    Route::get('/pages-management', [PageController::class, 'management'])->name('pages.management');
    Route::get('/pages/{page}', [PageController::class, 'show'])->name('pages.show');
    Route::get('/pages/{page}/edit', [PageController::class, 'edit'])->name('pages.edit');
    Route::patch('/pages/{page}', [PageController::class, 'update'])->name('pages.update');
    Route::delete('/pages/{page}', [PageController::class, 'destroy'])->name('pages.destroy');
    
    // Page Audio Management
    Route::get('/pages/{page}/audio', [PageController::class, 'audioManagement'])->name('pages.audio');
    Route::post('/pages/{page}/audio', [PageController::class, 'storeAudio'])->name('pages.storeAudio');
    Route::delete('/audio/{audio}', [PageController::class, 'deleteAudio'])->name('audio.delete');
    
    // Bounding Box (Annotations)
    Route::post('/bounding-boxes', [PageController::class, 'storeBoundingBox']);
    Route::patch('/bounding-boxes/{box}', [PageController::class, 'updateBoundingBox']);
    Route::delete('/bounding-boxes/{box}', [PageController::class, 'deleteBoundingBox']);

    Route::post('/pages/narration', [PageController::class, 'storeNarration']);
    Route::post('/pages/backsound', [PageController::class, 'storeBacksound']);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/api/books', [BookController::class, 'apiBooks']);
Route::get('/api/books/{id}', [BookController::class, 'apiBookDetail']);

require __DIR__.'/auth.php';