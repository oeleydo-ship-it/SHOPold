@extends('layouts.app')

{{--
    Login / register shell: a centred card. Pages fill @section('auth_card') (heading, form, links).
    A theme restyles them by overriding this file (themes/<slug>/layouts/auth.blade.php) and/or auth/partials/skin.blade.php.
--}}
@section('content')
@include('auth.partials.skin')
<div class="auth-page min-h-[70vh] flex items-center justify-center py-12 px-4">
    <div class="max-w-md w-full">
        <div class="bg-white rounded-2xl shadow-sm p-8">
            @yield('auth_card')
        </div>
    </div>
</div>
@endsection
