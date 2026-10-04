<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // User Points Balance
        Schema::create('user_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('balance')->default(0);
            $table->integer('lifetime_points')->default(0);
            $table->integer('redeemed_points')->default(0);
            $table->timestamps();
        });

        // Points Transactions
        Schema::create('points_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('type', ['earned', 'redeemed', 'expired', 'adjusted', 'bonus']);
            $table->integer('points');
            $table->integer('balance_after');
            $table->string('description');
            $table->string('reference_type')->nullable(); // order, referral, signup, admin
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'type']);
            $table->index('expires_at');
        });

        // Referrals
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('referred_id')->constrained('users')->onDelete('cascade');
            $table->string('referral_code');
            $table->enum('status', ['pending', 'completed', 'rewarded'])->default('pending');
            $table->integer('points_awarded')->default(0);
            $table->timestamps();
            
            $table->unique('referred_id');
            $table->index('referral_code');
        });

        // Add referral code to users
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code')->nullable()->unique()->after('remember_token');
            $table->foreignId('referred_by')->nullable()->after('referral_code')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referred_by']);
            $table->dropColumn(['referral_code', 'referred_by']);
        });

        Schema::dropIfExists('referrals');
        Schema::dropIfExists('points_transactions');
        Schema::dropIfExists('user_points');
    }
};
