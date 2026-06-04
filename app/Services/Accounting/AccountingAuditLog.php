<?php

namespace App\Services\Accounting;

use App\Models\Accounting\ActivityLog;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Model;

class AccountingAuditLog
{
    public static function record(
        Workspace $workspace,
        string $action,
        ?Model $subject = null,
        ?string $subjectLabel = null,
        array $metadata = []
    ): void {
        $user = auth('accounting')->user();

        ActivityLog::create([
            'workspace_id'       => $workspace->id,
            'accounting_user_id' => $user?->id,
            'action'             => $action,
            'subject_type'       => $subject ? class_basename($subject) : null,
            'subject_id'         => $subject?->id,
            'subject_label'      => $subjectLabel,
            'metadata'           => $metadata ?: null,
            'ip'                 => request()->ip(),
            'created_at'         => now(),
        ]);
    }
}
