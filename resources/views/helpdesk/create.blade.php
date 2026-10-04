@extends('layouts.account')

{{-- New support ticket form, inside the themed account shell. --}}
@section('title', 'Create Support Ticket')

@section('account_heading', 'New Support Ticket')

@section('account_actions')
    <a href="{{ route('helpdesk.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
        <i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>All tickets
    </a>
@endsection

@section('account_content_class', 'max-w-3xl')

@section('account_content')

    @if($errors->any())
    <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('helpdesk.store') }}" method="POST" class="space-y-6">
        @csrf
        
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-bold text-lg mb-4">Ticket Details</h3>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Subject *</label>
                    <input type="text" name="subject" value="{{ old('subject') }}" required
                           class="w-full px-4 py-2 border rounded-lg focus:ring-primary-500 focus:border-primary-500"
                           placeholder="Brief description of your issue">
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                        <select name="category" required class="w-full px-4 py-2 border rounded-lg focus:ring-primary-500 focus:border-primary-500">
                            @foreach($categories as $key => $label)
                            <option value="{{ $key }}" {{ old('category') == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Priority *</label>
                        <select name="priority" required class="w-full px-4 py-2 border rounded-lg focus:ring-primary-500 focus:border-primary-500">
                            <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Low</option>
                            <option value="medium" {{ old('priority', 'medium') == 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>High</option>
                            <option value="urgent" {{ old('priority') == 'urgent' ? 'selected' : '' }}>Urgent</option>
                        </select>
                    </div>
                </div>

                @if($orders->count() > 0)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Related Order (Optional)</label>
                    <select name="order_id" class="w-full px-4 py-2 border rounded-lg focus:ring-primary-500 focus:border-primary-500">
                        <option value="">-- Select an order --</option>
                        @foreach($orders as $order)
                        <option value="{{ $order->id }}" {{ old('order_id') == $order->id ? 'selected' : '' }}>
                            {{ $order->order_number }} - {{ $order->created_at->format('M d, Y') }} ({{ format_currency($order->total) }})
                        </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description *</label>
                    <textarea name="description" rows="6" required
                              class="w-full px-4 py-2 border rounded-lg focus:ring-primary-500 focus:border-primary-500"
                              placeholder="Please describe your issue in detail...">{{ old('description') }}</textarea>
                    <p class="text-xs text-gray-500 mt-1">Be as detailed as possible to help us resolve your issue quickly.</p>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('helpdesk.index') }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                <i class="fas fa-paper-plane mr-2"></i>Submit Ticket
            </button>
        </div>
    </form>
@endsection
