<?php

use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('product show page includes variation data with images', function () {
    // Create a category
    $category = Category::factory()->create();
    
    // Create a variable product
    $product = Product::factory()->create([
        'product_type' => 'variable',
        'category_id' => $category->id,
        'is_active' => true,
    ]);
    
    // Create color attribute
    $colorAttribute = Attribute::factory()->create([
        'name' => 'Color',
        'type' => 'color'
    ]);
    
    // Create attribute values
    $redValue = AttributeValue::factory()->create([
        'attribute_id' => $colorAttribute->id,
        'value' => 'Red',
        'color_code' => '#ff0000'
    ]);
    
    $blueValue = AttributeValue::factory()->create([
        'attribute_id' => $colorAttribute->id,
        'value' => 'Blue',
        'color_code' => '#0000ff'
    ]);
    
    // Create variations with images
    $redVariation = ProductVariation::factory()->create([
        'product_id' => $product->id,
        'sku' => 'PROD-RED',
        'price' => 19.99,
        'quantity' => 10,
        'image' => 'products/red-variant.jpg',
        'is_active' => true,
    ]);
    
    $blueVariation = ProductVariation::factory()->create([
        'product_id' => $product->id,
        'sku' => 'PROD-BLUE',
        'price' => 19.99,
        'quantity' => 5,
        'image' => 'products/blue-variant.jpg',
        'is_active' => true,
    ]);
    
    // Associate attribute values with variations
    $redVariation->attributeValues()->attach($redValue->id, ['attribute_id' => $colorAttribute->id]);
    $blueVariation->attributeValues()->attach($blueValue->id, ['attribute_id' => $colorAttribute->id]);
    
    // Visit the product page
    $response = $this->get(route('shop.show', $product->slug));
    
    $response->assertStatus(200);
    
    // Check that variation data is included in the page
    $response->assertSee('productVariations');
    
    // Check that variation images are included
    $response->assertSee('red-variant.jpg');
    $response->assertSee('blue-variant.jpg');
    
    // Check that variation selector is present
    $response->assertSee('variationSelector()');
    
    // Check that image update functionality is present
    $response->assertSee('variation-changed');
    $response->assertSee('updateImageForVariation');
});

test('product show page handles variations without images', function () {
    // Create a category
    $category = Category::factory()->create();
    
    // Create a variable product
    $product = Product::factory()->create([
        'product_type' => 'variable',
        'category_id' => $category->id,
        'is_active' => true,
        'image' => 'products/main-product.jpg'
    ]);
    
    // Create size attribute
    $sizeAttribute = Attribute::factory()->create([
        'name' => 'Size',
        'type' => 'select'
    ]);
    
    // Create attribute values
    $smallValue = AttributeValue::factory()->create([
        'attribute_id' => $sizeAttribute->id,
        'value' => 'Small'
    ]);
    
    // Create variation without image
    $smallVariation = ProductVariation::factory()->create([
        'product_id' => $product->id,
        'sku' => 'PROD-SMALL',
        'price' => 15.99,
        'quantity' => 8,
        'image' => null, // No variation image
        'is_active' => true,
    ]);
    
    // Associate attribute value with variation
    $smallVariation->attributeValues()->attach($smallValue->id, ['attribute_id' => $sizeAttribute->id]);
    
    // Visit the product page
    $response = $this->get(route('shop.show', $product->slug));
    
    $response->assertStatus(200);
    
    // Check that main product image is shown
    $response->assertSee('main-product.jpg');
    
    // Check that variation data includes null image
    $response->assertSee('"image":null', false); // raw JSON inside a <script>, so don't HTML-escape the needle
});

test('simple product shows main image without variation functionality', function () {
    // Create a category
    $category = Category::factory()->create();
    
    // Create a simple product
    $product = Product::factory()->create([
        'product_type' => 'simple',
        'category_id' => $category->id,
        'is_active' => true,
        'image' => 'products/simple-product.jpg'
    ]);
    
    // Visit the product page
    $response = $this->get(route('shop.show', $product->slug));
    
    $response->assertStatus(200);
    
    // Check that main product image is shown
    $response->assertSee('simple-product.jpg');
    
    // Check that variation functionality is not present
    $response->assertDontSee('variationSelector()');
    $response->assertDontSee('productVariations');
});