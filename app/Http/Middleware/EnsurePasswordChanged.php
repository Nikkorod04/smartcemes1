<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    /**
     * Keep accounts with a temporary password inside the password-change
     * flow until they choose a password of their own.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $routeName = $request->route()?->getName();

        $allowedRoutes = [
            'password.first',
            'password.first.update',
            'logout',
        ];

        if ($user?->must_change_password && ! in_array($routeName, $allowedRoutes, true)) {
            return redirect()->route('password.first');
        }

        return $next($request);
    }
}
