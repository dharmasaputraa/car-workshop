<?php

namespace App\Http\Middleware\Filament;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && ! Auth::user()->is_active) {
            Auth::logout();

            return redirect()
                ->route('filament.admin.auth.login')
                ->withErrors([
                    'email' => 'Your account has been deactivated.',
                ]);
        }

        return $next($request);
    }
}
