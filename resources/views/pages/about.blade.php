@extends('layouts.app')

@section('title', 'About Us')

@section('content')
@include('pages.partials.skin')
<div class="content-page">
<div class="bg-gradient-to-r from-primary-600 to-primary-800 py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-white">
        <h1 class="text-4xl font-bold mb-4">About Us</h1>
        <p class="text-xl text-white/80">Your trusted destination for premium products</p>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="grid md:grid-cols-2 gap-12 items-center mb-16">
        <div>
            <h2 class="text-3xl font-bold mb-6">Our Story</h2>
            <p class="text-gray-600 mb-4">Founded in 2024, LaravelShop has grown from a small online store to one of the most trusted e-commerce platforms. We believe in providing quality products at competitive prices with exceptional customer service.</p>
            <p class="text-gray-600 mb-4">Our mission is to make online shopping easy, secure, and enjoyable for everyone. We carefully curate our product selection to ensure only the best items make it to our store.</p>
            <p class="text-gray-600">With thousands of satisfied customers worldwide, we continue to grow and improve every day.</p>
        </div>
        <div>
            <img src="https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=600&h=400&fit=crop" alt="About Us" class="rounded-2xl shadow-lg">
        </div>
    </div>

    <div class="grid md:grid-cols-4 gap-8 text-center mb-16">
        <div class="bg-white rounded-xl shadow-sm p-8">
            <div class="text-4xl font-bold text-primary-600 mb-2">10K+</div>
            <div class="text-gray-600">Happy Customers</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-8">
            <div class="text-4xl font-bold text-primary-600 mb-2">5K+</div>
            <div class="text-gray-600">Products</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-8">
            <div class="text-4xl font-bold text-primary-600 mb-2">50+</div>
            <div class="text-gray-600">Countries</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-8">
            <div class="text-4xl font-bold text-primary-600 mb-2">24/7</div>
            <div class="text-gray-600">Support</div>
        </div>
    </div>

    <div class="text-center">
        <h2 class="text-3xl font-bold mb-6">Why Choose Us</h2>
        <div class="grid md:grid-cols-3 gap-8">
            <div class="bg-white rounded-xl shadow-sm p-8">
                <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-gem text-2xl text-primary-600"></i>
                </div>
                <h3 class="font-bold text-xl mb-2">Quality Products</h3>
                <p class="text-gray-600">We source only the finest products from trusted suppliers worldwide.</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-8">
                <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-shipping-fast text-2xl text-primary-600"></i>
                </div>
                <h3 class="font-bold text-xl mb-2">Fast Delivery</h3>
                <p class="text-gray-600">Quick and reliable shipping to your doorstep anywhere in the world.</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-8">
                <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-headset text-2xl text-primary-600"></i>
                </div>
                <h3 class="font-bold text-xl mb-2">24/7 Support</h3>
                <p class="text-gray-600">Our dedicated support team is always ready to help you.</p>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
