<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\CompanySettingsController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\InvoiceController;
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

    // Clients
    Route::apiResource('clients', ClientController::class);

    // Products
    Route::apiResource('products', ProductController::class);

    // Invoices
    Route::apiResource('invoices', InvoiceController::class);
    Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send']);
    Route::patch('invoices/{invoice}/status', [InvoiceController::class, 'updateStatus']);
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'generatePdf']);
    Route::get('invoices/{invoice}/pdf-url', [InvoiceController::class, 'getPdfUrl']);

    // Company Settings
    Route::get('settings', [CompanySettingsController::class, 'show']);
    Route::put('settings', [CompanySettingsController::class, 'update']);
    Route::post('settings/logo', [CompanySettingsController::class, 'uploadLogo']);
});
