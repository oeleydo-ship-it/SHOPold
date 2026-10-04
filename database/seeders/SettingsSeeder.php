<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Setting;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General Settings
            ['key' => 'store.name', 'value' => 'Laravel Shop', 'group' => 'general'],
            ['key' => 'store.tagline', 'value' => 'Your Online Shopping Destination', 'group' => 'general'],
            ['key' => 'store.description', 'value' => 'A modern e-commerce platform built with Laravel', 'group' => 'general'],
            ['key' => 'store.email', 'value' => 'info@laravelshop.com', 'group' => 'general'],
            ['key' => 'store.phone', 'value' => '+1 (555) 123-4567', 'group' => 'general'],
            ['key' => 'store.address', 'value' => '123 Commerce Street, Business City, BC 12345', 'group' => 'general'],
            ['key' => 'store.currency', 'value' => 'USD', 'group' => 'general'],
            ['key' => 'store.currency_symbol', 'value' => '$', 'group' => 'general'],
            ['key' => 'store.currency_position', 'value' => 'before', 'group' => 'general'],
            ['key' => 'store.tax_rate', 'value' => '10', 'group' => 'general'],
            ['key' => 'store.timezone', 'value' => 'UTC', 'group' => 'general'],
            ['key' => 'store.language', 'value' => 'en', 'group' => 'general'],
            
            // Features
            ['key' => 'features.multi_vendor', 'value' => '0', 'group' => 'features'],
            ['key' => 'features.driver_portal', 'value' => '1', 'group' => 'features'],
            ['key' => 'features.reviews', 'value' => '1', 'group' => 'features'],
            ['key' => 'features.wishlist', 'value' => '1', 'group' => 'features'],
            ['key' => 'features.coupons', 'value' => '1', 'group' => 'features'],
            ['key' => 'features.guest_checkout', 'value' => '1', 'group' => 'features'],
            ['key' => 'features.email_verification', 'value' => '0', 'group' => 'features'],
            ['key' => 'features.newsletter', 'value' => '1', 'group' => 'features'],
            ['key' => 'features.blog', 'value' => '1', 'group' => 'features'],
            ['key' => 'features.loyalty_points', 'value' => '0', 'group' => 'features'],
            
            // Appearance
            ['key' => 'appearance.theme', 'value' => 'default', 'group' => 'appearance'],
            ['key' => 'appearance.logo', 'value' => '', 'group' => 'appearance'],
            ['key' => 'appearance.favicon', 'value' => '', 'group' => 'appearance'],
            ['key' => 'appearance.primary_color', 'value' => '#3B82F6', 'group' => 'appearance'],
            ['key' => 'appearance.secondary_color', 'value' => '#64748B', 'group' => 'appearance'],
            
            // Homepage
            ['key' => 'homepage.hero_title', 'value' => 'Welcome to Laravel Shop', 'group' => 'homepage'],
            ['key' => 'homepage.hero_subtitle', 'value' => 'Discover amazing products at great prices', 'group' => 'homepage'],
            ['key' => 'homepage.hero_image', 'value' => '', 'group' => 'homepage'],
            ['key' => 'homepage.show_categories', 'value' => '1', 'group' => 'homepage'],
            ['key' => 'homepage.show_featured', 'value' => '1', 'group' => 'homepage'],
            ['key' => 'homepage.show_flash_deals', 'value' => '1', 'group' => 'homepage'],
            ['key' => 'homepage.show_top_rated', 'value' => '1', 'group' => 'homepage'],
            ['key' => 'homepage.show_new_arrivals', 'value' => '1', 'group' => 'homepage'],
            
            // Shop Settings
            ['key' => 'shop.products_per_page', 'value' => '12', 'group' => 'shop'],
            ['key' => 'shop.show_out_of_stock', 'value' => '1', 'group' => 'shop'],
            ['key' => 'shop.stock_management', 'value' => '1', 'group' => 'shop'],
            ['key' => 'shop.low_stock_threshold', 'value' => '5', 'group' => 'shop'],
            ['key' => 'shop.allow_backorders', 'value' => '0', 'group' => 'shop'],
            
            // Payment Settings
            ['key' => 'payment.stripe_enabled', 'value' => '0', 'group' => 'payment'],
            ['key' => 'payment.stripe_public_key', 'value' => '', 'group' => 'payment'],
            ['key' => 'payment.stripe_secret_key', 'value' => '', 'group' => 'payment'],
            ['key' => 'payment.paypal_enabled', 'value' => '0', 'group' => 'payment'],
            ['key' => 'payment.paypal_client_id', 'value' => '', 'group' => 'payment'],
            ['key' => 'payment.paypal_client_secret', 'value' => '', 'group' => 'payment'],
            ['key' => 'payment.razorpay_enabled', 'value' => '0', 'group' => 'payment'],
            ['key' => 'payment.razorpay_key_id', 'value' => '', 'group' => 'payment'],
            ['key' => 'payment.razorpay_key_secret', 'value' => '', 'group' => 'payment'],
            ['key' => 'payment.cod_enabled', 'value' => '1', 'group' => 'payment'],
            
            // Shipping Settings
            ['key' => 'shipping.free_shipping_threshold', 'value' => '100', 'group' => 'shipping'],
            ['key' => 'shipping.default_rate', 'value' => '10', 'group' => 'shipping'],
            ['key' => 'shipping.calculation_method', 'value' => 'flat_rate', 'group' => 'shipping'],
            
            // Email Settings
            ['key' => 'email.from_name', 'value' => 'Laravel Shop', 'group' => 'email'],
            ['key' => 'email.from_address', 'value' => 'noreply@laravelshop.com', 'group' => 'email'],
            ['key' => 'email.order_confirmation', 'value' => '1', 'group' => 'email'],
            ['key' => 'email.order_status_updates', 'value' => '1', 'group' => 'email'],
            ['key' => 'email.welcome_email', 'value' => '1', 'group' => 'email'],
            ['key' => 'email.newsletter_signup', 'value' => '1', 'group' => 'email'],
            
            // SEO Settings
            ['key' => 'seo.meta_title', 'value' => 'Laravel Shop - Your Online Shopping Destination', 'group' => 'seo'],
            ['key' => 'seo.meta_description', 'value' => 'Shop the latest products at Laravel Shop. Fast shipping, great prices, and excellent customer service.', 'group' => 'seo'],
            ['key' => 'seo.meta_keywords', 'value' => 'ecommerce, online shopping, laravel, shop', 'group' => 'seo'],
            ['key' => 'seo.google_analytics', 'value' => '', 'group' => 'seo'],
            ['key' => 'seo.facebook_pixel', 'value' => '', 'group' => 'seo'],
            
            // Social Media
            ['key' => 'social.facebook', 'value' => '', 'group' => 'social'],
            ['key' => 'social.twitter', 'value' => '', 'group' => 'social'],
            ['key' => 'social.instagram', 'value' => '', 'group' => 'social'],
            ['key' => 'social.youtube', 'value' => '', 'group' => 'social'],
            ['key' => 'social.linkedin', 'value' => '', 'group' => 'social'],
            
            // Mobile App
            ['key' => 'mobile.app_name', 'value' => 'Laravel Shop', 'group' => 'mobile'],
            ['key' => 'mobile.app_version', 'value' => '1.0.0', 'group' => 'mobile'],
            ['key' => 'mobile.force_update', 'value' => '0', 'group' => 'mobile'],
            ['key' => 'mobile.maintenance_mode', 'value' => '0', 'group' => 'mobile'],
            
            // System Settings
            ['key' => 'system.maintenance_mode', 'value' => '0', 'group' => 'system'],
            ['key' => 'system.maintenance_message', 'value' => 'We are currently performing maintenance. Please check back soon.', 'group' => 'system'],
            ['key' => 'system.debug_mode', 'value' => '0', 'group' => 'system'],
            ['key' => 'system.cache_enabled', 'value' => '1', 'group' => 'system'],
            ['key' => 'system.session_lifetime', 'value' => '120', 'group' => 'system'],
            
            // Update Settings
            ['key' => 'updates.auto_check', 'value' => '1', 'group' => 'updates'],
            ['key' => 'updates.auto_backup', 'value' => '1', 'group' => 'updates'],
            ['key' => 'updates.last_check', 'value' => '', 'group' => 'updates'],
            ['key' => 'updates.current_version', 'value' => '1.0.0', 'group' => 'updates'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'group' => $setting['group'],
                    'type' => 'string'
                ]
            );
        }
    }
}