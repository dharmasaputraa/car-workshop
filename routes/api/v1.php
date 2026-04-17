<?php

use App\Enums\RoleType;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Car\CarController;
use App\Http\Controllers\Api\V1\User\ProfileController;
use App\Http\Controllers\Api\V1\User\UserController;
use App\Http\Controllers\Api\V1\Complaint\ComplaintController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\Invoice\InvoiceController;
use App\Http\Controllers\Api\V1\Mechanic\MechanicAssignmentController;
use App\Http\Controllers\Api\V1\Service\ServiceController;
use App\Http\Controllers\Api\V1\WorkOrder\WorkOrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Health
|--------------------------------------------------------------------------
*/

Route::prefix('health')->name('health.')->group(function () {
    Route::get('/basic', [HealthController::class, 'basic'])->name('basic');
    Route::middleware(['auth:api', 'active'])
        ->get('/full', [HealthController::class, 'full'])->name('full');
});

/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->name('auth.')->group(function () {

    $strictThrottle = app()->isLocal() ? 'throttle:100,1' : 'throttle:3,1';
    $loginThrottle  = app()->isLocal() ? 'throttle:100,1' : 'throttle:30,1';

    // Public (strict)
    Route::middleware($strictThrottle)->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->name('register');
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.forgot');
        Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');
    });

    // Login (custom throttle)
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware($loginThrottle)
        ->name('login');

    // Authenticated
    Route::middleware(['auth:api', 'active'])->group(function () {
        Route::post('/refresh', [AuthController::class, 'refresh'])->name('token.refresh');
        Route::post('/revoke', [AuthController::class, 'revokeToken'])->name('token.revoke');

        Route::post('/email/verification-notification', [AuthController::class, 'resendVerificationEmail'])
            ->name('email.verification.resend');
    });

    // Email verification (signed URL)
    Route::middleware('signed')
        ->get('/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->name('email.verification.verify');
});

/*
|--------------------------------------------------------------------------
| PROFILE
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:api', 'active'])
    ->prefix('profile')
    ->name('profile.')
    ->group(function () {
        Route::get('/', [ProfileController::class, 'show'])->name('show');
        Route::put('/', [ProfileController::class, 'update'])->name('update');
        Route::post('/change-password', [ProfileController::class, 'changePassword'])->name('change-password');
        Route::patch('/avatar', [ProfileController::class, 'uploadAvatar'])->name('upload-avatar');
    });


/*
|--------------------------------------------------------------------------
| USER
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:api', 'active'])
    ->prefix('users')
    ->name('users.')
    ->group(function () {

        Route::get('/trashed', [UserController::class, 'trashed'])->name('trashed');
        Route::post('/{id}/restore', [UserController::class, 'restore'])->name('restore');
        Route::patch('/{id}/toggle-active', [UserController::class, 'toggleActive'])->name('toggle-active');
        Route::patch('/{id}/role', [UserController::class, 'changeRole'])->name('change-role');

        Route::apiResource('/', UserController::class)
            ->parameters(['' => 'user'])
            ->names([
                'index'   => 'index',
                'store'   => 'store',
                'show'    => 'show',
                'update'  => 'update',
                'destroy' => 'destroy',
            ]);
    });


/*
|--------------------------------------------------------------------------
| CAR
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:api', 'active'])
    ->prefix('cars')
    ->name('cars.')->group(function () {
        Route::apiResource('/', CarController::class)
            ->parameters(['' => 'car'])
            ->names([
                'index'   => 'index',
                'store'   => 'store',
                'show'    => 'show',
                'update'  => 'update',
                'destroy' => 'destroy',
            ]);
    });


/*
|--------------------------------------------------------------------------
| SERVICE
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:api', 'active'])
    ->prefix('services')
    ->name('services.')->group(function () {
        Route::patch('{service}/toggle-active', [ServiceController::class, 'toggleActive']);

        Route::apiResource('/', ServiceController::class)
            ->parameters(['' => 'service'])
            ->names([
                'index'   => 'index',
                'store'   => 'store',
                'show'    => 'show',
                'update'  => 'update',
                'destroy' => 'destroy',
            ]);
    });

/*
|--------------------------------------------------------------------------
| MECHANIC ASSIGNMENTS
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:api', 'active'])
    ->prefix('mechanic-assignments')
    ->name('mechanic-assignments.')
    ->group(function () {
        Route::patch('{id}/start', [MechanicAssignmentController::class, 'start'])->name('start');
        Route::patch('{id}/complete', [MechanicAssignmentController::class, 'complete'])->name('complete');
        Route::patch('{id}/cancel', [MechanicAssignmentController::class, 'cancel'])->name('cancel');

        Route::apiResource('/', MechanicAssignmentController::class)
            ->parameters(['' => 'id'])
            ->except(['destroy'])
            ->names([
                'index'   => 'index',
                'store'   => 'store',
                'show'    => 'show',
                'update'  => 'update',
            ]);
    });


/*
|--------------------------------------------------------------------------
| WORK ORDERS
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:api', 'active'])
    ->prefix('work-orders')
    ->name('work-orders.')
    ->group(function () {
        Route::patch('{id}/diagnose', [WorkOrderController::class, 'diagnose'])->name('diagnose');
        Route::patch('{id}/approve', [WorkOrderController::class, 'approve'])->name('approve');
        Route::patch('{id}/complete', [WorkOrderController::class, 'complete'])->name('complete');
        Route::patch('{id}/cancel', [WorkOrderController::class, 'cancel'])->name('cancel');

        Route::patch('{id}/mark-invoiced', [WorkOrderController::class, 'markAsInvoiced'])->name('mark-invoiced');
        Route::patch('{id}/record-complaint', [WorkOrderController::class, 'recordComplaint'])->name('record-complaint');

        Route::patch('services/{workOrderServiceId}/assign-mechanic', [WorkOrderController::class, 'assignMechanic'])->name('assign-mechanic');
        Route::patch('services/{workOrderServiceId}/start', [WorkOrderController::class, 'startService'])->name('services.start');
        Route::patch('services/{workOrderServiceId}/complete', [WorkOrderController::class, 'completeService'])->name('services.complete');
        Route::patch('assignments/{assignmentId}/cancel', [WorkOrderController::class, 'cancelMechanicAssignment'])->name('cancel-mechanic-assignment');

        Route::apiResource('/', WorkOrderController::class)
            ->parameters(['' => 'id'])
            ->except(['destroy'])
            ->names([
                'index'   => 'index',
                'store'   => 'store',
                'show'    => 'show',
                'update'  => 'update',
            ]);
    });


/*
|--------------------------------------------------------------------------
| COMPLAINTS
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:api', 'active'])
    ->prefix('complaints')
    ->name('complaints.')
    ->group(function () {
        Route::patch('{id}/reassign', [ComplaintController::class, 'reassign'])->name('reassign');
        Route::patch('{id}/resolve', [ComplaintController::class, 'resolve'])->name('resolve');
        Route::patch('{id}/reject', [ComplaintController::class, 'reject'])->name('reject');

        Route::patch('services/{complaintServiceId}/assign-mechanic', [ComplaintController::class, 'assignMechanic'])->name('assign-mechanic');

        Route::apiResource('/', ComplaintController::class)
            ->parameters(['' => 'id'])
            ->only(['index', 'show'])
            ->names([
                'index' => 'index',
                'show'  => 'show',
            ]);
    });


/*
|--------------------------------------------------------------------------
| INVOICES
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:api', 'active'])
    ->prefix('invoices')
    ->name('invoices.')
    ->group(function () {
        // Invoice generation is now automatic:
        // - Original WO invoice: via PATCH /work-orders/{id}/mark-invoiced
        // - Complaint invoice: auto-generated on ComplaintResolved event
        // Route::post('generate', [InvoiceController::class, 'generate'])->name('generate');

        Route::patch('{id}/send', [InvoiceController::class, 'send'])->name('send');
        Route::patch('{id}/pay', [InvoiceController::class, 'pay'])->name('pay');
        Route::patch('{id}/cancel', [InvoiceController::class, 'cancel'])->name('cancel');

        Route::apiResource('/', InvoiceController::class)
            ->parameters(['' => 'id'])
            ->only(['index', 'show'])
            ->names([
                'index' => 'index',
                'show'  => 'show',
            ]);
    });
