@extends('emails.layouts.branded')
@php($paid = $payout->status === 'completed')

@section('title', $paid ? 'Payout sent' : 'Payout not sent')
@section('preheader', ($paid ? 'We sent your payout of ' : 'We could not send your payout of ') . format_currency($payout->amount))
@section('heading', $paid ? 'Your payout is on its way' : 'Your payout could not be sent')

@section('content')
    <p style="margin:0 0 12px;">Hi {{ $payout->vendor?->store_name }},</p>
    @if($paid)
        <p style="margin:0 0 12px;">We've sent your payout of <strong>{{ format_currency($payout->amount) }}</strong> by {{ ucwords(str_replace('_', ' ', $payout->payment_method)) }}. Depending on your bank it can take a few days to arrive.</p>
    @else
        <p style="margin:0 0 12px;">We couldn't send your payout of <strong>{{ format_currency($payout->amount) }}</strong>. Please check your payout details; the amount is available to request again.</p>
    @endif

    @include('emails.partials.box', ['title' => 'Payout #' . $payout->id, 'lines' => [
        'Amount: ' . format_currency($payout->amount),
        'Method: ' . ucwords(str_replace('_', ' ', $payout->payment_method)),
        $payout->transaction_id ? 'Reference: ' . $payout->transaction_id : null,
        'Requested: ' . $payout->created_at?->format('M d, Y'),
    ]])

    @include('emails.partials.button', ['url' => route($paid ? 'vendor.payouts' : 'vendor.payment-settings'), 'label' => $paid ? 'View payouts' : 'Check payout details'])
@endsection
