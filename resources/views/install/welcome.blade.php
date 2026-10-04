@extends('install.layout')

@section('title', 'Welcome')

@section('content')
<div class="p-8 text-center">
    <div class="w-20 h-20 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-6">
        <i class="fas fa-rocket text-primary-600 text-3xl"></i>
    </div>
    
    <h2 class="text-2xl font-bold text-gray-800 mb-4">Welcome to Laravel Shop</h2>
    
    <p class="text-gray-600 mb-8 max-w-md mx-auto">
        Thank you for choosing Laravel Shop! This wizard will guide you through the installation process. 
        It should only take a few minutes.
    </p>

    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-8 text-left">
        <h3 class="font-semibold text-blue-800 mb-2"><i class="fas fa-info-circle mr-2"></i>Before you begin</h3>
        <ul class="text-sm text-blue-700 space-y-1">
            <li>• Make sure you have your database credentials ready</li>
            <li>• Ensure PHP 8.2+ is installed on your server</li>
            <li>• The storage and bootstrap/cache directories must be writable</li>
        </ul>
    </div>

    <a href="{{ route('install.requirements') }}" class="inline-flex items-center px-8 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition font-medium">
        Let's Get Started <i class="fas fa-arrow-right ml-2"></i>
    </a>
</div>
@endsection
