<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * Single entry point at /login (and /register) that asks for email first
 * and routes to the sign-in or sign-up sub-form depending on whether the
 * account already exists. Linear / Notion-style.
 */
class UnifiedAuthController extends Controller
{
    public function show(Request $request)
    {
        $email = (string) $request->query('email', '');
        $step = (string) $request->query('step', 'identify');

        // Step that requires an email but has none → reset to identify.
        if (in_array($step, ['signin', 'signup'], true) && $email === '') {
            return redirect()->route('login');
        }

        // Detect tampering / stale state. If they're on signin but the user
        // doesn't exist (or vice versa), quietly correct.
        if ($email !== '') {
            $userExists = User::where('email', $email)->exists();
            if ($step === 'signin' && ! $userExists) {
                return redirect()->route('login', ['step' => 'signup', 'email' => $email]);
            }
            if ($step === 'signup' && $userExists) {
                return redirect()->route('login', ['step' => 'signin', 'email' => $email]);
            }
        }

        return view('auth.login', compact('email', 'step'));
    }

    public function identify(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|max:160',
        ]);

        $step = User::where('email', $data['email'])->exists() ? 'signin' : 'signup';

        return redirect()->route('login', [
            'step' => $step,
            'email' => $data['email'],
        ]);
    }
}
