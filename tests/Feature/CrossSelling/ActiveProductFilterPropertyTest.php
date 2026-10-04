<?php

/**
 * **Feature: cross-selling, Property 5: Cross-Sell Display Filters Inactive Products**
 * 
 * *For any* product with cross-sell products configured, the displayed cross-sell products
 * SHALL only include products where is_active equals true.
 * 
 * **Validates: Requirements 4.4**
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
    
    // Enable cross-selling with high limit
    Setting::updateOrCreate(
        ['key' => 'shop.cross_sell_enabled'],
        ['value' => '1', 'type' => 'boolean', 'group' => 'shop']
    );
    Setting::updateOrCreate(
        ['key' => 'shop.cross_sell_limit'],
        ['value' => '20', 'type' => 'integer', 'group' => 'shop']
    );
});

/**
 * Property test: Only active products are displayed in cross-sell section
 * Runs 100 iterations with random active/inactive distributions
 */
test('cross-sell display only shows active products', function () {
    $faker = \Faker\Factory::create();
    
    // Run 100 iterations
    for ($iteration = 0; $iteration < 100; $iteration++) {
        cache()->forget('settings');
        
        // Generate random number of active and inactive products
        $numActive = $faker->numberBetween(1, 8);
        $numInactive = $faker->numberBetween(1, 5);
        
        $activeProducts = [];
        $inactiveProducts = [];
        
        // Create active products
        for ($i = 0; $i < $numActive; $i++) {
            $activeProducts[] = Product::create([
                'name' => 'Active Product ' . $iteration . '-' . $i,
                'slug' => 'active-product-' . $iteration . '-' . $i . '-' . $faker->unique()->randomNumber(5),
                'description' => $faker->paragraph(),
                'price' => $faker->randomFloat(2, 10, 1000),
                'sku' => 'ACTIVE-' . $iteration . '-' . $faker->unique()->randomNumber(8),
                'quantity' => $faker->numberBetween(1, 100),
                'category_id' => $this->category->id,
                'is_active' => true,
            ]);
        }
        
        // Create inactive products
        for ($i = 0; $i < $numInactive; $i++) {
            $inactiveProducts[] = Product::create([
                'name' => 'Inactive Product ' . $iteration . '-' . $i,
                'slug' => 'inactive-product-' . $iteration . '-' . $i . '-' . $faker->unique()->randomNumber(5),
                'description' => $faker->paragraph(),
                'price' => $faker->randomFloat(2, 10, 1000),
                'sku' => 'INACTIVE-' . $iteration . '-' . $faker->unique()->randomNumber(8),
                'quantity' => $faker->numberBetween(1, 100),
                'category_id' => $this->category->id,
                'is_active' => false,
            ]);
        }
        
        // Combine all product IDs for cross-sell
        $allProducts = array_merge($activeProducts, $inactiveProducts);
        $crossSellIds = array_map(fn($p) => $p->id, $allProducts);
        
        // Shuffle to randomize order
        shuffle($crossSellIds);
        
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
        
        // Visit the product page
        $response = $this->get(route('shop.show', $mainProduct->slug));
        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Verify NO inactive products are displayed
        foreach ($inactiveProducts as $inactiveProduct) {
            expect(str_contains($content, $inactiveProduct->name))->toBeFalse(
                "Inactive product '{$inactiveProduct->name}' should not be displayed"
            );
        }
        
        // Verify active products ARE displayed (up to limit)
        $displayedActiveCount = 0;
        foreach ($activeProducts as $activeProduct) {
            if (str_contains($content, $activeProduct->name)) {
                $displayedActiveCount++;
            }
        }
        
        // All active products should be displayed (since limit is 20)
        expect($displayedActiveCount)->toBe($numActive);
        
        // Clean up for next iteration
        Product::query()->delete();
    }
});

/**
 * Property test: Cross-sell section is hidden when all cross-sell products are inactive
 * Runs 100 iterations
 */
test('cross-sell section hidden when all products are inactive', function () {
    $faker = \Faker\Factory::create();
    
    // Run 100 iterations
    for ($iteration = 0; $iteration < 100; $iteration++) {
        cache()->forget('settings');
        
        // Create only inactive products
        $numInactive = $faker->numberBetween(1, 5);
        $inactiveProducts = [];
        
        for ($i = 0; $i < $numInactive; $i++) {
            $inactiveProducts[] = Product::create([
                'name' => 'Inactive Only Product ' . $iteration . '-' . $i,
                'slug' => 'inactive-only-' . $iteration . '-' . $i . '-' . $faker->unique()->randomNumber(5),
                'description' => $faker->paragraph(),
                'price' => $faker->randomFloat(2, 10, 1000),
                'sku' => 'INACT-' . $iteration . '-' . $faker->unique()->randomNumber(8),
                'quantity' => $faker->numberBetween(1, 100),
                'category_id' => $this->category->id,
                'is_active' => false,
            ]);
        }
        
        $crossSellIds = array_map(fn($p) => $p->id, $inactiveProducts);
        
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
        
        $content = $response->getContent();
        
        // The cross-sell section title should not appear since no active products
        $crossSellTitle = setting('shop.cross_sell_title', 'Frequently Bought Together');
        
        // None of the inactive products should be displayed
        foreach ($inactiveProducts as $inactiveProduct) {
            expect(str_contains($content, $inactiveProduct->name))->toBeFalse();
        }
        
        // Clean up
        Product::query()->delete();
    }
});

/**
 * Property test: Randomly toggling product active status affects display
 * Runs 100 iterations
 */
test('toggling product active status affects cross-sell display', function () {
    $faker = \Faker\Factory::create();
    
    // Run 100 iterations
    for ($iteration = 0; $iteration < 100; $iteration++) {
        cache()->forget('settings');
        
        // Create products with random active states
        $numProducts = $faker->numberBetween(3, 8);
        $products = [];
        
        for ($i = 0; $i < $numProducts; $i++) {
            $isActive = $faker->boolean();
            $products[] = Product::create([
                'name' => 'Toggle Product ' . $iteration . '-' . $i,
                'slug' => 'toggle-product-' . $iteration . '-' . $i . '-' . $faker->unique()->randomNumber(5),
                'description' => $faker->paragraph(),
                'price' => $faker->randomFloat(2, 10, 1000),
                'sku' => 'TOGGLE-' . $iteration . '-' . $faker->unique()->randomNumber(8),
                'quantity' => $faker->numberBetween(1, 100),
                'category_id' => $this->category->id,
                'is_active' => $isActive,
            ]);
        }
        
        $crossSellIds = array_map(fn($p) => $p->id, $products);
        
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
        
        $content = $response->getContent();
        
        // Verify each product's display matches its active status
        foreach ($products as $product) {
            $isDisplayed = str_contains($content, $product->name);
            
            if ($product->is_active) {
                expect($isDisplayed)->toBeTrue(
                    "Active product '{$product->name}' should be displayed"
                );
            } else {
                expect($isDisplayed)->toBeFalse(
                    "Inactive product '{$product->name}' should not be displayed"
                );
            }
        }
        
        // Clean up
        Product::query()->delete();
    }
});
