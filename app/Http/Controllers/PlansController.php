<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Services\CurrentWorkspace;

class PlansController extends Controller
{
    public function index()
    {
        $plans = Plan::all();
        $workspace = CurrentWorkspace::get();
        $currentPlanKey = $workspace?->subscription?->effectivePlanKey();
        $subscription = $workspace?->subscription;

        return view('plans.index', compact('plans', 'workspace', 'currentPlanKey', 'subscription'));
    }
}
