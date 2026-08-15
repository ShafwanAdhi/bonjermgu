<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deactivation was only ever checked at login (Login::login). A session
 * started before Admin flips is_active to false kept working until it
 * expired — SESSION_LIFETIME is two hours, longer with "remember me". This
 * closes that gap on every request, not just the next login.
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'username' => 'Akun ini tidak aktif. Hubungi Admin.',
            ]);
        }

        return $next($request);
    }
}
