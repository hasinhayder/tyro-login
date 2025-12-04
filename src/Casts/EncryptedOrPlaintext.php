<?php

namespace HasinHayder\TyroLogin\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class EncryptedOrPlaintext implements CastsAttributes {
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string {
        if ($value === null) {
            return null;
        }

        // Try to decrypt - if it fails, assume it's plaintext from before encryption was added
        try {
            return Crypt::decryptString($value);
        } catch (DecryptException $e) {
            // Value is not encrypted (legacy plaintext data), return as-is
            // It will be encrypted on next save
            return $value;
        }
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string {
        if ($value === null) {
            return null;
        }

        // Always encrypt when setting
        return Crypt::encryptString($value);
    }
}
