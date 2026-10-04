<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Attributes (e.g., Color, Size, Material)
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('type', ['select', 'color', 'button', 'radio'])->default('select');
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_variation')->default(true); // Used for variations
            $table->boolean('is_global')->default(true); // Available for all products
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Attribute Values (e.g., Red, Blue, Small, Large)
        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained()->onDelete('cascade');
            $table->string('value');
            $table->string('slug');
            $table->string('color_code')->nullable(); // For color type attributes
            $table->string('image')->nullable(); // For visual swatches
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->unique(['attribute_id', 'slug']);
        });

        // Product-Attribute relationship (which attributes a product uses)
        Schema::create('product_attribute', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('attribute_id')->constrained()->onDelete('cascade');
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_variation')->default(true);
            $table->timestamps();
            
            $table->unique(['product_id', 'attribute_id']);
        });

        // Product-Attribute Values (which values a product has for each attribute)
        Schema::create('product_attribute_value', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('attribute_id')->constrained()->onDelete('cascade');
            $table->foreignId('attribute_value_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['product_id', 'attribute_id', 'attribute_value_id'], 'pav_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_attribute_value');
        Schema::dropIfExists('product_attribute');
        Schema::dropIfExists('attribute_values');
        Schema::dropIfExists('attributes');
    }
};
