<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, $role)
    {
        if (Auth::guard('admin')->check()) {
            $user = Auth::guard('admin')->user();

            if ($user->role !== $role) {
                // Redirect based on user role
                if ($user->role === 'Admin') {
                    return redirect()->intended('admin/dashboard');
                } elseif ($user->role === 'Doctor') {
                    return redirect()->intended('doctor/patient');
                } elseif ($user->role === 'Staff') {
                    return redirect()->intended('staff/appointment');
                } else {
                    return redirect()->route('admin.login')->with('error', 'Unauthorized role');
                }
            }
        }

        return $next($request);
    }
}
