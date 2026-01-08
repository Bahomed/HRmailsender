<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\OrderController;

Route::get('/', function () {
    return redirect()->route('admin.login');
});

// Admin routes
Route::prefix('admin')->name('admin.')->group(function () {
    // Auth routes (no middleware)
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Protected admin routes
    Route::middleware(\App\Http\Middleware\AdminAuth::class)->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Users CRUD
        Route::resource('users', UserController::class);

        // Orders
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/print-list', [OrderController::class, 'printOrderList'])->name('orders.print-list');
        Route::delete('/orders/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');

        // Scan Step 1 - SKU Label Scanning
        Route::get('/orders/scan-step1', [OrderController::class, 'scanStep1'])->name('orders.scan-step1');
        Route::post('/orders/check-sku', [OrderController::class, 'checkSku'])->name('orders.check-sku');
        Route::post('/orders/store-scan', [OrderController::class, 'storeScan'])->name('orders.store-scan');

        // Scan Print Page - Barcode Scanner & PDF Print
        Route::get('/orders/scan-print', [OrderController::class, 'scanPrint'])->name('orders.scan-print');
        Route::post('/orders/find-by-sku', [OrderController::class, 'findBySku'])->name('orders.find-by-sku');
        Route::post('/orders/mark-as-printed', [OrderController::class, 'markAsPrinted'])->name('orders.mark-as-printed');
        Route::get('/orders/{order}/pdf', [OrderController::class, 'generatePdf'])->name('orders.pdf');
    });
});
