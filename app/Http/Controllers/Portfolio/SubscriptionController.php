<?php

namespace App\Http\Controllers\Portfolio;

use App\Http\Controllers\Controller;
use App\Models\Portfolio\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SubscriptionController extends Controller
{
    public function index()
    {
        $userId = $this->resolvePortfolioUserId();

        $activeMonth = Subscription::forUser($userId)->max('month')
            ?? Carbon::now()->format('Y-m');

        $subscriptions = Subscription::forUser($userId)
            ->where('month', $activeMonth)
            ->orderByRaw('billing_day IS NULL, billing_day, label')
            ->get();

        $monthlyTotal  = $subscriptions->sum(fn ($s) => (float) $s->monthly_payment);
        $annualTotal   = $monthlyTotal * 12;
        $count         = $subscriptions->count();
        $avgPerService = $count > 0 ? $monthlyTotal / $count : 0.0;

        $byDay       = $subscriptions->filter(fn ($s) => $s->billing_day !== null)
                                     ->groupBy('billing_day')
                                     ->sortKeys();
        $unscheduled = $subscriptions->filter(fn ($s) => $s->billing_day === null)->values();

        return view('portfolio.subscriptions', compact(
            'subscriptions', 'activeMonth', 'monthlyTotal', 'annualTotal',
            'count', 'avgPerService', 'byDay', 'unscheduled'
        ));
    }

    public function store(Request $request)
    {
        $userId = $this->resolvePortfolioUserId();

        $data = $request->validate([
            'month'           => 'required|date_format:Y-m',
            'label'           => 'required|string|max:120',
            'monthly_payment' => 'required|numeric|min:0',
            'billing_day'     => 'nullable|integer|min:1|max:31',
            'notes'           => 'nullable|string|max:500',
        ]);

        $data['user_id']    = $userId;
        $data['is_checked'] = false;

        Subscription::create($data);

        return redirect()->route('portfolio.subscriptions.index')
            ->with('status', 'เพิ่มค่าบริการรายเดือนแล้ว');
    }

    public function update(Request $request, Subscription $subscription)
    {
        abort_unless($subscription->user_id === $this->resolvePortfolioUserId(), 403);

        $data = $request->validate([
            'label'           => 'required|string|max:120',
            'monthly_payment' => 'required|numeric|min:0',
            'billing_day'     => 'nullable|integer|min:1|max:31',
            'notes'           => 'nullable|string|max:500',
        ]);

        $subscription->update($data);

        return redirect()->route('portfolio.subscriptions.index')
            ->with('status', 'อัปเดตค่าบริการรายเดือนแล้ว');
    }

    public function destroy(Subscription $subscription)
    {
        abort_unless($subscription->user_id === $this->resolvePortfolioUserId(), 403);
        $subscription->delete();

        return redirect()->route('portfolio.subscriptions.index')
            ->with('status', 'ลบค่าบริการรายเดือนแล้ว');
    }

    private function resolvePortfolioUserId(): int
    {
        $allowed      = (array) config('portfolio.allowed_emails', []);
        $primaryEmail = !empty($allowed) ? strtolower($allowed[0]) : null;
        if ($primaryEmail) {
            $owner = \App\Models\User::where('email', $primaryEmail)->first();
            if ($owner) return $owner->id;
        }
        return (int) auth()->id();
    }
}
