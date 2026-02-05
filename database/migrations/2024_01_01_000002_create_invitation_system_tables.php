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
        Schema::create('invitation_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('hash', 32)->unique();
            $table->timestamps();
            
            $table->index('user_id');
        });

        Schema::create('invitation_referrals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invitation_link_id');
            $table->unsignedBigInteger('referred_user_id');
            $table->timestamps();
            
            $table->foreign('invitation_link_id')
                  ->references('id')
                  ->on('invitation_links')
                  ->onDelete('cascade');
            
            $table->index('invitation_link_id');
            $table->index('referred_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitation_referrals');
        Schema::dropIfExists('invitation_links');
    }
};
