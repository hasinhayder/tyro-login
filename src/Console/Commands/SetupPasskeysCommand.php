<?php

namespace HasinHayder\TyroLogin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class SetupPasskeysCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tyro-login:setup-passkeys
                            {--force : Overwrite existing files / run migration without confirmation}
                            {--skip-migration : Do not publish or run the passkeys migration}
                            {--skip-dependency : Do not attempt to install laravel/passkeys via composer}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enable passkey (WebAuthn) login for an existing Tyro Login installation';

    /**
     * Execute the console command.
     */
    public function handle(): int {
        $this->info('');
        $this->info('  ╔════════════════════════════════════════╗');
        $this->info('  ║     Tyro Login - Passkey Setup         ║');
        $this->info('  ╚════════════════════════════════════════╝');
        $this->info('');

        $this->installPasskeysPackage();
        $this->publishMigration();
        $this->enablePasskeysInEnv();

        $this->info('');
        $this->info('  Passkey login is almost ready.');
        $this->info('');
        $this->info('  ⚠  One manual step remaining: update your User model.');
        $this->info('     See the "Passkeys (Passwordless WebAuthn) > Manual Setup" section of');
        $this->info('     the README for the exact trait + contract to add, then:');
        $this->info('');
        $this->info('         use Laravel\Passkeys\Contracts\PasskeyUser;');
        $this->info('         use Laravel\Passkeys\PasskeyAuthenticatable;');
        $this->info('');
        $this->info('         class User extends Authenticatable implements PasskeyUser {');
        $this->info('             use PasskeyAuthenticatable;');
        $this->info('         }');
        $this->info('');
        $this->info('  After updating the model:');
        $this->info('  1. Visit /login to see the "Sign in with a passkey" button');
        $this->info('  2. Visit /passkeys-setup (while logged in) to register a passkey');
        $this->info('');
        $this->info('  The browser client (@laravel/passkeys) is auto-loaded from a CDN.');
        $this->info('  To self-host it: npm install @laravel/passkeys, then set TYRO_LOGIN_PASSKEYS_CDN.');
        $this->info('');

        return self::SUCCESS;
    }

    /**
     * Install the laravel/passkeys composer package if it is missing.
     */
    protected function installPasskeysPackage(): void {
        if (class_exists(\Laravel\Passkeys\Passkeys::class)) {
            $this->info('   ✓ laravel/passkeys is already installed');

            return;
        }

        if ($this->option('skip-dependency')) {
            $this->warn('   ⚠ laravel/passkeys is not installed (skipped via --skip-dependency)');

            return;
        }

        if (! $this->confirm('   laravel/passkeys is not installed. Install it now?', true)) {
            $this->warn('   ⚠ Skipping. Passkey login requires laravel/passkeys. Run: composer require laravel/passkeys');

            return;
        }

        $this->info('   Installing laravel/passkeys...');

        $result = $this->runComposerRequire('laravel/passkeys');

        if ($result !== 0) {
            $this->error('   ✗ Failed to install laravel/passkeys. Please run: composer require laravel/passkeys');

            return;
        }

        $this->info('   ✓ laravel/passkeys installed successfully');
    }

    /**
     * Publish and (optionally) run the passkeys migration.
     */
    protected function publishMigration(): void {
        if ($this->option('skip-migration')) {
            return;
        }

        $this->info('   Publishing passkeys migration...');
        $this->callSilently('vendor:publish', [
            '--tag' => 'passkeys-migrations',
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
    }

    /**
     * Enable the passkeys feature in the .env file.
     */
    protected function enablePasskeysInEnv(): void {
        $envPath = base_path('.env');

        if (! File::exists($envPath)) {
            $this->warn('   No .env file found. Set TYRO_LOGIN_PASSKEYS_ENABLED=true manually.');

            return;
        }

        $contents = File::get($envPath);

        if (preg_match('/^\s*TYRO_LOGIN_PASSKEYS_ENABLED\s*=/m', $contents)) {
            $contents = preg_replace(
                '/^\s*TYRO_LOGIN_PASSKEYS_ENABLED\s*=.*/m',
                'TYRO_LOGIN_PASSKEYS_ENABLED=true',
                $contents
            );
        } else {
            $contents = rtrim($contents)."\n\n# Tyro Login - Passkeys\nTYRO_LOGIN_PASSKEYS_ENABLED=true\n";
        }

        File::put($envPath, $contents);
        $this->info('   ✓ TYRO_LOGIN_PASSKEYS_ENABLED=true set in .env');
    }

    /**
     * Run composer require command.
     */
    protected function runComposerRequire(string $package): int {
        $composer = $this->findComposer();

        $process = Process::fromShellCommandline(
            $composer.' require '.$package,
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
    protected function findComposer(): string {
        $composerPath = base_path('composer.phar');

        if (file_exists($composerPath)) {
            return '"'.PHP_BINARY.'" '.$composerPath;
        }

        return 'composer';
    }
}
