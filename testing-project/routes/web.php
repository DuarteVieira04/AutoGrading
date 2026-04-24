<?php

use Illuminate\Support\Facades\Route;

// Importação de todos os controladores necessários
use App\Http\Controllers\CardController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ItemsOrderController;
use App\Http\Controllers\OperationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SettingsShippingCostController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\SupplyOrderController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\InventoryController;


/*
|--------------------------------------------------------------------------
| Rotas Públicas
|--------------------------------------------------------------------------
*/
Route::view('/', 'home')->name('home');
Route::redirect('/', '/products/showcase');
Route::get('products/showcase', [ProductController::class, 'showCase'])->name('products.showcase');
Route::resource('products', ProductController::class)->only(['index', 'show']);

// Rotas do Carrinho de Compras
Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'show'])->name('show');
    Route::post('/add/{product}', [CartController::class, 'add'])->name('add');
    Route::patch('/update/{product}', [CartController::class, 'update'])->name('update');
    Route::delete('/remove/{product}', [CartController::class, 'remove'])->name('remove');
    Route::delete('/destroy', [CartController::class, 'destroy'])->name('destroy');
    Route::post('/add-quantity/{product}', [CartController::class, 'addQuantity'])->name('addQuantity');
    Route::post('/remove-quantity/{product}', [CartController::class, 'removeQuantity'])->name('removeQuantity');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/meu-cartao', [CardController::class, 'showMyCard'])->name('card.my.show');
    Route::post('/meu-cartao/carregar', [CardController::class, 'topUp'])->name('card.topup');
});


/*
|--------------------------------------------------------------------------
| Rotas para Utilizadores Autenticados
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    // Dashboard - Redirecionador inteligente para a página de estatísticas apropriada
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Perfil do Utilizador
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Rotas de Confirmação do Carrinho
    Route::get('/cart/confirm', [CartController::class, 'confirm'])->name('cart.confirm');
    Route::post('/cart/processConfirm', [CartController::class, 'processConfirm'])->name('cart.processConfirm');


    // Bloco de redirecionamento para chamadas incorretas
    Route::get('/settings', function () {
        return redirect()->route('admin.settings.index');
    })->middleware('role:board')->name('settings.index');

    Route::get('/settings/create', function () {
        $setting = \App\Models\Setting::firstOrFail();
        return redirect()->route('admin.settings.edit', $setting);
    })->middleware('role:board')->name('settings.create');

    Route::get('/settings/{setting}', function (\App\Models\Setting $setting) {
        return redirect()->route('admin.settings.edit', $setting);
    })->middleware('role:board')->name('settings.show');

    Route::get('/settings/{setting}/edit', function (\App\Models\Setting $setting) {
        return redirect()->route('admin.settings.edit', $setting);
    })->middleware('role:board')->name('settings.edit');

    Route::delete('/settings/{setting}', function () {
        return back()->withErrors(['error' => 'Application settings cannot be deleted.']);
    })->middleware('role:board')->name('settings.destroy');

    Route::get('/settings-shipping-costs', function () {
        return redirect()->route('admin.settingsShippingCosts.index');
    })->middleware('role:board')->name('settingsShippingCosts.index');

    Route::get('/settings-shipping-costs/create', function () {
        return redirect()->route('admin.settingsShippingCosts.create');
    })->middleware('role:board')->name('settingsShippingCosts.create');

    Route::get('/settings-shipping-costs/{settingsShippingCost}', function (\App\Models\SettingsShippingCost $settingsShippingCost) {
        return redirect()->route('admin.settingsShippingCosts.show', $settingsShippingCost);
    })->middleware('role:board')->name('settingsShippingCosts.show');

    Route::get('/settings-shipping-costs/{settingsShippingCost}/edit', function (\App\Models\SettingsShippingCost $settingsShippingCost) {
        return redirect()->route('admin.settingsShippingCosts.edit', $settingsShippingCost);
    })->middleware('role:board')->name('settingsShippingCosts.edit');

    Route::delete('/settings-shipping-costs/{settingsShippingCost}', function (\App\Models\SettingsShippingCost $settingsShippingCost) {
        return redirect()->route('admin.settingsShippingCosts.destroy', $settingsShippingCost);
    })->middleware('role:board')->name('settingsShippingCosts.destroy');

    Route::get('/users', function () {
        return redirect()->route('admin.users.index');
    })->middleware('role:board')->name('users.index');

    Route::get('/users/create', function () {
        return redirect()->route('admin.users.create');
    })->middleware('role:board')->name('users.create');

    Route::get('/products/create', function () {
        return redirect()->route('admin.products.create');
    })->middleware('role:board')->name('products.create');

    Route::get('/categories', function () {
        return redirect()->route('admin.categories.index');
    })->middleware('role:board')->name('categories.index');

    Route::get('/categories/create', function () {
        return redirect()->route('admin.categories.create');
    })->middleware('role:board')->name('categories.create');

    Route::get('/categories/{category}', function (\App\Models\Category $category) {
        return redirect()->route('admin.categories.show', $category);
    })->middleware('role:board')->name('categories.show');

    Route::get('/categories/{category}/edit', function (\App\Models\Category $category) {
        return redirect()->route('admin.categories.edit', $category);
    })->middleware('role:board')->name('categories.edit');

    Route::delete('/categories/{category}', function (\App\Models\Category $category) {
        return app()->call('App\Http\Controllers\CategoryController@destroy', ['category' => $category]);
    })->middleware('role:board')->name('categories.destroy');

    Route::get('/cards', function () {
        return redirect()->route('admin.cards.index');
    })->middleware('role:board')->name('cards.index');

    Route::get('/cards/create', function () {
        return redirect()->route('admin.cards.index');
    })->middleware('role:board')->name('cards.create');

    Route::get('/cards/{card}', function (\App\Models\Card $card) {
        return redirect()->route('admin.cards.show', $card);
    })->middleware('role:board')->name('cards.show');

    Route::get('/cards/{card}/edit', function (\App\Models\Card $card) {
        return redirect()->route('admin.cards.edit', $card);
    })->middleware('role:board')->name('cards.edit');

    Route::delete('/cards/{card}', function (\App\Models\Card $card) {
        return redirect()->route('admin.cards.destroy', $card);
    })->middleware('role:board')->name('cards.destroy');

    Route::get('/supply-orders', function () {
        return redirect()->route('admin.supply-orders.index');
    })->middleware('role:employee,board')->name('supplyOrders.index');

    Route::get('/supply-orders/create', function () {
        return redirect()->route('admin.supply-orders.create');
    })->middleware('role:employee,board')->name('supplyOrders.create');

    Route::get('/supply-orders/{supplyOrder}', function (\App\Models\SupplyOrder $supplyOrder) {
        return redirect()->route('admin.supply-orders.show', $supplyOrder);
    })->middleware('role:employee,board')->name('supplyOrders.show');

    Route::get('/supply-orders/{supplyOrder}/edit', function (\App\Models\SupplyOrder $supplyOrder) {
        return redirect()->route('admin.supply-orders.edit', $supplyOrder);
    })->middleware('role:employee,board')->name('supplyOrders.edit');

    Route::delete('/supply-orders/{supplyOrder}', function (\App\Models\SupplyOrder $supplyOrder) {
        return redirect()->route('admin.supply-orders.destroy', $supplyOrder);
    })->middleware('role:employee,board')->name('supplyOrders.destroy');

    Route::get('/stock-adjustments', function () {
        return redirect()->route('admin.stock-adjustments.index');
    })->middleware('role:employee,board')->name('stockAdjustments.index');

    Route::get('/stock-adjustments/create', function () {
        return redirect()->route('admin.stock-adjustments.create');
    })->middleware('role:employee,board')->name('stockAdjustments.create');

    /*
    |--------------------------------------------------------------------------
    | Área do Membro (Acessível a 'member' e 'board')
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:member,board')->group(function () {
        Route::get('orders/showcase', [OrderController::class, 'showCase'])->name('orders.showcase');
        Route::resource('orders', OrderController::class)->only(['show', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::resource('operations', OperationController::class);
        Route::get('/minhas-estatisticas', [StatisticsController::class, 'memberIndex'])->name('member.statistics.index');
        Route::prefix('member')->name('member.')->group(function () {
            Route::get('/card', [CardController::class, 'show'])->name('card.show');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | PAINEL DE GESTÃO (Acessível a 'employee' e 'board')
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:employee,board')->prefix('admin')->name('admin.')->group(function () {
        // Rotas de gestão para Funcionários e Direção
        Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        Route::patch('/orders/{order}/complete', [AdminOrderController::class, 'complete'])->name('orders.complete');
        Route::patch('/orders/{order}/cancel', [AdminOrderController::class, 'cancel'])->name('orders.cancel');

        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::post('/inventory/adjust', [InventoryController::class, 'adjustStock'])->name('inventory.adjust');

        Route::resource('supply-orders', SupplyOrderController::class);
        Route::resource('stock-adjustments', StockAdjustmentController::class);

        /*
        |--------------------------------------------------------------------------
        | Área Exclusiva de Administradores ('board')
        |--------------------------------------------------------------------------
        */
        Route::middleware('role:board')->group(function () {
            Route::get('/statistics', [StatisticsController::class, 'index'])->name('statistics.index');

            // Gestão de Recursos
            Route::resource('products', ProductController::class);
            Route::resource('users', UserController::class);
            Route::resource('categories', CategoryController::class);
            Route::resource('cards', CardController::class);
            Route::resource('settings', SettingController::class);
            Route::resource('operations', OperationController::class);
            Route::resource('itemsOrders', ItemsOrderController::class);
            Route::resource('settingsShippingCosts', SettingsShippingCostController::class);

            // Ações Específicas
            Route::patch('/users/{user}/block', [UserController::class, 'block'])->name('users.block');
            Route::patch('/users/{user}/unblock', [UserController::class, 'unblock'])->name('users.unblock');
        });
    });
});

/*
|--------------------------------------------------------------------------
| Rotas de Autenticação do Laravel
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';