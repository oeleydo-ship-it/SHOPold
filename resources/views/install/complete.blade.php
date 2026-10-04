@extends('install.layout')

@section('title', 'Installation Complete')

@section('content')
<div class="p-8 text-center">
    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
        <i class="fas fa-check text-green-600 text-3xl"></i>
    </div>
    
    <h2 class="text-2xl font-bold text-gray-800 mb-4">Installation Complete!</h2>
    
    <p class="text-gray-600 mb-8 max-w-md mx-auto">
        Congratulations! Laravel Shop has been successfully installed. You can now start using your new e-commerce store.
    </p>

    <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-8 text-left max-w-md mx-auto">
        <h3 class="font-semibold text-green-800 mb-3"><i class="fas fa-info-circle mr-2"></i>What's Next?</h3>
        <ul class="text-sm text-green-700 space-y-2">
            <li><i class="fas fa-check mr-2"></i> Login to the admin panel</li>
            <li><i class="fas fa-check mr-2"></i> Configure your store settings</li>
            <li><i class="fas fa-check mr-2"></i> Add categories and products</li>
            <li><i class="fas fa-check mr-2"></i> Set up payment methods</li>
            <li><i class="fas fa-check mr-2"></i> Configure shipping zones</li>
        </ul>
    </div>

    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-8 text-left max-w-md mx-auto">
        <p class="text-yellow-800 text-sm">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <strong>Security Tip:</strong> For security, consider deleting the <code>resources/views/install</code> folder 
            and removing the install routes from <code>routes/web.php</code> after installation.
        </p>
    </div>

    <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <a href="{{ url('/') }}" class="px-6 py-3 border border-primary-600 text-primary-600 rounded-lg hover:bg-primary-50 transition font-medium">
            <i class="fas fa-home mr-2"></i> Visit Store
        </a>
        <a href="{{ url('/admin') }}" class="px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition font-medium">
            <i class="fas fa-cog mr-2"></i> Admin Panel
        </a>
    </div>
</div>
@endsection
