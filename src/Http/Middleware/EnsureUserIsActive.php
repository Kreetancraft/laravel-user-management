<?php

namespace Kreetancraft\UserManagement\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Kreetancraft\UserManagement\Models\User;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user instanceof User && ! $user->is_active) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => __('Your account has been deactivated. Please contact an administrator.'),
            ]);
        }

        return $next($request);
    }
}
