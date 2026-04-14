<?php

namespace App\Services;

use App\Actions\Auth\RegisterUser;
use App\Actions\Auth\ResetUserPassword;
use App\Actions\Auth\VerifyEmail;
use App\DTOs\Auth\LoginData;
use App\DTOs\Auth\RegisterData;
use App\DTOs\Auth\ResetPasswordData;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\JWTGuard;

class AuthService
{
    public function __construct(
        protected RegisterUser $registerUser,
        protected VerifyEmail $verifyEmail,
        protected ResetUserPassword $resetUserPassword,
    ) {}

    /**
     * Return the currently authenticated user from the API guard.
     */
    public function me(): User
    {
        return Auth::guard('api')->user();
    }

    /**
     * Register a new user.
     * Delegates to RegisterUser action.
     * Token generation is intentionally left to the controller.
     */
    public function register(RegisterData $data): User
    {
        return $this->registerUser->execute($data);
    }

    /**
     * Attempt login and return a token payload.
     *
     * @return array{ access_token: string, token_type: string, expires_in: int }
     *
     * @throws ValidationException
     */
    public function login(LoginData $data): array
    {
        $guard = $this->guard();

        $token = $guard->attempt([
            'email' => $data->email,
            'password' => $data->password,
        ]);

        if (! $token) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        $this->ensureUserIsActive($guard->user(), $guard);

        return $this->buildTokenData($token);
    }

    /**
     * Refresh the current JWT token and return a new token payload.
     *
     * @return array{ access_token: string, token_type: string, expires_in: int }
     *
     * @throws ValidationException
     */
    public function refresh(): array
    {
        $guard = $this->guard();

        $this->ensureUserIsActive($guard->user(), $guard);

        return $this->buildTokenData($guard->refresh());
    }

    /**
     * Invalidate the current JWT token (logout).
     */
    public function revokeToken(): void
    {
        $this->guard()->logout();
    }

    /**
     * Send a password reset link to the given email address.
     *
     * @throws ValidationException
     */
    public function sendPasswordResetLink(string $email): void
    {
        $status = Password::broker()->sendResetLink(['email' => $email]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }
    }

    /**
     * Reset the user's password using the token sent via email.
     * Delegates to ResetUserPassword action.
     *
     * @throws ValidationException
     */
    public function resetPassword(ResetPasswordData $data): void
    {
        $status = Password::broker()->reset(
            $data->toArray(),
            fn(User $user, string $password) => $this->resetUserPassword->execute($user, $password)
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }
    }

    /**
     * Verify the user's email address via a signed URL.
     * Delegates to VerifyEmail action.
     *
     * @throws ValidationException
     */
    public function verifyEmail(string $id, string $hash): void
    {
        $this->verifyEmail->execute($id, $hash);
    }

    /**
     * Resend the email verification notification.
     *
     * @throws ValidationException
     */
    public function resendVerificationEmail(User $user): void
    {
        if ($user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => ['Email address is already verified.'],
            ]);
        }

        $user->sendEmailVerificationNotification();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Build and return a token payload array.
     *
     * @return array{ access_token: string, token_type: string, expires_in: int }
     */
    public function buildTokenData(string $token): array
    {
        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $this->guard()->factory()->getTTL() * 60,
        ];
    }

    /**
     * Resolve the JWT guard instance.
     */
    private function guard(): JWTGuard
    {
        /** @var JWTGuard */
        return Auth::guard('api');
    }

    /**
     * Throw a ValidationException and logout if the user is inactive.
     *
     * @throws ValidationException
     */
    private function ensureUserIsActive(User $user, JWTGuard $guard): void
    {
        if ($user->is_active) {
            return;
        }

        $guard->logout();

        throw ValidationException::withMessages([
            'email' => ['Your account has been deactivated.'],
        ]);
    }
}
