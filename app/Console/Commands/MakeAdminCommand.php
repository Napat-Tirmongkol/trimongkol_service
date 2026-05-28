<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeAdminCommand extends Command
{
    protected $signature = 'app:make-admin {email : The email of the user to promote} {--demote : Remove admin instead of granting}';

    protected $description = 'Grant (or revoke) admin access for the user with the given email.';

    public function handle(): int
    {
        $email = trim($this->argument('email'));
        $demote = (bool) $this->option('demote');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("No user with email [{$email}].");
            return self::FAILURE;
        }

        $user->is_admin = ! $demote;
        $user->save();

        $this->info(
            $demote
                ? "Revoked admin from {$user->name} <{$user->email}>."
                : "Granted admin to {$user->name} <{$user->email}>."
        );

        return self::SUCCESS;
    }
}
