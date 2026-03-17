<?php

namespace HasinHayder\TyroLogin\Console\Commands;

use Illuminate\Console\Command;

class VersionCommand extends Command
{
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
    public function handle(): int
    {

        $version = '2.4.0'; //Laravel 13 support
        $this->info('');
        $this->info('  ╔════════════════════════════════════════╗');
        $this->info('  ║                                        ║');
        $this->info('  ║        Tyro Login                      ║');
        $this->info('  ║                                        ║');
        $this->info('  ╚════════════════════════════════════════╝');
        $this->info('');
        $this->info("  Version: <comment>{$version}</comment>");
        $this->info('  Laravel: <comment>' . app()->version() . '</comment>');
        $this->info('  PHP: <comment>' . PHP_VERSION . '</comment>');
        $this->info('');
        $this->info('  Documentation: <comment>https://hasinhayder.github.io/tyro-login/doc.html</comment>');
        $this->info('  GitHub: <comment>https://github.com/hasinhayder/tyro-login</comment>');
        $this->info('');

        return self::SUCCESS;
    }
}

//2.4.0 - Laravel 13 support
//2.3.4 - fix(tests): all tests are passing now, so updating version to 2.3.4
//2.3.3 - fix(otp): improvement for OTP code generation by implementing proper type casting config values, issue #5
//2.3.2 - fix(social): automatically create account if the user doesn't exist after social login
//2.3.1 - Bug fix release for referral tracking logic
//2.3.0 - Add complete invitation/referral link management with automatic referral tracking during registration, including CLI commands for managing links and models for data persistence.
//2.2.1 - Fix migration loading issue
//2.2.0 - magic link ui
//2.1.0 - magic link artisan command