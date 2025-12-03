<?php

namespace HasinHayder\TyroLogin\Console\Commands;

use Illuminate\Console\Command;

class PublishStyleCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tyro-login:publish-style 
                            {--force : Overwrite existing files}
                            {--theme-only : Publish only the shadcn theme file}';

    /**
     * The console command description.
     */
    protected $description = 'Publish Tyro Login styles to customize shadcn variables';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('');
        $this->info('Publishing Tyro Login styles...');
        
        if ($this->option('theme-only')) {
            $this->callSilently('vendor:publish', [
                '--tag' => 'tyro-login-theme',
                '--force' => $this->option('force'),
            ]);
            
            $this->info('   ✓ Theme published to resources/views/vendor/tyro-login/partials/shadcn-theme.blade.php');
        } else {
            $this->callSilently('vendor:publish', [
                '--tag' => 'tyro-login-styles',
                '--force' => $this->option('force'),
            ]);
            
            $this->info('   ✓ Styles published to resources/views/vendor/tyro-login/partials/');
            $this->info('     - shadcn-theme.blade.php (theme variables)');
            $this->info('     - styles.blade.php (component styles)');
        }
        
        $this->info('');
        $this->info('You can now customize the shadcn theme variables in:');
        $this->info('   resources/views/vendor/tyro-login/partials/shadcn-theme.blade.php');
        $this->info('');
        $this->info('Tip: Use --theme-only to publish just the theme file for quick customization.');
        $this->info('');

        return self::SUCCESS;
    }
}
