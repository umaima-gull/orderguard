<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourierController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\RecoveryController;
use App\Http\Controllers\RiskController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\PublicVerifyController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PublicRecoveryController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProfileController;


use App\Http\Controllers\ExportController;
use Illuminate\Support\Facades\Route;

// Public: Shopify / WooCommerce call this (protected by HMAC signature)
Route::post('/webhooks/{source}/{store}', [WebhookController::class, 'receive'])->name('webhooks.receive');

// Public: customer opens the confirmation link from WhatsApp/SMS
Route::get('/verify/{token}', [PublicVerifyController::class, 'show'])->middleware('throttle:30,1')->name('verify.show');
Route::post('/verify/{token}', [PublicVerifyController::class, 'respond'])->middleware('throttle:10,1')->name('verify.respond');
Route::get('/', function () {
    return auth()->check()
        ? app(\App\Http\Controllers\DashboardController::class)->index()
        : view('landing');
})->name('dashboard');
// Guests only
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
        Route::get('/forgot-password', [PasswordResetController::class, 'showForgot'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:5,1')->name('password.update');
});

// Public: customer opens the recovery link after a failed delivery
Route::get('/recover/{token}', [PublicRecoveryController::class, 'show'])->middleware('throttle:30,1')->name('recover.show');
Route::post('/recover/{token}', [PublicRecoveryController::class, 'submit'])->middleware('throttle:10,1')->name('recover.submit');

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Everything else needs login
Route::middleware('auth')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/password', [ProfileController::class, 'password'])->middleware('throttle:6,1')->name('profile.password');

    // ---------- GET routes (viewer bhi dekh sakta hai) ----------
    

    // Orders
    Route::get('/orders', [OrderController::class, 'index'])->name('orders');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');

    // Risk intelligence
    Route::get('/risk-intelligence', [RiskController::class, 'index'])->name('risk-intelligence');

    // COD verification
    Route::get('/cod-verification', [VerificationController::class, 'index'])->name('cod-verification');

    // Shipments
    Route::get('/shipments', [ShipmentController::class, 'index'])->name('shipments');
    Route::get('/shipments/{shipment}', [ShipmentController::class, 'show'])->name('shipments.show');

    // RTO recovery
    Route::get('/rto-recovery', [RecoveryController::class, 'index'])->name('rto-recovery');
    Route::get('/rto-recovery/{case}', [RecoveryController::class, 'show'])->name('rto-recovery.show');

    // Customers
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');

    // Courier partners
    Route::get('/courier-partners', [CourierController::class, 'index'])->name('courier-partners');

    // Analytics
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');

    // Integrations
    Route::get('/integrations', [IntegrationController::class, 'index'])->name('integrations');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');

    // Notifications (role ke bina)
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readall');
    Route::post('/notifications/{id}/open', [NotificationController::class, 'open'])->name('notifications.open');
    Route::get('/export/{type}', [ExportController::class, 'download'])->name('export');
    // ---------- ACT level ----------
    Route::middleware('role:act')->group(function () {
        Route::post('/orders/{order}/decision', [OrderController::class, 'decide'])->name('orders.decide');
        Route::post('/orders/{order}/rescore', [OrderController::class, 'rescore'])->name('orders.rescore');
        Route::post('/cod-verification/orders/{order}/send', [VerificationController::class, 'send'])->name('verification.send');
        Route::post('/cod-verification/logs/{log}/respond', [VerificationController::class, 'respond'])->name('verification.respond');
        Route::post('/shipments', [ShipmentController::class, 'store'])->name('shipments.store');
        Route::post('/shipments/{shipment}/status', [ShipmentController::class, 'updateStatus'])->name('shipments.status');
        Route::post('/shipments/{shipment}/case', [ShipmentController::class, 'openCase'])->name('shipments.case');
        Route::post('/rto-recovery/{case}/act', [RecoveryController::class, 'act'])->name('rto-recovery.act');
        Route::post('/customers/{customer}/notes', [CustomerController::class, 'notes'])->name('customers.notes');
        Route::post('/customers/{customer}/refresh', [CustomerController::class, 'refresh'])->name('customers.refresh');
    });

    // ---------- MANAGE level ----------
    Route::middleware('role:manage')->group(function () {
        Route::post('/risk-intelligence/rules/{rule}', [RiskController::class, 'updateRule'])->name('risk.rule.update');
        Route::post('/risk-intelligence/rescore', [RiskController::class, 'rescoreAll'])->name('risk.rescore');
        Route::post('/customers/{customer}/blacklist', [CustomerController::class, 'blacklist'])->name('customers.blacklist');
        Route::post('/courier-partners', [CourierController::class, 'connect'])->name('courier-partners.connect');
        Route::post('/courier-partners/{account}/default', [CourierController::class, 'makeDefault'])->name('courier-partners.default');
        Route::post('/courier-partners/{account}/toggle', [CourierController::class, 'toggle'])->name('courier-partners.toggle');
        Route::post('/courier-partners/{account}/test', [CourierController::class, 'test'])->name('courier-partners.test');
        Route::post('/courier-partners/{account}/disconnect', [CourierController::class, 'disconnect'])->name('courier-partners.disconnect');
    });

    // ---------- ADMIN level ----------
    Route::middleware('role:admin')->group(function () {
        Route::post('/courier-partners/{account}/credentials', [CourierController::class, 'credentials'])->name('courier-partners.credentials');

        Route::get('/team', [TeamController::class, 'index'])->name('team');
        Route::post('/team', [TeamController::class, 'store'])->name('team.store');
        Route::post('/team/{user}/role', [TeamController::class, 'role'])->name('team.role');
        Route::post('/team/{user}/toggle', [TeamController::class, 'toggle'])->name('team.toggle');
        Route::post('/team/{user}/remove', [TeamController::class, 'remove'])->name('team.remove');

        Route::post('/settings/store', [SettingsController::class, 'updateStore'])->name('settings.store');
        Route::post('/settings/risk', [SettingsController::class, 'updateRisk'])->name('settings.risk');

        Route::post('/integrations', [IntegrationController::class, 'update'])->name('integrations.update');
        Route::post('/integrations/secret', [IntegrationController::class, 'regenerateSecret'])->name('integrations.secret');
        Route::post('/integrations/sync', [IntegrationController::class, 'sync'])->name('integrations.sync');
        Route::post('/integrations/disconnect', [IntegrationController::class, 'disconnect'])->name('integrations.disconnect');
        Route::post('/integrations/test', [IntegrationController::class, 'test'])->name('integrations.test');
        Route::post('/integrations/events/{event}/retry', [IntegrationController::class, 'retry'])->name('integrations.retry');
    });
});