<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate 1: Can this user enter the admin panel at all?
 * - manager → yes (full access)
 * - hr      → yes (legacy role)
 * - has admin_role_id → yes (custom role, module check done by AdminModuleMiddleware)
 * - else → redirect to employee dashboard
 */
class AdminAccessMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        if (
            $user->role === 'manager'
            || $user->role === 'hr'
            || $user->admin_role_id !== null
        ) {
            return $next($request);
        }

        return redirect()->route('employee.dashboard');
    }
}
