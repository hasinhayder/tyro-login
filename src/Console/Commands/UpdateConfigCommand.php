<?php

namespace HasinHayder\TyroLogin\Console\Commands;

use Illuminate\Console\Command;

class UpdateConfigCommand extends Command {
    protected $signature = 'tyro-login:update-config {--with-backup : Create backup before publishing}';

    protected $description = 'Update tyro-login config with missing settings from the latest release';

    public function handle(): int {
        $appConfigPath = config_path('tyro-login.php');

        if ($this->option('with-backup')) {
            $backupFilename = 'tyro-login-backup-' . date('Y-m-d-His') . '.txt';
            $backupPath = config_path($backupFilename);

            if (file_exists($appConfigPath)) {
                copy($appConfigPath, $backupPath);
                $this->info("  ✓ Backup created: {$backupFilename}");
            }
        }

        $this->call('vendor:publish', [
            '--tag' => 'tyro-login-config',
            '--force' => true,
        ]);

        return self::SUCCESS;
    }
}
