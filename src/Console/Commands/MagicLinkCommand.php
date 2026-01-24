<?php

namespace HasinHayder\TyroLogin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MagicLinkCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tyro-login:magic-links 
                            {--create : Create a new magic link} 
                            {--remove : Remove a magic link (interactively or via --remove-hash)} 
                            {--remove-hash= : The hash to remove} 
                            {--flush : Remove all magic links} 
                            {--list : List all magic links (default)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage magic links for Tyro Login';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!config('tyro-login.features.magic_links_enabled', false)) {
            $this->error('Magic links are currently disabled in the configuration.');
            $this->info('To enable, set TYRO_LOGIN_DISABLE_MAGIC_LINKS=false in your .env file.');
            return;
        }
        if ($this->option('create')) {
            $this->createMagicLink();
        } elseif ($this->option('remove') || $this->option('remove-hash')) {
            $this->removeMagicLink($this->option('remove-hash'));
        } elseif ($this->option('flush')) {
            $this->flushMagicLinks();
        } else {
            $this->listMagicLinks();
        }
    }

    protected function createMagicLink()
    {
        $userId = $this->ask('Enter User ID');
        
        $userModel = config('tyro-login.user_model', 'App\\Models\\User');
        if (!class_exists($userModel) || !$userModel::find($userId)) {
            $this->error("User with ID {$userId} not found.");
            return;
        }

        $validityInput = $this->ask('Link validity (e.g., 7d, 1h, 30m) (default: 7d)', '7d');

        $validity = $this->parseTime($validityInput);
        if (!$validity) {
            $this->error('Invalid time format.');
            return;
        }

        $hash = Str::random(32);
        
        $data = [
            'hash' => $hash,
            'user_id' => $userId,
            'expires_at' => now()->addMinutes($validity)->timestamp,
            'created_at' => now()->timestamp,
            'used' => false,
            'ip' => null,
        ];

        // Store link data
        Cache::put("tyro_magic_link_{$hash}", $data, now()->addMinutes($validity));

        // Update index
        $index = Cache::get('tyro_magic_links_index', []);
        $index[] = $hash;
        Cache::forever('tyro_magic_links_index', array_unique($index));

        $url = url('/mlogin?hash=' . $hash);
        
        $this->info('Magic link created successfully!');
        $this->line("URL: {$url}");
        $this->line("Expires: " . Carbon::createFromTimestamp($data['expires_at'])->toDateTimeString());
    }

    protected function listMagicLinks()
    {
        $index = Cache::get('tyro_magic_links_index', []);
        $rows = [];
        $validHashes = [];

        foreach ($index as $hash) {
            $data = Cache::get("tyro_magic_link_{$hash}");
            if (!$data) {
                continue; // Expired or removed
            }
            
            $validHashes[] = $hash;
            
            $status = $data['used'] ? 'Used' : 'Unused';
            if ($data['used'] && isset($data['ip'])) {
                $status .= " ({$data['ip']})";
            }

            $rows[] = [
                $data['hash'],
                $data['user_id'],
                Carbon::createFromTimestamp($data['created_at'])->toDateTimeString(),
                Carbon::createFromTimestamp($data['expires_at'])->toDateTimeString(),
                $status
            ];
        }

        // Cleanup index if needed
        if (count($index) !== count($validHashes)) {
            Cache::forever('tyro_magic_links_index', $validHashes);
        }

        if (empty($rows)) {
            $this->info('No active magic links found.');
            return;
        }

        $this->table(
            ['Hash', 'User ID', 'Created At', 'Link Expires', 'Status'],
            $rows
        );
    }

    protected function removeMagicLink($hash = null)
    {
        if (!$hash) {
            $hash = $this->ask('Enter the hash of the magic link to remove');
        }

        if (!$hash) {
            $this->error('Hash is required.');
            return;
        }

        $key = "tyro_magic_link_{$hash}";
        if (!Cache::has($key)) {
            $this->error("Magic link with hash {$hash} not found.");
            return;
        }

        Cache::forget($key);
        
        $index = Cache::get('tyro_magic_links_index', []);
        $index = array_diff($index, [$hash]);
        Cache::forever('tyro_magic_links_index', array_values($index));

        $this->info("Magic link {$hash} removed.");
    }

    protected function flushMagicLinks()
    {
        if (!$this->confirm('Are you sure you want to remove ALL magic links?')) {
            return;
        }

        $index = Cache::get('tyro_magic_links_index', []);
        foreach ($index as $hash) {
            Cache::forget("tyro_magic_link_{$hash}");
        }
        
        Cache::forget('tyro_magic_links_index');
        
        $this->info('All magic links flushed.');
    }

    protected function parseTime($time)
    {
        // Simple parser for d, h, m
        if (preg_match('/^(\d+)([dhm])$/', $time, $matches)) {
            $value = (int) $matches[1];
            $unit = $matches[2];
            
            return match ($unit) {
                'd' => $value * 24 * 60,
                'h' => $value * 60,
                'm' => $value,
                default => null,
            };
        }
        return null;
    }
}
