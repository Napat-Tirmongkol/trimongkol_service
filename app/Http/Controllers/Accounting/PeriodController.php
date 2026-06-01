<?php

namespace App\Http\Controllers\Accounting;

use App\Exceptions\Accounting\LedgerException;
use App\Models\Accounting\Period;
use App\Services\Accounting\PeriodClose;
use Illuminate\Http\Request;

class PeriodController extends AccountingController
{
    public function index()
    {
        $workspace = $this->requireWorkspace();

        $periods = Period::query()
            ->forWorkspace($workspace)
            ->orderByDesc('starts_on')
            ->get();

        // Group by fiscal year (Buddhist year from starts_on)
        $byYear = $periods->groupBy(fn ($p) => $p->starts_on->year + 543);

        return view('accounting.periods.index', compact('workspace', 'periods', 'byYear'));
    }

    public function store(Request $request)
    {
        $workspace = $this->requireWorkspace();
        $this->assertPoster($workspace);

        $data = $request->validate([
            'name' => 'required|string|max:40',
            'starts_on' => 'required|date',
            'ends_on' => 'required|date|after:starts_on',
        ]);

        Period::create([
            'workspace_id' => $workspace->id,
            'name' => $data['name'],
            'starts_on' => $data['starts_on'],
            'ends_on' => $data['ends_on'],
            'status' => Period::STATUS_OPEN,
        ]);

        return back()->with('status', __('app.accounting.period_created'));
    }

    public function close(Period $period)
    {
        $workspace = $this->requireWorkspace();
        abort_unless($period->workspace_id === $workspace->id, 404);
        $this->assertPoster($workspace);

        try {
            PeriodClose::close($period, auth()->id());
        } catch (LedgerException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('app.accounting.period_closed'));
    }

    public function reopen(Period $period)
    {
        $workspace = $this->requireWorkspace();
        abort_unless($period->workspace_id === $workspace->id, 404);
        $this->assertPoster($workspace);

        try {
            PeriodClose::reopen($period, auth()->id());
        } catch (LedgerException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('app.accounting.period_reopened'));
    }

    public function yearEndClose(Request $request)
    {
        $workspace = $this->requireWorkspace();
        $this->assertPoster($workspace);

        $data = $request->validate([
            'buddhist_year' => 'required|integer|min:2500|max:2700',
        ]);

        try {
            PeriodClose::yearEndClose($workspace, (int) $data['buddhist_year'], auth()->id());
        } catch (LedgerException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('app.accounting.year_end_closed', ['year' => $data['buddhist_year']]));
    }
}
