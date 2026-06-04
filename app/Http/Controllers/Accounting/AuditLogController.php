<?php

namespace App\Http\Controllers\Accounting;

use App\Models\Accounting\ActivityLog;

class AuditLogController extends AccountingController
{
    public function index()
    {
        $workspace = $this->requireWorkspace();

        $logs = ActivityLog::forWorkspace($workspace)
            ->with('accountingUser')
            ->latest('created_at')
            ->paginate(50);

        return view('accounting.audit-log.index', compact('workspace', 'logs'));
    }
}
