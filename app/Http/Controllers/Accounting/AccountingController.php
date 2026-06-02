<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\Account;
use App\Models\Accounting\Invoice;
use App\Models\Workspace;

abstract class AccountingController extends Controller
{
    protected function currentWorkspace(): ?Workspace
    {
        return auth('accounting')->user()?->workspace;
    }

    protected function requireWorkspace(): Workspace
    {
        $workspace = $this->currentWorkspace();
        abort_unless($workspace, 422, __('app.workspaces.no_workspace'));

        return $workspace;
    }

    protected function scopedInvoice(Invoice $invoice): Workspace
    {
        $workspace = $this->requireWorkspace();
        abort_unless($invoice->workspace_id === $workspace->id, 404);

        return $workspace;
    }

    /** Owners/admins only — the "checker" who can post to the ledger. */
    protected function assertPoster(Workspace $workspace): void
    {
        $user = auth('accounting')->user();
        abort_unless($user && $user->canPost() && $user->workspace_id === $workspace->id, 403, __('app.accounting.no_post_permission'));
    }

    protected function isSetUp(Workspace $workspace): bool
    {
        return Account::forWorkspace($workspace)->exists();
    }
}
