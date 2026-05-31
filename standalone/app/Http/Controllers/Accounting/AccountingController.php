<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\Account;
use App\Models\Accounting\Invoice;
use App\Models\Workspace;
use App\Services\CurrentWorkspace;

/**
 * Shared plumbing for the tenant-facing accounting screens: resolve the
 * current workspace and enforce the maker-checker split — any member can
 * draft documents, but only owners/admins may post to the ledger.
 */
abstract class AccountingController extends Controller
{
    protected function currentWorkspace(): ?Workspace
    {
        return CurrentWorkspace::get();
    }

    protected function requireWorkspace(): Workspace
    {
        $workspace = CurrentWorkspace::get();
        abort_unless($workspace, 422, __('app.workspaces.no_workspace'));

        return $workspace;
    }

    /** Resolve the workspace and confirm the invoice belongs to it (blocks id guessing). */
    protected function scopedInvoice(Invoice $invoice): Workspace
    {
        $workspace = $this->requireWorkspace();
        abort_unless($invoice->workspace_id === $workspace->id, 404);

        return $workspace;
    }

    /** Owners/admins only — the "checker" who can post to the ledger. */
    protected function assertPoster(Workspace $workspace): void
    {
        abort_unless(
            in_array($workspace->roleFor(auth()->user()), ['owner', 'admin'], true),
            403,
            __('app.accounting.no_post_permission'),
        );
    }

    protected function isSetUp(Workspace $workspace): bool
    {
        return Account::forWorkspace($workspace)->exists();
    }
}
