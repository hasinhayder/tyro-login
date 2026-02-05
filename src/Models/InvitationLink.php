<?php

namespace HasinHayder\TyroLogin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvitationLink extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'invitation_links';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'hash',
    ];

    /**
     * Get the user that owns the invitation link.
     */
    public function user(): BelongsTo
    {
        $userModel = config('tyro-login.user_model', 'App\\Models\\User');
        return $this->belongsTo($userModel, 'user_id');
    }

    /**
     * Get the referrals for the invitation link.
     */
    public function referrals(): HasMany
    {
        return $this->hasMany(InvitationReferral::class, 'invitation_link_id');
    }

    /**
     * Get the invitation URL.
     */
    public function getUrlAttribute(): string
    {
        return url('/register?invite=' . $this->hash);
    }

    /**
     * Get the referral count.
     */
    public function getReferralCountAttribute(): int
    {
        return $this->referrals()->count();
    }
}
