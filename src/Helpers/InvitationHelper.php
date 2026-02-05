<?php

namespace HasinHayder\TyroLogin\Helpers;

use HasinHayder\TyroLogin\Models\InvitationLink;
use HasinHayder\TyroLogin\Models\InvitationReferral;

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
        $invitationLink = self::validateInvitationHash($invitationHash);

        // For invalid/non-existing invitation links, silently return null
        // (no error, no referral creation)
        if (!$invitationLink) {
            return null;
        }

        // Don't allow self-referrals
        if ($invitationLink->user_id === $newUserId) {
            return null;
        }

        // Check if this user was already referred (prevent duplicate referrals)
        $existingReferral = InvitationReferral::where('referred_user_id', $newUserId)->first();
        if ($existingReferral) {
            return $existingReferral;
        }

        return InvitationReferral::create([
            'invitation_link_id' => $invitationLink->id,
            'referred_user_id' => $newUserId,
        ]);
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
