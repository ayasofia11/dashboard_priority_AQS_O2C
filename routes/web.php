    <?php

    use App\Http\Controllers\Auth\LoginController;
    use App\Http\Controllers\DashboardController;
    use App\Http\Controllers\ImportController;
    use App\Http\Controllers\ExportController;
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
        Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');

        // Tout ce qui touche à l'import est regroupé sous le même Gate.
    Route::middleware('can:import-orders')->group(function () {
        Route::get('/imports/create', fn () => 'Formulaire d’import (à venir)')->name('imports.create');
        Route::post('/imports/customers', [ImportController::class, 'customers'])->name('imports.customers');
        Route::post('/imports/products', [ImportController::class, 'products'])->name('imports.products');
        Route::post('/imports/orders', [ImportController::class, 'orders'])->name('imports.orders');
        Route::post('/evaluations/{evaluation}/decision', [PriorityDecisionController::class, 'store'])->name('evaluations.decision');
        });

        Route::get('/orders/export', [ExportController::class, 'export'])
            ->middleware('can:export-orders')
            ->name('orders.export');

        // Provisoire : gestion des utilisateurs, réservée à l'admin.
        Route::get('/admin/users', fn () => 'Gestion des utilisateurs (à venir)')
            ->middleware('can:manage-users')
            ->name('admin.users.index');
    });
