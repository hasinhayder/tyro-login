<?php

namespace HasinHayder\TyroLogin\Console\Commands;

use Illuminate\Console\Command;

class ResetTwoFactorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tyro-login:reset-2fa {user : The ID or email of the user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset two-factor authentication for a user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $identifier = $this->argument('user');
        $userModel = config('tyro-login.user_model', 'App\\Models\\User');

        $user = $userModel::where('email', $identifier)
            ->orWhere('id', $identifier)
            ->first();

        if (!$user) {
            $this->error("User not found with ID or email: {$identifier}");
            return 1;
        }

        if (!$user->two_factor_confirmed_at && !$user->two_factor_secret) {
             $this->info("Two-factor authentication is not enabled for this user.");
             return 0;
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $this->info("Two-factor authentication has been reset for user: {$user->email}");
        
        return 0;
    }
}
