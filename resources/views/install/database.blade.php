@extends('install.layout')

@section('title', 'Database Configuration')

@section('content')
<div class="p-8">
    <h2 class="text-xl font-bold text-gray-800 mb-6">Database Configuration</h2>

    @if($errors->has('database'))
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
            <p class="text-red-700"><i class="fas fa-exclamation-circle mr-2"></i>{{ $errors->first('database') }}</p>
        </div>
    @endif

    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
        <p class="text-yellow-800 text-sm">
            <i class="fas fa-info-circle mr-2"></i>
            <strong>cPanel Users:</strong> Your database name is usually <code>username_dbname</code> and username is <code>username_dbuser</code>. 
            Create the database and user in cPanel's MySQL Databases section first.
        </p>
    </div>

    <form action="{{ route('install.database.save') }}" method="POST">
        @csrf
        
        <div class="grid md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Database Host</label>
                <input type="text" name="db_host" value="{{ old('db_host', 'localhost') }}" required
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                <p class="text-xs text-gray-500 mt-1">Usually "localhost" for cPanel</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Database Port</label>
                <input type="text" name="db_port" value="{{ old('db_port', '3306') }}" required
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Database Name</label>
            <input type="text" name="db_database" value="{{ old('db_database') }}" required
                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                   placeholder="e.g., cpaneluser_shopdb">
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Database Username</label>
            <input type="text" name="db_username" value="{{ old('db_username') }}" required
                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                   placeholder="e.g., cpaneluser_dbuser">
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Database Password</label>
            <input type="password" name="db_password" value="{{ old('db_password') }}"
                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
        </div>

        <div class="flex justify-between">
            <a href="{{ route('install.requirements') }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                <i class="fas fa-arrow-left mr-2"></i> Back
            </a>
            <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                Test & Continue <i class="fas fa-arrow-right ml-2"></i>
            </button>
        </div>
    </form>
</div>
@endsection
