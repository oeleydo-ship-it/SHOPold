@extends('install.layout')

@section('title', 'Requirements')

@section('content')
<div class="p-8">
    <h2 class="text-xl font-bold text-gray-800 mb-6">Server Requirements</h2>

    <!-- PHP Extensions -->
    <div class="mb-6">
        <h3 class="font-semibold text-gray-700 mb-3">PHP Extensions</h3>
        <div class="space-y-2">
            @foreach($requirements as $key => $req)
                <div class="flex items-center justify-between p-3 rounded-lg {{ $req['passed'] ? 'bg-green-50' : 'bg-red-50' }}">
                    <span class="text-gray-700">{{ $req['name'] }}</span>
                    @if($req['passed'])
                        <span class="text-green-600"><i class="fas fa-check-circle"></i> OK</span>
                    @else
                        <span class="text-red-600"><i class="fas fa-times-circle"></i> Missing</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <!-- Directory Permissions -->
    <div class="mb-6">
        <h3 class="font-semibold text-gray-700 mb-3">Directory Permissions</h3>
        <div class="space-y-2">
            @foreach($permissions as $key => $perm)
                <div class="flex items-center justify-between p-3 rounded-lg {{ $perm['writable'] ? 'bg-green-50' : 'bg-red-50' }}">
                    <span class="text-gray-700">{{ $perm['name'] }}</span>
                    @if($perm['writable'])
                        <span class="text-green-600"><i class="fas fa-check-circle"></i> Writable</span>
                    @else
                        <span class="text-red-600"><i class="fas fa-times-circle"></i> Not Writable</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    @if(!$allPassed)
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
            <p class="text-red-700">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                Please fix the issues above before continuing.
            </p>
        </div>
    @endif

    <div class="flex justify-between">
        <a href="{{ route('install.welcome') }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
        @if($allPassed)
            <a href="{{ route('install.database') }}" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                Continue <i class="fas fa-arrow-right ml-2"></i>
            </a>
        @else
            <button disabled class="px-6 py-2 bg-gray-300 text-gray-500 rounded-lg cursor-not-allowed">
                Continue <i class="fas fa-arrow-right ml-2"></i>
            </button>
        @endif
    </div>
</div>
@endsection
