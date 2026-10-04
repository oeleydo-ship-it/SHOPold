@extends('layouts.app')

@section('title', __('messages.downloads'))

@section('content')
@include('account.partials.skin')
<div class="account-page account-page--detail container mx-auto px-4 py-8">
    <div class="flex flex-col lg:flex-row gap-8">
        {{-- Sidebar --}}
        @include('account.partials.sidebar')

        {{-- Main Content --}}
        <div class="flex-1">
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-bold">{{ __('messages.downloads') }}</h1>
                        <p class="text-gray-500">{{ __('messages.order') }} #{{ $order->order_number }}</p>
                    </div>
                    <a href="{{ route('account.orders.show', $order) }}" class="text-primary-600 hover:text-primary-700">
                        <i class="fas fa-arrow-left mr-1"></i>{{ __('messages.back_to_order') }}
                    </a>
                </div>

                @if($digitalItems->isEmpty())
                    <div class="text-center py-12">
                        <i class="fas fa-download text-gray-300 text-5xl mb-4"></i>
                        <p class="text-gray-500">{{ __('messages.no_downloads_available') }}</p>
                    </div>
                @else
                    <div class="space-y-6">
                        @foreach($digitalItems as $item)
                            <div class="border rounded-lg p-4">
                                <div class="flex items-start gap-4 mb-4">
                                    <img src="{{ $item->product->image_url }}" alt="{{ $item->product_name }}" class="w-16 h-16 object-cover rounded-lg">
                                    <div class="flex-1">
                                        <h3 class="font-semibold">{{ $item->product_name }}</h3>
                                        <div class="text-sm text-gray-500 mt-1">
                                            @if($item->downloads_remaining !== null)
                                                <span class="mr-4">
                                                    <i class="fas fa-download mr-1"></i>
                                                    {{ $item->downloads_remaining }} {{ __('messages.downloads_remaining') }}
                                                </span>
                                            @else
                                                <span class="mr-4">
                                                    <i class="fas fa-infinity mr-1"></i>
                                                    {{ __('messages.unlimited_downloads') }}
                                                </span>
                                            @endif
                                            @if($item->download_expiry)
                                                <span class="{{ now()->gt($item->download_expiry) ? 'text-red-600' : '' }}">
                                                    <i class="fas fa-clock mr-1"></i>
                                                    @if(now()->gt($item->download_expiry))
                                                        {{ __('messages.expired') }}
                                                    @else
                                                        {{ __('messages.expires') }}: {{ $item->download_expiry->format('M d, Y') }}
                                                    @endif
                                                </span>
                                            @else
                                                <span>
                                                    <i class="fas fa-infinity mr-1"></i>
                                                    {{ __('messages.never_expires') }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- Download Files --}}
                                @if($item->product->digitalFiles->count() > 0)
                                    <div class="space-y-2">
                                        @foreach($item->product->digitalFiles as $file)
                                            @php
                                                $canDownload = true;
                                                $reason = '';
                                                
                                                if ($item->downloads_remaining !== null && $item->downloads_remaining <= 0) {
                                                    $canDownload = false;
                                                    $reason = __('messages.download_limit_exceeded');
                                                }
                                                
                                                if ($item->download_expiry && now()->gt($item->download_expiry)) {
                                                    $canDownload = false;
                                                    $reason = __('messages.download_expired');
                                                }
                                            @endphp
                                            
                                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-10 h-10 rounded-lg flex items-center justify-center {{ $file->file_type === 'url' ? 'bg-purple-100 text-purple-600' : 'bg-blue-100 text-blue-600' }}">
                                                        <i class="fas {{ $file->file_type === 'url' ? 'fa-link' : 'fa-file' }}"></i>
                                                    </div>
                                                    <div>
                                                        <div class="font-medium">{{ $file->name }}</div>
                                                        @if($file->file_type === 'upload' && $file->file_size)
                                                            <div class="text-xs text-gray-500">{{ $file->formatted_size }}</div>
                                                        @endif
                                                    </div>
                                                </div>
                                                
                                                @if($canDownload)
                                                    <a href="{{ route('download.file', ['token' => $item->download_token, 'file' => $file->id]) }}" 
                                                       class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition flex items-center gap-2">
                                                        <i class="fas fa-download"></i>
                                                        <span>{{ __('messages.download') }}</span>
                                                    </a>
                                                @else
                                                    <span class="px-4 py-2 bg-gray-200 text-gray-500 rounded-lg cursor-not-allowed" title="{{ $reason }}">
                                                        <i class="fas fa-ban mr-1"></i>
                                                        {{ __('messages.unavailable') }}
                                                    </span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-gray-500 text-sm">{{ __('messages.no_files_available') }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
