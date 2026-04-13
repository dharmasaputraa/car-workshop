<?php

namespace App\Http\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

trait ThrottlesRequests
{
    /**
     * Pastikan request tidak melebihi batas rate limit.
     * Lempar 422 jika sudah terlalu banyak attempt.
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
     * Tambah hit pada throttle key.
     */
    protected function hitThrottle(
        Request $request,
        string  $field = 'email',
        int     $decaySeconds = 60,
    ): void {
        RateLimiter::hit($this->throttleKey($request, $field), $decaySeconds);
    }

    /**
     * Hapus throttle key setelah berhasil (misal: login sukses).
     */
    protected function clearThrottle(Request $request, string $field = 'email'): void
    {
        RateLimiter::clear($this->throttleKey($request, $field));
    }

    /**
     * Bangun Redis key dari nilai field + IP address.
     *
     * Format: throttle:<action>:<value>|<ip>
     * Contoh: throttle:login:john@example.com|127.0.0.1
     *
     * RateLimiter::hit() / clear() / tooManyAttempts() di Laravel
     * sudah otomatis pakai cache driver yang dikonfigurasi (Redis).
     * Key ini cukup unik untuk mencegah brute-force per user per IP.
     */
    protected function throttleKey(Request $request, string $field = 'email'): string
    {
        $action = $this->resolveThrottleAction();
        $value  = mb_strtolower((string) $request->input($field, $request->ip()));

        return "throttle:{$action}:{$value}|{$request->ip()}";
    }

    /**
     * Resolve nama action dari nama class controller secara otomatis.
     * Contoh: AuthController -> auth, UserController -> user
     */
    private function resolveThrottleAction(): string
    {
        $class = class_basename(static::class);

        return mb_strtolower(str_replace('Controller', '', $class));
    }
}
