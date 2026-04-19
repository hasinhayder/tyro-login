<?php

namespace HasinHayder\TyroLogin\Console\Commands;

use Illuminate\Console\Command;

class VersionCommand extends Command {
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tyro-login:version';

    /**
     * The console command description.
     */
    protected $description = 'Display the current Tyro Login version';

    /**
     * Execute the console command.
     */
    public function handle(): int {

        $version = '2.6.0'; // Added dark-mode logo support for auth pages
        $this->info('');
        $this->info('  ╔════════════════════════════════════════╗');
        $this->info('  ║                                        ║');
        $this->info('  ║        Tyro Login                      ║');
        $this->info('  ║                                        ║');
        $this->info('  ╚════════════════════════════════════════╝');
        $this->info('');
        $this->info("  Version: <comment>{$version}</comment>");
        $this->info('  Laravel: <comment>'.app()->version().'</comment>');
        $this->info('  PHP: <comment>'.PHP_VERSION.'</comment>');
        $this->info('');
        $this->info('  Documentation: <comment>https://hasinhayder.github.io/tyro-login/doc.html</comment>');
        $this->info('  GitHub: <comment>https://github.com/hasinhayder/tyro-login</comment>');
        $this->info('');

        return self::SUCCESS;
    }
}

// 2.6.0 - Added dark-mode logo support for auth pages
// 2.5.0 - Added tyro-login:update-config and tyro-login:update-style commands
// 2.4.3 - fix(2fa): 2FA checks now properly apply to social login and magic link login flows
// 2.4.2 - Added option to force specific user roles to set up 2FA, allowing administrators to require 2FA for certain roles while giving others the choice to skip it. This enhances security for high-risk accounts while maintaining flexibility for users with lower risk profiles.
// 2.4.1 - Added 2FA ignore option to allow users to skip 2FA setup if they choose, with a cookie-based mechanism to remember their choice for a configurable number of days. This provides more flexibility for users who may not want to set up 2FA immediately while still encouraging them to do so in the future.
// 2.4.0 - Laravel 13 support
// 2.3.4 - fix(tests): all tests are passing now, so updating version to 2.3.4
// 2.3.3 - fix(otp): improvement for OTP code generation by implementing proper type casting config values, issue #5
// 2.3.2 - fix(social): automatically create account if the user doesn't exist after social login
// 2.3.1 - Bug fix release for referral tracking logic
// 2.3.0 - Add complete invitation/referral link management with automatic referral tracking during registration, including CLI commands for managing links and models for data persistence.
// 2.2.1 - Fix migration loading issue
// 2.2.0 - magic link ui
// 2.1.0 - magic link artisan command
