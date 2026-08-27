<?php

namespace Kreetancraft\UserManagement\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Kreetancraft\UserManagement\Models\User;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorEnforced
{
    /**
     * Redirect users who must enroll 2FA to their security settings.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User
            && $user->requiresTwoFactorEnforcement()
            && ! $user->hasEnabledTwoFactor()
        ) {
            if ($request->routeIs('security.edit', 'security.edit.*', 'logout')) {
                return $next($request);
            }

            return redirect()->route('security.edit')
                ->with('flash', ['variant' => 'warning', 'text' => __('Two-factor authentication is required for your account. Please enable it to continue.')]);
        }

        return $next($request);
    }
}
