<?php

namespace HasinHayder\TyroLogin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvitationReferral extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'invitation_referrals';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'invitation_link_id',
        'referred_user_id',
    ];

    /**
     * Get the invitation link that this referral belongs to.
     */
    public function invitationLink(): BelongsTo
    {
        return $this->belongsTo(InvitationLink::class, 'invitation_link_id');
    }

    /**
     * Get the referred user.
     */
    public function referredUser(): BelongsTo
    {
        $userModel = config('tyro-login.user_model', 'App\\Models\\User');
        return $this->belongsTo($userModel, 'referred_user_id');
    }
}
