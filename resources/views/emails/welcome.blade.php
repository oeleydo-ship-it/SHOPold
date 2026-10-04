@extends('emails.layouts.branded')
@php($store = setting('store.name', config('app.name')))

@section('title', 'Welcome to ' . $store)
@section('preheader', 'Your account is ready. Here is what you can do next.')
@section('heading', 'Welcome to ' . $store . '!')

@section('content')
    <p style="margin:0 0 12px;">Hi {{ $user->name }},</p>
    <p style="margin:0 0 12px;">Thank you for joining {{ $store }}! We're excited to have you with us.</p>

    @include('emails.partials.box', ['title' => 'With your account you can', 'lines' => [
        '✓ Browse our full range of products',
        '✓ Track your orders in real time',
        '✓ Save items to your wishlist',
        '✓ Get exclusive deals and offers',
    ]])

    @include('emails.partials.button', ['url' => route('shop.index'), 'label' => 'Start shopping'])

    <p style="margin:0;">If you have any questions, just reply to this email or contact our support team.</p>
@endsection
