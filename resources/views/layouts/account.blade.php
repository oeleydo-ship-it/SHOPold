@extends('layouts.app')

{{--
    Customer account shell: heading, account navigation (mobile + desktop) and the content column.
    Pages set @section('account_heading'), optionally @section('account_content_class') and @section('account_actions') (buttons beside the heading), and fill @section('account_content').
    A theme restyles every account page by overriding this file (themes/<slug>/layouts/account.blade.php)
    and/or account/partials/skin.blade.php and account/partials/sidebar.blade.php.
--}}
@section('content')
@include('account.partials.skin')
<div class="account-page max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 md:py-8">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6 md:mb-8">
        <h1 class="text-2xl md:text-3xl font-bold">@yield('account_heading')</h1>
        @yield('account_actions')
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
