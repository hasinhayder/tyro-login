<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider'); // google, facebook, github, twitter, linkedin, etc.
            $table->string('provider_user_id'); // User ID from the social provider
            $table->string('provider_email')->nullable(); // Email from the social provider
            $table->string('provider_avatar')->nullable(); // Avatar URL from the provider
            $table->text('access_token')->nullable(); // OAuth access token
            $table->text('refresh_token')->nullable(); // OAuth refresh token (if available)
            $table->timestamp('token_expires_at')->nullable(); // Token expiration time
            $table->timestamps();

            // Unique constraint: one provider account per user
            $table->unique(['provider', 'provider_user_id']);
            
            // Index for faster lookups
            $table->index(['provider', 'provider_user_id']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
