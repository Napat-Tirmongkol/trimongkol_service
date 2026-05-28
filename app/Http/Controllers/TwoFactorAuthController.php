<?php

namespace App\Http\Controllers;

use App\Services\AuditLog;
use App\Services\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TwoFactorAuthController extends Controller
{
    /**
     * Begin enrollment: generate a fresh secret and recovery codes.
     * Stored on the user but two_factor_confirmed_at stays null until
     * the user proves they can read codes from their authenticator app.
     */
    public function enable(Request $request)
    {
        $user = $request->user();
        abort_if($user->hasTwoFactorEnabled(), 422, __('app.two_factor.already_enabled'));

        $user->forceFill([
            'two_factor_secret' => Totp::generateSecret(),
            'two_factor_recovery_codes' => Totp::generateRecoveryCodes(),
            'two_factor_confirmed_at' => null,
        ])->save();

        return back();
    }

    /**
     * Verify the first code the user types from their authenticator.
     * Only after a valid code is two-factor enrollment considered complete.
     */
    public function confirm(Request $request)
    {
        $user = $request->user();
        abort_if(! $user->two_factor_secret || $user->two_factor_confirmed_at, 422);

        $data = $request->validate([
            'code' => 'required|string|max:10',
        ]);

        if (! Totp::verify($user->two_factor_secret, $data['code'])) {
            throw ValidationException::withMessages([
                'code' => __('app.two_factor.invalid_code'),
            ]);
        }

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();
        $request->session()->put('auth.two_factor_passed', true);

        AuditLog::record('two_factor.enable', $user, $user->email);

        return back()->with('status', __('app.two_factor.enabled_message'));
    }

    /**
     * Disable 2FA. Requires re-entering the password so a stolen session
     * can't quietly turn protection off.
     */
    public function disable(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'password' => 'required|current_password',
        ]);

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        AuditLog::record('two_factor.disable', $user, $user->email);

        return back()->with('status', __('app.two_factor.disabled_message'));
    }

    public function regenerateRecoveryCodes(Request $request)
    {
        $user = $request->user();
        abort_if(! $user->hasTwoFactorEnabled(), 422);

        $request->validate([
            'password' => 'required|current_password',
        ]);

        $user->forceFill([
            'two_factor_recovery_codes' => Totp::generateRecoveryCodes(),
        ])->save();

        AuditLog::record('two_factor.regenerate_codes', $user, $user->email);

        return back()->with('status', __('app.two_factor.recovery_regenerated'));
    }

    public function challenge(Request $request)
    {
        $user = $request->user();
        abort_if(! $user || ! $user->hasTwoFactorEnabled(), 404);

        if ($request->session()->get('auth.two_factor_passed')) {
            return redirect()->intended(route('dashboard'));
        }

        return view('auth.two-factor-challenge');
    }

    public function verifyChallenge(Request $request)
    {
        $user = $request->user();
        abort_if(! $user || ! $user->hasTwoFactorEnabled(), 404);

        $data = $request->validate([
            'code' => 'required|string|max:20',
        ]);

        $code = trim($data['code']);

        // First try TOTP, then fall back to recovery codes.
        if (Totp::verify($user->two_factor_secret, $code)) {
            $request->session()->put('auth.two_factor_passed', true);
            AuditLog::record('two_factor.challenge_passed', $user, $user->email);

            return redirect()->intended(route('dashboard'));
        }

        $recovery = $user->two_factor_recovery_codes ?? [];
        $idx = array_search(strtolower($code), array_map('strtolower', $recovery), true);
        if ($idx !== false) {
            unset($recovery[$idx]);
            $user->forceFill(['two_factor_recovery_codes' => array_values($recovery)])->save();
            $request->session()->put('auth.two_factor_passed', true);
            AuditLog::record('two_factor.recovery_used', $user, $user->email, [
                'remaining' => count($recovery),
            ]);

            return redirect()->intended(route('dashboard'))
                ->with('status', __('app.two_factor.recovery_used', ['n' => count($recovery)]));
        }

        AuditLog::record('two_factor.challenge_failed', $user, $user->email);

        throw ValidationException::withMessages([
            'code' => __('app.two_factor.invalid_code'),
        ]);
    }
}
