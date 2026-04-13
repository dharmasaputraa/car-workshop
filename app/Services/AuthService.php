<?php

namespace App\Services;

use App\DTOs\Auth\LoginData;
use App\DTOs\Auth\RegisterData;
use App\DTOs\Auth\ResetPasswordData;
use App\Events\UserRegistered;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\JWTGuard;

class AuthService
{
    /**
     * Return the currently authenticated user from the API guard.
     */
    public function me(): User
    {
        return Auth::guard('api')->user();
    }

    /**
     * Register a new user inside a DB transaction.
     * Token generation is intentionally left to the controller.
     */
    public function register(RegisterData $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name'      => $data->name,
                'email'     => $data->email,
                'password'  => $data->password,
                'is_active' => true,
            ]);

            event(new UserRegistered($user));

            return $user;
        });
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
            'email'    => $data->email,
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
     *
     * @throws ValidationException
     */
    public function resetPassword(ResetPasswordData $data): void
    {
        $status = Password::broker()->reset(
            $data->toArray(),
            function (User $user, string $password) {
                $user->forceFill([
                    'password'       => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }
    }

    /**
     * Verify the user's email address via a signed URL.
     *
     * @throws ValidationException
     */
    public function verifyEmail(string $id, string $hash): void
    {
        /** @var User $user */
        $user = User::findOrFail($id);

        if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            throw ValidationException::withMessages([
                'email' => ['Invalid verification link.'],
            ]);
        }

        if ($user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => ['Email address is already verified.'],
            ]);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }
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
            'token_type'   => 'bearer',
            'expires_in'   => $this->guard()->factory()->getTTL() * 60,
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
