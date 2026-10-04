<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;

echo "Checking and creating missing tables...\n";

// Create categories table first (products depend on it)
if (!Schema::hasTable('categories')) {
    Schema::create('categories', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('slug')->unique();
        $table->text('description')->nullable();
        $table->string('image')->nullable();
        $table->unsignedBigInteger('parent_id')->nullable();
        $table->integer('sort_order')->default(0);
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
    echo "Created categories table\n";
}

// Create vendors table (products depend on it)
if (!Schema::hasTable('vendors')) {
    Schema::create('vendors', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->string('store_name');
        $table->string('slug')->unique();
        $table->text('description')->nullable();
        $table->string('logo')->nullable();
        $table->string('banner')->nullable();
        $table->string('email')->nullable();
        $table->string('phone')->nullable();
        $table->text('address')->nullable();
        $table->string('city')->nullable();
        $table->string('state')->nullable();
        $table->string('country')->nullable();
        $table->string('zip_code')->nullable();
        $table->decimal('commission_rate', 5, 2)->default(10);
        $table->string('status')->default('pending');
        $table->decimal('total_sales', 12, 2)->default(0);
        $table->decimal('total_earnings', 12, 2)->default(0);
        $table->decimal('balance', 12, 2)->default(0);
        $table->json('social_links')->nullable();
        $table->json('business_info')->nullable();
        $table->boolean('featured')->default(false);
        $table->integer('rating_count')->default(0);
        $table->decimal('average_rating', 3, 2)->default(0);
        $table->timestamp('approved_at')->nullable();
        $table->timestamps();
    });
    echo "Created vendors table\n";
}

// Create products table (order_items depend on it)
if (!Schema::hasTable('products')) {
    Schema::create('products', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('slug')->unique();
        $table->string('product_type')->default('simple');
        $table->text('description')->nullable();
        $table->text('short_description')->nullable();
        $table->text('purchase_note')->nullable();
        $table->decimal('price', 10, 2);
        $table->decimal('sale_price', 10, 2)->nullable();
        $table->timestamp('sale_start_date')->nullable();
        $table->timestamp('sale_end_date')->nullable();
        $table->decimal('cost_price', 10, 2)->nullable();
        $table->string('sku')->unique();
        $table->string('barcode')->nullable();
        $table->string('mpn')->nullable();
        $table->integer('quantity')->default(0);
        $table->boolean('manage_stock')->default(true);
        $table->string('stock_status')->default('in_stock');
        $table->integer('low_stock_threshold')->default(5);
        $table->boolean('sold_individually')->default(false);
        $table->integer('min_purchase_qty')->default(1);
        $table->integer('max_purchase_qty')->nullable();
        $table->unsignedBigInteger('category_id')->nullable();
        $table->unsignedBigInteger('brand_id')->nullable();
        $table->unsignedBigInteger('vendor_id')->nullable();
        $table->string('image')->nullable();
        $table->json('gallery')->nullable();
        $table->json('related_products')->nullable();
        $table->json('upsell_products')->nullable();
        $table->json('cross_sell_products')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamp('publish_date')->nullable();
        $table->boolean('is_featured')->default(false);
        $table->boolean('is_virtual')->default(false);
        $table->boolean('is_downloadable')->default(false);
        $table->string('external_url')->nullable();
        $table->string('button_text')->nullable();
        $table->decimal('weight', 8, 2)->nullable();
        $table->string('dimensions')->nullable();
        $table->string('meta_title')->nullable();
        $table->text('meta_description')->nullable();
        $table->string('meta_keywords')->nullable();
        $table->string('canonical_url')->nullable();
        $table->integer('views')->default(0);
        $table->timestamps();
    });
    echo "Created products table\n";
}

// Create orders table if not exists
if (!Schema::hasTable('orders')) {
    Schema::create('orders', function (Blueprint $table) {
        $table->id();
        $table->string('order_number')->unique();
        $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
        $table->foreignId('vendor_id')->nullable();
        $table->decimal('subtotal', 10, 2)->default(0);
        $table->decimal('tax', 10, 2)->default(0);
        $table->decimal('shipping_cost', 10, 2)->default(0);
        $table->decimal('discount', 10, 2)->default(0);
        $table->decimal('total', 10, 2)->default(0);
        $table->string('status')->default('pending');
        $table->string('payment_status')->default('pending');
        $table->string('payment_method')->nullable();
        $table->string('delivery_type')->default('shipping');
        $table->unsignedBigInteger('pickup_location_id')->nullable();
        $table->date('scheduled_date')->nullable();
        $table->unsignedBigInteger('delivery_slot_id')->nullable();
        $table->text('delivery_instructions')->nullable();
        $table->string('transaction_id')->nullable();
        $table->string('shipping_name')->nullable();
        $table->string('shipping_email')->nullable();
        $table->string('shipping_phone')->nullable();
        $table->text('shipping_address')->nullable();
        $table->string('shipping_city')->nullable();
        $table->string('shipping_state')->nullable();
        $table->string('shipping_zip')->nullable();
        $table->string('shipping_country')->nullable();
        $table->string('billing_name')->nullable();
        $table->text('billing_address')->nullable();
        $table->string('billing_city')->nullable();
        $table->string('billing_state')->nullable();
        $table->string('billing_zip')->nullable();
        $table->string('billing_country')->nullable();
        $table->text('notes')->nullable();
        $table->string('tracking_number')->nullable();
        $table->string('tracking_url')->nullable();
        $table->timestamp('shipped_at')->nullable();
        $table->timestamp('delivered_at')->nullable();
        $table->timestamps();
    });
    echo "Created orders table\n";
}

// Create order_items table if not exists
if (!Schema::hasTable('order_items')) {
    Schema::create('order_items', function (Blueprint $table) {
        $table->id();
        $table->foreignId('order_id')->constrained()->cascadeOnDelete();
        $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
        $table->unsignedBigInteger('product_variation_id')->nullable();
        $table->string('product_name');
        $table->json('variation_options')->nullable();
        $table->decimal('price', 10, 2);
        $table->integer('quantity');
        $table->decimal('total', 10, 2);
        $table->string('fulfillment_status')->default('pending');
        $table->string('tracking_number')->nullable();
        $table->string('shipping_carrier')->nullable();
        $table->timestamp('shipped_at')->nullable();
        $table->timestamp('delivered_at')->nullable();
        $table->text('vendor_note')->nullable();
        $table->timestamps();
    });
    echo "Created order_items table\n";
} else {
    // Add fulfillment columns if missing
    if (!Schema::hasColumn('order_items', 'fulfillment_status')) {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('fulfillment_status')->default('pending')->after('total');
            $table->string('tracking_number')->nullable()->after('fulfillment_status');
            $table->string('shipping_carrier')->nullable()->after('tracking_number');
            $table->timestamp('shipped_at')->nullable()->after('shipping_carrier');
            $table->timestamp('delivered_at')->nullable()->after('shipped_at');
            $table->text('vendor_note')->nullable()->after('delivered_at');
        });
        echo "Added fulfillment columns to order_items table\n";
    }
}

echo "\nDone! All required tables are now available.\n";

