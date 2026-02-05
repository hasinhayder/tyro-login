<?php

namespace HasinHayder\TyroLogin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use HasinHayder\TyroLogin\Models\InvitationLink;
use HasinHayder\TyroLogin\Models\InvitationReferral;

class InviteLinkCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tyro-login:invite-links 
                            {--create : Create a new invitation link} 
                            {--remove : Remove an invitation link} 
                            {--flush : Remove all invitation links} 
                            {--list : List all invitation links (default)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage invitation/referral links for Tyro Login';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hasOptions = $this->option('create') || $this->option('remove') || $this->option('flush') || $this->option('list');
        
        if (!$hasOptions || $this->option('create')) {
            $this->createInviteLink();
        } elseif ($this->option('remove')) {
            $this->removeInviteLink();
        } elseif ($this->option('flush')) {
            $this->flushInviteLinks();
        } else {
            $this->listInviteLinks();
        }
    }

    protected function createInviteLink()
    {
        $userInput = $this->ask('Enter User ID or Email');
        
        $userModel = config('tyro-login.user_model', 'App\\Models\\User');
        
        if (!class_exists($userModel)) {
            $this->error("User model {$userModel} not found.");
            return;
        }

        // Find user by ID or email
        $user = null;
        if (is_numeric($userInput)) {
            $user = $userModel::find($userInput);
        } else {
            $user = $userModel::where('email', $userInput)->first();
        }

        if (!$user) {
            $this->error("User not found.");
            return;
        }

        // Check if user already has an invitation link
        $existingLink = InvitationLink::where('user_id', $user->id)->first();

        if ($existingLink) {
            $referralCount = $existingLink->referrals()->count();
            
            $this->info('User already has an invitation link.');
            $this->line("URL: {$existingLink->url}");
            $this->line("Hash: {$existingLink->hash}");
            $this->line("Created: {$existingLink->created_at}");
            $this->line("Referrals: {$referralCount}");
            return;
        }

        // Create new invitation link
        $hash = Str::random(32);
        
        $invitationLink = InvitationLink::create([
            'user_id' => $user->id,
            'hash' => $hash,
        ]);

        $this->info('Invitation link created successfully!');
        $this->line("URL: {$invitationLink->url}");
        $this->line("Hash: {$hash}");
        $this->line("User ID: {$user->id}");
        $this->line("User Email: {$user->email}");
    }

    protected function listInviteLinks()
    {
        $links = InvitationLink::with('user', 'referrals')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($links->isEmpty()) {
            $this->info('No invitation links found.');
            return;
        }

        $rows = [];

        foreach ($links as $link) {
            $email = $link->user ? $link->user->email : 'N/A';
            $referralCount = $link->referrals->count();

            $rows[] = [
                $link->hash,
                $link->user_id,
                $email,
                $link->created_at,
                $referralCount
            ];
        }

        $this->table(
            ['Hash', 'User ID', 'Email', 'Created At', 'Referrals'],
            $rows
        );
    }

    protected function removeInviteLink()
    {
        $userInput = $this->ask('Enter User ID or Email to remove their invitation link');

        $userModel = config('tyro-login.user_model', 'App\\Models\\User');
        
        if (!class_exists($userModel)) {
            $this->error("User model {$userModel} not found.");
            return;
        }

        // Find user by ID or email
        $user = null;
        if (is_numeric($userInput)) {
            $user = $userModel::find($userInput);
        } else {
            $user = $userModel::where('email', $userInput)->first();
        }

        if (!$user) {
            $this->error("User not found.");
            return;
        }

        $link = InvitationLink::where('user_id', $user->id)->first();

        if (!$link) {
            $this->error("No invitation link found for this user.");
            return;
        }

        // Check for referrals
        $referralCount = $link->referrals()->count();

        if ($referralCount > 0) {
            $this->warn("Warning: This invitation link has {$referralCount} referral signup(s).");
            if (!$this->confirm('Do you still want to remove this link?')) {
                $this->info('Operation cancelled.');
                return;
            }
        }

        $link->delete();

        $this->info("Invitation link for user {$user->email} (ID: {$user->id}) has been removed.");
    }

    protected function flushInviteLinks()
    {
        $totalLinks = InvitationLink::count();
        
        if ($totalLinks === 0) {
            $this->info('No invitation links to flush.');
            return;
        }

        $totalReferrals = InvitationReferral::count();
        
        $this->warn("You are about to remove {$totalLinks} invitation link(s).");
        if ($totalReferrals > 0) {
            $this->warn("This will also affect {$totalReferrals} referral record(s).");
        }
        
        if (!$this->confirm('Are you sure you want to remove ALL invitation links?')) {
            $this->info('Operation cancelled.');
            return;
        }

        InvitationLink::query()->delete();
        
        $this->info('All invitation links have been flushed.');
    }
}
