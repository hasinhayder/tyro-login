<?php

namespace HasinHayder\TyroLogin\Console\Commands;

use Illuminate\Console\Command;

class UnverifyUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tyro-login:unverify-user 
                            {identifier? : User email address or ID to unverify}
                            {--all : Unverify all verified users}';

    /**
     * The console command description.
     */
    protected $description = 'Remove email verification from user (set email_verified_at to null)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $identifier = $this->argument('identifier');
        $unverifyAll = $this->option('all');

        // Get the user model
        $userModel = config('tyro-login.user_model', 'App\\Models\\User');

        if (!class_exists($userModel)) {
            $this->error("User model '{$userModel}' not found.");
            return self::FAILURE;
        }

        // Unverify all verified users
        if ($unverifyAll) {
            return $this->unverifyAllUsers($userModel);
        }

        // Unverify single user
        if (empty($identifier)) {
            $this->error('Please provide a user email or ID, or use --all to unverify all users.');
            $this->line('');
            $this->line('Usage:');
            $this->line('  php artisan tyro-login:unverify-user john@example.com');
            $this->line('  php artisan tyro-login:unverify-user 123');
            $this->line('  php artisan tyro-login:unverify-user --all');
            return self::FAILURE;
        }

        return $this->unverifySingleUser($userModel, $identifier);
    }

    /**
     * Unverify a single user by email or ID.
     */
    protected function unverifySingleUser(string $userModel, string $identifier): int
    {
        // Find user by email or ID
        $user = $this->findUser($userModel, $identifier);

        if (!$user) {
            $this->error("User not found: {$identifier}");
            return self::FAILURE;
        }

        // Check if already unverified
        if (empty($user->email_verified_at)) {
            $this->warn("User '{$user->email}' is already unverified.");
            return self::SUCCESS;
        }

        // Unverify the user
        $user->email_verified_at = null;
        $user->save();

        $this->info("✓ User '{$user->email}' has been unverified successfully.");

        return self::SUCCESS;
    }

    /**
     * Unverify all verified users.
     */
    protected function unverifyAllUsers(string $userModel): int
    {
        // Count verified users
        $verifiedCount = $userModel::whereNotNull('email_verified_at')->count();

        if ($verifiedCount === 0) {
            $this->info('No verified users found.');
            return self::SUCCESS;
        }

        // Confirm action with warning
        $this->warn("⚠️  WARNING: This will remove email verification from {$verifiedCount} user(s).");
        $this->warn('   Users may need to re-verify their email addresses.');
        
        if (!$this->confirm('Are you sure you want to continue?', false)) {
            $this->info('Operation cancelled.');
            return self::SUCCESS;
        }

        // Unverify all verified users
        $updated = $userModel::whereNotNull('email_verified_at')
            ->update(['email_verified_at' => null]);

        $this->info("✓ Successfully unverified {$updated} user(s).");

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
