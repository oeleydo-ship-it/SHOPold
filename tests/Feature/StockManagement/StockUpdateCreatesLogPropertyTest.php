<?php

/**
 * **Feature: stock-management, Property 2: Stock Update Creates Log Entry**
 * 
 * *For any* stock update operation, the inventory log should contain an entry with
 * the correct quantity_before, quantity_change, and quantity_after values where
 * quantity_after = quantity_before + quantity_change.
 * 
 * **Validates: Requirements 3.3, 4.5**
 */

use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Category;
use App\Models\InventoryLog;
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
 * Property test: Stock update creates log entry for products
 * For any product stock update, a log entry is created with correct values
 * Runs 100 iterations with random quantities
 */
test('stock update creates log entry for products with correct values', function () {
    $faker = \Faker\Factory::create();
    
    // Run 100 iterations with different products and quantities
    for ($iteration = 0; $iteration < 100; $iteration++) {
        // Generate random initial quantity
        $initialQuantity = $faker->numberBetween(0, 1000);
        
        // Create a product with initial quantity
        $product = Product::create([
            'name' => 'Test Product ' . $iteration,
            'slug' => 'test-product-' . $faker->unique()->randomNumber(8),
            'description' => $faker->paragraph(),
            'price' => $faker->randomFloat(2, 10, 1000),
            'sku' => 'TEST-' . $faker->unique()->randomNumber(8),
            'quantity' => $initialQuantity,
            'category_id' => $this->category->id,
            'is_active' => true,
        ]);
        
        // Generate a random new quantity
        $newQuantity = $faker->numberBetween(0, 10000);
        $expectedChange = $newQuantity - $initialQuantity;
        
        // Count logs before update
        $logCountBefore = InventoryLog::where('product_id', $product->id)->count();
        
        // Update stock using StockService
        $this->stockService->updateStock($product, $newQuantity, 'adjust', 'Test iteration ' . $iteration);
        
        // Get the latest log entry for this product
        $log = InventoryLog::where('product_id', $product->id)
            ->whereNull('product_variation_id')
            ->latest('id')
            ->first();
        
        // Verify log entry was created
        expect($log)->not->toBeNull();
        
        // Verify log count increased by 1
        $logCountAfter = InventoryLog::where('product_id', $product->id)->count();
        expect($logCountAfter)->toBe($logCountBefore + 1);
        
        // Verify quantity_before matches initial quantity
        expect($log->quantity_before)->toBe($initialQuantity);
        
        // Verify quantity_change is correct
        expect($log->quantity_change)->toBe($expectedChange);
        
        // Verify quantity_after matches new quantity
        expect($log->quantity_after)->toBe($newQuantity);
        
        // Verify the invariant: quantity_after = quantity_before + quantity_change
        expect($log->quantity_after)->toBe($log->quantity_before + $log->quantity_change);
    }
});

/**
 * Property test: Stock update creates log entry for product variations
 * For any variation stock update, a log entry is created with correct values
 * Runs 100 iterations with random quantities
 */
test('stock update creates log entry for variations with correct values', function () {
    $faker = \Faker\Factory::create();
    
    // Create a parent product
    $product = Product::create([
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
    
    // Run 100 iterations with different variations and quantities
    for ($iteration = 0; $iteration < 100; $iteration++) {
        // Generate random initial quantity
        $initialQuantity = $faker->numberBetween(0, 1000);
        
        // Create a variation with initial quantity
        $variation = ProductVariation::create([
            'product_id' => $product->id,
            'sku' => 'VAR-CHILD-' . $faker->unique()->randomNumber(8),
            'name' => 'Test Variation ' . $iteration,
            'price' => $faker->randomFloat(2, 10, 1000),
            'quantity' => $initialQuantity,
            'is_active' => true,
        ]);
        
        // Generate a random new quantity
        $newQuantity = $faker->numberBetween(0, 10000);
        $expectedChange = $newQuantity - $initialQuantity;
        
        // Count logs before update
        $logCountBefore = InventoryLog::where('product_variation_id', $variation->id)->count();
        
        // Update stock using StockService
        $this->stockService->updateStock($variation, $newQuantity, 'adjust', 'Test variation iteration ' . $iteration);
        
        // Get the latest log entry for this variation
        $log = InventoryLog::where('product_variation_id', $variation->id)
            ->latest('id')
            ->first();
        
        // Verify log entry was created
        expect($log)->not->toBeNull();
        
        // Verify log count increased by 1
        $logCountAfter = InventoryLog::where('product_variation_id', $variation->id)->count();
        expect($logCountAfter)->toBe($logCountBefore + 1);
        
        // Verify quantity_before matches initial quantity
        expect($log->quantity_before)->toBe($initialQuantity);
        
        // Verify quantity_change is correct
        expect($log->quantity_change)->toBe($expectedChange);
        
        // Verify quantity_after matches new quantity
        expect($log->quantity_after)->toBe($newQuantity);
        
        // Verify the invariant: quantity_after = quantity_before + quantity_change
        expect($log->quantity_after)->toBe($log->quantity_before + $log->quantity_change);
        
        // Verify the log references the correct product
        expect($log->product_id)->toBe($product->id);
    }
});

/**
 * Property test: Bulk stock updates create log entries for each item
 * For any bulk update, each affected item should have a log entry
 * Runs 100 iterations with random quantities and item counts
 */
test('bulk stock updates create log entries for each item', function () {
    $faker = \Faker\Factory::create();
    
    // Run 100 iterations
    for ($iteration = 0; $iteration < 100; $iteration++) {
        // Create random number of products (1-5)
        $productCount = $faker->numberBetween(1, 5);
        $products = collect();
        $initialQuantities = [];
        
        for ($i = 0; $i < $productCount; $i++) {
            $initialQuantity = $faker->numberBetween(0, 1000);
            $product = Product::create([
                'name' => 'Bulk Product ' . $iteration . '-' . $i,
                'slug' => 'bulk-product-' . $faker->unique()->randomNumber(8),
                'description' => $faker->paragraph(),
                'price' => $faker->randomFloat(2, 10, 1000),
                'sku' => 'BULK-' . $faker->unique()->randomNumber(8),
                'quantity' => $initialQuantity,
                'category_id' => $this->category->id,
                'is_active' => true,
            ]);
            $products->push($product);
            $initialQuantities[$product->id] = $initialQuantity;
        }
        
        // Generate a random quantity to set
        $setQuantity = $faker->numberBetween(0, 10000);
        
        // Perform bulk set stock
        $this->stockService->bulkSetStock($products, $setQuantity, 'Bulk test iteration ' . $iteration);
        
        // Verify each product has a log entry with correct values
        foreach ($products as $product) {
            $log = InventoryLog::where('product_id', $product->id)
                ->whereNull('product_variation_id')
                ->latest('id')
                ->first();
            
            // Verify log entry was created
            expect($log)->not->toBeNull();
            
            // Verify quantity_before matches initial quantity
            expect($log->quantity_before)->toBe($initialQuantities[$product->id]);
            
            // Verify quantity_after matches set quantity
            expect($log->quantity_after)->toBe($setQuantity);
            
            // Verify the invariant: quantity_after = quantity_before + quantity_change
            expect($log->quantity_after)->toBe($log->quantity_before + $log->quantity_change);
        }
    }
});
