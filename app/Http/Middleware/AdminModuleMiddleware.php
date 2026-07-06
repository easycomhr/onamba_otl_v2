<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate 2: Can this user access a specific admin module?
 * Usage in routes: middleware('admin.module:module_key')
 *
 * - manager     → always allowed
 * - hr (legacy) → allowed for everything except 'salary'
 * - custom role → check admin_role_permissions table (cached 5 min)
 */
class AdminModuleMiddleware
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = Auth::user();

        $isFullAccess = in_array($user->employee_code, config('const.full_access_users', []));

        if ($isFullAccess) {
            $user->role = 'manager';
        }

        // manager bypasses all module checks
        if ($user->role === 'manager') {
            return $next($request);
        }

        // legacy hr role
        if ($user->role === 'hr') {
            if ($module !== 'salary') {
                return $next($request);
            }
            abort(403, 'Bạn không có quyền truy cập module này.');
        }

        // custom role via admin_role_id
        if ($user->admin_role_id) {
            $allowed = Cache::remember(
                "admin_role_{$user->admin_role_id}_modules",
                300,
                fn () => $user->adminRole()->with('permissions')->first()?->moduleKeys() ?? []
            );

            if (in_array($module, $allowed)) {
                return $next($request);
            }
        }

        abort(403, 'Bạn không có quyền truy cập module này.');
    }
}
