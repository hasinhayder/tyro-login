<?php

namespace HasinHayder\TyroLogin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tyro-login:install 
                            {--force : Overwrite existing files}
                            {--with-social : Install with social login support (Laravel Socialite)}';

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

        // Ask about social login
        $withSocial = $this->option('with-social') || $this->confirm('Would you like to enable social login (Google, Facebook, GitHub, etc.)?', false);
        
        if ($withSocial) {
            $this->installSocialLogin();
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

        if ($withSocial) {
            $this->info('  Social Login Setup:');
            $this->info('  1. Add provider credentials to config/services.php');
            $this->info('  2. Set TYRO_LOGIN_SOCIAL_ENABLED=true in .env');
            $this->info('  3. Enable desired providers (e.g., TYRO_LOGIN_SOCIAL_GOOGLE=true)');
            $this->info('');
            $this->info('  Example config/services.php entry:');
            $this->info("  'github' => [");
            $this->info("      'client_id' => env('GITHUB_CLIENT_ID'),");
            $this->info("      'client_secret' => env('GITHUB_CLIENT_SECRET'),");
            $this->info("      'redirect' => env('GITHUB_REDIRECT_URI'),");
            $this->info('  ],');
            $this->info('');
        }

        $this->info('  Helpful commands:');
        $this->info('  - tyro-login:publish --emails : Publish email templates');
        $this->info('  - tyro-login:version          : Show version info');
        $this->info('  - tyro-login:doc              : Open documentation');
        $this->info('  - tyro-login:star             : Star on GitHub');
        $this->info('');

        return self::SUCCESS;
    }

    /**
     * Install social login components.
     */
    protected function installSocialLogin(): void
    {
        $this->info('');
        $this->info('Setting up Social Login...');

        // Check if Laravel Socialite is installed
        if (!$this->isSocialiteInstalled()) {
            $this->warn('   Laravel Socialite is not installed.');
            
            if ($this->confirm('   Would you like to install Laravel Socialite now?', true)) {
                $this->info('   Installing Laravel Socialite...');
                
                $result = $this->runComposerRequire('laravel/socialite');
                
                if ($result === 0) {
                    $this->info('   ✓ Laravel Socialite installed successfully');
                } else {
                    $this->error('   ✗ Failed to install Laravel Socialite. Please run: composer require laravel/socialite');
                    return;
                }
            } else {
                $this->warn('   Skipping Socialite installation. Social login requires laravel/socialite.');
                $this->warn('   Run: composer require laravel/socialite');
                return;
            }
        } else {
            $this->info('   ✓ Laravel Socialite is already installed');
        }

        // Publish and run migrations
        $this->info('   Publishing social login migration...');
        $this->callSilently('vendor:publish', [
            '--tag' => 'tyro-login-migrations',
            '--force' => $this->option('force'),
        ]);
        $this->info('   ✓ Migration published');

        if ($this->confirm('   Would you like to run the migration now?', true)) {
            $this->info('   Running migration...');
            $this->call('migrate', ['--force' => $this->option('force')]);
            $this->info('   ✓ Migration completed');
        } else {
            $this->warn('   Remember to run: php artisan migrate');
        }

        // Show available providers
        $this->info('');
        $this->info('   Available social login providers:');
        $this->info('   - Google     (TYRO_LOGIN_SOCIAL_GOOGLE=true)');
        $this->info('   - Facebook   (TYRO_LOGIN_SOCIAL_FACEBOOK=true)');
        $this->info('   - GitHub     (TYRO_LOGIN_SOCIAL_GITHUB=true)');
        $this->info('   - Twitter/X  (TYRO_LOGIN_SOCIAL_TWITTER=true)');
        $this->info('   - LinkedIn   (TYRO_LOGIN_SOCIAL_LINKEDIN=true)');
        $this->info('   - Bitbucket  (TYRO_LOGIN_SOCIAL_BITBUCKET=true)');
        $this->info('   - GitLab     (TYRO_LOGIN_SOCIAL_GITLAB=true)');

        // Add example .env entries
        $this->addEnvExamples();
    }

    /**
     * Check if Laravel Socialite is installed.
     */
    protected function isSocialiteInstalled(): bool
    {
        return class_exists(\Laravel\Socialite\SocialiteServiceProvider::class);
    }

    /**
     * Run composer require command.
     */
    protected function runComposerRequire(string $package): int
    {
        $composer = $this->findComposer();
        
        $process = \Symfony\Component\Process\Process::fromShellCommandline(
            $composer . ' require ' . $package,
            base_path()
        );
        
        $process->setTimeout(300);
        
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        return $process->getExitCode() ?? 1;
    }

    /**
     * Get the composer command for the environment.
     */
    protected function findComposer(): string
    {
        $composerPath = base_path('composer.phar');

        if (file_exists($composerPath)) {
            return '"' . PHP_BINARY . '" ' . $composerPath;
        }

        return 'composer';
    }

    /**
     * Add example .env entries for social login.
     */
    protected function addEnvExamples(): void
    {
        $envPath = base_path('.env');
        
        if (!File::exists($envPath)) {
            return;
        }

        $envContent = File::get($envPath);

        // Check if social login env vars already exist
        if (str_contains($envContent, 'TYRO_LOGIN_SOCIAL_ENABLED')) {
            return;
        }

        $socialEnvExample = <<<'ENV'

# Tyro Login - Social Authentication
# Uncomment and configure the providers you want to use
TYRO_LOGIN_SOCIAL_ENABLED=false

# Google OAuth
# TYRO_LOGIN_SOCIAL_GOOGLE=true
# GOOGLE_CLIENT_ID=
# GOOGLE_CLIENT_SECRET=
# GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"

# Facebook OAuth
# TYRO_LOGIN_SOCIAL_FACEBOOK=true
# FACEBOOK_CLIENT_ID=
# FACEBOOK_CLIENT_SECRET=
# FACEBOOK_REDIRECT_URI="${APP_URL}/auth/facebook/callback"

# GitHub OAuth
# TYRO_LOGIN_SOCIAL_GITHUB=true
# GITHUB_CLIENT_ID=
# GITHUB_CLIENT_SECRET=
# GITHUB_REDIRECT_URI="${APP_URL}/auth/github/callback"

# Twitter/X OAuth
# TYRO_LOGIN_SOCIAL_TWITTER=true
# TWITTER_CLIENT_ID=
# TWITTER_CLIENT_SECRET=
# TWITTER_REDIRECT_URI="${APP_URL}/auth/twitter/callback"

# LinkedIn OAuth
# TYRO_LOGIN_SOCIAL_LINKEDIN=true
# LINKEDIN_CLIENT_ID=
# LINKEDIN_CLIENT_SECRET=
# LINKEDIN_REDIRECT_URI="${APP_URL}/auth/linkedin/callback"

ENV;

        if ($this->confirm('   Would you like to add social login environment variables to .env?', false)) {
            File::append($envPath, $socialEnvExample);
            $this->info('   ✓ Environment variables added to .env');
        }
    }
}
