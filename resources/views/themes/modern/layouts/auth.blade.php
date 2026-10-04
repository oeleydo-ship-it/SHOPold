@extends('layouts.app')

{{--
    Modern theme login / register: split screen. A gradient brand panel with the store name, tagline and
    three reasons to sign up on the left (desktop only), the form card on the right. Same auth_card section
    as the shared shell, so the login and register forms are unchanged.
--}}
@section('content')
@include('auth.partials.skin')
<div class="auth-page auth-page--modern py-10 px-4">
    <div class="max-w-5xl mx-auto grid lg:grid-cols-2 rounded-3xl overflow-hidden shadow-2xl ring-1 ring-gray-100 bg-white">
        <div class="hidden lg:flex flex-col justify-between p-10 text-white"
            style="background: linear-gradient(135deg, var(--color-primary, #7c3aed), var(--color-accent, #f472b6));">
            <div>
                <p class="text-sm uppercase tracking-widest text-white/70">{{ $appSettings['store_name'] ?? config('app.name') }}</p>
                <h2 class="mt-3 text-3xl font-extrabold leading-tight">{{ ($appSettings['store_tagline'] ?? '') ?: 'Shop smarter, faster.' }}</h2>
            </div>
            <ul class="space-y-4 text-white/90">
                <li class="flex items-center gap-3"><span class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center"><i class="fas fa-truck-fast"></i></span>Track every order in one place</li>
                <li class="flex items-center gap-3"><span class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center"><i class="fas fa-heart"></i></span>Save favourites to your wishlist</li>
                <li class="flex items-center gap-3"><span class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center"><i class="fas fa-bolt"></i></span>Faster checkout next time</li>
            </ul>
        </div>
        <div class="p-8 sm:p-10 lg:p-12 flex items-center">
            <div class="w-full max-w-md mx-auto">
                @yield('auth_card')
            </div>
        </div>
    </div>
</div>
@endsection
