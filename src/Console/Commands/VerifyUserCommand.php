<?php

namespace HasinHayder\TyroLogin\Console\Commands;

use Illuminate\Console\Command;

class VerifyUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tyro-login:verify-user 
                            {identifier? : User email address or ID to verify}
                            {--all : Verify all unverified users}';

    /**
     * The console command description.
     */
    protected $description = 'Mark user email as verified';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $identifier = $this->argument('identifier');
        $verifyAll = $this->option('all');

        // Get the user model
        $userModel = config('tyro-login.user_model', 'App\\Models\\User');

        if (!class_exists($userModel)) {
            $this->error("User model '{$userModel}' not found.");
            return self::FAILURE;
        }

        // Verify all unverified users
        if ($verifyAll) {
            return $this->verifyAllUsers($userModel);
        }

        // Verify single user
        if (empty($identifier)) {
            $this->error('Please provide a user email or ID, or use --all to verify all users.');
            $this->line('');
            $this->line('Usage:');
            $this->line('  php artisan tyro-login:verify-user john@example.com');
            $this->line('  php artisan tyro-login:verify-user 123');
            $this->line('  php artisan tyro-login:verify-user --all');
            return self::FAILURE;
        }

        return $this->verifySingleUser($userModel, $identifier);
    }

    /**
     * Verify a single user by email or ID.
     */
    protected function verifySingleUser(string $userModel, string $identifier): int
    {
        // Find user by email or ID
        $user = $this->findUser($userModel, $identifier);

        if (!$user) {
            $this->error("User not found: {$identifier}");
            return self::FAILURE;
        }

        // Check if already verified
        if (!empty($user->email_verified_at)) {
            $this->warn("User '{$user->email}' is already verified (verified at: {$user->email_verified_at})");
            return self::SUCCESS;
        }

        // Verify the user
        $user->email_verified_at = now();
        $user->save();

        $this->info("✓ User '{$user->email}' has been verified successfully.");

        return self::SUCCESS;
    }

    /**
     * Verify all unverified users.
     */
    protected function verifyAllUsers(string $userModel): int
    {
        // Count unverified users
        $unverifiedCount = $userModel::whereNull('email_verified_at')->count();

        if ($unverifiedCount === 0) {
            $this->info('All users are already verified.');
            return self::SUCCESS;
        }

        // Confirm action
        if (!$this->confirm("This will verify {$unverifiedCount} unverified user(s). Continue?", false)) {
            $this->info('Operation cancelled.');
            return self::SUCCESS;
        }

        // Verify all unverified users
        $updated = $userModel::whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);

        $this->info("✓ Successfully verified {$updated} user(s).");

        return self::SUCCESS;
    }

    /**
     * Find a user by email or ID.
     */
    protected function findUser(string $userModel, string $identifier): mixed
    {
        // Check if identifier is numeric (ID)
        if (is_numeric($identifier)) {
            return $userModel::find($identifier);
        }

        // Otherwise, treat as email
        return $userModel::where('email', $identifier)->first();
    }
}
