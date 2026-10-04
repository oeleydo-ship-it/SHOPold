<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_usage', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('openai');
            $table->string('model')->nullable();
            $table->string('feature')->default('chat'); // chat, translation, content_generation, etc.
            $table->integer('prompt_tokens')->default(0);
            $table->integer('completion_tokens')->default(0);
            $table->integer('total_tokens')->default(0);
            $table->decimal('estimated_cost', 10, 6)->default(0);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained('ai_conversations')->nullOnDelete();
            $table->timestamps();

            $table->index(['created_at']);
            $table->index(['feature']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage');
    }
};
