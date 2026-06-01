<?php

namespace App\Models\Concerns;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tenant plumbing for accounting models. This codebase scopes tenants
 * explicitly (like Classroom/Queue) rather than via a hidden global scope,
 * so this trait just gives every model the `workspace()` relation and a
 * `forWorkspace()` local scope — keeping isolation visible at the call site.
 */
trait BelongsToWorkspace
{
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function scopeForWorkspace(Builder $query, Workspace|int $workspace): Builder
    {
        return $query->where(
            $this->qualifyColumn('workspace_id'),
            $workspace instanceof Workspace ? $workspace->id : $workspace,
        );
    }
}
