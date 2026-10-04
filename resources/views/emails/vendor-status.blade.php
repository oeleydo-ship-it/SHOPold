@extends('emails.layouts.branded')

@section('title', 'Your store status')
@section('preheader', 'Your store ' . $vendor->store_name . ' is now ' . $vendor->status . '.')
@section('heading', match ($vendor->status) { 'approved' => 'Your store is approved!', 'rejected' => 'Application not approved', 'suspended' => 'Store suspended', default => 'Store status update' })

@section('content')
    <p style="margin:0 0 12px;">Hi {{ $vendor->user?->name ?? $vendor->store_name }},</p>

    @if($vendor->status === 'approved')
        <p style="margin:0 0 12px;">Good news: <strong>{{ $vendor->store_name }}</strong> is approved. You can now add products, receive orders and request payouts.</p>
        @include('emails.partials.button', ['url' => route('vendor.dashboard'), 'label' => 'Go to your vendor portal'])
    @elseif($vendor->status === 'rejected')
        <p style="margin:0 0 12px;">We're sorry, your application for <strong>{{ $vendor->store_name }}</strong> was not approved. Reply to this email if you'd like to know more.</p>
    @elseif($vendor->status === 'suspended')
        <p style="margin:0 0 12px;"><strong>{{ $vendor->store_name }}</strong> has been suspended, so its products are not shown in the shop for now. Reply to this email to talk to us about it.</p>
    @else
        <p style="margin:0 0 12px;">The status of <strong>{{ $vendor->store_name }}</strong> is now: {{ ucfirst($vendor->status) }}.</p>
    @endif
@endsection
