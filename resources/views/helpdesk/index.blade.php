@extends('layouts.account')

{{-- Customer helpdesk list. Uses the account shell, so each theme's account styling and navigation apply. --}}
@section('title', 'My Support Tickets')

@section('account_heading', 'My Support Tickets')

@section('account_actions')
    <a href="{{ route('helpdesk.create') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
        <i class="fas fa-plus mr-2" aria-hidden="true"></i>New Ticket
    </a>
@endsection

@section('account_content')
            @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                {{ session('success') }}
            </div>
            @endif

            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                @if($tickets->count() > 0)
                <div class="divide-y">
                    @foreach($tickets as $ticket)
                    <a href="{{ route('helpdesk.show', $ticket) }}" class="block p-4 hover:bg-gray-50 transition">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-xs font-mono text-gray-500">{{ $ticket->ticket_number }}</span>
                                    @if($ticket->created_by_ai)
                                    <span class="px-2 py-0.5 text-xs bg-purple-100 text-purple-800 rounded-full">AI Created</span>
                                    @endif
                                </div>
                                <h3 class="font-semibold text-gray-900 truncate">{{ $ticket->subject }}</h3>
                                <p class="text-sm text-gray-500 mt-1 line-clamp-2">{{ Str::limit($ticket->description, 100) }}</p>
                            </div>
                            <div class="flex flex-col items-end gap-2">
                                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $ticket->status_badge }}">
                                    {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                                </span>
                                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $ticket->priority_badge }}">
                                    {{ ucfirst($ticket->priority) }}
                                </span>
                                <span class="text-xs text-gray-500">{{ $ticket->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
                
                <div class="p-4 border-t">
                    {{ $tickets->links() }}
                </div>
                @else
                <div class="p-12 text-center">
                    <i class="fas fa-ticket-alt text-4xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500 mb-4">You haven't created any support tickets yet.</p>
                    <a href="{{ route('helpdesk.create') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                        <i class="fas fa-plus mr-2"></i>Create Your First Ticket
                    </a>
                </div>
                @endif
            </div>
@endsection
