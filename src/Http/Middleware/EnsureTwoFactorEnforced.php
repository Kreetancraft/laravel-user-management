<?php

namespace Kreetancraft\UserManagement\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Kreetancraft\UserManagement\Models\User;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorEnforced
{
    /**
     * Redirect users who must enroll 2FA to the host application's security page.
     *
     * The destination is host territory — this package ships no profile screens —
     * so it comes from config. If it is not configured the middleware fails
     * CLOSED: enforcement that silently stops enforcing is worse than an error.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User
            || ! $user->requiresTwoFactorEnforcement()
            || $user->hasEnabledTwoFactor()
        ) {
            return $next($request);
        }

        $securityRoute = config('user-management.routes.names.security_edit');

        // Always let them reach the page that lets them comply, or sign out.
        if ($securityRoute !== null && $request->routeIs($securityRoute, $securityRoute.'.*', 'logout')) {
            return $next($request);
        }

        if ($request->routeIs('logout')) {
            return $next($request);
        }

        abort_if(
            $securityRoute === null,
            403,
            'Two-factor authentication is required, but no security page is configured. '
            .'Set [user-management.routes.names.security_edit] to the route where users enable 2FA.'
        );

        return redirect()->route($securityRoute)->with('flash', [
            'variant' => 'warning',
            'text' => __('Two-factor authentication is required for your account. Please enable it to continue.'),
        ]);
    }
}
