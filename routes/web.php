<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

// Réservé aux visiteurs NON connectés (un utilisateur connecté est renvoyé au dashboard).
Route::middleware('guest')->group(function () {
    // Le nom 'login' est important : Laravel y envoie automatiquement les non-connectés.
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

// Réservé aux utilisateurs connectés. 'auth' vérifie la connexion AVANT 'can:...'.
Route::middleware('auth')->group(function () {

    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // Tous les rôles voient le dashboard.
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Provisoire : sera remplacé par ton OrderImportController.
    Route::get('/imports/create', fn () => 'Formulaire d’import (à venir)')
        ->middleware('can:import-orders')
        ->name('imports.create');

    // Provisoire : sera remplacé par l'export.
    Route::get('/orders/export', fn () => 'Export (à venir)')
        ->middleware('can:export-orders')
        ->name('orders.export');

    // Provisoire : gestion des utilisateurs, réservée à l'admin.
    Route::get('/admin/users', fn () => 'Gestion des utilisateurs (à venir)')
        ->middleware('can:manage-users')
        ->name('admin.users.index');
});
