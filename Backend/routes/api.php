<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Api\ClearanceController;
use App\Http\Controllers\Admin\Api\AuthController;
use Illuminate\Support\Facades\Mail;
use App\Mail\MyTestEmail;
use Barryvdh\DomPDF\Facade\Pdf;

/*
|--------------------------------------------------------------------------
| API Routes for Clearance Management
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Public Test Routes
|--------------------------------------------------------------------------
*/
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

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
|
| /api/auth/login     - Login
| /api/auth/register  - Register
| /api/auth/logout    - Logout (protected)
| /api/auth/user      - Authenticated user (protected)
|
*/
Route::prefix('auth')->group(function () {

    // Public Auth Endpoints
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');

    // Protected Auth Endpoints
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/user', [AuthController::class, 'user'])->name('auth.user');
    });
});

/*
|--------------------------------------------------------------------------
| Public Clearance Routes
|--------------------------------------------------------------------------
*/
Route::prefix('public')->group(function () {
    Route::prefix('clearances')->group(function () {
        Route::get('/request-types', [ClearanceController::class, 'getRequestTypes']);
        Route::get('/approver-roles', [ClearanceController::class, 'getApproverRoles']);
    });
});

/*
|--------------------------------------------------------------------------
| Admin Routes (Protected)
|--------------------------------------------------------------------------
|
| All routes here require a valid Sanctum token.
|
*/
Route::prefix('admin')->middleware('auth:sanctum')->group(function () {

    Route::prefix('clearances')->group(function () {

        // CRUD
        Route::get('/', [ClearanceController::class, 'index'])->name('clearances.index');
        Route::post('/', [ClearanceController::class, 'store'])->name('clearances.store');
        Route::get('/{id}', [ClearanceController::class, 'show'])->name('clearances.show');
        Route::put('/{id}', [ClearanceController::class, 'update'])->name('clearances.update');
        Route::delete('/{id}', [ClearanceController::class, 'destroy'])->name('clearances.destroy');

        // Bulk actions
        Route::post('/bulk-action', [ClearanceController::class, 'bulkAction'])
            ->name('clearances.bulk-action');

        // Approver Update
        Route::put('/{clearanceId}/approvers/{approverId}', [ClearanceController::class, 'updateApprover'])
            ->name('clearances.update-approver');

        // Meta dropdown helpers
        Route::get('/meta/request-types', [ClearanceController::class, 'getRequestTypes'])
            ->name('clearances.request-types');
        Route::get('/meta/approver-roles', [ClearanceController::class, 'getApproverRoles'])
            ->name('clearances.approver-roles');
    });
});

/*
|--------------------------------------------------------------------------
| Test Routes (Email & PDF)
|--------------------------------------------------------------------------
*/
Route::get('/testroute', function () {
    Mail::to('sv8905958@gmail.com')->send(new MyTestEmail('EC'));
    return "Email sent!";
});

Route::get('/export-pdf', function () {
    $data = ['name' => 'EC'];
    $pdf = Pdf::loadView('pdf.example', $data);
    return $pdf->download('myfile.pdf');
});

/*
|--------------------------------------------------------------------------
| Available Endpoints (Documentation)
|--------------------------------------------------------------------------
|
| Public:
|   GET  /api/
|   GET  /api/health
|   POST /api/auth/login
|   POST /api/auth/register
|   GET  /api/public/clearances/request-types
|   GET  /api/public/clearances/approver-roles
|
| Auth Required:
|   POST /api/auth/logout
|   GET  /api/auth/user
|
| Admin (Auth Required):
|   GET    /api/admin/clearances
|   POST   /api/admin/clearances
|   GET    /api/admin/clearances/{id}
|   PUT    /api/admin/clearances/{id}
|   DELETE /api/admin/clearances/{id}
|   POST   /api/admin/clearances/bulk-action
|   PUT    /api/admin/clearances/{id}/approvers/{approverId}
|   GET    /api/admin/clearances/meta/request-types
|   GET    /api/admin/clearances/meta/approver-roles
|
*/
