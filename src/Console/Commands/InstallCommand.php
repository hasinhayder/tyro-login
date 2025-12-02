<?php

namespace HasinHayder\TyroLogin\Console\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tyro-login:install 
                            {--force : Overwrite existing files}';

    /**
     * The console command description.
     */
    protected $description = 'Install Tyro Login package resources';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('');
        $this->info('  ╔════════════════════════════════════════╗');
        $this->info('  ║                                        ║');
        $this->info('  ║     Tyro Login Installation            ║');
        $this->info('  ║                                        ║');
        $this->info('  ╚════════════════════════════════════════╝');
        $this->info('');

        // Publish config
        $this->info('Publishing configuration...');
        $this->callSilently('vendor:publish', [
            '--tag' => 'tyro-login-config',
            '--force' => $this->option('force'),
        ]);
        $this->info('   ✓ Configuration published to config/tyro-login.php');

        // Ask about views
        if ($this->confirm('Would you like to publish the views for customization?', false)) {
            $this->info('Publishing views...');
            $this->callSilently('vendor:publish', [
                '--tag' => 'tyro-login-views',
                '--force' => $this->option('force'),
            ]);
            $this->info('   ✓ Views published to resources/views/vendor/tyro-login/');
        }

        // Ask about email templates
        if ($this->confirm('Would you like to publish the email templates for customization?', false)) {
            $this->info('Publishing email templates...');
            $this->callSilently('vendor:publish', [
                '--tag' => 'tyro-login-emails',
                '--force' => $this->option('force'),
            ]);
            $this->info('   ✓ Email templates published to resources/views/vendor/tyro-login/emails/');
        }

        $this->info('');
        $this->info('  Tyro Login installed successfully!');
        $this->info('');
        $this->info('  Next steps:');
        $this->info('  1. Review config/tyro-login.php for customization options');
        $this->info('  2. Visit /login to see your new login page');
        $this->info('  3. Visit /register to see the registration page');
        $this->info('');
        $this->info('  Available layouts:');
        $this->info('  - centered     : Form in the center of the page');
        $this->info('  - split-left   : Background on left, form on right');
        $this->info('  - split-right  : Form on left, background on right');
        $this->info('');
        $this->info('  Email templates (4 included):');
        $this->info('  - OTP verification email');
        $this->info('  - Password reset email');
        $this->info('  - Email verification email');
        $this->info('  - Welcome email');
        $this->info('');
        $this->info('  Helpful commands:');
        $this->info('  - tyro-login:publish --emails : Publish email templates');
        $this->info('  - tyro-login:version          : Show version info');
        $this->info('  - tyro-login:doc              : Open documentation');
        $this->info('  - tyro-login:star             : Star on GitHub');
        $this->info('');

        return self::SUCCESS;
    }
}
