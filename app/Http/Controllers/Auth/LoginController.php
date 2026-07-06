<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function show()
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user(), 'employee');
        }

        return view('auth.login', ['adminPortal' => false]);
    }

    public function showAdmin()
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user(), 'admin');
        }

        return view('auth.login', ['adminPortal' => true]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|string',
            'password'    => 'required|string',
        ]);

        $credentials = [
            'employee_code' => $request->employee_id,
            'password'      => $request->password,
        ];

        $portal = $request->input('portal', 'employee');

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return $this->redirectByRole(Auth::user(), $portal);
        }

        return back()
            ->withInput($request->only('employee_id'))
            ->withErrors(['employee_id' => 'Mã nhân viên hoặc mật khẩu không đúng.']);
    }

    public function logout(Request $request)
    {
        $portal = $request->input('portal');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($portal === 'admin') {
            return redirect()->route('admin.login');
        }

        return redirect()->route('login');
    }

    private function redirectByRole($user, string $portal = 'employee'): \Illuminate\Http\RedirectResponse
    {
        $hasAdminAccess = in_array($user->role, ['manager', 'hr']) || $user->admin_role_id !== null;

        // Portal admin: chỉ redirect nếu user thực sự có admin access
        if ($portal === 'admin' && $hasAdminAccess) {
            return redirect()->route('admin.dashboard');
        }

        // Portal employee: nếu role là employee (kể cả team approver có admin_role_id)
        if ($user->role === 'employee') {
            return redirect()->route('employee.dashboard');
        }

        // manager/hr không dùng employee portal
        if ($hasAdminAccess) {
            return redirect()->route('admin.dashboard');
        }

        abort(403);
    }
}
