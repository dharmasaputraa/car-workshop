<?php

use App\Http\Controllers\Api\V1\User\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Health
|--------------------------------------------------------------------------
*/

// Route::prefix('health')->name('health.')->group(function () {
//     Route::get('/basic', [HealthController::class, 'basic'])->name('basic');

//     Route::middleware(['auth:api', 'verified', 'active', 'role:' . RoleType::SUPER_ADMIN->value]) // Nanti Ganti dengan permission: view-full-health
//         ->get('/full', [HealthController::class, 'full'])
//         ->name('full');
// });


/*
|--------------------------------------------------------------------------
| User
|--------------------------------------------------------------------------
*/

Route::prefix('users')->name('users.')->group(function () {
    Route::get('/trashed', [UserController::class, 'trashed'])->name('trashed');
    Route::post('/{id}/restore', [UserController::class, 'restore'])->name('restore');
    Route::patch('/{id}/toggle-active', [UserController::class, 'toggleActive'])->name('toggle-active');
    Route::patch('/{id}/role', [UserController::class, 'changeRole'])->name('change-role');
});

Route::apiResource('users', UserController::class)->names('users');


/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/
// Route::prefix('auth')->name('auth.')->group(function () {

//     $strictThrottle = app()->isLocal() ? 'throttle:100,1' : 'throttle:3,1';
//     $loginThrottle  = app()->isLocal() ? 'throttle:100,1' : 'throttle:30,1';

//     // Public (strict)
//     Route::middleware($strictThrottle)->group(function () {
//         Route::post('/register', [AuthController::class, 'register'])->name('register');
//         Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.forgot');
//         Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');
//     });

//     // Login (custom throttle)
//     Route::post('/login', [AuthController::class, 'login'])
//         ->middleware($loginThrottle)
//         ->name('login');

//     // OAuth (Public)
//     Route::get('/social/{provider}/redirect', [AuthController::class, 'redirectToProvider'])
//         ->name('social.redirect');
//     Route::get('/social/{provider}/callback', [AuthController::class, 'handleProviderCallback'])
//         ->name('social.callback');

//     // Authenticated
//     Route::middleware(['auth:api', 'active'])->group(function () {
//         Route::post('/refresh', [AuthController::class, 'refresh'])->name('token.refresh');
//         Route::post('/revoke', [AuthController::class, 'revokeToken'])->name('token.revoke');

//         Route::post('/email/verification-notification', [AuthController::class, 'resendVerificationEmail'])
//             ->name('email.verification.resend');

//         // Social Account Management
//         Route::post('/social/link', [AuthController::class, 'linkSocialAccount'])
//             ->name('social.link');
//         Route::delete('/social/unlink/{provider}', [AuthController::class, 'unlinkSocialAccount'])
//             ->name('social.unlink');
//         Route::get('/social/accounts', [AuthController::class, 'getLinkedAccounts'])
//             ->name('social.accounts');
//     });

//     // Email verification (signed URL)
//     Route::middleware('signed')
//         ->get('/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])
//         ->name('email.verification.verify');
// });
