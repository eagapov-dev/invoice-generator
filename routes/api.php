<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\CompanySettingsController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\LemonSqueezyWebhookController;
use App\Http\Controllers\Api\PublicInvoiceController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\RecurringInvoiceController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Auth routes (public)
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
});

// Public routes
Route::get('plans', [PlanController::class, 'index']);

// Lemon Squeezy webhooks (no auth, signature verified in controller)
Route::post('webhooks/lemon-squeezy', [LemonSqueezyWebhookController::class, 'handle']);

// Public invoice view and PDF download via token
Route::get('p/{token}', [PublicInvoiceController::class, 'show']);
Route::get('p/{token}/pdf', [PublicInvoiceController::class, 'downloadPdf']);

// Public PDF download with signed URL
Route::get('invoices/{invoice}/pdf/download', [InvoiceController::class, 'downloadPdf'])
    ->name('invoices.pdf.download')
    ->middleware('signed');

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('user', fn (Request $request) => new UserResource($request->user()));

    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index']);

    // User plan & limits
    Route::get('user/limits', [PlanController::class, 'limits']);
    Route::get('user/subscription', [PlanController::class, 'subscription']);

    // Clients
    Route::apiResource('clients', ClientController::class)->except(['store']);
    Route::post('clients', [ClientController::class, 'store'])->middleware('plan.limit:client');

    // Products
    Route::apiResource('products', ProductController::class)->except(['store']);
    Route::post('products', [ProductController::class, 'store'])->middleware('plan.limit:product');

    // Invoices
    Route::apiResource('invoices', InvoiceController::class)->except(['store']);
    Route::post('invoices', [InvoiceController::class, 'store'])->middleware('plan.limit:invoice');
    Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send']);
    Route::patch('invoices/{invoice}/status', [InvoiceController::class, 'updateStatus']);
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'generatePdf']);
    Route::get('invoices/{invoice}/pdf-url', [InvoiceController::class, 'getPdfUrl']);
    Route::patch('invoices/{invoice}/share', [InvoiceController::class, 'toggleShare']);

    // Recurring Invoices
    Route::apiResource('recurring-invoices', RecurringInvoiceController::class);
    Route::patch('recurring-invoices/{recurring_invoice}/toggle', [RecurringInvoiceController::class, 'toggleActive']);

    // Export
    Route::get('export/invoices/csv', [ExportController::class, 'csv']);
    Route::get('export/invoices/excel', [ExportController::class, 'excel']);

    // Company Settings
    Route::get('settings', [CompanySettingsController::class, 'show']);
    Route::put('settings', [CompanySettingsController::class, 'update']);
    Route::post('settings/logo', [CompanySettingsController::class, 'uploadLogo']);

    // Billing
    Route::post('billing/checkout', [BillingController::class, 'checkout']);
    Route::get('billing/portal', [BillingController::class, 'portal']);
    Route::get('billing/status', [BillingController::class, 'status']);
});
