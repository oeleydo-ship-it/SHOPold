<?php

/**
 * **Feature: stock-management, Property 1: Stock Update Round-Trip**
 * 
 * *For any* product or variation and any valid quantity N, updating the stock to N
 * and then reading the stock should return exactly N.
 * 
 * **Validates: Requirements 3.2**
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
 * Property test: Stock update round-trip for products
 * For any product and any valid quantity N, updating stock to N then reading returns N
 * Runs 100 iterations with random quantities
 */
test('stock update round-trip for products', function () {
    $faker = \Faker\Factory::create();
    
    // Create a product to test with
    $product = Product::create([
        'name' => 'Test Product',
        'slug' => 'test-product-' . $faker->unique()->randomNumber(5),
        'description' => $faker->paragraph(),
        'price' => $faker->randomFloat(2, 10, 1000),
        'sku' => 'TEST-' . $faker->unique()->randomNumber(8),
        'quantity' => $faker->numberBetween(0, 100),
        'category_id' => $this->category->id,
        'is_active' => true,
    ]);
    
    // Run 100 iterations with different quantities
    for ($iteration = 0; $iteration < 100; $iteration++) {
        // Generate a random valid quantity (non-negative integer)
        $newQuantity = $faker->numberBetween(0, 10000);
        
        // Update stock using StockService
        $this->stockService->updateStock($product, $newQuantity, 'adjust', 'Test update iteration ' . $iteration);
        
        // Refresh the product from database
        $product->refresh();
        
        // Verify the quantity matches what we set
        expect($product->quantity)->toBe($newQuantity);
    }
});

/**
 * Property test: Stock update round-trip for product variations
 * For any variation and any valid quantity N, updating stock to N then reading returns N
 * Runs 100 iterations with random quantities
 */
test('stock update round-trip for product variations', function () {
    $faker = \Faker\Factory::create();
    
    // Create a product with variations
    $product = Product::create([
        'name' => 'Variable Product',
        'slug' => 'variable-product-' . $faker->unique()->randomNumber(5),
        'description' => $faker->paragraph(),
        'price' => $faker->randomFloat(2, 10, 1000),
        'sku' => 'VAR-' . $faker->unique()->randomNumber(8),
        'quantity' => 0,
        'product_type' => 'variable',
        'category_id' => $this->category->id,
        'is_active' => true,
    ]);
    
    // Create a variation
    $variation = ProductVariation::create([
        'product_id' => $product->id,
        'sku' => 'VAR-CHILD-' . $faker->unique()->randomNumber(8),
        'name' => 'Test Variation',
        'price' => $faker->randomFloat(2, 10, 1000),
        'quantity' => $faker->numberBetween(0, 100),
        'is_active' => true,
    ]);
    
    // Run 100 iterations with different quantities
    for ($iteration = 0; $iteration < 100; $iteration++) {
        // Generate a random valid quantity (non-negative integer)
        $newQuantity = $faker->numberBetween(0, 10000);
        
        // Update stock using StockService
        $this->stockService->updateStock($variation, $newQuantity, 'adjust', 'Test variation update iteration ' . $iteration);
        
        // Refresh the variation from database
        $variation->refresh();
        
        // Verify the quantity matches what we set
        expect($variation->quantity)->toBe($newQuantity);
    }
});

/**
 * Property test: Stock update round-trip with various update types
 * For any product and any valid quantity N, updating stock with any type returns N
 * Runs 100 iterations with random quantities and types
 */
test('stock update round-trip with various update types', function () {
    $faker = \Faker\Factory::create();
    
    $updateTypes = ['add', 'remove', 'adjust', 'restock', 'return'];
    
    // Create a product to test with
    $product = Product::create([
        'name' => 'Test Product Types',
        'slug' => 'test-product-types-' . $faker->unique()->randomNumber(5),
        'description' => $faker->paragraph(),
        'price' => $faker->randomFloat(2, 10, 1000),
        'sku' => 'TYPE-' . $faker->unique()->randomNumber(8),
        'quantity' => $faker->numberBetween(0, 100),
        'category_id' => $this->category->id,
        'is_active' => true,
    ]);
    
    // Run 100 iterations with different quantities and types
    for ($iteration = 0; $iteration < 100; $iteration++) {
        // Generate a random valid quantity (non-negative integer)
        $newQuantity = $faker->numberBetween(0, 10000);
        
        // Pick a random update type
        $type = $faker->randomElement($updateTypes);
        
        // Update stock using StockService
        $this->stockService->updateStock($product, $newQuantity, $type, 'Test type ' . $type . ' iteration ' . $iteration);
        
        // Refresh the product from database
        $product->refresh();
        
        // Verify the quantity matches what we set regardless of type
        expect($product->quantity)->toBe($newQuantity);
    }
});
