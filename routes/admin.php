<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Tenant\AuthController;
use App\Http\Controllers\Tenant\ContactController;
use App\Http\Controllers\Tenant\DealController;
use App\Http\Controllers\Tenant\FileController;
use App\Http\Controllers\Tenant\ReportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
| These routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Admin routes
Route::prefix('admin')->group(function () {
    // Tenant management routes
    Route::apiResource('tenants', TenantController::class);
    
    // Additional tenant actions
    Route::patch('tenants/{tenant}/suspend', [TenantController::class, 'suspend']);
    Route::patch('tenants/{tenant}/activate', [TenantController::class, 'activate']);
    
    // Database status
    Route::get('database/status', [TenantController::class, 'databaseStatus']);
});

// Tenant routes
Route::prefix('tenant')->group(function () {
    // Public routes (no authentication required)
    Route::post('login', [AuthController::class, 'login']);

    // Protected routes (require JWT authentication)
    Route::middleware('tenant.jwt')->group(function () {
        // Authentication routes
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        
        // Contact routes
        Route::apiResource('contacts', ContactController::class);
        
        // Deal routes
        Route::apiResource('deals', DealController::class);
        Route::patch('deals/{deal}/won', [DealController::class, 'markWon']);
        Route::patch('deals/{deal}/lost', [DealController::class, 'markLost']);
        
        // File upload routes
        Route::prefix('files')->group(function () {
            Route::post('upload', [FileController::class, 'upload']);
            Route::get('/', [FileController::class, 'index']);
            Route::get('{fileId}', [FileController::class, 'show']);
            Route::delete('{fileId}', [FileController::class, 'destroy']);
        });
        
        // Report routes
        Route::prefix('reports')->group(function () {
            Route::get('deals', [ReportController::class, 'deals']);
            Route::get('contacts', [ReportController::class, 'contacts']);
            Route::get('activities', [ReportController::class, 'activities']);
        });
    });
});
