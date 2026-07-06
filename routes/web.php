<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Employee\DashboardController as EmployeeDashboard;
use App\Http\Controllers\Employee\OtController;
use App\Http\Controllers\Employee\LeaveController;
use App\Http\Controllers\Employee\ProfileController;
use App\Http\Controllers\Employee\SalaryController;
use App\Http\Controllers\Employee\TeamApprovalController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ── Zalo OAuth callback (temporary, for getting OA access token) ───────────
Route::get('/zalo/callback', function (Request $request) {
    $code = $request->query('code');
    if (!$code) {
        return response()->json(['error' => 'No code received', 'params' => $request->all()]);
    }

    $appId     = '3700176445760170348';
    $appSecret = 'QKS93Xd74K8E6Q8U7m8X';

    $response = \Illuminate\Support\Facades\Http::withHeaders([
        'secret_key' => $appSecret,
        'app_id'     => $appId,
    ])->asForm()->post('https://oauth.zaloapp.com/v4/oa/access_token', [
        'app_id'      => $appId,
        'grant_type'  => 'authorization_code',
        'code'        => $code,
        'redirect_uri' => 'https://shoji.easycomhr.tech/zalo/callback',
    ]);

    return response()->json([
        'code'   => $code,
        'token'  => $response->json(),
        'status' => $response->status(),
    ]);
});

// ── Zalo token refresh (temporary) ───────────────────────────────────────
Route::get('/zalo/refresh', function () {
    $appId        = config('services.zalo.app_id') ?: '3700176445760170348';
    $appSecret    = config('services.zalo.app_secret') ?: 'QKS93Xd74K8E6Q8U7m8X';
    $refreshToken = env('ZALO_OA_REFRESH_TOKEN');

    if (!$refreshToken) {
        return response()->json(['error' => 'ZALO_OA_REFRESH_TOKEN not set']);
    }

    $response = \Illuminate\Support\Facades\Http::withHeaders([
        'secret_key' => $appSecret,
        'app_id'     => $appId,
    ])->asForm()->post('https://oauth.zaloapp.com/v4/oa/access_token', [
        'app_id'        => $appId,
        'grant_type'    => 'refresh_token',
        'refresh_token' => $refreshToken,
    ]);

    return response()->json([
        'status' => $response->status(),
        'body'   => $response->json(),
    ]);
});

// ── Zalo debug (temporary) ────────────────────────────────────────────────
Route::get('/zalo/test', function () {
    $token        = config('services.zalo.oa_access_token');
    $notifyUserId = config('services.zalo.notify_user_id');

    $result = [
        'token_set'       => !empty($token),
        'token_prefix'    => $token ? substr($token, 0, 10) . '...' : null,
        'notify_user_id'  => $notifyUserId ?: '(empty)',
    ];

    if (!$token || !$notifyUserId) {
        return response()->json(array_merge($result, ['error' => 'Missing config']));
    }

    // Test 1: get OA info (verify token works at all)
    $oaInfo = \Illuminate\Support\Facades\Http::withHeaders([
        'Authorization' => 'OA ' . $token,
    ])->get('https://openapi.zalo.me/v2.0/oa/getoa');

    // Test 2: send message
    $msgResponse = \Illuminate\Support\Facades\Http::withHeaders([
        'Authorization' => 'OA ' . $token,
    ])->post('https://openapi.zalo.me/v2.0/oa/message', [
        'recipient' => ['user_id' => $notifyUserId],
        'message'   => ['text' => '[OTLMS] Test thông báo - ' . now()->toDateTimeString()],
    ]);

    return response()->json(array_merge($result, [
        'oa_info'     => $oaInfo->json(),
        'zalo_status' => $msgResponse->status(),
        'zalo_body'   => $msgResponse->json(),
    ]));
});

// ── Redirect root ─────────────────────────────────────────────────────────
Route::get('/', fn () => redirect()->route('login'));
Route::get('/set-locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

// ── Auth ──────────────────────────────────────────────────────────────────
Route::get('/login',  [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Admin login shortcut — renders same login view nhưng redirect về admin sau khi đăng nhập
Route::get('/admin/login', [LoginController::class, 'showAdmin'])->name('admin.login')->middleware('guest');

// ── Employee ──────────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:employee'])
    ->prefix('employee')
    ->name('employee.')
    ->group(function () {

        Route::get('/dashboard', [EmployeeDashboard::class, 'index'])->name('dashboard');

        // OT
        Route::get('/ot',                        [OtController::class, 'index']) ->name('ot.index');
        Route::get('/ot/register',               [OtController::class, 'create'])->name('ot.create');
        Route::post('/ot',                       [OtController::class, 'store']) ->name('ot.store');
        Route::post('/ot/{otRequest}/sos',       [OtController::class, 'sos'])   ->name('ot.sos');
        Route::delete('/ot/{otRequest}',         [OtController::class, 'destroy'])->name('ot.destroy');

        // Leave
        Route::get('/leave',                          [LeaveController::class, 'index']) ->name('leave.index');
        Route::get('/leave/register',                 [LeaveController::class, 'create'])->name('leave.create');
        Route::post('/leave',                         [LeaveController::class, 'store']) ->name('leave.store');
        Route::post('/leave/{leaveRequest}/sos',      [LeaveController::class, 'sos'])   ->name('leave.sos');

        // Salary
        Route::get('/salary',                    [SalaryController::class, 'index'])->name('salary.index');
        Route::get('/salary/{month}/{year}',     [SalaryController::class, 'show']) ->name('salary.show');

        // Profile
        Route::get('/profile',           [ProfileController::class, 'show'])          ->name('profile');
        Route::post('/change-password',  [ProfileController::class, 'changePassword'])->name('change-password');

        // Team Approver routes (accessible by employees who are team approvers)
        Route::prefix('team-approvals')->name('team-approvals.')->group(function () {
            Route::get('/', [TeamApprovalController::class, 'index'])->name('index');
            Route::get('ot/{otRequest}', [TeamApprovalController::class, 'showOt'])->name('ot.show');
            Route::post('ot/{otRequest}/approve', [TeamApprovalController::class, 'approveOt'])->name('ot.approve');
            Route::post('ot/{otRequest}/reject', [TeamApprovalController::class, 'rejectOt'])->name('ot.reject');
            Route::post('ot/{otRequest}/sos', [TeamApprovalController::class, 'sosOt'])->name('ot.sos');
            Route::get('leave/{leaveRequest}', [TeamApprovalController::class, 'showLeave'])->name('leave.show');
            Route::post('leave/{leaveRequest}/approve', [TeamApprovalController::class, 'approveLeave'])->name('leave.approve');
            Route::post('leave/{leaveRequest}/reject', [TeamApprovalController::class, 'rejectLeave'])->name('leave.reject');
            Route::post('leave/{leaveRequest}/sos', [TeamApprovalController::class, 'sosLeave'])->name('leave.sos');
        });
    });
