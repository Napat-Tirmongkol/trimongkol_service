<?php

namespace App\Services;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Session;

/**
 * Source of truth for "which workspace is the signed-in user looking
 * at right now?". Stored in session so each tab on this device sees
 * the same workspace.
 */
class CurrentWorkspace
{
    private const SESSION_KEY = 'current_workspace_id';

    public static function get(?User $user = null): ?Workspace
    {
        $user ??= auth()->user();
        if (! $user) {
            return null;
        }

        $id = Session::get(self::SESSION_KEY);
        if ($id) {
            $workspace = Workspace::find($id);
            if ($workspace && $workspace->hasMember($user)) {
                return $workspace;
            }
            // Stale session pointer (kicked out, deleted, etc.) — clear it.
            self::clear();
        }

        // Default to any workspace the user is a member of. Owned workspaces
        // are preferred so new users land in their own space first.
        $owned = $user->ownedWorkspaces()->first();
        if ($owned) {
            self::set($owned);
            return $owned;
        }

        $any = $user->workspaces()->first();
        if ($any) {
            self::set($any);
            return $any;
        }

        return null;
    }

    public static function set(Workspace $workspace): void
    {
        Session::put(self::SESSION_KEY, $workspace->id);
    }

    public static function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }
}
