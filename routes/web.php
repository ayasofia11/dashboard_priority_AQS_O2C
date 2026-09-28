<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\PriorityDecisionController;
use App\Http\Controllers\PriorityConfigController;
use App\Http\Controllers\ImportHistoryController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

// Réservé aux visiteurs NON connectés.
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

// Réservé aux utilisateurs connectés.
Route::middleware('auth')->group(function () {

    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // Dashboard : tous les rôles connectés.
    Route::get('/dashboard', fn () => redirect()->route('dashboard.stats'))->name('dashboard');
    Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');

    // Liste et détail des commandes : tous les rôles connectés (matrice : "Voir dashboard/liste" = tous).
    Route::get('/orders', [SalesOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [SalesOrderController::class, 'show'])->name('orders.show')->whereNumber('order');

    // Import et décision du planificateur : Admin + Planificateur.
    Route::middleware('can:import-orders')->group(function () {
        Route::get('/imports/create', fn () => 'Formulaire d’import (à venir)')->name('imports.create');
        Route::post('/imports/customers', [ImportController::class, 'customers'])->name('imports.customers');
        Route::post('/imports/products', [ImportController::class, 'products'])->name('imports.products');
        Route::post('/imports/orders', [ImportController::class, 'orders'])->name('imports.orders');
        Route::post('/evaluations/{evaluation}/decision', [PriorityDecisionController::class, 'store'])->name('evaluations.decision');
        Route::get('/imports', [ImportHistoryController::class, 'index'])->name('imports.index');
        Route::get('/imports/{batch}', [ImportHistoryController::class, 'show'])->name('imports.show');
        Route::get('/imports/{batch}/errors', [ImportHistoryController::class, 'errors'])->name('imports.errors');
    });

Route::middleware('can:manage-priority-config')->prefix('admin/priority-models')->group(function () {
    Route::get('/', [PriorityConfigController::class, 'index'])->name('priority-models.index');
    Route::get('/{model}', [PriorityConfigController::class, 'show'])->name('priority-models.show');
    Route::post('/', [PriorityConfigController::class, 'store'])->name('priority-models.store');
    Route::patch('/{model}/thresholds', [PriorityConfigController::class, 'updateThresholds'])->name('priority-models.thresholds');
    Route::patch('/{model}/weights', [PriorityConfigController::class, 'updateWeights'])->name('priority-models.weights');
    Route::patch('/factors/{factor}/config', [PriorityConfigController::class, 'updateFactorConfig'])->name('priority-factors.config');
    Route::post('/{model}/activate', [PriorityConfigController::class, 'activate'])->name('priority-models.activate');
    });

    // Export : tous sauf Lecteur.
    Route::get('/orders/export', [ExportController::class, 'export'])
        ->middleware('can:export-orders')
        ->name('orders.export');

    // Gestion des utilisateurs : Admin seulement (provisoire, à remplacer par UserController).
    Route::get('/admin/users', fn () => 'Gestion des utilisateurs (à venir)')
        ->middleware('can:manage-users')
        ->name('admin.users.index');
});
