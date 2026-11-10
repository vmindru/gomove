<?php

use App\Http\Controllers\GoMoveController;
use Illuminate\Support\Facades\Route;

Route::get('/', [GoMoveController::class, 'index'])->name('home');
Route::get('/facilities/{id}', [GoMoveController::class, 'show'])->whereNumber('id')->name('facilities.show');
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [GoMoveController::class, 'dashboard'])->name('dashboard');
    Route::post('/resources/{id}/book', [GoMoveController::class, 'book'])->middleware('throttle:30,1');
    Route::post('/bookings/{id}/decision', [GoMoveController::class, 'decide']);
    Route::get('/manage', [GoMoveController::class, 'manage']);
    Route::post('/resources/{id}/block', [GoMoveController::class, 'block']);
    Route::put('/resources/{id}', [GoMoveController::class, 'saveResource']);
    Route::post('/facilities', [GoMoveController::class, 'createFacility']);
    Route::put('/facilities/{id}/whole-field', [GoMoveController::class, 'wholeField']);
    Route::put('/facilities/{id}', [GoMoveController::class, 'editFacility']);
    Route::post('/facilities/{id}/resources', [GoMoveController::class, 'addResource']);
    Route::put('/organizations/{id}', [GoMoveController::class, 'organization']);
    Route::get('/admin', [GoMoveController::class, 'admin']);
    Route::post('/facilities/{id}/publish', [GoMoveController::class, 'publish']);
    Route::put('/browsing', [GoMoveController::class, 'browsing']);
});
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
