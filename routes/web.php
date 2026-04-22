<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Front\CategoryController as FrontCategoryController;
use App\Http\Controllers\Front\ProductController as FrontProductController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Front\HomeController;
use App\Http\Controllers\Front\CartController;
use App\Http\Controllers\Front\CheckoutController;
use App\Http\Controllers\Front\AccountOrderController;
use Inertia\Inertia; 
use App\Models\User;
use App\Models\Product;
use App\Models\Category;


// 1st way 
Route::get('/dashboard', function () {
    return Inertia::render('Dashboard', [
        'users'         => (int) User::count(), 
        'categories'    => (int) Category::count(),
        'products'      => (int) Product::count(),
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


// 2nd way
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('categories', CategoryController::class);
        Route::resource('users', UserController::class);
        Route::resource('permissions', PermissionController::class)->except('create', 'show', 'edit');
        Route::resource('products', ProductController::class);
    });


Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/category/{category:slug?}', [FrontCategoryController::class, 'index'])->name('category');
Route::get('/product/{product:slug}', [FrontProductController::class, 'index'])->name('product');

Route::prefix('cart')->controller(CartController::class)->group(function () {
    Route::get('view', 'view')->name('cart.view');
    Route::post('store/{product}', 'store')->name('cart.store');
    Route::patch('update/{product}', 'update')->name('cart.update');
    Route::delete('delete/{product}', 'delete')->name('cart.delete');
});

Route::middleware('auth')->group(function () {

    // account profile routes
    Route::prefix('account')->name('account.')->group(function () {
        Route::get('orders', [AccountOrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [AccountOrderController::class, 'show'])->name('orders.show');
    });

    //checkout routes
    Route::prefix('checkout')->controller(CheckoutController::class)->group(function () {
        Route::post('order', 'store')->name('checkout.order');
        Route::get('success', 'success')->name('checkout.success');
        Route::get('cancel', 'cancel')->name('checkout.cancel');
    });
});


require __DIR__ . '/auth.php';
