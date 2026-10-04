@extends('emails.layouts.branded')

@section('title', 'Verify your email address')
@section('preheader', 'Confirm your email address to activate your account.')
@section('heading', 'Verify your email address')

@section('content')
    <p style="margin:0 0 12px;">Hello {{ $user->name }},</p>
    <p style="margin:0 0 12px;">Thank you for registering! Please confirm your email address to activate your account.</p>

    @include('emails.partials.button', ['url' => $verificationUrl, 'label' => 'Verify email address'])

    <p style="margin:0 0 12px;font-size:13px;color:#6b7280;">This link expires in 24 hours. If you did not create an account, you can ignore this email.</p>
    <p style="margin:0;font-size:12px;color:#6b7280;">If the button doesn't work, copy this link into your browser:<br>
        <span style="word-break:break-all;color:#374151;">{{ $verificationUrl }}</span></p>
@endsection
