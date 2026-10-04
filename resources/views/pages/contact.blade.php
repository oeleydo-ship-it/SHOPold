@extends('layouts.app')

@section('title', 'Contact Us')

@section('content')
@include('pages.partials.skin')
<div class="content-page">
<div class="bg-gradient-to-r from-primary-600 to-primary-800 py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-white">
        <h1 class="text-4xl font-bold mb-4">Contact Us</h1>
        <p class="text-xl text-white/80">We'd love to hear from you</p>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="grid md:grid-cols-2 gap-12">
        <div>
            <h2 class="text-2xl font-bold mb-6">Get in Touch</h2>
            <p class="text-gray-600 mb-8">Have questions? We're here to help. Send us a message and we'll respond as soon as possible.</p>
            
            <div class="space-y-6">
                <div class="flex items-start">
                    <div class="w-12 h-12 bg-primary-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-map-marker-alt text-primary-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="font-semibold">Address</h3>
                        <p class="text-gray-600">123 Commerce Street, City, Country</p>
                    </div>
                </div>
                <div class="flex items-start">
                    <div class="w-12 h-12 bg-primary-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-phone-alt text-primary-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="font-semibold">Phone</h3>
                        <p class="text-gray-600">+1 234 567 890</p>
                    </div>
                </div>
                <div class="flex items-start">
                    <div class="w-12 h-12 bg-primary-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-envelope text-primary-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="font-semibold">Email</h3>
                        <p class="text-gray-600">info@laravelshop.com</p>
                    </div>
                </div>
                <div class="flex items-start">
                    <div class="w-12 h-12 bg-primary-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-clock text-primary-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="font-semibold">Business Hours</h3>
                        <p class="text-gray-600">Mon - Fri: 9:00 AM - 6:00 PM</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-8">
            <form action="{{ route('contact.submit') }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                        <input type="text" name="name" value="{{ old('name') }}" required class="w-full px-4 py-3 border rounded-lg focus:ring-primary-500 focus:border-primary-500">
                        @error('name')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" required class="w-full px-4 py-3 border rounded-lg focus:ring-primary-500 focus:border-primary-500">
                        @error('email')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                        <input type="text" name="subject" value="{{ old('subject') }}" required class="w-full px-4 py-3 border rounded-lg focus:ring-primary-500 focus:border-primary-500">
                        @error('subject')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                        <textarea name="message" rows="5" required class="w-full px-4 py-3 border rounded-lg focus:ring-primary-500 focus:border-primary-500">{{ old('message') }}</textarea>
                        @error('message')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="w-full py-3 bg-primary-600 text-white font-semibold rounded-lg hover:bg-primary-700 transition">
                        Send Message
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
@endsection
