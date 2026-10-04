<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use App\Models\Coupon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Get the connection name from the seeder
        $connection = DB::getDefaultConnection();

        // Seed Settings and Pages first
        $this->call([
            SettingsSeeder::class,
            PagesSeeder::class,
        ]);

        // Create or update Admin User
        User::on($connection)->updateOrCreate(
            ['email' => 'admin@shop.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'is_admin' => true,
                'admin_role' => 'super_admin',
            ]
        );

        // Create or update Customer User
        User::on($connection)->updateOrCreate(
            ['email' => 'customer@shop.com'],
            [
                'name' => 'John Customer',
                'password' => Hash::make('password'),
                'is_admin' => false,
            ]
        );

        // Create Categories
        $categories = [
            ['name' => 'Electronics', 'slug' => 'electronics', 'description' => 'Latest electronic gadgets and devices'],
            ['name' => 'Clothing', 'slug' => 'clothing', 'description' => 'Fashion and apparel for all'],
            ['name' => 'Home & Garden', 'slug' => 'home-garden', 'description' => 'Everything for your home'],
            ['name' => 'Sports', 'slug' => 'sports', 'description' => 'Sports equipment and accessories'],
            ['name' => 'Books', 'slug' => 'books', 'description' => 'Books for every reader'],
            ['name' => 'Beauty', 'slug' => 'beauty', 'description' => 'Beauty and personal care products'],
        ];

        foreach ($categories as $cat) {
            Category::on($connection)->updateOrCreate(
                ['slug' => $cat['slug']],
                $cat
            );
        }

        // Create Products
        $products = [
            // Electronics
            ['name' => 'Wireless Bluetooth Headphones', 'category_id' => 1, 'price' => 79.99, 'sale_price' => 59.99, 'quantity' => 50, 'is_featured' => true],
            ['name' => 'Smart Watch Pro', 'category_id' => 1, 'price' => 299.99, 'quantity' => 30, 'is_featured' => true],
            ['name' => 'Portable Power Bank 20000mAh', 'category_id' => 1, 'price' => 49.99, 'sale_price' => 39.99, 'quantity' => 100],
            ['name' => 'Wireless Charging Pad', 'category_id' => 1, 'price' => 29.99, 'quantity' => 75],
            ['name' => 'USB-C Hub Adapter', 'category_id' => 1, 'price' => 45.99, 'quantity' => 60],
            
            // Clothing
            ['name' => 'Classic Cotton T-Shirt', 'category_id' => 2, 'price' => 24.99, 'quantity' => 200],
            ['name' => 'Slim Fit Jeans', 'category_id' => 2, 'price' => 59.99, 'sale_price' => 49.99, 'quantity' => 150, 'is_featured' => true],
            ['name' => 'Wool Winter Jacket', 'category_id' => 2, 'price' => 149.99, 'quantity' => 40],
            ['name' => 'Running Sneakers', 'category_id' => 2, 'price' => 89.99, 'sale_price' => 69.99, 'quantity' => 80],
            ['name' => 'Leather Belt', 'category_id' => 2, 'price' => 34.99, 'quantity' => 120],
            
            // Home & Garden
            ['name' => 'Stainless Steel Cookware Set', 'category_id' => 3, 'price' => 199.99, 'sale_price' => 149.99, 'quantity' => 25, 'is_featured' => true],
            ['name' => 'Memory Foam Pillow', 'category_id' => 3, 'price' => 39.99, 'quantity' => 100],
            ['name' => 'LED Desk Lamp', 'category_id' => 3, 'price' => 44.99, 'quantity' => 60],
            ['name' => 'Indoor Plant Pot Set', 'category_id' => 3, 'price' => 29.99, 'quantity' => 80],
            
            // Sports
            ['name' => 'Yoga Mat Premium', 'category_id' => 4, 'price' => 34.99, 'quantity' => 90],
            ['name' => 'Adjustable Dumbbells Set', 'category_id' => 4, 'price' => 199.99, 'sale_price' => 169.99, 'quantity' => 20, 'is_featured' => true],
            ['name' => 'Resistance Bands Kit', 'category_id' => 4, 'price' => 24.99, 'quantity' => 150],
            ['name' => 'Sports Water Bottle', 'category_id' => 4, 'price' => 19.99, 'quantity' => 200],
            
            // Books
            ['name' => 'The Art of Programming', 'category_id' => 5, 'price' => 49.99, 'quantity' => 50],
            ['name' => 'Business Strategy Guide', 'category_id' => 5, 'price' => 29.99, 'sale_price' => 24.99, 'quantity' => 75],
            ['name' => 'Cooking Masterclass', 'category_id' => 5, 'price' => 34.99, 'quantity' => 60],
            
            // Beauty
            ['name' => 'Organic Face Cream', 'category_id' => 6, 'price' => 44.99, 'quantity' => 100, 'is_featured' => true],
            ['name' => 'Hair Care Essential Kit', 'category_id' => 6, 'price' => 59.99, 'sale_price' => 49.99, 'quantity' => 80],
            ['name' => 'Natural Lip Balm Set', 'category_id' => 6, 'price' => 14.99, 'quantity' => 200],
        ];

        foreach ($products as $prod) {
            Product::on($connection)->updateOrCreate(
                ['name' => $prod['name']],
                [
                    'slug' => Str::slug($prod['name']) . '-' . Str::random(5),
                    'description' => 'This is a high-quality ' . strtolower($prod['name']) . '. Perfect for everyday use with premium materials and excellent craftsmanship. Customer satisfaction guaranteed.',
                    'short_description' => 'Premium quality ' . strtolower($prod['name']) . ' with excellent features.',
                    'price' => $prod['price'],
                    'sale_price' => $prod['sale_price'] ?? null,
                    'sku' => 'SKU-' . strtoupper(Str::random(8)),
                    'quantity' => $prod['quantity'],
                    'category_id' => $prod['category_id'],
                    'is_active' => true,
                    'is_featured' => $prod['is_featured'] ?? false,
                ]
            );
        }

        // Create Coupons
        Coupon::on($connection)->updateOrCreate([
            'code' => 'WELCOME10',
        ], [
            'type' => 'percent',
            'value' => 10,
            'minimum_amount' => 50,
            'is_active' => true,
        ]);

        Coupon::on($connection)->updateOrCreate([
            'code' => 'SAVE20',
        ], [
            'type' => 'fixed',
            'value' => 20,
            'minimum_amount' => 100,
            'max_uses' => 100,
            'is_active' => true,
        ]);
    }
}
