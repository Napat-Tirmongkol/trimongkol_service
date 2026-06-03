<?php

namespace App\Http\Controllers\Accounting;

use App\Models\Accounting\Partner;
use App\Services\Accounting\AccountingAuditLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PartnerController extends AccountingController
{
    public function index()
    {
        $workspace = $this->currentWorkspace();
        $partners = $workspace
            ? Partner::forWorkspace($workspace)->orderBy('name')->paginate(30)
            : collect();

        return view('accounting.partners.index', compact('workspace', 'partners'));
    }

    public function create()
    {
        return view('accounting.partners.create');
    }

    public function store(Request $request)
    {
        $workspace = $this->requireWorkspace();

        $data = $request->validate([
            'name' => 'required|string|max:200',
            'code' => ['nullable', 'string', 'max:30', Rule::unique('accounting_partners', 'code')->where('workspace_id', $workspace->id)],
            'tax_id' => 'nullable|string|max:13',
            'branch_code' => 'nullable|string|max:10',
            'credit_days' => 'nullable|integer|min:0|max:365',
            'is_customer' => 'nullable|boolean',
            'is_vendor' => 'nullable|boolean',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:200',
            'address' => 'nullable|string|max:255',
        ]);

        Partner::create([
            'workspace_id' => $workspace->id,
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'tax_id' => $data['tax_id'] ?? null,
            'branch_code' => ($data['branch_code'] ?? '') ?: '00000',
            'credit_days' => $data['credit_days'] ?? 0,
            'is_customer' => $request->boolean('is_customer'),
            'is_vendor' => $request->boolean('is_vendor'),
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
        ]);

        AccountingAuditLog::record($workspace, 'partner.created', null, $data['name']);

        return redirect()->route('accounting.partners.index')
            ->with('status', __('app.accounting.partner_saved'));
    }
}
