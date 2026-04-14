<?php

namespace HasinHayder\TyroLogin\Console\Commands;

use Illuminate\Console\Command;

class UpdateStylesCommand extends Command {
    protected $signature = 'tyro-login:update-style';

    protected $description = 'Update published tyro-login styles with the latest version';

    public function handle(): int {
        $this->call('tyro-login:publish-style', ['--force' => true]);

        return self::SUCCESS;
    }
}