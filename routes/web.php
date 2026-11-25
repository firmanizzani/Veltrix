<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IntelligenceController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProductSalesController;
use App\Http\Controllers\ResourceController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn() => Inertia::render('Landing'))->name('home');
Route::middleware('auth')->group(function () {
    Route::get('/settings', [\App\Http\Controllers\SettingsController::class, 'edit']);
    Route::put('/settings', [\App\Http\Controllers\SettingsController::class, 'update']);
    Route::post('/alerts/read', [
        \App\Http\Controllers\SettingsController::class,
        'readNotifications',
    ]);
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/reports', [DashboardController::class, 'index']);
    Route::get('/reports/export', [DashboardController::class, 'export']);
    Route::get('/reports/pdf', [DashboardController::class, 'pdf']);
    Route::get('/reports/products', [ProductSalesController::class, 'index']);
    Route::get('/reports/products/export', [ProductSalesController::class, 'export']);
    Route::get('/alerts', [DashboardController::class, 'alerts']);
    Route::get('/pos', [PosController::class, 'index'])->name('pos');
    Route::post('/pos/checkout', [PosController::class, 'checkout'])->middleware(
        'throttle:60,1',
    );
    Route::post('/shifts/open', [PosController::class, 'openShift']);
    Route::post('/shifts/close', [PosController::class, 'closeShift']);
    Route::get('/orders/{order}', [PosController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/refund', [ResourceController::class, 'requestRefund']);
    Route::get('/expenses/{expense}/receipt', [ResourceController::class, 'receipt']);
    Route::get('/manage/{resource}', [ResourceController::class, 'index']);
    Route::post('/manage/{resource}', [ResourceController::class, 'store']);
    Route::put('/manage/{resource}/{record}', [ResourceController::class, 'update']);
    Route::post('/manage/{resource}/{record}/{action}', [
        ResourceController::class,
        'action',
    ]);
    Route::get('/profile', [ProfileController::class, 'edit']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/password', [ProfileController::class, 'password']);
    Route::get('/intelligence', [IntelligenceController::class, 'index']);
    Route::post('/intelligence/messages', [
        IntelligenceController::class,
        'message',
    ])->middleware('throttle:business-chat');
    Route::post('/intelligence/generate', [
        IntelligenceController::class,
        'generate',
    ])->middleware('throttle:business-insights');
    Route::patch('/intelligence/insights/{insight}', [
        IntelligenceController::class,
        'status',
    ]);
    Route::post('/intelligence/insights/{insight}/explain', [
        IntelligenceController::class,
        'explain',
    ])->middleware('throttle:business-chat');
});
