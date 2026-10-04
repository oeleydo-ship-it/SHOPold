@extends('layouts.driver')

@section('title', 'Notifications')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Notifications</h1>
        <p class="text-gray-500">Stay updated with your deliveries</p>
    </div>
    @if($notifications->where('is_read', false)->count() > 0)
        <form action="{{ route('driver.notifications.read-all') }}" method="POST">
            @csrf
            <button type="submit" class="px-4 py-2 text-driver-600 hover:bg-driver-50 rounded-lg">
                <i class="fas fa-check-double mr-1"></i> Mark All as Read
            </button>
        </form>
    @endif
</div>

<div class="bg-white rounded-xl shadow-sm">
    @if($notifications->isEmpty())
        <div class="p-8 text-center text-gray-500">
            <i class="fas fa-bell-slash text-4xl mb-3"></i>
            <p>No notifications yet</p>
            <p class="text-sm">You'll receive notifications about new orders and updates here</p>
        </div>
    @else
        <div class="divide-y">
            @foreach($notifications as $notification)
                <div class="p-4 flex items-start gap-4 {{ $notification->is_read ? 'bg-white' : 'bg-blue-50' }} hover:bg-gray-50">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center bg-gray-100 {{ $notification->color }}">
                        <i class="fas {{ $notification->icon }}"></i>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="font-medium {{ $notification->is_read ? 'text-gray-700' : 'text-gray-900' }}">
                                    {{ $notification->title }}
                                </p>
                                <p class="text-sm text-gray-600 mt-1">{{ $notification->message }}</p>
                                @if($notification->order)
                                    <a href="{{ route('driver.orders.show', $notification->order) }}" 
                                       class="text-sm text-driver-600 hover:underline mt-1 inline-block">
                                        View Order →
                                    </a>
                                @endif
                            </div>
                            @if(!$notification->is_read)
                                <form action="{{ route('driver.notifications.read', $notification) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="text-gray-400 hover:text-driver-600" title="Mark as read">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                        <p class="text-xs text-gray-400 mt-2">{{ $notification->created_at->diffForHumans() }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        
        <div class="p-4 border-t">
            {{ $notifications->links() }}
        </div>
    @endif
</div>
@endsection
