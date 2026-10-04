<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create digital files table
        Schema::create('digital_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Display name
            $table->string('file_path')->nullable(); // For local uploads
            $table->string('file_url')->nullable(); // For external URLs
            $table->enum('file_type', ['upload', 'url'])->default('upload');
            $table->string('file_name')->nullable(); // Original filename
            $table->string('file_extension')->nullable();
            $table->bigInteger('file_size')->nullable(); // In bytes
            $table->integer('download_limit')->nullable(); // null = unlimited
            $table->integer('download_expiry_days')->nullable(); // null = never expires
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Create download logs table for tracking
        Schema::create('download_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_item_id')->constrained()->onDelete('cascade');
            $table->foreignId('digital_file_id')->constrained()->onDelete('cascade');
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });

        // Add digital product fields to order_items
        Schema::table('order_items', function (Blueprint $table) {
            $table->boolean('is_digital')->default(false)->after('total');
            $table->integer('downloads_remaining')->nullable()->after('is_digital');
            $table->timestamp('download_expiry')->nullable()->after('downloads_remaining');
            $table->string('download_token')->nullable()->unique()->after('download_expiry');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['is_digital', 'downloads_remaining', 'download_expiry', 'download_token']);
        });

        Schema::dropIfExists('download_logs');
        Schema::dropIfExists('digital_files');
    }
};
