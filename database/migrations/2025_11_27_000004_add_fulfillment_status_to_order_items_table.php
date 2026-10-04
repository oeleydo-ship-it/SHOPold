<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('fulfillment_status')->default('pending')->after('total');
            $table->string('tracking_number')->nullable()->after('fulfillment_status');
            $table->string('shipping_carrier')->nullable()->after('tracking_number');
            $table->timestamp('shipped_at')->nullable()->after('shipping_carrier');
            $table->timestamp('delivered_at')->nullable()->after('shipped_at');
            $table->text('vendor_note')->nullable()->after('delivered_at');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn([
                'fulfillment_status',
                'tracking_number',
                'shipping_carrier',
                'shipped_at',
                'delivered_at',
                'vendor_note'
            ]);
        });
    }
};


