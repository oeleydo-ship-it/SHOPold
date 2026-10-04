<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

echo "Creating missing tables...\n\n";

// Carts
if (!Schema::hasTable('carts')) {
    Schema::create('carts', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
        $table->string('session_id')->nullable();
        $table->foreignId('coupon_id')->nullable();
        $table->timestamps();
        $table->index('session_id');
    });
    echo "- Created carts table\n";
}

// Cart Items
if (!Schema::hasTable('cart_items')) {
    Schema::create('cart_items', function (Blueprint $table) {
        $table->id();
        $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
        $table->foreignId('product_id')->constrained()->cascadeOnDelete();
        $table->unsignedBigInteger('product_variation_id')->nullable();
        $table->integer('quantity')->default(1);
        $table->timestamps();
    });
    echo "- Created cart_items table\n";
}

// Brands
if (!Schema::hasTable('brands')) {
    Schema::create('brands', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('slug')->unique();
        $table->text('description')->nullable();
        $table->string('logo')->nullable();
        $table->string('website')->nullable();
        $table->boolean('is_active')->default(true);
        $table->integer('sort_order')->default(0);
        $table->timestamps();
    });
    echo "- Created brands table\n";
}

// Coupons
if (!Schema::hasTable('coupons')) {
    Schema::create('coupons', function (Blueprint $table) {
        $table->id();
        $table->string('code')->unique();
        $table->string('type')->default('fixed');
        $table->decimal('value', 10, 2);
        $table->decimal('min_amount', 10, 2)->nullable();
        $table->decimal('max_discount', 10, 2)->nullable();
        $table->integer('usage_limit')->nullable();
        $table->integer('used_count')->default(0);
        $table->timestamp('starts_at')->nullable();
        $table->timestamp('expires_at')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
    echo "- Created coupons table\n";
}

// Reviews
if (!Schema::hasTable('reviews')) {
    Schema::create('reviews', function (Blueprint $table) {
        $table->id();
        $table->foreignId('product_id')->constrained()->cascadeOnDelete();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->integer('rating');
        $table->text('comment')->nullable();
        $table->boolean('is_approved')->default(false);
        $table->timestamps();
    });
    echo "- Created reviews table\n";
}

// Wishlists
if (!Schema::hasTable('wishlists')) {
    Schema::create('wishlists', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->foreignId('product_id')->constrained()->cascadeOnDelete();
        $table->timestamps();
        $table->unique(['user_id', 'product_id']);
    });
    echo "- Created wishlists table\n";
}

// Product Images
if (!Schema::hasTable('product_images')) {
    Schema::create('product_images', function (Blueprint $table) {
        $table->id();
        $table->foreignId('product_id')->constrained()->cascadeOnDelete();
        $table->string('image');
        $table->integer('sort_order')->default(0);
        $table->boolean('is_primary')->default(false);
        $table->timestamps();
    });
    echo "- Created product_images table\n";
}

// Product Variations
if (!Schema::hasTable('product_variations')) {
    Schema::create('product_variations', function (Blueprint $table) {
        $table->id();
        $table->foreignId('product_id')->constrained()->cascadeOnDelete();
        $table->string('sku')->unique();
        $table->string('variation_name')->nullable();
        $table->decimal('price', 10, 2)->nullable();
        $table->decimal('sale_price', 10, 2)->nullable();
        $table->decimal('cost_price', 10, 2)->nullable();
        $table->integer('quantity')->default(0);
        $table->string('stock_status')->default('in_stock');
        $table->decimal('weight', 8, 2)->nullable();
        $table->string('dimensions')->nullable();
        $table->string('image')->nullable();
        $table->boolean('is_active')->default(true);
        $table->integer('sort_order')->default(0);
        $table->timestamps();
    });
    echo "- Created product_variations table\n";
}

// Attributes
if (!Schema::hasTable('attributes')) {
    Schema::create('attributes', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('slug')->unique();
        $table->string('type')->default('select');
        $table->integer('sort_order')->default(0);
        $table->timestamps();
    });
    echo "- Created attributes table\n";
}

// Attribute Values
if (!Schema::hasTable('attribute_values')) {
    Schema::create('attribute_values', function (Blueprint $table) {
        $table->id();
        $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
        $table->string('value');
        $table->string('slug');
        $table->string('color_code')->nullable();
        $table->string('image')->nullable();
        $table->integer('sort_order')->default(0);
        $table->timestamps();
    });
    echo "- Created attribute_values table\n";
}

// Product Attribute Values Pivot
if (!Schema::hasTable('attribute_value_product')) {
    Schema::create('attribute_value_product', function (Blueprint $table) {
        $table->id();
        $table->foreignId('product_id')->constrained()->cascadeOnDelete();
        $table->foreignId('attribute_value_id')->constrained()->cascadeOnDelete();
        $table->timestamps();
    });
    echo "- Created attribute_value_product table\n";
}

// Variation Attribute Values Pivot
if (!Schema::hasTable('attribute_value_product_variation')) {
    Schema::create('attribute_value_product_variation', function (Blueprint $table) {
        $table->id();
        $table->foreignId('product_variation_id')->constrained()->cascadeOnDelete();
        $table->foreignId('attribute_value_id')->constrained()->cascadeOnDelete();
        $table->timestamps();
    });
    echo "- Created attribute_value_product_variation table\n";
}

// Tags
if (!Schema::hasTable('tags')) {
    Schema::create('tags', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('slug')->unique();
        $table->string('color')->default('#3B82F6');
        $table->timestamps();
    });
    echo "- Created tags table\n";
}

// Product Tags Pivot
if (!Schema::hasTable('product_tag')) {
    Schema::create('product_tag', function (Blueprint $table) {
        $table->id();
        $table->foreignId('product_id')->constrained()->cascadeOnDelete();
        $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
        $table->timestamps();
    });
    echo "- Created product_tag table\n";
}

// Shipping Zones
if (!Schema::hasTable('shipping_zones')) {
    Schema::create('shipping_zones', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->json('regions')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
    echo "- Created shipping_zones table\n";
}

// Shipping Methods
if (!Schema::hasTable('shipping_methods')) {
    Schema::create('shipping_methods', function (Blueprint $table) {
        $table->id();
        $table->foreignId('shipping_zone_id')->constrained()->cascadeOnDelete();
        $table->string('name');
        $table->string('type')->default('flat_rate');
        $table->decimal('cost', 10, 2)->default(0);
        $table->decimal('free_shipping_threshold', 10, 2)->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
    echo "- Created shipping_methods table\n";
}

// Pickup Locations
if (!Schema::hasTable('pickup_locations')) {
    Schema::create('pickup_locations', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->text('address');
        $table->string('city');
        $table->string('state')->nullable();
        $table->string('zip_code')->nullable();
        $table->string('country');
        $table->string('phone')->nullable();
        $table->text('hours')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
    echo "- Created pickup_locations table\n";
}

// Delivery Slots
if (!Schema::hasTable('delivery_slots')) {
    Schema::create('delivery_slots', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->time('start_time');
        $table->time('end_time');
        $table->integer('max_orders')->default(10);
        $table->decimal('extra_charge', 10, 2)->default(0);
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
    echo "- Created delivery_slots table\n";
}

// Pages
if (!Schema::hasTable('pages')) {
    Schema::create('pages', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->string('slug')->unique();
        $table->longText('content')->nullable();
        $table->string('meta_title')->nullable();
        $table->text('meta_description')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
    echo "- Created pages table\n";
}

// Media
if (!Schema::hasTable('media')) {
    Schema::create('media', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('file_name');
        $table->string('mime_type');
        $table->string('path');
        $table->string('disk')->default('public');
        $table->string('collection')->default('default');
        $table->unsignedBigInteger('size');
        $table->json('custom_properties')->nullable();
        $table->timestamps();
    });
    echo "- Created media table\n";
}

// Blog Categories
if (!Schema::hasTable('blog_categories')) {
    Schema::create('blog_categories', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('slug')->unique();
        $table->text('description')->nullable();
        $table->timestamps();
    });
    echo "- Created blog_categories table\n";
}

// Blog Posts
if (!Schema::hasTable('blog_posts')) {
    Schema::create('blog_posts', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->string('slug')->unique();
        $table->text('excerpt')->nullable();
        $table->longText('content');
        $table->string('image')->nullable();
        $table->foreignId('blog_category_id')->nullable()->constrained()->nullOnDelete();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->string('status')->default('draft');
        $table->timestamp('published_at')->nullable();
        $table->string('meta_title')->nullable();
        $table->text('meta_description')->nullable();
        $table->timestamps();
    });
    echo "- Created blog_posts table\n";
}

// Blog Comments
if (!Schema::hasTable('blog_comments')) {
    Schema::create('blog_comments', function (Blueprint $table) {
        $table->id();
        $table->foreignId('post_id')->constrained('blog_posts')->cascadeOnDelete();
        $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
        $table->string('name')->nullable();
        $table->string('email')->nullable();
        $table->text('content');
        $table->string('status')->default('pending');
        $table->timestamps();
    });
    echo "- Created blog_comments table\n";
}

// Inventory Logs
if (!Schema::hasTable('inventory_logs')) {
    Schema::create('inventory_logs', function (Blueprint $table) {
        $table->id();
        $table->foreignId('product_id')->constrained()->cascadeOnDelete();
        $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
        $table->string('type');
        $table->integer('quantity_before');
        $table->integer('quantity_change');
        $table->integer('quantity_after');
        $table->text('note')->nullable();
        $table->timestamps();
    });
    echo "- Created inventory_logs table\n";
}

// User Points
if (!Schema::hasTable('user_points')) {
    Schema::create('user_points', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->integer('points')->default(0);
        $table->integer('lifetime_points')->default(0);
        $table->timestamps();
    });
    echo "- Created user_points table\n";
}

// Points Transactions
if (!Schema::hasTable('points_transactions')) {
    Schema::create('points_transactions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->integer('points');
        $table->string('type');
        $table->string('description')->nullable();
        $table->morphs('reference');
        $table->timestamps();
    });
    echo "- Created points_transactions table\n";
}

// Vendor Payouts
if (!Schema::hasTable('vendor_payouts')) {
    Schema::create('vendor_payouts', function (Blueprint $table) {
        $table->id();
        $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
        $table->decimal('amount', 12, 2);
        $table->string('method')->default('bank_transfer');
        $table->string('status')->default('pending');
        $table->text('notes')->nullable();
        $table->timestamp('processed_at')->nullable();
        $table->timestamps();
    });
    echo "- Created vendor_payouts table\n";
}

echo "\nAll tables created successfully!\n";


