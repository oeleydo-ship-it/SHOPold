@extends('layouts.vendor')

@section('title', 'Stock History - ' . $product->name)
@section('page-title', 'Stock History')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold">Stock History</h1>
        <p class="text-gray-500">{{ $product->name }}</p>
    </div>
    <a href="{{ route('vendor.stock.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
        <i class="fas fa-arrow-left mr-2"></i>Back to Stock
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Before</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Change</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">After</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Note</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($logs as $log)
            <tr>
                <td class="px-4 py-3 text-gray-500 text-sm">{{ $log->created_at->format('M d, Y H:i') }}</td>
                <td class="px-4 py-3 text-sm">{{ $log->user?->name ?? 'System' }}</td>
                <td class="px-4 py-3">
                    @if($log->type === 'add')
                        <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs rounded">{{ $log->type }}</span>
                    @elseif($log->type === 'remove' || $log->type === 'sale')
                        <span class="px-2 py-0.5 bg-red-100 text-red-700 text-xs rounded">{{ $log->type }}</span>
                    @else
                        <span class="px-2 py-0.5 bg-blue-100 text-blue-700 text-xs rounded">{{ $log->type }}</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-right text-sm">{{ $log->quantity_before }}</td>
                <td class="px-4 py-3 text-right text-sm {{ $log->quantity_change >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ $log->quantity_change >= 0 ? '+' : '' }}{{ $log->quantity_change }}
                </td>
                <td class="px-4 py-3 text-right text-sm font-medium">{{ $log->quantity_after }}</td>
                <td class="px-4 py-3 text-gray-500 text-sm">{{ $log->note ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                    <i class="fas fa-inbox text-4xl mb-2"></i>
                    <p>No history records found</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    <div class="p-4">{{ $logs->links('vendor.pagination.tailwind') }}</div>
</div>
@endsection
