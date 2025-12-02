<?php

namespace HasinHayder\TyroLogin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialAccount extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'social_accounts';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'provider',
        'provider_user_id',
        'provider_email',
        'provider_avatar',
        'access_token',
        'refresh_token',
        'token_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'token_expires_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the social account.
     */
    public function user(): BelongsTo
    {
        $userModel = config('tyro-login.user_model', 'App\\Models\\User');
        return $this->belongsTo($userModel);
    }

    /**
     * Find a social account by provider and provider user ID.
     */
    public static function findByProvider(string $provider, string $providerUserId): ?self
    {
        return static::where('provider', $provider)
            ->where('provider_user_id', $providerUserId)
            ->first();
    }
}
