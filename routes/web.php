<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CameraController;
use App\Http\Controllers\HonkDetectionController;
use App\Http\Controllers\SpeedViolationsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/camera', [CameraController::class, 'index'])->name('camera.index');

    Route::get('/honk-detection', [HonkDetectionController::class, 'index'])
        ->name('honk.index');

    Route::get('/speed-violations', [SpeedViolationsController::class, 'index'])
        ->name('speed.index');
});

// Image URLs must work without auth so <img> tags load (e.g. on honk-detection / speed-violations)
Route::get('/speed-violations/image/{filename}', [SpeedViolationsController::class, 'image'])
    ->name('speed.image')->where('filename', '[a-zA-Z0-9_\-\.]+');

require __DIR__.'/auth.php';
