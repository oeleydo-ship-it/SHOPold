@extends('layouts.app')

{{--
    Modern theme account shell: a gradient welcome band with the customer's initial and the page heading,
    then the account navigation and content in soft rounded cards. Same sections as the shared shell
    (account_heading / account_content_class / account_content), so every account page works unchanged.
--}}
@section('content')
@include('account.partials.skin')
<div class="account-page account-page--modern max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 md:py-10">
    <div class="rounded-3xl px-6 py-7 md:px-8 mb-6 md:mb-8 text-white flex items-center gap-4 shadow-lg"
        style="background: linear-gradient(135deg, var(--color-primary, #7c3aed), var(--color-accent, #f472b6));">
        <span class="w-14 h-14 shrink-0 rounded-2xl bg-white/20 backdrop-blur flex items-center justify-center text-2xl font-extrabold ring-2 ring-white/30">
            {{ mb_strtoupper(mb_substr(auth()->user()->name ?? '?', 0, 1)) }}
        </span>
        <div class="min-w-0">
            <p class="text-white/80 text-sm">Hi, {{ auth()->user()->name }}</p>
            <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight">@yield('account_heading')</h1>
        </div>
        @hasSection('account_actions')
            <div class="ml-auto [&_a]:!bg-white [&_a]:!text-gray-900 [&_a]:!rounded-full">@yield('account_actions')</div>
        @endif
    </div>

    {{-- Mobile Sidebar (horizontal navigation) --}}
    <div class="lg:hidden">
        @include('account.partials.sidebar')
    </div>

    <div class="grid lg:grid-cols-4 gap-6 md:gap-8 items-start">
        {{-- Desktop Sidebar --}}
        <div class="hidden lg:block lg:sticky lg:top-28">
            @include('account.partials.sidebar')
        </div>

        <!-- Content -->
        <div class="lg:col-span-3 @yield('account_content_class')">
            @yield('account_content')
        </div>
    </div>
</div>
@endsection
