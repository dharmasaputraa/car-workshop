<?php

namespace App\Http\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

trait ThrottlesRequests
{
    /**
     * Ensure that requests do not exceed the rate limit.
     * Throw 422 if there are too many attempts.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function ensureIsNotRateLimited(
        Request $request,
        string  $field = 'email',
        int     $maxAttempts = 5,
        int     $decaySeconds = 60,
    ): void {
        $key = $this->throttleKey($request, $field);

        if (! RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return;
        }

        $seconds = RateLimiter::availableIn($key);

        throw ValidationException::withMessages([
            $field => [trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ])],
        ]);
    }

    /**
     * Add hit on throttle key.
     */
    protected function hitThrottle(
        Request $request,
        string  $field = 'email',
        int     $decaySeconds = 60,
    ): void {
        RateLimiter::hit($this->throttleKey($request, $field), $decaySeconds);
    }

    /**
     * Delete the throttle key after success (e.g., successful login).
     */
    protected function clearThrottle(Request $request, string $field = 'email'): void
    {
        RateLimiter::clear($this->throttleKey($request, $field));
    }

    /**
     * Construct a Redis key from the field value + IP address.
     *
     * Format: throttle:<action>:<value>|<ip>
     * Example: throttle:login:john@example.com|127.0.0.1
     *
     * RateLimiter::hit() / clear() / tooManyAttempts() in Laravel
     * Automatically uses the configured driver cache (Redis).
     * This key is unique enough to prevent brute-force attacks per user per IP.
     */
    protected function throttleKey(Request $request, string $field = 'email'): string
    {
        $action = $this->resolveThrottleAction();
        $value  = mb_strtolower((string) $request->input($field, $request->ip()));

        return "throttle:{$action}:{$value}|{$request->ip()}";
    }

    /**
     * Automatically resolves the action name from the controller class name.
     * Example: AuthController -> auth, UserController -> user
     */
    private function resolveThrottleAction(): string
    {
        $class = class_basename(static::class);

        return mb_strtolower(str_replace('Controller', '', $class));
    }
}
