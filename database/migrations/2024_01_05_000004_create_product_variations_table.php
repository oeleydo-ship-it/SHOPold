<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('sku')->unique();
            $table->string('name')->nullable(); // Auto-generated from attribute values
            $table->decimal('price', 10, 2)->nullable(); // Override parent price
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->integer('quantity')->default(0);
            $table->string('image')->nullable();
            $table->decimal('weight', 8, 2)->nullable();
            $table->string('dimensions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('manage_stock')->default(true);
            $table->enum('stock_status', ['in_stock', 'out_of_stock', 'on_backorder'])->default('in_stock');
            $table->integer('low_stock_threshold')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Variation-Attribute Values (which attribute values make up this variation)
        Schema::create('variation_attribute_value', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variation_id')->constrained()->onDelete('cascade');
            $table->foreignId('attribute_id')->constrained()->onDelete('cascade');
            $table->foreignId('attribute_value_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['product_variation_id', 'attribute_id'], 'vav_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variation_attribute_value');
        Schema::dropIfExists('product_variations');
    }
};
