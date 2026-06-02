<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\AccountingUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (auth('accounting')->check()) {
            return redirect()->route('accounting.dashboard');
        }

        return view('accounting.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = AccountingUser::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => [__('app.accounting.account_inactive')],
            ]);
        }

        auth('accounting')->login($user, $request->boolean('remember'));

        $request->session()->regenerate();

        return redirect()->intended(route('accounting.dashboard'));
    }

    public function logout(Request $request)
    {
        auth('accounting')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('accounting.login');
    }

    public function showChangePassword()
    {
        return view('accounting.auth.change-password');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required'],
            'password'         => ['required', 'min:8', 'confirmed'],
        ]);

        $user = auth('accounting')->user();

        if (! Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => [__('app.accounting.wrong_current_password')],
            ]);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return back()->with('status', __('app.accounting.password_changed'));
    }
}
