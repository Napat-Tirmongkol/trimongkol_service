<?php

namespace App\Http\Controllers\Accounting;

use App\Exceptions\Accounting\LedgerException;
use App\Models\Accounting\Account;
use App\Models\Accounting\FixedAsset;
use App\Services\Accounting\AccountingAuditLog;
use App\Services\Accounting\FixedAssets;
use Illuminate\Http\Request;

class FixedAssetController extends AccountingController
{
    public function index()
    {
        $workspace = $this->requireWorkspace();

        $assets = FixedAsset::forWorkspace($workspace)
            ->with('assetAccount', 'accumulatedAccount', 'depreciationExpenseAccount')
            ->orderBy('code')
            ->get()
            ->map(function ($asset) {
                $asset->book_value = FixedAssets::bookValue($asset);
                $asset->accumulated = \App\Services\Accounting\Money::fromMinor(
                    FixedAssets::accumulatedDepreciationMinor($asset)
                );

                return $asset;
            });

        return view('accounting.fixed-assets.index', compact('workspace', 'assets'));
    }

    public function create()
    {
        $workspace = $this->requireWorkspace();

        $assetAccounts = Account::forWorkspace($workspace)
            ->where('type', 'asset')->where('is_active', true)->orderBy('code')->get();
        $expenseAccounts = Account::forWorkspace($workspace)
            ->where('type', 'expense')->where('is_active', true)->orderBy('code')->get();

        return view('accounting.fixed-assets.create', compact('workspace', 'assetAccounts', 'expenseAccounts'));
    }

    public function store(Request $request)
    {
        $workspace = $this->requireWorkspace();
        $this->assertPoster($workspace);

        $data = $request->validate([
            'code' => 'required|string|max:40',
            'name' => 'required|string|max:120',
            'purchase_date' => 'required|date',
            'cost' => 'required|numeric|min:0',
            'salvage_value' => 'nullable|numeric|min:0',
            'useful_life_months' => 'required|integer|min:1',
            'asset_account_id' => 'required|integer',
            'accumulated_account_id' => 'required|integer',
            'depreciation_expense_account_id' => 'required|integer',
        ]);

        try {
            FixedAssets::register($workspace, $data);
        } catch (LedgerException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        AccountingAuditLog::record($workspace, 'asset.registered', null, "{$data['code']} {$data['name']}");

        return redirect()->route('accounting.fixed-assets.index')
            ->with('status', __('app.accounting.asset_registered'));
    }

    public function show(FixedAsset $fixedAsset)
    {
        $workspace = $this->requireWorkspace();
        abort_unless($fixedAsset->workspace_id === $workspace->id, 404);
        $fixedAsset->load('assetAccount', 'accumulatedAccount', 'depreciationExpenseAccount');

        $bookValue = FixedAssets::bookValue($fixedAsset);
        $accumulated = \App\Services\Accounting\Money::fromMinor(
            FixedAssets::accumulatedDepreciationMinor($fixedAsset)
        );
        $monthlyAmount = \App\Services\Accounting\Money::fromMinor(
            FixedAssets::monthlyDepreciationMinor($fixedAsset)
        );

        // Depreciation history
        $journals = \App\Models\Accounting\Journal::forWorkspace($workspace)
            ->where('type', 'depreciation')
            ->where('source_type', $fixedAsset->getMorphClass())
            ->where('source_id', $fixedAsset->id)
            ->where('status', 'posted')
            ->orderByDesc('date')
            ->get();

        return view('accounting.fixed-assets.show', compact(
            'workspace', 'fixedAsset', 'bookValue', 'accumulated', 'monthlyAmount', 'journals'
        ));
    }

    public function depreciate(Request $request, FixedAsset $fixedAsset)
    {
        $workspace = $this->requireWorkspace();
        abort_unless($fixedAsset->workspace_id === $workspace->id, 404);
        $this->assertPoster($workspace);

        $data = $request->validate([
            'for_month' => 'required|date_format:Y-m',
        ]);

        try {
            FixedAssets::depreciate($fixedAsset, $data['for_month']);
        } catch (LedgerException $e) {
            return back()->with('error', $e->getMessage());
        }

        AccountingAuditLog::record($workspace, 'asset.depreciated', $fixedAsset,
            "{$fixedAsset->code} {$fixedAsset->name}", ['for_month' => $data['for_month']]);

        return back()->with('status', __('app.accounting.depreciation_posted'));
    }

    public function dispose(Request $request, FixedAsset $fixedAsset)
    {
        $workspace = $this->requireWorkspace();
        abort_unless($fixedAsset->workspace_id === $workspace->id, 404);
        $this->assertPoster($workspace);

        $data = $request->validate([
            'disposed_on' => 'required|date',
        ]);

        try {
            FixedAssets::dispose($fixedAsset, $data['disposed_on']);
        } catch (LedgerException $e) {
            return back()->with('error', $e->getMessage());
        }

        AccountingAuditLog::record($workspace, 'asset.disposed', $fixedAsset,
            "{$fixedAsset->code} {$fixedAsset->name}", ['disposed_on' => $data['disposed_on']]);

        return back()->with('status', __('app.accounting.asset_disposed'));
    }
}
