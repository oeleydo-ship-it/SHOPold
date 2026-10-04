<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Delivery time slots configuration
        Schema::create('delivery_slots', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Morning", "Afternoon", "Evening"
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('max_orders')->default(10); // Max orders per slot
            $table->decimal('extra_charge', 10, 2)->default(0); // Extra charge for this slot
            $table->json('available_days')->nullable(); // [0,1,2,3,4,5,6] for days of week
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Store pickup locations
        Schema::create('pickup_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('address');
            $table->string('city');
            $table->string('state')->nullable();
            $table->string('zip')->nullable();
            $table->string('country');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('instructions')->nullable();
            $table->json('opening_hours')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Add delivery fields to orders
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('delivery_type', ['shipping', 'pickup'])->default('shipping')->after('payment_method');
            $table->foreignId('pickup_location_id')->nullable()->after('delivery_type')->constrained()->nullOnDelete();
            $table->date('scheduled_date')->nullable()->after('pickup_location_id');
            $table->foreignId('delivery_slot_id')->nullable()->after('scheduled_date')->constrained()->nullOnDelete();
            $table->text('delivery_instructions')->nullable()->after('delivery_slot_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['pickup_location_id']);
            $table->dropForeign(['delivery_slot_id']);
            $table->dropColumn(['delivery_type', 'pickup_location_id', 'scheduled_date', 'delivery_slot_id', 'delivery_instructions']);
        });
        
        Schema::dropIfExists('pickup_locations');
        Schema::dropIfExists('delivery_slots');
    }
};
