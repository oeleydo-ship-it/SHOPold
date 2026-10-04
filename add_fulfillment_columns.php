<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;

// Check if table exists first
if (!Schema::hasTable('order_items')) {
    echo "ERROR: order_items table does not exist!\n";
    echo "Please run: php artisan migrate\n";
    
    // List existing tables
    $tables = DB::select('SHOW TABLES');
    echo "\nExisting tables in database:\n";
    foreach ($tables as $table) {
        $arr = (array)$table;
        echo "- " . reset($arr) . "\n";
    }
    exit(1);
}

// Check if column exists
if (!Schema::hasColumn('order_items', 'fulfillment_status')) {
    Schema::table('order_items', function (Blueprint $table) {
        $table->string('fulfillment_status')->default('pending')->after('total');
        $table->string('tracking_number')->nullable()->after('fulfillment_status');
        $table->string('shipping_carrier')->nullable()->after('tracking_number');
        $table->timestamp('shipped_at')->nullable()->after('shipping_carrier');
        $table->timestamp('delivered_at')->nullable()->after('shipped_at');
        $table->text('vendor_note')->nullable()->after('delivered_at');
    });
    echo "Columns added successfully!\n";
} else {
    echo "Columns already exist.\n";
}

