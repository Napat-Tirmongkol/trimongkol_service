<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Email verification is being switched on (User now implements
     * MustVerifyEmail). Grandfather every existing account as verified so
     * current users aren't locked out behind the new gate. Accounts created
     * from here on go through the normal verify flow.
     */
    public function up(): void
    {
        DB::table('users')->whereNull('email_verified_at')->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        // One-way data backfill — re-nulling would re-lock legitimate users,
        // so there's nothing safe to reverse.
    }
};
