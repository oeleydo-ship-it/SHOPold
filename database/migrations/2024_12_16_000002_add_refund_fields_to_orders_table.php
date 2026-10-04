<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('refund_id')->nullable()->after('transaction_id');
            $table->decimal('refund_amount', 10, 2)->nullable()->after('refund_id');
            $table->timestamp('refunded_at')->nullable()->after('refund_amount');
            $table->text('refund_reason')->nullable()->after('refunded_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['refund_id', 'refund_amount', 'refunded_at', 'refund_reason']);
        });
    }
};
