<?php

namespace App\Http\Controllers\Accounting;

use App\Exceptions\Accounting\LedgerException;
use App\Models\Accounting\Account;
use App\Models\Accounting\Product;
use App\Services\Accounting\Inventory;
use Illuminate\Http\Request;

class ProductController extends AccountingController
{
    public function index()
    {
        $workspace = $this->requireWorkspace();

        $products = Product::forWorkspace($workspace)
            ->with('inventoryAccount', 'cogsAccount')
            ->orderBy('sku')
            ->get();

        $totalValue = Inventory::totalValue($workspace);

        return view('accounting.products.index', compact('workspace', 'products', 'totalValue'));
    }

    public function create()
    {
        $workspace = $this->requireWorkspace();

        $assetAccounts = Account::forWorkspace($workspace)->where('type', 'asset')->where('is_active', true)->orderBy('code')->get();
        $expenseAccounts = Account::forWorkspace($workspace)->where('type', 'expense')->where('is_active', true)->orderBy('code')->get();

        return view('accounting.products.create', compact('workspace', 'assetAccounts', 'expenseAccounts'));
    }

    public function store(Request $request)
    {
        $workspace = $this->requireWorkspace();
        $this->assertPoster($workspace);

        $data = $request->validate([
            'sku' => 'required|string|max:40',
            'name' => 'required|string|max:160',
            'unit' => 'required|string|max:20',
            'inventory_account_id' => 'required|integer',
            'cogs_account_id' => 'required|integer',
        ]);

        try {
            Inventory::register($workspace, $data);
        } catch (LedgerException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('accounting.products.index')
            ->with('status', __('app.accounting.product_registered'));
    }

    public function show(Product $product)
    {
        $workspace = $this->requireWorkspace();
        abort_unless($product->workspace_id === $workspace->id, 404);

        $product->load(['movements' => fn ($q) => $q->limit(50)]);

        $bankAccounts = Account::forWorkspace($workspace)
            ->whereIn('type', ['asset', 'liability'])->where('is_active', true)->orderBy('code')->get();
        $expenseAccounts = Account::forWorkspace($workspace)
            ->where('type', 'expense')->where('is_active', true)->orderBy('code')->get();

        return view('accounting.products.show', compact('workspace', 'product', 'bankAccounts', 'expenseAccounts'));
    }

    public function receive(Request $request, Product $product)
    {
        $workspace = $this->requireWorkspace();
        abort_unless($product->workspace_id === $workspace->id, 404);
        $this->assertPoster($workspace);

        $data = $request->validate([
            'movement_date' => 'required|date',
            'quantity' => 'required|numeric|min:0.001',
            'unit_cost' => 'required|numeric|min:0',
            'clearing_account_id' => 'required|integer',
            'reference' => 'nullable|string|max:120',
            'note' => 'nullable|string|max:255',
        ]);

        try {
            Inventory::receive($product, $data, (int) $data['clearing_account_id']);
        } catch (LedgerException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('app.accounting.stock_received'));
    }

    public function issue(Request $request, Product $product)
    {
        $workspace = $this->requireWorkspace();
        abort_unless($product->workspace_id === $workspace->id, 404);
        $this->assertPoster($workspace);

        $data = $request->validate([
            'movement_date' => 'required|date',
            'quantity' => 'required|numeric|min:0.001',
            'reference' => 'nullable|string|max:120',
            'note' => 'nullable|string|max:255',
        ]);

        try {
            Inventory::issue($product, $data);
        } catch (LedgerException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('app.accounting.stock_issued'));
    }

    public function adjust(Request $request, Product $product)
    {
        $workspace = $this->requireWorkspace();
        abort_unless($product->workspace_id === $workspace->id, 404);
        $this->assertPoster($workspace);

        $data = $request->validate([
            'movement_date' => 'required|date',
            'delta_quantity' => 'required|numeric',
            'adjustment_account_id' => 'required|integer',
            'reference' => 'nullable|string|max:120',
            'note' => 'nullable|string|max:255',
        ]);

        try {
            Inventory::adjust($product, $data, (int) $data['adjustment_account_id']);
        } catch (LedgerException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('app.accounting.stock_adjusted'));
    }
}
