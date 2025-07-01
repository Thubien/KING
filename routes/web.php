<?php

use App\Http\Controllers\PartnerInvitationController;
use App\Http\Controllers\ShopifyConnectionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Partner invitation routes
Route::get('/partnership/accept/{token}', [PartnerInvitationController::class, 'showAcceptForm'])
    ->name('partnership.accept');

Route::post('/partnership/accept/{token}', [PartnerInvitationController::class, 'acceptInvitation'])
    ->name('partnership.accept.process');

Route::get('/partnership/invalid', [PartnerInvitationController::class, 'invalidInvitation'])
    ->name('partnership.invalid');

// Shopify connection routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/shopify/connect', [ShopifyConnectionController::class, 'connect'])
        ->name('shopify.connect');

    Route::post('/stores/{store}/disconnect', [ShopifyConnectionController::class, 'disconnect'])
        ->name('shopify.disconnect');
});

// Public Shopify callback (no auth middleware needed)
Route::get('/shopify/callback', [ShopifyConnectionController::class, 'callback'])
    ->name('shopify.callback');

// Shopify webhooks (no auth middleware - verified via HMAC)
Route::post('/webhooks/shopify/orders/create', [\App\Http\Controllers\ShopifyWebhookController::class, 'handleOrderCreated'])
    ->name('shopify.webhooks.orders.create');

Route::post('/webhooks/shopify/orders/updated', [\App\Http\Controllers\ShopifyWebhookController::class, 'handleOrderUpdated'])
        ->name('shopify.webhooks.orders.updated');

Route::post('/webhooks/shopify/orders/paid', [\App\Http\Controllers\ShopifyWebhookController::class, 'handleOrderPaid'])
        ->name('shopify.webhooks.orders.paid');

// Health check endpoints for production monitoring
Route::get('/health', [\App\Http\Controllers\HealthCheckController::class, 'check'])
    ->name('health.check');

Route::get('/ping', [\App\Http\Controllers\HealthCheckController::class, 'ping'])
    ->name('health.ping');
