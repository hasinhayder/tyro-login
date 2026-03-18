<?php

namespace HasinHayder\TyroLogin\Console\Commands;

use Illuminate\Console\Command;

class DocCommand extends Command {
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tyro-login:doc';

    /**
     * The console command description.
     */
    protected $description = 'Open Tyro Login documentation in your browser';

    /**
     * The documentation URL.
     */
    protected string $docUrl = 'https://hasinhayder.github.io/tyro-login/doc.html';

    /**
     * Execute the console command.
     */
    public function handle(): int {
        $this->info('');
        $this->info('  📖 Opening Tyro Login documentation...');
        $this->info('');
        $this->info("  URL: <comment>{$this->docUrl}</comment>");
        $this->info('');

        // Detect OS and open browser
        $command = match (PHP_OS_FAMILY) {
            'Darwin' => 'open',
            'Windows' => 'start',
            'Linux' => 'xdg-open',
            default => null,
        };

        if ($command) {
            exec("{$command} {$this->docUrl}");
            $this->info('  ✓ Documentation opened in your default browser.');
        } else {
            $this->warn('  Could not detect your operating system.');
            $this->info('  Please open the URL manually in your browser.');
        }

        $this->info('');

        return self::SUCCESS;
    }
}
