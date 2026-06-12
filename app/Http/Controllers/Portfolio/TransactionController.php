<?php

namespace App\Http\Controllers\Portfolio;

use App\Http\Controllers\Controller;
use App\Models\Portfolio\Holding;
use App\Models\Portfolio\Transaction;
use App\Services\Portfolio\PriceFetcher;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Holding $holding)
    {
        $this->authorizeOwnership($holding);

        $transactions = $holding->transactions()
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return view('portfolio.transactions.index', compact('holding', 'transactions'));
    }

    public function store(Request $request, Holding $holding, PriceFetcher $prices)
    {
        $this->authorizeOwnership($holding);

        $data = $request->validate([
            'type'             => 'required|in:in,out',
            'amount'           => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'notes'            => 'nullable|string|max:500',
        ]);

        $holding->transactions()->create($data);
        $holding->recalculateFromTransactions($prices);

        return redirect()->route('portfolio.holdings.transactions.index', $holding)
            ->with('status', __('app.portfolio.flash_transaction_created'));
    }

    public function destroy(Holding $holding, Transaction $transaction, PriceFetcher $prices)
    {
        $this->authorizeOwnership($holding);
        abort_unless($transaction->holding_id === $holding->id, 403);

        $transaction->delete();
        $holding->recalculateFromTransactions($prices);

        return redirect()->route('portfolio.holdings.transactions.index', $holding)
            ->with('status', __('app.portfolio.flash_transaction_deleted'));
    }

    private function authorizeOwnership(Holding $holding): void
    {
        abort_unless($holding->user_id === (int) auth()->id(), 403);
    }
}
