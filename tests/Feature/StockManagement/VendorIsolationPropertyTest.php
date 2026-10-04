<?php

/**
 * **Feature: stock-management, Property 6: Vendor Isolation**
 * 
 * *For any* vendor V, the stock management list should only contain products
 * where vendor_id equals V's ID or vendor_id is null (admin products).
 * 
 * **Validates: Requirements 6.2, 6.4**
 */

use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Enable multi-vendor feature
    Setting::set('features.multi_vendor', true);
    
    // Create a category for products
    $this->category = Category::create([
        'name' => 'Test Category',
        'slug' => 'test-category',
        'is_active' => true,
    ]);
});

/**
 * Helper function to create a vendor with user
 */
function createVendorWithUser(string $suffix): array
{
    $user = User::create([
        'name' => 'Vendor User ' . $suffix,
        'email' => 'vendor' . $suffix . '@test.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    
    $vendor = Vendor::create([
        'user_id' => $user->id,
        'store_name' => 'Store ' . $suffix,
        'slug' => 'store-' . strtolower($suffix),
        'status' => 'approved',
    ]);
    
    return ['user' => $user, 'vendor' => $vendor];
}

/**
 * Property test: Vendor stock list only shows vendor's own products
 * For any vendor V, the stock list should only contain products belonging to V
 * Runs 100 iterations with random product distributions
 */
test('vendor stock list only contains products belonging to that vendor', function () {
    $faker = \Faker\Factory::create();
    
    // Run 100 iterations
    for ($iteration = 0; $iteration < 100; $iteration++) {
        // Create 2-4 vendors
        $vendorCount = $faker->numberBetween(2, 4);
        $vendors = [];
        
        for ($v = 0; $v < $vendorCount; $v++) {
            $vendors[] = createVendorWithUser($iteration . '-' . $v);
        }
        
        // Create random products for each vendor
        $allProducts = [];
        foreach ($vendors as $index => $vendorData) {
            $productCount = $faker->numberBetween(1, 5);
            for ($p = 0; $p < $productCount; $p++) {
                $product = Product::create([
                    'name' => 'Vendor ' . $index . ' Product ' . $p . ' Iter ' . $iteration,
                    'slug' => 'vendor-' . $index . '-product-' . $p . '-' . $faker->unique()->randomNumber(8),
                    'description' => $faker->paragraph(),
                    'price' => $faker->randomFloat(2, 10, 1000),
                    'sku' => 'V' . $index . 'P' . $p . '-' . $faker->unique()->randomNumber(8),
                    'quantity' => $faker->numberBetween(0, 100),
                    'category_id' => $this->category->id,
                    'vendor_id' => $vendorData['vendor']->id,
                    'is_active' => true,
                ]);
                $allProducts[] = ['product' => $product, 'vendor_id' => $vendorData['vendor']->id];
            }
        }
        
        // Also create some admin products (no vendor)
        $adminProductCount = $faker->numberBetween(0, 3);
        for ($a = 0; $a < $adminProductCount; $a++) {
            $product = Product::create([
                'name' => 'Admin Product ' . $a . ' Iter ' . $iteration,
                'slug' => 'admin-product-' . $a . '-' . $faker->unique()->randomNumber(8),
                'description' => $faker->paragraph(),
                'price' => $faker->randomFloat(2, 10, 1000),
                'sku' => 'ADMIN' . $a . '-' . $faker->unique()->randomNumber(8),
                'quantity' => $faker->numberBetween(0, 100),
                'category_id' => $this->category->id,
                'vendor_id' => null,
                'is_active' => true,
            ]);
            $allProducts[] = ['product' => $product, 'vendor_id' => null];
        }
        
        // Pick a random vendor to test
        $testVendorIndex = $faker->numberBetween(0, $vendorCount - 1);
        $testVendorData = $vendors[$testVendorIndex];
        
        // Act as the vendor user and access stock management
        $this->actingAs($testVendorData['user']);
        $response = $this->inertia('GET', route('vendor.stock.index', [], false));

        $response->assertStatus(200);

        // Get the stock items from the page props (the page is a React page)
        $stockItems = collect($response->json('props.items.data'));
        $ownIds = collect($allProducts)
            ->filter(fn($p) => $p['vendor_id'] === $testVendorData['vendor']->id)
            ->map(fn($p) => $p['product']->id)
            ->values()
            ->all();

        // Verify: ALL items in the list belong to this vendor
        foreach ($stockItems as $item) {
            expect(in_array($item['product_id'], $ownIds, true))->toBeTrue(
                "Stock list contains product {$item['product_id']} that does not belong to vendor {$testVendorData['vendor']->id}"
            );
        }

        // Verify: Count of items matches expected vendor products
        $expectedCount = collect($allProducts)
            ->filter(fn($p) => $p['vendor_id'] === $testVendorData['vendor']->id)
            ->count();
        
        expect($stockItems->count())->toBe($expectedCount,
            "Expected {$expectedCount} products for vendor, got {$stockItems->count()}"
        );
    }
});


/**
 * Property test: Vendor cannot update stock for another vendor's product
 * For any vendor V and any product P belonging to another vendor, V should be denied access
 * Runs 100 iterations with random vendor/product combinations
 */
test('vendor cannot update stock for another vendors product', function () {
    $faker = \Faker\Factory::create();
    
    // Run 100 iterations
    for ($iteration = 0; $iteration < 100; $iteration++) {
        // Create 2 vendors
        $vendor1Data = createVendorWithUser('A' . $iteration);
        $vendor2Data = createVendorWithUser('B' . $iteration);
        
        // Create products for vendor 2
        $productCount = $faker->numberBetween(1, 5);
        $vendor2Products = [];
        
        for ($p = 0; $p < $productCount; $p++) {
            $product = Product::create([
                'name' => 'Vendor2 Product ' . $p . ' Iter ' . $iteration,
                'slug' => 'v2-product-' . $p . '-' . $faker->unique()->randomNumber(8),
                'description' => $faker->paragraph(),
                'price' => $faker->randomFloat(2, 10, 1000),
                'sku' => 'V2P' . $p . '-' . $faker->unique()->randomNumber(8),
                'quantity' => $faker->numberBetween(0, 100),
                'category_id' => $this->category->id,
                'vendor_id' => $vendor2Data['vendor']->id,
                'is_active' => true,
            ]);
            $vendor2Products[] = $product;
        }
        
        // Pick a random product from vendor 2
        $targetProduct = $faker->randomElement($vendor2Products);
        $newQuantity = $faker->numberBetween(0, 1000);
        
        // Vendor 1 tries to update vendor 2's product stock
        $response = $this->actingAs($vendor1Data['user'])
            ->post(route('vendor.stock.update'), [
                'item_id' => $targetProduct->id,
                'item_type' => 'product',
                'quantity' => $newQuantity,
                'note' => 'Unauthorized update attempt',
            ]);
        
        // Should be denied (403 Forbidden)
        $response->assertStatus(403);
        
        // Verify the product quantity was NOT changed
        $targetProduct->refresh();
        expect($targetProduct->quantity)->not->toBe($newQuantity);
    }
});

/**
 * Property test: Vendor cannot view history for another vendor's product
 * For any vendor V and any product P belonging to another vendor, V should be denied access to history
 * Runs 100 iterations with random vendor/product combinations
 */
test('vendor cannot view history for another vendors product', function () {
    $faker = \Faker\Factory::create();
    
    // Run 100 iterations
    for ($iteration = 0; $iteration < 100; $iteration++) {
        // Create 2 vendors
        $vendor1Data = createVendorWithUser('C' . $iteration);
        $vendor2Data = createVendorWithUser('D' . $iteration);
        
        // Create a product for vendor 2
        $product = Product::create([
            'name' => 'Vendor2 History Product Iter ' . $iteration,
            'slug' => 'v2-history-product-' . $faker->unique()->randomNumber(8),
            'description' => $faker->paragraph(),
            'price' => $faker->randomFloat(2, 10, 1000),
            'sku' => 'V2HP-' . $faker->unique()->randomNumber(8),
            'quantity' => $faker->numberBetween(0, 100),
            'category_id' => $this->category->id,
            'vendor_id' => $vendor2Data['vendor']->id,
            'is_active' => true,
        ]);
        
        // Vendor 1 tries to view vendor 2's product history
        $response = $this->actingAs($vendor1Data['user'])
            ->get(route('vendor.stock.history', $product));
        
        // Should be denied (403 Forbidden)
        $response->assertStatus(403);
    }
});

/**
 * Property test: Vendor can update their own products
 * For any vendor V and any product P belonging to V, the update should succeed
 * Runs 100 iterations with random quantities
 */
test('vendor can update stock for their own products', function () {
    $faker = \Faker\Factory::create();
    
    // Run 100 iterations
    for ($iteration = 0; $iteration < 100; $iteration++) {
        // Create a vendor
        $vendorData = createVendorWithUser('E' . $iteration);
        
        // Create a product for this vendor
        $initialQuantity = $faker->numberBetween(0, 100);
        $product = Product::create([
            'name' => 'Own Product Iter ' . $iteration,
            'slug' => 'own-product-' . $faker->unique()->randomNumber(8),
            'description' => $faker->paragraph(),
            'price' => $faker->randomFloat(2, 10, 1000),
            'sku' => 'OWN-' . $faker->unique()->randomNumber(8),
            'quantity' => $initialQuantity,
            'category_id' => $this->category->id,
            'vendor_id' => $vendorData['vendor']->id,
            'is_active' => true,
        ]);
        
        // Generate a new quantity
        $newQuantity = $faker->numberBetween(0, 1000);
        
        // Vendor updates their own product stock
        $response = $this->actingAs($vendorData['user'])
            ->post(route('vendor.stock.update'), [
                'item_id' => $product->id,
                'item_type' => 'product',
                'quantity' => $newQuantity,
                'note' => 'Own product update',
            ]);
        
        // Should succeed (redirect with success)
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        
        // Verify the product quantity was updated
        $product->refresh();
        expect($product->quantity)->toBe($newQuantity);
    }
});
