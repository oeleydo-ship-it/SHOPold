<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

class PagesSeeder extends Seeder
{
    public function run(): void
    {
        // Create pages table if it doesn't exist
        if (!Schema::hasTable('pages')) {
            Schema::create('pages', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->longText('content')->nullable();
                $table->string('meta_title')->nullable();
                $table->text('meta_description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        $pages = [
            [
                'title' => 'About Us',
                'slug' => 'about-us',
                'content' => '<h2>About Laravel Shop</h2>
                <p>Welcome to Laravel Shop, your premier destination for online shopping. We are committed to providing you with the best products at competitive prices, backed by excellent customer service.</p>
                
                <h3>Our Mission</h3>
                <p>To make online shopping accessible, enjoyable, and secure for everyone. We strive to offer a wide range of quality products while maintaining the highest standards of customer satisfaction.</p>
                
                <h3>Why Choose Us?</h3>
                <ul>
                    <li>Wide selection of quality products</li>
                    <li>Competitive pricing</li>
                    <li>Fast and reliable shipping</li>
                    <li>Secure payment processing</li>
                    <li>Excellent customer support</li>
                    <li>Easy returns and exchanges</li>
                </ul>
                
                <h3>Contact Information</h3>
                <p>Have questions? We\'re here to help!</p>
                <p>Email: info@laravelshop.com<br>
                Phone: +1 (555) 123-4567<br>
                Address: 123 Commerce Street, Business City, BC 12345</p>',
                'meta_title' => 'About Us - Laravel Shop',
                'meta_description' => 'Learn more about Laravel Shop, our mission, and why we\'re your best choice for online shopping.',
                'is_active' => true,
            ],
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'content' => '<h2>Privacy Policy</h2>
                <p><strong>Last updated:</strong> ' . date('F d, Y') . '</p>
                
                <h3>Information We Collect</h3>
                <p>We collect information you provide directly to us, such as when you create an account, make a purchase, or contact us for support.</p>
                
                <h4>Personal Information</h4>
                <ul>
                    <li>Name and contact information</li>
                    <li>Billing and shipping addresses</li>
                    <li>Payment information</li>
                    <li>Order history and preferences</li>
                </ul>
                
                <h4>Automatically Collected Information</h4>
                <ul>
                    <li>Device and browser information</li>
                    <li>IP address and location data</li>
                    <li>Website usage and navigation patterns</li>
                    <li>Cookies and similar technologies</li>
                </ul>
                
                <h3>How We Use Your Information</h3>
                <p>We use the information we collect to:</p>
                <ul>
                    <li>Process and fulfill your orders</li>
                    <li>Communicate with you about your account and orders</li>
                    <li>Improve our website and services</li>
                    <li>Send you marketing communications (with your consent)</li>
                    <li>Comply with legal obligations</li>
                </ul>
                
                <h3>Information Sharing</h3>
                <p>We do not sell, trade, or otherwise transfer your personal information to third parties without your consent, except as described in this policy.</p>
                
                <h3>Data Security</h3>
                <p>We implement appropriate security measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction.</p>
                
                <h3>Your Rights</h3>
                <p>You have the right to access, update, or delete your personal information. Contact us if you wish to exercise these rights.</p>
                
                <h3>Contact Us</h3>
                <p>If you have questions about this Privacy Policy, please contact us at privacy@laravelshop.com.</p>',
                'meta_title' => 'Privacy Policy - Laravel Shop',
                'meta_description' => 'Read our privacy policy to understand how we collect, use, and protect your personal information.',
                'is_active' => true,
            ],
            [
                'title' => 'Terms of Service',
                'slug' => 'terms-of-service',
                'content' => '<h2>Terms of Service</h2>
                <p><strong>Last updated:</strong> ' . date('F d, Y') . '</p>
                
                <h3>Acceptance of Terms</h3>
                <p>By accessing and using Laravel Shop, you accept and agree to be bound by the terms and provision of this agreement.</p>
                
                <h3>Use License</h3>
                <p>Permission is granted to temporarily download one copy of the materials on Laravel Shop for personal, non-commercial transitory viewing only.</p>
                
                <h3>Account Registration</h3>
                <p>To make a purchase, you may be required to create an account. You are responsible for maintaining the confidentiality of your account information.</p>
                
                <h3>Product Information</h3>
                <p>We strive to provide accurate product information, but we do not warrant that product descriptions or other content is accurate, complete, reliable, current, or error-free.</p>
                
                <h3>Pricing and Payment</h3>
                <p>All prices are subject to change without notice. We reserve the right to refuse or cancel any order for any reason.</p>
                
                <h3>Shipping and Delivery</h3>
                <p>We will make every effort to deliver products within the estimated timeframe, but delivery times are not guaranteed.</p>
                
                <h3>Returns and Refunds</h3>
                <p>Please review our return policy for information about returns, exchanges, and refunds.</p>
                
                <h3>Limitation of Liability</h3>
                <p>Laravel Shop shall not be liable for any indirect, incidental, special, consequential, or punitive damages.</p>
                
                <h3>Governing Law</h3>
                <p>These terms and conditions are governed by and construed in accordance with the laws of the jurisdiction in which we operate.</p>
                
                <h3>Contact Information</h3>
                <p>Questions about the Terms of Service should be sent to us at legal@laravelshop.com.</p>',
                'meta_title' => 'Terms of Service - Laravel Shop',
                'meta_description' => 'Read our terms of service to understand the rules and regulations for using Laravel Shop.',
                'is_active' => true,
            ],
            [
                'title' => 'Shipping & Returns',
                'slug' => 'shipping-returns',
                'content' => '<h2>Shipping & Returns</h2>
                
                <h3>Shipping Information</h3>
                
                <h4>Shipping Methods</h4>
                <ul>
                    <li><strong>Standard Shipping:</strong> 5-7 business days</li>
                    <li><strong>Express Shipping:</strong> 2-3 business days</li>
                    <li><strong>Overnight Shipping:</strong> 1 business day</li>
                </ul>
                
                <h4>Shipping Costs</h4>
                <p>Shipping costs are calculated based on the weight and destination of your order. Free shipping is available on orders over $100.</p>
                
                <h4>Processing Time</h4>
                <p>Orders are typically processed within 1-2 business days. You will receive a tracking number once your order has shipped.</p>
                
                <h3>Return Policy</h3>
                
                <h4>Return Window</h4>
                <p>You may return most items within 30 days of delivery for a full refund or exchange.</p>
                
                <h4>Return Conditions</h4>
                <ul>
                    <li>Items must be in original condition</li>
                    <li>Items must include original packaging and tags</li>
                    <li>Some items may not be eligible for return (perishables, personalized items, etc.)</li>
                </ul>
                
                <h4>Return Process</h4>
                <ol>
                    <li>Contact our customer service team to initiate a return</li>
                    <li>Receive return authorization and shipping label</li>
                    <li>Package items securely and attach return label</li>
                    <li>Drop off at designated shipping location</li>
                </ol>
                
                <h4>Refund Processing</h4>
                <p>Refunds are processed within 5-10 business days after we receive your returned item. Refunds will be issued to the original payment method.</p>
                
                <h3>Exchanges</h3>
                <p>We offer exchanges for different sizes or colors of the same item, subject to availability.</p>
                
                <h3>Damaged or Defective Items</h3>
                <p>If you receive a damaged or defective item, please contact us immediately. We will provide a prepaid return label and expedite a replacement.</p>
                
                <h3>International Shipping</h3>
                <p>We currently ship to select international destinations. International customers are responsible for any customs duties or taxes.</p>
                
                <h3>Contact Us</h3>
                <p>For questions about shipping or returns, contact us at:</p>
                <p>Email: support@laravelshop.com<br>
                Phone: +1 (555) 123-4567<br>
                Hours: Monday-Friday, 9 AM - 6 PM EST</p>',
                'meta_title' => 'Shipping & Returns - Laravel Shop',
                'meta_description' => 'Learn about our shipping options, return policy, and how to exchange or return items.',
                'is_active' => true,
            ],
            [
                'title' => 'FAQ',
                'slug' => 'faq',
                'content' => '<h2>Frequently Asked Questions</h2>
                
                <h3>Ordering</h3>
                
                <h4>How do I place an order?</h4>
                <p>Simply browse our products, add items to your cart, and proceed to checkout. You can create an account or checkout as a guest.</p>
                
                <h4>Can I modify or cancel my order?</h4>
                <p>Orders can be modified or cancelled within 1 hour of placement. After that, please contact customer service for assistance.</p>
                
                <h4>What payment methods do you accept?</h4>
                <p>We accept major credit cards (Visa, MasterCard, American Express), PayPal, and other secure payment methods.</p>
                
                <h3>Shipping</h3>
                
                <h4>How long does shipping take?</h4>
                <p>Standard shipping takes 5-7 business days. Express and overnight options are also available.</p>
                
                <h4>Do you ship internationally?</h4>
                <p>Yes, we ship to select international destinations. Shipping costs and delivery times vary by location.</p>
                
                <h4>How can I track my order?</h4>
                <p>You\'ll receive a tracking number via email once your order ships. You can also track orders in your account dashboard.</p>
                
                <h3>Returns & Exchanges</h3>
                
                <h4>What is your return policy?</h4>
                <p>We accept returns within 30 days of delivery. Items must be in original condition with tags attached.</p>
                
                <h4>How do I return an item?</h4>
                <p>Contact our customer service team to initiate a return. We\'ll provide you with a return authorization and prepaid shipping label.</p>
                
                <h4>When will I receive my refund?</h4>
                <p>Refunds are processed within 5-10 business days after we receive your returned item.</p>
                
                <h3>Account & Security</h3>
                
                <h4>Do I need an account to shop?</h4>
                <p>No, you can checkout as a guest. However, creating an account allows you to track orders, save addresses, and view order history.</p>
                
                <h4>Is my personal information secure?</h4>
                <p>Yes, we use industry-standard encryption and security measures to protect your personal and payment information.</p>
                
                <h4>How do I reset my password?</h4>
                <p>Click "Forgot Password" on the login page and follow the instructions sent to your email.</p>
                
                <h3>Products & Inventory</h3>
                
                <h4>Are product photos accurate?</h4>
                <p>We strive to display accurate product photos. However, colors may vary slightly due to monitor settings.</p>
                
                <h4>What if an item is out of stock?</h4>
                <p>Out of stock items will show as unavailable. You can sign up for notifications when items are back in stock.</p>
                
                <h3>Customer Service</h3>
                
                <h4>How can I contact customer service?</h4>
                <p>You can reach us via:</p>
                <ul>
                    <li>Email: support@laravelshop.com</li>
                    <li>Phone: +1 (555) 123-4567</li>
                    <li>Live chat on our website</li>
                </ul>
                
                <h4>What are your customer service hours?</h4>
                <p>Monday-Friday: 9 AM - 6 PM EST<br>
                Saturday: 10 AM - 4 PM EST<br>
                Sunday: Closed</p>
                
                <p><strong>Still have questions?</strong> Contact our customer service team and we\'ll be happy to help!</p>',
                'meta_title' => 'FAQ - Laravel Shop',
                'meta_description' => 'Find answers to frequently asked questions about ordering, shipping, returns, and more.',
                'is_active' => true,
            ],
        ];

        foreach ($pages as $page) {
            DB::table('pages')->updateOrInsert(
                ['slug' => $page['slug']],
                array_merge($page, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}