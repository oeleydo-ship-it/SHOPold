<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add is_driver field to users table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_driver')) {
                $table->boolean('is_driver')->default(false)->after('is_admin');
            }
            if (!Schema::hasColumn('users', 'driver_phone')) {
                $table->string('driver_phone')->nullable()->after('is_driver');
            }
            if (!Schema::hasColumn('users', 'driver_vehicle')) {
                $table->string('driver_vehicle')->nullable()->after('driver_phone');
            }
            if (!Schema::hasColumn('users', 'driver_license')) {
                $table->string('driver_license')->nullable()->after('driver_vehicle');
            }
            if (!Schema::hasColumn('users', 'driver_status')) {
                $table->enum('driver_status', ['available', 'busy', 'offline'])->default('offline')->after('driver_license');
            }
        });

        // Add driver assignment to orders
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'driver_id')) {
                $table->foreignId('driver_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('orders', 'driver_assigned_at')) {
                $table->timestamp('driver_assigned_at')->nullable()->after('driver_id');
            }
            if (!Schema::hasColumn('orders', 'picked_up_at')) {
                $table->timestamp('picked_up_at')->nullable()->after('shipped_at');
            }
            if (!Schema::hasColumn('orders', 'driver_notes')) {
                $table->text('driver_notes')->nullable()->after('notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_driver', 'driver_phone', 'driver_vehicle', 'driver_license', 'driver_status']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['driver_id']);
            $table->dropColumn(['driver_id', 'driver_assigned_at', 'picked_up_at', 'driver_notes']);
        });
    }
};
