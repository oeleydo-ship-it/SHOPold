<?php

/**
 * **Feature: stock-management, Property 3: Bulk Set Stock Consistency**
 * 
 * *For any* set of products/variations and any quantity N, after a bulk "set" operation,
 * all selected items should have quantity exactly equal to N.
 * 
 * **Validates: Requirements 4.2**
 */

use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Category;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create a category for products
    $this->category = Category::create([
        'name' => 'Test Category',
        'slug' => 'test-category',
        'is_active' => true,
    ]);
    
    $this->stockService = new StockService();
});

/**
 * Property test: Bulk set stock consistency for products
 * For any set of products and any quantity N, after bulk set, all products have quantity N
 * Runs 100 iterations with random product counts and quantities
 */
test('bulk set stock sets all products to exact quantity', function () {
    $faker = \Faker\Factory::create();
    
    // Run 100 iterations
    for ($iteration = 0; $iteration < 100; $iteration++) {
        // Create random number of products (1-10)
        $productCount = $faker->numberBetween(1, 10);
        $products = collect();
        
        for ($i = 0; $i < $productCount; $i++) {
            // Each product starts with a random initial quantity
            $initialQuantity = $faker->numberBetween(0, 1000);
            $product = Product::create([
                'name' => 'Bulk Set Product ' . $iteration . '-' . $i,
                'slug' => 'bulk-set-product-' . $faker->unique()->randomNumber(8),
                'description' => $faker->paragraph(),
                'price' => $faker->randomFloat(2, 10, 1000),
                'sku' => 'BULKSET-' . $faker->unique()->randomNumber(8),
                'quantity' => $initialQuantity,
                'category_id' => $this->category->id,
                'is_active' => true,
            ]);
            $products->push($product);
        }
        
        // Generate a random quantity to set (non-negative)
        $setQuantity = $faker->numberBetween(0, 10000);
        
        // Perform bulk set stock
        $this->stockService->bulkSetStock($products, $setQuantity, 'Bulk set test iteration ' . $iteration);
        
        // Verify ALL products now have exactly the set quantity
        foreach ($products as $product) {
            $product->refresh();
            expect($product->quantity)->toBe($setQuantity);
        }
    }
});

/**
 * Property test: Bulk set stock consistency for product variations
 * For any set of variations and any quantity N, after bulk set, all variations have quantity N
 * Runs 100 iterations with random variation counts and quantities
 */
test('bulk set stock sets all variations to exact quantity', function () {
    $faker = \Faker\Factory::create();
    
    // Create a parent product for variations
    $parentProduct = Product::create([
        'name' => 'Variable Product',
        'slug' => 'variable-product-' . $faker->unique()->randomNumber(8),
        'description' => $faker->paragraph(),
        'price' => $faker->randomFloat(2, 10, 1000),
        'sku' => 'VAR-PARENT-' . $faker->unique()->randomNumber(8),
        'quantity' => 0,
        'product_type' => 'variable',
        'category_id' => $this->category->id,
        'is_active' => true,
    ]);
    
    // Run 100 iterations
    for ($iteration = 0; $iteration < 100; $iteration++) {
        // Create random number of variations (1-10)
        $variationCount = $faker->numberBetween(1, 10);
        $variations = collect();
        
        for ($i = 0; $i < $variationCount; $i++) {
            // Each variation starts with a random initial quantity
            $initialQuantity = $faker->numberBetween(0, 1000);
            $variation = ProductVariation::create([
                'product_id' => $parentProduct->id,
                'sku' => 'VAR-CHILD-' . $faker->unique()->randomNumber(8),
                'name' => 'Test Variation ' . $iteration . '-' . $i,
                'price' => $faker->randomFloat(2, 10, 1000),
                'quantity' => $initialQuantity,
                'is_active' => true,
            ]);
            $variations->push($variation);
        }
        
        // Generate a random quantity to set (non-negative)
        $setQuantity = $faker->numberBetween(0, 10000);
        
        // Perform bulk set stock
        $this->stockService->bulkSetStock($variations, $setQuantity, 'Bulk set variation test iteration ' . $iteration);
        
        // Verify ALL variations now have exactly the set quantity
        foreach ($variations as $variation) {
            $variation->refresh();
            expect($variation->quantity)->toBe($setQuantity);
        }
    }
});

/**
 * Property test: Bulk set stock consistency for mixed products and variations
 * For any mixed set of products and variations and any quantity N, after bulk set, all items have quantity N
 * Runs 100 iterations with random item counts and quantities
 */
test('bulk set stock sets mixed products and variations to exact quantity', function () {
    $faker = \Faker\Factory::create();
    
    // Run 100 iterations
    for ($iteration = 0; $iteration < 100; $iteration++) {
        $items = collect();
        
        // Create random number of simple products (0-5)
        $productCount = $faker->numberBetween(0, 5);
        for ($i = 0; $i < $productCount; $i++) {
            $initialQuantity = $faker->numberBetween(0, 1000);
            $product = Product::create([
                'name' => 'Mixed Product ' . $iteration . '-' . $i,
                'slug' => 'mixed-product-' . $faker->unique()->randomNumber(8),
                'description' => $faker->paragraph(),
                'price' => $faker->randomFloat(2, 10, 1000),
                'sku' => 'MIXED-' . $faker->unique()->randomNumber(8),
                'quantity' => $initialQuantity,
                'category_id' => $this->category->id,
                'is_active' => true,
            ]);
            $items->push($product);
        }
        
        // Create a variable product with variations (0-5 variations)
        $variationCount = $faker->numberBetween(0, 5);
        if ($variationCount > 0) {
            $parentProduct = Product::create([
                'name' => 'Variable Product ' . $iteration,
                'slug' => 'variable-product-' . $faker->unique()->randomNumber(8),
                'description' => $faker->paragraph(),
                'price' => $faker->randomFloat(2, 10, 1000),
                'sku' => 'VAR-PARENT-' . $faker->unique()->randomNumber(8),
                'quantity' => 0,
                'product_type' => 'variable',
                'category_id' => $this->category->id,
                'is_active' => true,
            ]);
            
            for ($i = 0; $i < $variationCount; $i++) {
                $initialQuantity = $faker->numberBetween(0, 1000);
                $variation = ProductVariation::create([
                    'product_id' => $parentProduct->id,
                    'sku' => 'VAR-MIXED-' . $faker->unique()->randomNumber(8),
                    'name' => 'Mixed Variation ' . $iteration . '-' . $i,
                    'price' => $faker->randomFloat(2, 10, 1000),
                    'quantity' => $initialQuantity,
                    'is_active' => true,
                ]);
                $items->push($variation);
            }
        }
        
        // Skip iteration if no items were created
        if ($items->isEmpty()) {
            continue;
        }
        
        // Generate a random quantity to set (non-negative)
        $setQuantity = $faker->numberBetween(0, 10000);
        
        // Perform bulk set stock
        $this->stockService->bulkSetStock($items, $setQuantity, 'Bulk set mixed test iteration ' . $iteration);
        
        // Verify ALL items now have exactly the set quantity
        foreach ($items as $item) {
            $item->refresh();
            expect($item->quantity)->toBe($setQuantity);
        }
    }
});
