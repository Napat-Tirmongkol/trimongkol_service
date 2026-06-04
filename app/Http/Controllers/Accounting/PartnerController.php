<?php

namespace App\Http\Controllers\Accounting;

use App\Models\Accounting\Bill;
use App\Models\Accounting\Invoice;
use App\Models\Accounting\Partner;
use App\Services\Accounting\AccountingAuditLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PartnerController extends AccountingController
{
    public function index(Request $request)
    {
        $workspace = $this->currentWorkspace();
        $q = trim((string) $request->query('q', ''));
        $type = (string) $request->query('type', '');

        $partners = $workspace
            ? Partner::forWorkspace($workspace)
                ->when($q !== '', fn ($qb) => $qb->where(fn ($w) => $w
                    ->where('name', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%")
                    ->orWhere('tax_id', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")))
                ->when($type === 'customer', fn ($qb) => $qb->where('is_customer', true))
                ->when($type === 'vendor', fn ($qb) => $qb->where('is_vendor', true))
                ->orderBy('name')
                ->paginate(30)->withQueryString()
            : collect();

        return view('accounting.partners.index', compact('workspace', 'partners', 'q', 'type'));
    }

    public function create()
    {
        return view('accounting.partners.form', [
            'partner' => new Partner(['is_customer' => true, 'branch_code' => '00000', 'credit_days' => 0]),
        ]);
    }

    public function store(Request $request)
    {
        $workspace = $this->requireWorkspace();
        $data = $this->validated($request, $workspace);

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

    public function edit(Partner $partner)
    {
        $this->scopedPartner($partner);

        return view('accounting.partners.form', compact('partner'));
    }

    public function update(Request $request, Partner $partner)
    {
        $workspace = $this->scopedPartner($partner);
        $data = $this->validated($request, $workspace, $partner);

        $partner->update([
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

        AccountingAuditLog::record($workspace, 'partner.updated', $partner, $partner->name);

        return redirect()->route('accounting.partners.index')
            ->with('status', __('app.accounting.partner_updated'));
    }

    public function destroy(Partner $partner)
    {
        $workspace = $this->scopedPartner($partner);

        if (Invoice::where('partner_id', $partner->id)->exists() || Bill::where('partner_id', $partner->id)->exists()) {
            return back()->with('error', __('app.accounting.partner_in_use'));
        }

        $label = $partner->name;
        $partner->delete();

        AccountingAuditLog::record($workspace, 'partner.deleted', null, $label);

        return redirect()->route('accounting.partners.index')
            ->with('status', __('app.accounting.partner_deleted'));
    }

    private function scopedPartner(Partner $partner): \App\Models\Workspace
    {
        $workspace = $this->requireWorkspace();
        abort_unless($partner->workspace_id === $workspace->id, 404);

        return $workspace;
    }

    private function validated(Request $request, \App\Models\Workspace $workspace, ?Partner $partner = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:200',
            'code' => ['nullable', 'string', 'max:30',
                Rule::unique('accounting_partners', 'code')
                    ->where('workspace_id', $workspace->id)
                    ->ignore($partner?->id),
            ],
            'tax_id' => 'nullable|string|max:13',
            'branch_code' => 'nullable|string|max:10',
            'credit_days' => 'nullable|integer|min:0|max:365',
            'is_customer' => 'nullable|boolean',
            'is_vendor' => 'nullable|boolean',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:200',
            'address' => 'nullable|string|max:255',
        ]);
    }
}
