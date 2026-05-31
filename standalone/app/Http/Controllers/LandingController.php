<?php

namespace App\Http\Controllers;

use App\Models\Plan;

class LandingController extends Controller
{
    /** Public marketing page. Signed-in users go straight to the app. */
    public function index()
    {
        if (auth()->check()) {
            return redirect()->route('accounting.dashboard');
        }

        return view('landing', ['plans' => Plan::all()]);
    }
}
