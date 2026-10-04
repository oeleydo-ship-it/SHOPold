<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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

        // Page translations
        if (!Schema::hasTable('page_translations')) {
            Schema::create('page_translations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('page_id')->constrained()->onDelete('cascade');
                $table->string('locale', 10);
                $table->string('title');
                $table->longText('content')->nullable();
                $table->string('meta_title')->nullable();
                $table->text('meta_description')->nullable();
                $table->timestamps();
                
                $table->unique(['page_id', 'locale']);
                $table->index('locale');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('page_translations');
        Schema::dropIfExists('brand_translations');
        Schema::dropIfExists('category_translations');
    }
};
