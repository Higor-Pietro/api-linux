<?php

use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuditoriaController;


Route::redirect('/', '/dashboard')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('usuarios', UserController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

    Route::resource('tickets', TicketController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

    Route::get('logs', [AuditoriaController::class, 'index'])->name('auditoria.index');

     Route::get('logs/{activity}', [AuditoriaController::class, 'show'])->name('auditoria.show');
});
