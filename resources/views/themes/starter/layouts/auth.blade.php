@extends('layouts.app')

{{--
    Starter theme login / register: a simple centred form on white with a primary rule on top and no card shadow.
    Same auth_card section as the shared shell, so the login and register forms are unchanged.
--}}
@section('content')
@include('auth.partials.skin')
<div class="auth-page auth-page--starter min-h-[60vh] flex items-start justify-center py-12 px-4">
    <div class="max-w-sm w-full">
        <div class="bg-white border border-gray-300 border-t-4 p-8" style="border-top-color: var(--color-primary, #059669);">
            @yield('auth_card')
        </div>
    </div>
</div>
@endsection
