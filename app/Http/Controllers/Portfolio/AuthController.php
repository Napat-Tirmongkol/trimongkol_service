<?php

namespace App\Http\Controllers\Portfolio;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

/**
 * Google OAuth sign-in for the personal portfolio. Three steps:
 *  - `showLogin` renders the marketing-ish entry page (one big button).
 *  - `redirect`  bounces to Google's consent screen via Socialite.
 *  - `callback`  trades the code for the user, enforces the allowlist,
 *    finds-or-creates the matching `users` row, signs them in.
 *
 * The allowlist enforcement here is belt-and-braces: even if Google returns
 * a different account, `EnsurePortfolioAccess` middleware will still slam
 * the door — but rejecting in the callback gives a friendlier error.
 */
class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check() && $this->emailAllowed(Auth::user()->email)) {
            return redirect()->route('portfolio.dashboard');
        }

        return view('portfolio.login');
    }

    public function redirect()
    {
        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    public function callback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            return redirect()->route('portfolio.login')
                ->with('error', __('app.portfolio.login.error_oauth'));
        }

        $email = strtolower((string) $googleUser->getEmail());

        if (! $email || ! $this->emailAllowed($email)) {
            return redirect()->route('portfolio.login')
                ->with('error', __('app.portfolio.login.error_not_allowed'));
        }

        $user = User::query()
            ->where('google_id', $googleUser->getId())
            ->orWhere('email', $email)
            ->first();

        if (! $user) {
            $user = new User();
            $user->name = $googleUser->getName() ?: $email;
            $user->email = $email;
            $user->password = bcrypt(Str::random(40)); // never used; we sign in via OAuth
            $user->email_verified_at = now();
        }

        $user->google_id = $googleUser->getId();
        $user->avatar_url = $googleUser->getAvatar();
        $user->last_login_at = now();
        $user->save();

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('portfolio.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portfolio.login');
    }

    private function emailAllowed(?string $email): bool
    {
        if (! $email) {
            return false;
        }

        $allowed = array_map('strtolower', (array) config('portfolio.allowed_emails', []));

        return in_array(strtolower($email), $allowed, true);
    }
}
