<?php

/**
 * **Feature: cross-selling, Property 2: Search Excludes Current and Inactive Products**
 * 
 * *For any* product search query in the cross-sell selector and any current product being edited,
 * the search results SHALL NOT include the current product AND SHALL only include products
 * where is_active equals true.
 * 
 * **Validates: Requirements 3.2**
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
 * Property test: Search results never include the excluded product
 * Runs 100 iterations with random product data
 */
test('search results never include the excluded product', function () {
    $faker = \Faker\Factory::create();
    
    // Create products once, then test with different exclusions
    $products = [];
    for ($i = 0; $i < 10; $i++) {
        $products[] = Product::create([
            'name' => 'Product ' . $i . ' ' . $faker->word(),
            'slug' => 'product-' . $i . '-' . $faker->unique()->randomNumber(5),
            'description' => $faker->paragraph(),
            'price' => $faker->randomFloat(2, 10, 1000),
            'sku' => 'SKU-' . $faker->unique()->randomNumber(8),
            'quantity' => $faker->numberBetween(0, 100),
            'category_id' => $this->category->id,
            'is_active' => true,
        ]);
    }
    
    // Run 100 iterations testing different exclusions and queries
    for ($iteration = 0; $iteration < 100; $iteration++) {
        $excludedProduct = $faker->randomElement($products);
        $query = $faker->randomElement(['', 'Product', $excludedProduct->sku]);
        
        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.products.search', [
                'q' => $query,
                'exclude' => $excludedProduct->id,
            ]));
        
        $response->assertStatus(200);
        $resultIds = array_column($response->json(), 'id');
        
        expect($resultIds)->not->toContain($excludedProduct->id);
    }
});

/**
 * Property test: Search results only include active products
 * Runs 100 iterations with random product data
 */
test('search results only include active products', function () {
    $faker = \Faker\Factory::create();
    
    // Create mix of active and inactive products
    $activeProducts = [];
    $inactiveProducts = [];
    
    for ($i = 0; $i < 5; $i++) {
        $activeProducts[] = Product::create([
            'name' => 'Active Product ' . $i,
            'slug' => 'active-product-' . $i . '-' . $faker->unique()->randomNumber(5),
            'description' => $faker->paragraph(),
            'price' => $faker->randomFloat(2, 10, 1000),
            'sku' => 'ACTIVE-' . $faker->unique()->randomNumber(8),
            'quantity' => $faker->numberBetween(0, 100),
            'category_id' => $this->category->id,
            'is_active' => true,
        ]);
        
        $inactiveProducts[] = Product::create([
            'name' => 'Inactive Product ' . $i,
            'slug' => 'inactive-product-' . $i . '-' . $faker->unique()->randomNumber(5),
            'description' => $faker->paragraph(),
            'price' => $faker->randomFloat(2, 10, 1000),
            'sku' => 'INACTIVE-' . $faker->unique()->randomNumber(8),
            'quantity' => $faker->numberBetween(0, 100),
            'category_id' => $this->category->id,
            'is_active' => false,
        ]);
    }
    
    // Run 100 iterations with different queries
    for ($iteration = 0; $iteration < 100; $iteration++) {
        $query = $faker->randomElement(['', 'Product', 'Active', 'Inactive']);
        
        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.products.search', ['q' => $query]));
        
        $response->assertStatus(200);
        $resultIds = array_column($response->json(), 'id');
        
        // No inactive product should appear
        foreach ($inactiveProducts as $inactiveProduct) {
            expect($resultIds)->not->toContain($inactiveProduct->id);
        }
        
        // All returned products should be active
        foreach ($resultIds as $resultId) {
            $product = Product::find($resultId);
            expect($product->is_active)->toBeTrue();
        }
    }
});

/**
 * Property test: Combined exclusion - both current product and inactive products are excluded
 * Runs 100 iterations with random product data
 */
test('search excludes both current product and inactive products', function () {
    $faker = \Faker\Factory::create();
    
    // Create mix of active and inactive products
    $allProducts = [];
    
    for ($i = 0; $i < 10; $i++) {
        $allProducts[] = Product::create([
            'name' => 'Test Product ' . $i,
            'slug' => 'test-product-' . $i . '-' . $faker->unique()->randomNumber(5),
            'description' => $faker->paragraph(),
            'price' => $faker->randomFloat(2, 10, 1000),
            'sku' => 'TEST-' . $faker->unique()->randomNumber(8),
            'quantity' => $faker->numberBetween(0, 100),
            'category_id' => $this->category->id,
            'is_active' => $i < 7, // First 7 active, last 3 inactive
        ]);
    }
    
    $activeProducts = array_filter($allProducts, fn($p) => $p->is_active);
    $inactiveProducts = array_filter($allProducts, fn($p) => !$p->is_active);
    
    // Run 100 iterations
    for ($iteration = 0; $iteration < 100; $iteration++) {
        $excludedProduct = $faker->randomElement(array_values($activeProducts));
        
        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.products.search', [
                'q' => '',
                'exclude' => $excludedProduct->id,
            ]));
        
        $response->assertStatus(200);
        $resultIds = array_column($response->json(), 'id');
        
        // Excluded product should not appear
        expect($resultIds)->not->toContain($excludedProduct->id);
        
        // No inactive products should appear
        foreach ($inactiveProducts as $inactiveProduct) {
            expect($resultIds)->not->toContain($inactiveProduct->id);
        }
        
        // All results should be active
        foreach ($resultIds as $resultId) {
            $product = Product::find($resultId);
            expect($product->is_active)->toBeTrue();
        }
    }
});
