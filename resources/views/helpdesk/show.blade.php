@extends('layouts.account')

{{-- One support ticket with its conversation, inside the themed account shell. --}}
@section('title', 'Ticket #' . $ticket->ticket_number)

@section('account_heading', 'Ticket #' . $ticket->ticket_number)

@section('account_actions')
    <div class="flex items-center gap-2">
        <a href="{{ route('helpdesk.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
            <i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>All tickets
        </a>
        @if($ticket->status !== 'closed')
        <form action="{{ route('helpdesk.close', $ticket) }}" method="POST" onsubmit="return confirm('Are you sure you want to close this ticket?')">
            @csrf
            @method('PATCH')
            <button type="submit" class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                <i class="fas fa-check mr-2" aria-hidden="true"></i>Close Ticket
            </button>
        </form>
        @endif
    </div>
@endsection

@section('account_content')
    <h2 class="text-xl font-bold mb-4">{{ $ticket->subject }}</h2>

    @if(session('success'))
    <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
        {{ session('success') }}
    </div>
    @endif

    <div class="grid xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 space-y-6">
            <!-- Original Description -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-primary-100 text-primary-600 rounded-full flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-medium">{{ $ticket->user->name }}</span>
                            <span class="text-sm text-gray-500">{{ $ticket->created_at->format('M d, Y h:i A') }}</span>
                        </div>
                        <div class="prose prose-sm max-w-none">
                            {!! nl2br(e($ticket->description)) !!}
                        </div>
                        @if($ticket->created_by_ai)
                        <div class="mt-3 flex items-center gap-2 text-sm text-purple-600">
                            <i class="fas fa-robot"></i>
                            <span>This ticket was created via AI assistant</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- AI Conversation (if exists) -->
            @if($ticket->aiConversation && $ticket->aiConversation->messages->count() > 0)
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="font-bold text-lg mb-4 flex items-center gap-2">
                    <i class="fas fa-robot text-purple-600"></i>
                    AI Conversation History
                </h3>
                <div class="space-y-3 max-h-64 overflow-y-auto">
                    @foreach($ticket->aiConversation->messages as $message)
                    <div class="flex gap-3 {{ $message->role === 'assistant' ? 'flex-row-reverse' : '' }}">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 {{ $message->role === 'assistant' ? 'bg-purple-100 text-purple-600' : 'bg-gray-100 text-gray-600' }}">
                            <i class="fas {{ $message->role === 'assistant' ? 'fa-robot' : 'fa-user' }} text-sm"></i>
                        </div>
                        <div class="max-w-[80%] px-4 py-2 rounded-lg {{ $message->role === 'assistant' ? 'bg-purple-50' : 'bg-gray-100' }}">
                            <p class="text-sm">{{ $message->content }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Replies -->
            @if($ticket->replies->count() > 0)
            <div class="space-y-4">
                @foreach($ticket->replies as $reply)
                <div class="bg-white rounded-xl shadow-sm p-6 {{ $reply->is_admin_reply ? 'border-l-4 border-primary-500' : '' }}">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 {{ $reply->is_admin_reply ? 'bg-primary-100 text-primary-600' : 'bg-gray-100 text-gray-600' }}">
                            <i class="fas {{ $reply->is_admin_reply ? 'fa-headset' : 'fa-user' }}"></i>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-medium">
                                    {{ $reply->user->name ?? 'Support Team' }}
                                    @if($reply->is_admin_reply)
                                    <span class="ml-2 px-2 py-0.5 text-xs bg-primary-100 text-primary-800 rounded-full">Support</span>
                                    @endif
                                </span>
                                <span class="text-sm text-gray-500">{{ $reply->created_at->format('M d, Y h:i A') }}</span>
                            </div>
                            <div class="prose prose-sm max-w-none">
                                {!! nl2br(e($reply->message)) !!}
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            <!-- Reply Form -->
            @if(!in_array($ticket->status, ['closed', 'resolved']))
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="font-bold mb-4">Add Reply</h3>
                <form action="{{ route('helpdesk.reply', $ticket) }}" method="POST">
                    @csrf
                    <textarea name="message" rows="4" required
                              class="w-full px-4 py-2 border rounded-lg focus:ring-primary-500 focus:border-primary-500 mb-4"
                              placeholder="Type your reply..."></textarea>
                    <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                        <i class="fas fa-reply mr-2"></i>Send Reply
                    </button>
                </form>
            </div>
            @else
            <div class="bg-gray-50 rounded-xl p-6 text-center text-gray-500">
                <i class="fas fa-lock text-2xl mb-2"></i>
                <p>This ticket is {{ $ticket->status }}. No more replies can be added.</p>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="font-bold mb-4">Ticket Details</h3>
                <div class="space-y-4">
                    <div>
                        <span class="text-sm text-gray-500">Status</span>
                        <p class="mt-1">
                            <span class="px-3 py-1 text-sm font-medium rounded-full {{ $ticket->status_badge }}">
                                {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                            </span>
                        </p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Priority</span>
                        <p class="mt-1">
                            <span class="px-3 py-1 text-sm font-medium rounded-full {{ $ticket->priority_badge }}">
                                {{ ucfirst($ticket->priority) }}
                            </span>
                        </p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Category</span>
                        <p class="mt-1 font-medium">{{ ucfirst(str_replace('_', ' ', $ticket->category ?? 'General')) }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Created</span>
                        <p class="mt-1 font-medium">{{ $ticket->created_at->format('M d, Y h:i A') }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Last Updated</span>
                        <p class="mt-1 font-medium">{{ $ticket->updated_at->diffForHumans() }}</p>
                    </div>
                </div>
            </div>

            @if($ticket->order)
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="font-bold mb-4">Related Order</h3>
                <a href="{{ route('account.orders.show', $ticket->order) }}" class="block p-3 bg-gray-50 rounded-lg hover:bg-gray-100">
                    <p class="font-medium">{{ $ticket->order->order_number }}</p>
                    <p class="text-sm text-gray-500">{{ $ticket->order->created_at->format('M d, Y') }}</p>
                    <p class="text-sm font-medium text-primary-600">{{ format_currency($ticket->order->total) }}</p>
                </a>
            </div>
            @endif
        </div>
    </div>
@endsection
