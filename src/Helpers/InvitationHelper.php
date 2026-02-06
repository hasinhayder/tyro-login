<?php

namespace HasinHayder\TyroLogin\Helpers;

use HasinHayder\TyroLogin\Models\InvitationLink;
use HasinHayder\TyroLogin\Models\InvitationReferral;
use Illuminate\Support\Facades\Log;

class InvitationHelper
{
    /**
     * Validate an invitation hash.
     *
     * @param string|null $hash
     * @return InvitationLink|null
     */
    public static function validateInvitationHash(?string $hash): ?InvitationLink
    {
        if (!$hash) {
            return null;
        }

        return InvitationLink::where('hash', $hash)->first();
    }

    /**
     * Track a referral signup.
     * This should be called after a user successfully registers.
     *
     * @param string|null $invitationHash
     * @param int $newUserId
     * @return InvitationReferral|null
     */
    public static function trackReferral(?string $invitationHash, int $newUserId): ?InvitationReferral
    {
        // If no hash provided, nothing to track
        if (!$invitationHash) {
            Log::info('[Tyro-Login] No invitation hash provided for user registration', [
                'user_id' => $newUserId,
            ]);
            return null;
        }

        $invitationLink = self::validateInvitationHash($invitationHash);

        // For invalid/non-existing invitation links, log and return null
        if (!$invitationLink) {
            Log::warning('[Tyro-Login] Invalid invitation hash used during registration', [
                'hash' => $invitationHash,
                'user_id' => $newUserId,
            ]);
            return null;
        }

        // Don't allow self-referrals
        if ($invitationLink->user_id === $newUserId) {
            Log::warning('[Tyro-Login] Self-referral attempt detected', [
                'user_id' => $newUserId,
                'invitation_hash' => $invitationHash,
            ]);
            return null;
        }

        // Check if this user was already referred (prevent duplicate referrals)
        $existingReferral = InvitationReferral::where('referred_user_id', $newUserId)->first();
        if ($existingReferral) {
            Log::info('[Tyro-Login] User already has a referral record', [
                'user_id' => $newUserId,
                'existing_referral_id' => $existingReferral->id,
            ]);
            return $existingReferral;
        }

        // Create the referral record
        $referral = InvitationReferral::create([
            'invitation_link_id' => $invitationLink->id,
            'referred_user_id' => $newUserId,
        ]);

        Log::info('[Tyro-Login] Referral tracked successfully', [
            'referral_id' => $referral->id,
            'invitation_link_id' => $invitationLink->id,
            'referrer_user_id' => $invitationLink->user_id,
            'referred_user_id' => $newUserId,
            'invitation_hash' => $invitationHash,
        ]);

        return $referral;
    }

    /**
     * Get invitation link for a user.
     *
     * @param int $userId
     * @return InvitationLink|null
     */
    public static function getInvitationLinkForUser(int $userId): ?InvitationLink
    {
        return InvitationLink::where('user_id', $userId)->first();
    }

    /**
     * Get referral count for a user's invitation link.
     *
     * @param int $userId
     * @return int
     */
    public static function getReferralCount(int $userId): int
    {
        $invitationLink = self::getInvitationLinkForUser($userId);

        if (!$invitationLink) {
            return 0;
        }

        return $invitationLink->referrals()->count();
    }

    /**
     * Get all users referred by a specific user.
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getReferredUsers(int $userId)
    {
        $invitationLink = self::getInvitationLinkForUser($userId);

        if (!$invitationLink) {
            return collect([]);
        }

        $userModel = config('tyro-login.user_model', 'App\\Models\\User');
        
        $referredUserIds = $invitationLink->referrals()->pluck('referred_user_id');

        return $userModel::whereIn('id', $referredUserIds)->get();
    }
}
