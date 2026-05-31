<?php

namespace App\Services;

use App\Models\AdminAction;
use Illuminate\Database\Eloquent\Model;

class AuditLog
{
    /**
     * Record an admin action. `target` is the affected model (User, Classroom, …),
     * `targetLabel` is a human-readable identifier kept around in case the target
     * is later deleted (so the log row still makes sense).
     */
    public static function record(
        string $action,
        ?Model $target = null,
        ?string $targetLabel = null,
        array $metadata = [],
    ): void {
        AdminAction::create([
            'admin_user_id' => auth()->id(),
            'action' => $action,
            'target_type' => $target ? class_basename($target) : null,
            'target_id' => $target?->getKey(),
            'target_label' => $targetLabel,
            'metadata' => $metadata !== [] ? $metadata : null,
            'ip' => request()?->ip(),
        ]);
    }
}
