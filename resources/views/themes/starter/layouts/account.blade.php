@extends('layouts.app')

{{--
    Starter theme account shell: plain heading with a primary rule, account navigation on the left as a simple
    bordered list, content on the right. Same sections as the shared shell (account_heading /
    account_content_class / account_content), so every account page works unchanged.
--}}
@section('content')
@include('account.partials.skin')
<div class="account-page account-page--starter max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex flex-wrap items-end justify-between gap-2 mb-6 pb-3 border-b-2" style="border-color: var(--color-primary, #059669);">
        <h1 class="text-2xl md:text-3xl font-bold">@yield('account_heading')</h1>
        @hasSection('account_actions')
            @yield('account_actions')
        @else
            <span class="text-sm text-gray-500">{{ auth()->user()->email }}</span>
        @endif
    </div>

    {{-- Mobile Sidebar (horizontal navigation) --}}
    <div class="lg:hidden">
        @include('account.partials.sidebar')
    </div>

    <div class="grid lg:grid-cols-4 gap-6 md:gap-8">
        {{-- Desktop Sidebar --}}
        <div class="hidden lg:block">
            @include('account.partials.sidebar')
        </div>

        <!-- Content -->
        <div class="lg:col-span-3 @yield('account_content_class')">
            @yield('account_content')
        </div>
    </div>
</div>
@endsection
