<?php

namespace App\Http\Controllers\Accounting;

use App\Models\Accounting\Period;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\Accounting\TaxCodes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * First-run setup wizard. A new workspace lands here to enter its company
 * profile (printed on documents) and pick a chart-of-accounts template; on
 * submit we seed the chart, tax codes and the current accounting period, then
 * stamp onboarded_at so the wizard never shows again.
 */
class OnboardingController extends AccountingController
{
    public function show()
    {
        $workspace = $this->requireWorkspace();

        // Already set up? Nothing to onboard — go to the dashboard.
        if ($workspace->isOnboarded() || $this->isSetUp($workspace)) {
            return redirect()->route('accounting.dashboard');
        }

        return view('accounting.onboarding', [
            'workspace' => $workspace,
        ]);
    }

    public function store(Request $request)
    {
        $workspace = $this->requireWorkspace();
        $this->assertPoster($workspace);

        if ($workspace->isOnboarded() || $this->isSetUp($workspace)) {
            return redirect()->route('accounting.dashboard');
        }

        $data = $request->validate([
            'business_type'   => ['required', 'in:company,individual'],
            'company_name'    => ['required', 'string', 'max:255'],
            'tax_id'          => ['nullable', 'string', 'max:13'],
            'branch'          => ['nullable', 'string', 'max:30'],
            'phone'           => ['nullable', 'string', 'max:30'],
            'company_address' => ['nullable', 'string', 'max:500'],
            'is_vat'          => ['boolean'],
        ]);

        DB::transaction(function () use ($workspace, $data) {
            ChartOfAccounts::seed($workspace, ChartOfAccounts::template());
            TaxCodes::seedDefault($workspace);

            $year = now()->year;
            Period::firstOrCreate(
                ['workspace_id' => $workspace->id, 'starts_on' => "{$year}-01-01"],
                ['name' => (string) ($year + 543), 'ends_on' => "{$year}-12-31", 'status' => Period::STATUS_OPEN],
            );

            $workspace->update([
                'company_name'    => $data['company_name'],
                'tax_id'          => $data['tax_id'] ?? null,
                'branch'          => $data['branch'] ?: 'สำนักงานใหญ่',
                'phone'           => $data['phone'] ?? null,
                'company_address' => $data['company_address'] ?? null,
                'chart_template'  => 'standard_th',
                'onboarded_at'    => now(),
            ]);
        });

        return redirect()->route('accounting.dashboard')->with('status', __('app.accounting.onboarding_done'));
    }
}
