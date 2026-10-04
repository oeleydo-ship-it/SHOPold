<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Product translations
        if (!Schema::hasTable('product_translations')) {
            Schema::create('product_translations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->string('locale', 10);
                $table->string('name');
                $table->text('description')->nullable();
                $table->text('short_description')->nullable();
                $table->string('meta_title')->nullable();
                $table->text('meta_description')->nullable();
                $table->string('meta_keywords')->nullable();
                $table->timestamps();

                $table->unique(['product_id', 'locale']);
                $table->index('locale');
            });
        }

        // Category translations
        if (!Schema::hasTable('category_translations')) {
            Schema::create('category_translations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('category_id')->constrained()->onDelete('cascade');
                $table->string('locale', 10);
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('meta_title')->nullable();
                $table->text('meta_description')->nullable();
                $table->timestamps();

                $table->unique(['category_id', 'locale']);
                $table->index('locale');
            });
        }

        // Brand translations
        if (!Schema::hasTable('brand_translations')) {
            Schema::create('brand_translations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('brand_id')->constrained()->onDelete('cascade');
                $table->string('locale', 10);
                $table->string('name');
                $table->text('description')->nullable();
                $table->timestamps();

                $table->unique(['brand_id', 'locale']);
                $table->index('locale');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_translations');
        Schema::dropIfExists('category_translations');
        Schema::dropIfExists('brand_translations');
    }
};
