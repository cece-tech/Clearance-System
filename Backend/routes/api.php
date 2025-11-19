<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\ClearanceController;
use App\Http\Controllers\Admin\Api\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes for Clearance Management
|--------------------------------------------------------------------------
*/

// Public test routes
Route::get('/', function () {
    return response()->json([
        'success' => true,
        'message' => 'Clearance API is running',
        'version' => '1.0',
        'timestamp' => now()->toDateTimeString(),
    ]);
});

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

// Authentication routes (No middleware needed for login/register)
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
    
    // Protected auth routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/user', [AuthController::class, 'user'])->name('auth.user');
    });
});

// Public routes (No authentication required)
Route::prefix('public')->group(function () {
    Route::prefix('clearances')->group(function () {
        Route::get('/request-types', [ClearanceController::class, 'getRequestTypes']);
        Route::get('/approver-roles', [ClearanceController::class, 'getApproverRoles']);
    });
});

// Admin routes WITH authentication
Route::prefix('admin')->middleware('auth:sanctum')->group(function () {
    
    // Clearance Management Routes
    Route::prefix('clearances')->group(function () {
        // Main CRUD operations
        Route::get('/', [ClearanceController::class, 'index'])->name('clearances.index');
        Route::post('/', [ClearanceController::class, 'store'])->name('clearances.store');
        Route::get('/{id}', [ClearanceController::class, 'show'])->name('clearances.show');
        Route::put('/{id}', [ClearanceController::class, 'update'])->name('clearances.update');
        Route::delete('/{id}', [ClearanceController::class, 'destroy'])->name('clearances.destroy');
        
        // Bulk actions
        Route::post('/bulk-action', [ClearanceController::class, 'bulkAction'])->name('clearances.bulk-action');
        
        // Approver management
        Route::put('/{clearanceId}/approvers/{approverId}', [ClearanceController::class, 'updateApprover'])
            ->name('clearances.update-approver');
        
        // Helper routes for dropdowns
        Route::get('/meta/request-types', [ClearanceController::class, 'getRequestTypes'])
            ->name('clearances.request-types');
        Route::get('/meta/approver-roles', [ClearanceController::class, 'getApproverRoles'])
            ->name('clearances.approver-roles');
    });
});

/*
|--------------------------------------------------------------------------
| Available Endpoints
|--------------------------------------------------------------------------
| 
| Public Routes (No Authentication):
| GET    /api/                                            - API status check
| GET    /api/health                                      - Health check
| POST   /api/auth/login                                  - Login
| POST   /api/auth/register                               - Register
| GET    /api/public/clearances/request-types            - Get request types
| GET    /api/public/clearances/approver-roles           - Get approver roles
|
| Protected Routes (Requires Authentication Token):
| POST   /api/auth/logout                                 - Logout
| GET    /api/auth/user                                   - Get current user
|
| Admin Routes (Requires Authentication):
| GET    /api/admin/clearances                           - List all clearances
| POST   /api/admin/clearances                           - Create new clearance
| GET    /api/admin/clearances/{id}                      - Get single clearance
| PUT    /api/admin/clearances/{id}                      - Update clearance
| DELETE /api/admin/clearances/{id}                      - Delete clearance
| POST   /api/admin/clearances/bulk-action               - Bulk actions
| PUT    /api/admin/clearances/{id}/approvers/{approverId} - Update approver
| GET    /api/admin/clearances/meta/request-types        - Get request types
| GET    /api/admin/clearances/meta/approver-roles       - Get approver roles
|
| IMPORTANT: I removed the 'admin' middleware because it might not exist.
| If you have a custom 'admin' middleware, add it back:
| Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(...)
|
*/