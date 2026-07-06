<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminRoleController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OtApprovalController;
use App\Http\Controllers\Admin\LeaveApprovalController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\OtImportController;
use App\Http\Controllers\Admin\LeaveImportController;
use App\Http\Controllers\Admin\OtReportController;
use App\Http\Controllers\Admin\LeaveReportController;
use App\Http\Controllers\Admin\OtRegisterController;
use App\Http\Controllers\Admin\LeaveRegisterController;
use App\Http\Controllers\Admin\SalaryController as AdminSalaryController;

/*
|--------------------------------------------------------------------------
| Admin Routes
| Prefix  : /admin
| Name    : admin.*
|
| Gate 1 (admin.access)  : manager | hr (legacy) | has admin_role_id
| Gate 2 (admin.module)  : module-level check per route group
| Admin-roles mgmt       : manager only (role:manager)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'admin.access'])->group(function () {

    // ── Dashboard ─────────────────────────────────────────────────────────
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('admin.module:dashboard')
        ->name('dashboard');

    // ── OT Approvals ─────────────────────────────────────────────────────
    Route::prefix('approvals/ot')->name('approvals.ot.')
        ->middleware('admin.module:approvals.ot')
        ->group(function () {
            Route::get('/',              [OtApprovalController::class, 'index'])  ->name('index');
            Route::get('/{id}',          [OtApprovalController::class, 'show'])   ->name('show');
            Route::post('/{id}/approve', [OtApprovalController::class, 'approve'])->name('approve');
            Route::post('/{id}/reject',  [OtApprovalController::class, 'reject']) ->name('reject');
        });

    // ── Leave Approvals ──────────────────────────────────────────────────
    Route::prefix('approvals/leave')->name('approvals.leave.')
        ->middleware('admin.module:approvals.leave')
        ->group(function () {
            Route::get('/',              [LeaveApprovalController::class, 'index'])  ->name('index');
            Route::get('/{id}',          [LeaveApprovalController::class, 'show'])   ->name('show');
            Route::post('/{id}/approve', [LeaveApprovalController::class, 'approve'])->name('approve');
            Route::post('/{id}/reject',  [LeaveApprovalController::class, 'reject']) ->name('reject');
        });

    // ── Register OT / Leave on behalf of employee ────────────────────────
    Route::prefix('register')->name('register.')->group(function () {
        Route::get('/ot',           [OtRegisterController::class,    'create'])   ->middleware('admin.module:register.ot')   ->name('ot.create');
        Route::get('/ot/employees', [OtRegisterController::class,    'employees'])->middleware('admin.module:register.ot')   ->name('ot.employees');
        Route::post('/ot',          [OtRegisterController::class,    'store'])    ->middleware('admin.module:register.ot')   ->name('ot.store');
        Route::get('/leave',        [LeaveRegisterController::class, 'create'])   ->middleware('admin.module:register.leave')->name('leave.create');
        Route::get('/leave/employees', [LeaveRegisterController::class, 'employees'])->middleware('admin.module:register.leave')->name('leave.employees');
        Route::post('/leave',       [LeaveRegisterController::class, 'store'])    ->middleware('admin.module:register.leave')->name('leave.store');
    });

    // ── Employee Management ───────────────────────────────────────────────
    Route::prefix('employees')->name('employees.')
        ->middleware('admin.module:employees')
        ->group(function () {
            Route::get('/',                       [EmployeeController::class, 'index'])         ->name('index');
            Route::get('/create',                 [EmployeeController::class, 'create'])        ->name('create');
            Route::post('/',                      [EmployeeController::class, 'store'])         ->name('store');
            Route::get('/{id}',                   [EmployeeController::class, 'show'])          ->name('show');
            Route::delete('/{id}',                [EmployeeController::class, 'destroy'])       ->name('destroy');
            Route::get('/{id}/edit',              [EmployeeController::class, 'edit'])          ->name('edit');
            Route::put('/{id}',                   [EmployeeController::class, 'update'])        ->name('update');
            Route::post('/{id}/change-password',  [EmployeeController::class, 'changePassword'])->name('change-password');
        });

    // ── Bulk Import ───────────────────────────────────────────────────────
    Route::prefix('import')->name('import.')->group(function () {
        Route::get('/ot',           [OtImportController::class,    'index'])   ->middleware('admin.module:import.ot')   ->name('ot');
        Route::get('/ot/template',  [OtImportController::class,    'template'])->middleware('admin.module:import.ot')   ->name('ot.template');
        Route::post('/ot',          [OtImportController::class,    'store'])   ->middleware('admin.module:import.ot')   ->name('ot.store');
        Route::get('/leave',        [LeaveImportController::class, 'index'])   ->middleware('admin.module:import.leave')->name('leave');
        Route::get('/leave/template',[LeaveImportController::class,'template'])->middleware('admin.module:import.leave')->name('leave.template');
        Route::post('/leave',       [LeaveImportController::class, 'store'])   ->middleware('admin.module:import.leave')->name('leave.store');
    });

    // ── Export Reports ────────────────────────────────────────────────────
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/ot',          [OtReportController::class,    'index']) ->middleware('admin.module:reports.ot')   ->name('ot');
        Route::post('/ot/export',  [OtReportController::class,    'export'])->middleware('admin.module:reports.ot')   ->name('ot.export');
        Route::get('/leave',       [LeaveReportController::class, 'index']) ->middleware('admin.module:reports.leave')->name('leave');
        Route::post('/leave/export',[LeaveReportController::class,'export'])->middleware('admin.module:reports.leave')->name('leave.export');
    });

    // ── Salary ────────────────────────────────────────────────────────────
    Route::prefix('salary')->name('salary.')
        ->middleware('admin.module:salary')
        ->group(function () {
            Route::get('/',          [AdminSalaryController::class, 'index'])      ->name('index');
            Route::get('/import',    [AdminSalaryController::class, 'import'])     ->name('import');
            Route::get('/template',  [AdminSalaryController::class, 'template'])   ->name('template');
            Route::post('/import',   [AdminSalaryController::class, 'importStore'])->name('import.store');
        });

    // ── Teams ─────────────────────────────────────────────────────────────
    Route::middleware('admin.module:teams')->group(function () {
        Route::put('teams/{team}/members', [\App\Http\Controllers\Admin\TeamController::class, 'syncMembers'])->name('teams.members.sync');
        Route::resource('teams', \App\Http\Controllers\Admin\TeamController::class)->names([
            'index'   => 'teams.index',
            'create'  => 'teams.create',
            'store'   => 'teams.store',
            'show'    => 'teams.show',
            'edit'    => 'teams.edit',
            'update'  => 'teams.update',
            'destroy' => 'teams.destroy',
        ]);
    });

    // ── Admin Role Management (manager only) ─────────────────────────────
    Route::middleware('role:manager')->group(function () {
        Route::resource('admin-roles', AdminRoleController::class)->names([
            'index'   => 'admin-roles.index',
            'create'  => 'admin-roles.create',
            'store'   => 'admin-roles.store',
            'show'    => 'admin-roles.show',
            'edit'    => 'admin-roles.edit',
            'update'  => 'admin-roles.update',
            'destroy' => 'admin-roles.destroy',
        ]);
    });
});
