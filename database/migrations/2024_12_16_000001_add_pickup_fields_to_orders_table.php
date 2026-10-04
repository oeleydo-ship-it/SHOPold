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
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'delivery_type')) {
                $table->string('delivery_type')->default('shipping')->after('is_guest_order');
            }
            if (!Schema::hasColumn('orders', 'pickup_location_id')) {
                $table->foreignId('pickup_location_id')->nullable()->after('delivery_type')->constrained('pickup_locations')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'pickup_location_id')) {
                $table->dropForeign(['pickup_location_id']);
                $table->dropColumn('pickup_location_id');
            }
            if (Schema::hasColumn('orders', 'delivery_type')) {
                $table->dropColumn('delivery_type');
            }
        });
    }
};
