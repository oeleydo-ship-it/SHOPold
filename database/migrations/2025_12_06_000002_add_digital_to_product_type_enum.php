<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // For MySQL, we need to modify the enum
        // For SQLite (testing), enums are stored as strings so no change needed
        $driver = Schema::getConnection()->getDriverName();
        
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE products MODIFY COLUMN product_type ENUM('simple', 'variable', 'grouped', 'external', 'digital') DEFAULT 'simple'");
        }
        // SQLite doesn't enforce enum values, so no change needed
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        
        if ($driver === 'mysql') {
            // First update any digital products to simple
            DB::table('products')->where('product_type', 'digital')->update(['product_type' => 'simple']);
            DB::statement("ALTER TABLE products MODIFY COLUMN product_type ENUM('simple', 'variable', 'grouped', 'external') DEFAULT 'simple'");
        }
    }
};
