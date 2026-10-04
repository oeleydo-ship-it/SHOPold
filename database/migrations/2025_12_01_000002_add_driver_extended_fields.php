<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add extended driver fields to users table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'driver_rating')) {
                $table->decimal('driver_rating', 3, 2)->default(5.00)->after('driver_status');
            }
            if (!Schema::hasColumn('users', 'driver_total_ratings')) {
                $table->integer('driver_total_ratings')->default(0)->after('driver_rating');
            }
            if (!Schema::hasColumn('users', 'driver_total_deliveries')) {
                $table->integer('driver_total_deliveries')->default(0)->after('driver_total_ratings');
            }
            if (!Schema::hasColumn('users', 'driver_total_earnings')) {
                $table->decimal('driver_total_earnings', 12, 2)->default(0)->after('driver_total_deliveries');
            }
            if (!Schema::hasColumn('users', 'driver_commission_rate')) {
                $table->decimal('driver_commission_rate', 5, 2)->default(10.00)->after('driver_total_earnings');
            }
            if (!Schema::hasColumn('users', 'driver_availability_schedule')) {
                $table->json('driver_availability_schedule')->nullable()->after('driver_commission_rate');
            }
            if (!Schema::hasColumn('users', 'driver_zones')) {
                $table->json('driver_zones')->nullable()->after('driver_availability_schedule');
            }
        });

        // Add delivery proof and rating fields to orders
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'delivery_proof_photo')) {
                $table->string('delivery_proof_photo')->nullable()->after('driver_notes');
            }
            if (!Schema::hasColumn('orders', 'delivery_signature')) {
                $table->text('delivery_signature')->nullable()->after('delivery_proof_photo');
            }
            if (!Schema::hasColumn('orders', 'driver_rating')) {
                $table->tinyInteger('driver_rating')->nullable()->after('delivery_signature');
            }
            if (!Schema::hasColumn('orders', 'driver_rating_comment')) {
                $table->text('driver_rating_comment')->nullable()->after('driver_rating');
            }
            if (!Schema::hasColumn('orders', 'driver_tip')) {
                $table->decimal('driver_tip', 10, 2)->default(0)->after('driver_rating_comment');
            }
            if (!Schema::hasColumn('orders', 'driver_earnings')) {
                $table->decimal('driver_earnings', 10, 2)->default(0)->after('driver_tip');
            }
        });

        // Create driver_earnings table for detailed tracking
        if (!Schema::hasTable('driver_earnings')) {
            Schema::create('driver_earnings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('driver_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
                $table->enum('type', ['delivery', 'tip', 'bonus', 'adjustment', 'payout']);
                $table->decimal('amount', 10, 2);
                $table->string('description')->nullable();
                $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
                $table->timestamps();
            });
        }

        // Create driver_notifications table
        if (!Schema::hasTable('driver_notifications')) {
            Schema::create('driver_notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('driver_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('order_id')->nullable()->constrained()->onDelete('cascade');
                $table->string('type'); // new_order, status_update, rating, etc.
                $table->string('title');
                $table->text('message');
                $table->boolean('is_read')->default(false);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'driver_rating', 'driver_total_ratings', 'driver_total_deliveries',
                'driver_total_earnings', 'driver_commission_rate', 
                'driver_availability_schedule', 'driver_zones'
            ]);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_proof_photo', 'delivery_signature', 'driver_rating',
                'driver_rating_comment', 'driver_tip', 'driver_earnings'
            ]);
        });

        Schema::dropIfExists('driver_earnings');
        Schema::dropIfExists('driver_notifications');
    }
};
