<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Sale dates
            $table->timestamp('sale_start_date')->nullable()->after('sale_price');
            $table->timestamp('sale_end_date')->nullable()->after('sale_start_date');
            
            // Product type
            $table->enum('product_type', ['simple', 'variable', 'grouped', 'external'])->default('simple')->after('slug');
            
            // Inventory management
            $table->boolean('manage_stock')->default(true)->after('quantity');
            $table->enum('stock_status', ['in_stock', 'out_of_stock', 'on_backorder'])->default('in_stock')->after('manage_stock');
            $table->integer('low_stock_threshold')->nullable()->after('stock_status');
            $table->boolean('sold_individually')->default(false)->after('low_stock_threshold'); // Limit to 1 per order
            $table->integer('min_purchase_qty')->default(1)->after('sold_individually');
            $table->integer('max_purchase_qty')->nullable()->after('min_purchase_qty');
            
            // SEO fields
            $table->string('meta_title')->nullable()->after('dimensions');
            $table->text('meta_description')->nullable()->after('meta_title');
            $table->string('meta_keywords')->nullable()->after('meta_description');
            $table->string('canonical_url')->nullable()->after('meta_keywords');
            
            // Additional info
            $table->string('barcode')->nullable()->after('sku'); // UPC, EAN, ISBN
            $table->string('mpn')->nullable()->after('barcode'); // Manufacturer Part Number
            $table->decimal('cost_price', 10, 2)->nullable()->after('sale_end_date'); // For profit calculation
            
            // Shipping
            $table->boolean('is_virtual')->default(false)->after('is_featured'); // No shipping needed
            $table->boolean('is_downloadable')->default(false)->after('is_virtual');
            
            // External/Affiliate products
            $table->string('external_url')->nullable()->after('is_downloadable');
            $table->string('button_text')->nullable()->after('external_url');
            
            // Scheduling
            $table->timestamp('publish_date')->nullable()->after('is_active');
            
            // Related products
            $table->json('related_products')->nullable()->after('gallery');
            $table->json('upsell_products')->nullable()->after('related_products');
            $table->json('cross_sell_products')->nullable()->after('upsell_products');
            
            // Purchase note
            $table->text('purchase_note')->nullable()->after('short_description');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'sale_start_date',
                'sale_end_date',
                'product_type',
                'manage_stock',
                'stock_status',
                'low_stock_threshold',
                'sold_individually',
                'min_purchase_qty',
                'max_purchase_qty',
                'meta_title',
                'meta_description',
                'meta_keywords',
                'canonical_url',
                'barcode',
                'mpn',
                'cost_price',
                'is_virtual',
                'is_downloadable',
                'external_url',
                'button_text',
                'publish_date',
                'related_products',
                'upsell_products',
                'cross_sell_products',
                'purchase_note',
            ]);
        });
    }
};
