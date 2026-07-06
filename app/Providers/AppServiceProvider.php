<?php

namespace App\Providers;

use App\Models\AdminRole;
use App\Models\LeaveRequest;
use App\Models\OtRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
        View::composer('layouts.admin', function ($view) {
            $user = Auth::user();

            // $allowedModules: null = full access (manager), array = specific keys, [] = none
            $allowedModules = null;
            if ($user && $user->role !== 'manager') {
                if ($user->role === 'hr') {
                    // legacy hr: everything except salary
                    $allowedModules = array_keys(array_filter(
                        AdminRole::MODULES,
                        fn ($key) => $key !== 'salary',
                        ARRAY_FILTER_USE_KEY
                    ));
                } elseif ($user->admin_role_id) {
                    $allowedModules = Cache::remember(
                        "admin_role_{$user->admin_role_id}_modules",
                        300,
                        fn () => $user->adminRole()->with('permissions')->first()?->moduleKeys() ?? []
                    );
                }
            }

            // Team approver: scope badge counts về member của team mình
            $isTeamScopedAdmin = $user && !$user->isManager() && !$user->isHr();
            if ($isTeamScopedAdmin) {
                $memberIds = $user->managedTeamMemberIds();
                $sidebarOtPending    = OtRequest::pending()->whereIn('user_id', $memberIds)->count();
                $sidebarLeavePending = LeaveRequest::pending()->whereIn('user_id', $memberIds)->count();
            } else {
                $sidebarOtPending    = OtRequest::pending()->count();
                $sidebarLeavePending = LeaveRequest::pending()->count();
            }

            $view->with([
                'sidebarOtPending'    => $sidebarOtPending,
                'sidebarLeavePending' => $sidebarLeavePending,
                'allowedModules'      => $allowedModules,
            ]);
        });

        View::composer('layouts.employee', function ($view) {
            if (auth()->check()) {
                $service = app(\App\Services\EmployeeDashboardService::class);
                $view->with('isApprover', $service->isApprover(auth()->id()));
            } else {
                $view->with('isApprover', false);
            }
        });
    }
}
