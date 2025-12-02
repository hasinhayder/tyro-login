<?php

namespace HasinHayder\TyroLogin\Console\Commands;

use Illuminate\Console\Command;

class StarCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tyro-login:star';

    /**
     * The console command description.
     */
    protected $description = 'Open Tyro Login GitHub repository to give it a star ⭐';

    /**
     * The GitHub repository URL.
     */
    protected string $repoUrl = 'https://github.com/hasinhayder/tyro-login';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('');
        $this->info('  ╔════════════════════════════════════════╗');
        $this->info('  ║                                        ║');
        $this->info('  ║        Star Tyro Login on GitHub!      ║');
        $this->info('  ║                                        ║');
        $this->info('  ╚════════════════════════════════════════╝');
        $this->info('');
        $this->info('  If you like Tyro Login, please consider giving it a star!');
        $this->info('  Your support helps us grow and improve the package.');
        $this->info('');
        $this->info("  URL: <comment>{$this->repoUrl}</comment>");
        $this->info('');

        // Detect OS and open browser
        $command = match (PHP_OS_FAMILY) {
            'Darwin' => 'open',
            'Windows' => 'start',
            'Linux' => 'xdg-open',
            default => null,
        };

        if ($command) {
            exec("{$command} {$this->repoUrl}");
            $this->info('  ✓ GitHub repository opened in your default browser.');
            $this->info('  Don\'t forget to click the star button!');
        } else {
            $this->warn('  Could not detect your operating system.');
            $this->info('  Please open the URL manually in your browser.');
        }

        $this->info('');

        return self::SUCCESS;
    }
}
