<?php

namespace App\Http\Middleware;

use Closure;

class DisableCsrfProtection
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Exclude specific routes from CSRF protection
        if ($request->is('api/login')) {
            return $next($request);
        }

        // Continue with CSRF protection for other routes
        return app(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)->handle($request, $next);
    }
}
