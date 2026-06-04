<?php

namespace App\Http\Controllers\Accounting;

use App\Exceptions\Accounting\LedgerException;
use App\Models\Accounting\Approval;
use App\Models\Accounting\Bill;
use App\Models\Accounting\Invoice;
use App\Services\Accounting\AccountingAuditLog;
use App\Services\Accounting\Purchasing;
use App\Services\Accounting\SalesInvoicing;
use Illuminate\Http\Request;

class ApprovalController extends AccountingController
{
    public function index()
    {
        $workspace = $this->requireWorkspace();
        $this->assertPoster($workspace);

        $pendingApprovals = Approval::forWorkspace($workspace)
            ->pending()
            ->with('requestedBy')
            ->latest()
            ->get();

        return view('accounting.approvals.index', compact('pendingApprovals'));
    }

    public function request(Request $request, string $type, int $id)
    {
        $workspace = $this->requireWorkspace();

        $subject = match ($type) {
            'invoice' => Invoice::find($id),
            'bill'    => Bill::find($id),
            default   => null,
        };

        abort_unless($subject && $subject->workspace_id === $workspace->id, 404);
        abort_unless($subject->status === 'draft', 422, __('app.accounting.approval_not_draft'));

        $user = auth('accounting')->user();

        // Owners/admins don't need to request approval
        abort_if($user->canPost(), 403);

        // Check no pending approval already exists
        $alreadyPending = Approval::forWorkspace($workspace)
            ->pending()
            ->where('subject_type', class_basename($subject))
            ->where('subject_id', $subject->id)
            ->exists();

        abort_if($alreadyPending, 422, __('app.accounting.approval_already_pending'));

        Approval::create([
            'workspace_id'    => $workspace->id,
            'subject_type'    => class_basename($subject),
            'subject_id'      => $subject->id,
            'subject_label'   => $subject->no,
            'requested_by_id' => $user->id,
            'status'          => Approval::STATUS_PENDING,
        ]);

        AccountingAuditLog::record($workspace, 'approval.requested', $subject, $subject->no);

        return back()->with('status', __('app.accounting.approval_requested'));
    }

    public function approve(Approval $approval)
    {
        $workspace = $this->requireWorkspace();
        $this->assertPoster($workspace);
        abort_unless($approval->workspace_id === $workspace->id, 404);

        $subject = $this->resolveSubject($approval);
        abort_unless($subject, 404);

        $approval->status = Approval::STATUS_APPROVED;
        $approval->reviewed_by_id = auth('accounting')->id();
        $approval->save();

        try {
            if ($subject instanceof Invoice) {
                SalesInvoicing::issue($subject);
            } else {
                Purchasing::post($subject);
            }
        } catch (LedgerException $e) {
            // Roll back approval status on ledger failure
            $approval->status = Approval::STATUS_PENDING;
            $approval->reviewed_by_id = null;
            $approval->save();

            return back()->with('error', $e->getMessage());
        }

        AccountingAuditLog::record($workspace, 'approval.approved', $subject, $subject->no);

        return back()->with('status', __('app.accounting.approval_approved'));
    }

    public function reject(Request $request, Approval $approval)
    {
        $workspace = $this->requireWorkspace();
        $this->assertPoster($workspace);
        abort_unless($approval->workspace_id === $workspace->id, 404);

        $data = $request->validate([
            'note' => 'nullable|string|max:500',
        ]);

        $subject = $this->resolveSubject($approval);

        $approval->status = Approval::STATUS_REJECTED;
        $approval->reviewed_by_id = auth('accounting')->id();
        $approval->note = $data['note'] ?? null;
        $approval->save();

        AccountingAuditLog::record($workspace, 'approval.rejected', $subject, $approval->subject_label);

        return back()->with('status', __('app.accounting.approval_rejected'));
    }

    /** Resolve Invoice or Bill from an approval record. */
    private function resolveSubject(Approval $approval): Invoice|Bill|null
    {
        return match ($approval->subject_type) {
            'Invoice' => Invoice::find($approval->subject_id),
            'Bill'    => Bill::find($approval->subject_id),
            default   => null,
        };
    }
}
