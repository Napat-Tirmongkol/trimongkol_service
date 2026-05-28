<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * For every existing user, create a workspace they own and reassign
     * all of their existing classrooms to it. After this migration runs
     * every classroom has a non-null workspace_id, so a follow-up can
     * tighten that constraint to NOT NULL.
     */
    public function up(): void
    {
        $users = DB::table('users')->select('id', 'name', 'email')->get();
        $now = now();
        $usedSlugs = DB::table('workspaces')->pluck('slug')->all();

        foreach ($users as $user) {
            // If this user already owns a workspace (e.g. partial re-run),
            // skip creating another one.
            $existingMembership = DB::table('workspace_members')
                ->where('user_id', $user->id)
                ->where('role', 'owner')
                ->first();

            if ($existingMembership) {
                $workspaceId = $existingMembership->workspace_id;
            } else {
                $base = Str::slug($user->name ?: explode('@', $user->email)[0]) ?: 'workspace';
                $slug = $base;
                $i = 1;
                while (in_array($slug, $usedSlugs, true)) {
                    $slug = $base . '-' . (++$i);
                }
                $usedSlugs[] = $slug;

                $workspaceId = DB::table('workspaces')->insertGetId([
                    'name' => ($user->name ?: explode('@', $user->email)[0]) . "'s workspace",
                    'slug' => $slug,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('workspace_members')->insert([
                    'workspace_id' => $workspaceId,
                    'user_id' => $user->id,
                    'role' => 'owner',
                    'joined_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // Reassign all of this user's classrooms to their new workspace.
            DB::table('classrooms')
                ->where('user_id', $user->id)
                ->whereNull('workspace_id')
                ->update(['workspace_id' => $workspaceId]);
        }
    }

    public function down(): void
    {
        // Reversal is destructive — wipe the new state so the schema
        // migrations above can roll back cleanly.
        DB::table('classrooms')->update(['workspace_id' => null]);
        DB::table('workspace_members')->delete();
        DB::table('workspaces')->delete();
    }
};
