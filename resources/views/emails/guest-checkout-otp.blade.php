@extends('emails.layouts.branded')
@php($brand = \App\View\EmailBrand::data())

@section('title', 'Your verification code')
@section('preheader', 'Your checkout verification code is ' . $otp)
@section('heading', 'Verify your email')

@section('content')
    <p style="margin:0 0 12px;text-align:center;">You're checking out as a guest. Use this code to continue with your order.</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0;">
        <tr>
            <td align="center" style="background:{{ $brand['primary'] }};border-radius:12px;padding:24px;">
                <p style="margin:0 0 8px;color:#ffffff;font-size:13px;text-transform:uppercase;letter-spacing:1px;">Your verification code</p>
                <p style="margin:0;color:#ffffff;font-size:34px;font-weight:bold;letter-spacing:8px;font-family:'Courier New',monospace;">{{ $otp }}</p>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 16px;text-align:center;font-size:14px;color:#6b7280;"><strong>This code expires in {{ $expiry }} minutes.</strong></p>
    <p style="margin:0;padding:12px 16px;background:#fef3c7;border-radius:8px;font-size:13px;color:#92400e;">
        <strong>Security notice:</strong> if you didn't request this code, ignore this email. Never share this code with anyone.
    </p>
@endsection

@section('footer_note')
    <p style="margin:4px 0 0;">This is an automated message. Please do not reply.</p>
@endsection
