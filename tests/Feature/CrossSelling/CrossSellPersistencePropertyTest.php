<?php

/**
 * **Feature: cross-selling, Property 3: Cross-Sell IDs Persistence Round-Trip**
 * 
 * *For any* valid array of product IDs saved as cross-sell products for a product,
 * retrieving the product's cross_sell_products field SHALL return the same array of IDs.
 * 
 * **Validates: Requirements 3.5**
 */

use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create a category for products
    $this->category = Category::create([
        'name' => 'Test Category',
        'slug' => 'test-category',
        'is_active' => true,
    ]);
    
    // Create an admin user
    $this->admin = User::create([
        'name' => 'Admin User',
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
        'is_admin' => true,
        'is_active' => true,
    ]);
});

/**
 * Property test: Cross-sell product IDs are persisted and retrieved correctly
 * Runs 100 iterations with random product selections
 */
test('cross-sell product IDs round-trip correctly', function () {
    $faker = \Faker\Factory::create();
    
    // Create a pool of products to select from
    $productPool = [];
    for ($i = 0; $i < 15; $i++) {
        $productPool[] = Product::create([
            'name' => 'Pool Product ' . $i,
            'slug' => 'pool-product-' . $i . '-' . $faker->unique()->randomNumber(5),
            'description' => $faker->paragraph(),
            'price' => $faker->randomFloat(2, 10, 1000),
            'sku' => 'POOL-' . $faker->unique()->randomNumber(8),
            'quantity' => $faker->numberBetween(0, 100),
            'category_id' => $this->category->id,
            'is_active' => true,
        ]);
    }
    
    // Create the main product to test with
    $mainProduct = Product::create([
        'name' => 'Main Product',
        'slug' => 'main-product-' . $faker->unique()->randomNumber(5),
        'description' => $faker->paragraph(),
        'price' => $faker->randomFloat(2, 10, 1000),
        'sku' => 'MAIN-' . $faker->unique()->randomNumber(8),
        'quantity' => $faker->numberBetween(0, 100),
        'category_id' => $this->category->id,
        'is_active' => true,
    ]);
    
    // Run 100 iterations with different cross-sell selections
    for ($iteration = 0; $iteration < 100; $iteration++) {
        // Generate random selection of cross-sell products (0 to 8 products)
        $numCrossSell = $faker->numberBetween(0, 8);
        $selectedProducts = $faker->randomElements($productPool, $numCrossSell);
        $crossSellIds = array_map(fn($p) => $p->id, $selectedProducts);
        
        // Save cross-sell products via controller update
        $response = $this->actingAs($this->admin)
            ->put(route('admin.products.update', $mainProduct), [
                'name' => $mainProduct->name,
                'description' => $mainProduct->description,
                'price' => $mainProduct->price,
                'sku' => $mainProduct->sku,
                'quantity' => $mainProduct->quantity,
                'category_id' => $mainProduct->category_id,
                'cross_sell_products' => $crossSellIds,
            ]);
        
        $response->assertRedirect();
        
        // Refresh the product from database
        $mainProduct->refresh();
        
        // Verify the cross_sell_products field contains the same IDs
        $retrievedIds = $mainProduct->cross_sell_products ?? [];
        
        // Sort both arrays for comparison (order may differ)
        sort($crossSellIds);
        sort($retrievedIds);
        
        expect($retrievedIds)->toBe($crossSellIds);
    }
});

/**
 * Property test: Empty cross-sell array persists correctly
 */
test('empty cross-sell array persists correctly', function () {
    $faker = \Faker\Factory::create();
    
    // Create some products first
    $productPool = [];
    for ($i = 0; $i < 5; $i++) {
        $productPool[] = Product::create([
            'name' => 'Pool Product ' . $i,
            'slug' => 'pool-product-' . $i . '-' . $faker->unique()->randomNumber(5),
            'description' => $faker->paragraph(),
            'price' => $faker->randomFloat(2, 10, 1000),
            'sku' => 'POOL-' . $faker->unique()->randomNumber(8),
            'quantity' => $faker->numberBetween(0, 100),
            'category_id' => $this->category->id,
            'is_active' => true,
        ]);
    }
    
    // Create main product with some cross-sell products
    $mainProduct = Product::create([
        'name' => 'Main Product',
        'slug' => 'main-product-' . $faker->unique()->randomNumber(5),
        'description' => $faker->paragraph(),
        'price' => $faker->randomFloat(2, 10, 1000),
        'sku' => 'MAIN-' . $faker->unique()->randomNumber(8),
        'quantity' => $faker->numberBetween(0, 100),
        'category_id' => $this->category->id,
        'is_active' => true,
        'cross_sell_products' => array_map(fn($p) => $p->id, $productPool),
    ]);
    
    // Run 100 iterations clearing and setting cross-sell products
    for ($iteration = 0; $iteration < 100; $iteration++) {
        // Clear cross-sell products
        $response = $this->actingAs($this->admin)
            ->put(route('admin.products.update', $mainProduct), [
                'name' => $mainProduct->name,
                'description' => $mainProduct->description,
                'price' => $mainProduct->price,
                'sku' => $mainProduct->sku,
                'quantity' => $mainProduct->quantity,
                'category_id' => $mainProduct->category_id,
                'cross_sell_products' => [],
            ]);
        
        $response->assertRedirect();
        $mainProduct->refresh();
        
        expect($mainProduct->cross_sell_products)->toBe([]);
    }
});

/**
 * Property test: Cross-sell IDs exclude the product itself
 */
test('cross-sell IDs exclude the product itself', function () {
    $faker = \Faker\Factory::create();
    
    // Create products
    $products = [];
    for ($i = 0; $i < 10; $i++) {
        $products[] = Product::create([
            'name' => 'Product ' . $i,
            'slug' => 'product-' . $i . '-' . $faker->unique()->randomNumber(5),
            'description' => $faker->paragraph(),
            'price' => $faker->randomFloat(2, 10, 1000),
            'sku' => 'PROD-' . $faker->unique()->randomNumber(8),
            'quantity' => $faker->numberBetween(0, 100),
            'category_id' => $this->category->id,
            'is_active' => true,
        ]);
    }
    
    // Run 100 iterations
    for ($iteration = 0; $iteration < 100; $iteration++) {
        $mainProduct = $faker->randomElement($products);
        
        // Try to include the product itself in cross-sell
        $crossSellIds = array_map(fn($p) => $p->id, $products);
        
        $response = $this->actingAs($this->admin)
            ->put(route('admin.products.update', $mainProduct), [
                'name' => $mainProduct->name,
                'description' => $mainProduct->description,
                'price' => $mainProduct->price,
                'sku' => $mainProduct->sku,
                'quantity' => $mainProduct->quantity,
                'category_id' => $mainProduct->category_id,
                'cross_sell_products' => $crossSellIds,
            ]);
        
        $response->assertRedirect();
        $mainProduct->refresh();
        
        // The product's own ID should not be in cross_sell_products
        expect($mainProduct->cross_sell_products)->not->toContain($mainProduct->id);
    }
});
