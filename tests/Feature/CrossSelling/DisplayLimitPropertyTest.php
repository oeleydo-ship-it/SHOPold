<?php

/**
 * **Feature: cross-selling, Property 1: Display Limit Enforcement**
 * 
 * *For any* configured cross-sell display limit N and any product with M cross-sell products configured,
 * the number of cross-sell products displayed on the product page SHALL be min(N, M, active_count)
 * where active_count is the number of active cross-sell products.
 * 
 * **Validates: Requirements 2.2**
 */

use App\Models\Product;
use App\Models\Category;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create a category for products
    $this->category = Category::create([
        'name' => 'Test Category',
        'slug' => 'test-category',
        'is_active' => true,
    ]);
    
    // Create a separate category for cross-sell products to avoid related products overlap
    $this->crossSellCategory = Category::create([
        'name' => 'Cross-Sell Category',
        'slug' => 'cross-sell-category',
        'is_active' => true,
    ]);
    
    // Enable cross-selling
    Setting::updateOrCreate(
        ['key' => 'shop.cross_sell_enabled'],
        ['value' => '1', 'type' => 'boolean', 'group' => 'shop']
    );
});

/**
 * Property test: Display limit is enforced correctly
 * Runs 100 iterations with random limits and product counts
 */
test('cross-sell display respects configured limit', function () {
    $faker = \Faker\Factory::create();
    
    // Run 100 iterations with different configurations
    for ($iteration = 0; $iteration < 100; $iteration++) {
        // Generate random limit between 1 and 10
        $limit = $faker->numberBetween(1, 10);
        
        // Set the cross-sell limit setting
        Setting::updateOrCreate(
            ['key' => 'shop.cross_sell_limit'],
            ['value' => (string) $limit, 'type' => 'integer', 'group' => 'shop']
        );
        
        // Clear settings cache (both Laravel cache and static cache)
        Setting::clearCache();
        
        // Generate random number of cross-sell products (0 to 15)
        $numCrossSellProducts = $faker->numberBetween(0, 15);
        
        // Use unique marker for this iteration's cross-sell products
        $uniqueMarker = 'XSELL_' . $iteration . '_' . uniqid();
        
        // Create cross-sell products (all active) in separate category
        $crossSellProducts = [];
        for ($i = 0; $i < $numCrossSellProducts; $i++) {
            $crossSellProducts[] = Product::create([
                'name' => $uniqueMarker . '_Product_' . $i,
                'slug' => 'cross-sell-product-' . $iteration . '-' . $i . '-' . $faker->unique()->randomNumber(5),
                'description' => $faker->paragraph(),
                'price' => $faker->randomFloat(2, 10, 1000),
                'sku' => 'CS-' . $iteration . '-' . $faker->unique()->randomNumber(8),
                'quantity' => $faker->numberBetween(1, 100),
                'category_id' => $this->crossSellCategory->id,
                'is_active' => true,
            ]);
        }
        
        $crossSellIds = array_map(fn($p) => $p->id, $crossSellProducts);
        
        // Create main product with cross-sell products
        $mainProduct = Product::create([
            'name' => 'Main Product ' . $iteration,
            'slug' => 'main-product-' . $iteration . '-' . $faker->unique()->randomNumber(5),
            'description' => $faker->paragraph(),
            'price' => $faker->randomFloat(2, 10, 1000),
            'sku' => 'MAIN-' . $iteration . '-' . $faker->unique()->randomNumber(8),
            'quantity' => $faker->numberBetween(1, 100),
            'category_id' => $this->category->id,
            'is_active' => true,
            'cross_sell_products' => $crossSellIds,
        ]);
        
        // Visit the product page
        $response = $this->get(route('shop.show', $mainProduct->slug));
        $response->assertStatus(200);
        
        // Calculate expected count: min(limit, total_cross_sell_products)
        $expectedCount = min($limit, $numCrossSellProducts);
        
        // Count cross-sell products in response by unique marker
        $content = $response->getContent();
        
        // Count occurrences of the unique marker in the content
        $actualCount = substr_count($content, $uniqueMarker);
        
        expect($actualCount)->toBeLessThanOrEqual($limit, 
            "Iteration $iteration: Expected at most $limit products, got $actualCount (configured: $numCrossSellProducts cross-sell products)");
        expect($actualCount)->toBe($expectedCount,
            "Iteration $iteration: Expected exactly $expectedCount products, got $actualCount");
        
        // Clean up for next iteration
        Product::query()->delete();
    }
});

/**
 * Property test: Display limit with mixed active/inactive products
 * Runs 100 iterations with random active/inactive distributions
 */
test('cross-sell display limit considers only active products', function () {
    $faker = \Faker\Factory::create();
    
    // Run 100 iterations
    for ($iteration = 0; $iteration < 100; $iteration++) {
        // Generate random limit between 1 and 8
        $limit = $faker->numberBetween(1, 8);
        
        Setting::updateOrCreate(
            ['key' => 'shop.cross_sell_limit'],
            ['value' => (string) $limit, 'type' => 'integer', 'group' => 'shop']
        );
        
        // Clear settings cache (both Laravel cache and static cache)
        Setting::clearCache();
        
        // Generate random number of active and inactive products
        $numActive = $faker->numberBetween(0, 10);
        $numInactive = $faker->numberBetween(0, 5);
        
        // Use unique markers for this iteration
        $activeMarker = 'ACTIVE_' . $iteration . '_' . uniqid();
        $inactiveMarker = 'INACTIVE_' . $iteration . '_' . uniqid();
        
        $crossSellProducts = [];
        
        // Create active products
        for ($i = 0; $i < $numActive; $i++) {
            $crossSellProducts[] = Product::create([
                'name' => $activeMarker . '_Product_' . $i,
                'slug' => 'active-cs-' . $iteration . '-' . $i . '-' . $faker->unique()->randomNumber(5),
                'description' => $faker->paragraph(),
                'price' => $faker->randomFloat(2, 10, 1000),
                'sku' => 'ACS-' . $iteration . '-' . $faker->unique()->randomNumber(8),
                'quantity' => $faker->numberBetween(1, 100),
                'category_id' => $this->crossSellCategory->id,
                'is_active' => true,
            ]);
        }
        
        // Create inactive products
        for ($i = 0; $i < $numInactive; $i++) {
            $crossSellProducts[] = Product::create([
                'name' => $inactiveMarker . '_Product_' . $i,
                'slug' => 'inactive-cs-' . $iteration . '-' . $i . '-' . $faker->unique()->randomNumber(5),
                'description' => $faker->paragraph(),
                'price' => $faker->randomFloat(2, 10, 1000),
                'sku' => 'ICS-' . $iteration . '-' . $faker->unique()->randomNumber(8),
                'quantity' => $faker->numberBetween(1, 100),
                'category_id' => $this->crossSellCategory->id,
                'is_active' => false,
            ]);
        }
        
        $crossSellIds = array_map(fn($p) => $p->id, $crossSellProducts);
        
        // Create main product
        $mainProduct = Product::create([
            'name' => 'Main Product ' . $iteration,
            'slug' => 'main-product-' . $iteration . '-' . $faker->unique()->randomNumber(5),
            'description' => $faker->paragraph(),
            'price' => $faker->randomFloat(2, 10, 1000),
            'sku' => 'MAIN-' . $iteration . '-' . $faker->unique()->randomNumber(8),
            'quantity' => $faker->numberBetween(1, 100),
            'category_id' => $this->category->id,
            'is_active' => true,
            'cross_sell_products' => $crossSellIds,
        ]);
        
        $response = $this->get(route('shop.show', $mainProduct->slug));
        $response->assertStatus(200);
        
        // Expected: min(limit, active_count)
        $expectedCount = min($limit, $numActive);
        
        $content = $response->getContent();
        
        // Count active products displayed
        $displayedActiveCount = substr_count($content, $activeMarker);
        
        // Count inactive products displayed (should be 0)
        $displayedInactiveCount = substr_count($content, $inactiveMarker);
        
        expect($displayedInactiveCount)->toBe(0,
            "Iteration $iteration: Inactive products should not be displayed");
        expect($displayedActiveCount)->toBeLessThanOrEqual($limit,
            "Iteration $iteration: Expected at most $limit active products, got $displayedActiveCount");
        expect($displayedActiveCount)->toBe($expectedCount,
            "Iteration $iteration: Expected exactly $expectedCount active products, got $displayedActiveCount");
        
        // Clean up
        Product::query()->delete();
    }
});
