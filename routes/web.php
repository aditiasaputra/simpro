<?php

use App\Http\Controllers\CartItemController;
use App\Http\Controllers\ItemController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/items/search', function (\Illuminate\Http\Request $request) {
        $query = $request->input('q');

        // Cari barang yang namanya mengandung kata kunci, ambil maksimal 10
        return \App\Models\Item::where('name', 'like', "%{$query}%")
            ->select('id', 'name', 'type')
            ->take(10)
            ->get();
    })->name('items.search');
    Route::resource('items', ItemController::class);

    Route::post('/cart-items/bulk', [CartItemController::class, 'bulkStore'])->name('cart-items.bulk');
    Route::post('/cart-items/checkout', [CartItemController::class, 'checkout'])->name('cart-items.checkout');
    Route::resource('cart-items', CartItemController::class);

    Route::resource('purchase-requisitions', App\Http\Controllers\PurchaseRequisitionController::class);
});

require __DIR__.'/settings.php';
