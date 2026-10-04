<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('countries')->nullable(); // JSON array of country codes
            $table->text('regions')->nullable(); // JSON array of regions/states
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_zone_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('type')->default('flat_rate'); // flat_rate, free, weight_based, price_based
            $table->decimal('cost', 10, 2)->default(0);
            $table->decimal('min_order_amount', 10, 2)->nullable(); // For free shipping threshold
            $table->decimal('per_kg_rate', 10, 2)->nullable(); // For weight-based
            $table->decimal('per_item_rate', 10, 2)->nullable(); // Per item rate
            $table->string('delivery_time')->nullable(); // e.g., "3-5 business days"
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_methods');
        Schema::dropIfExists('shipping_zones');
    }
};
