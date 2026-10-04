{{--
    Starter theme footer: plain three-column layout with a top rule, text-only social links and a one-line bottom bar.
    Uses the same footer settings as every theme (colors, links, socials, toggles, copyright) via App\View\StoreFooter.
--}}
@php($ft = \App\View\StoreFooter::data($appSettings ?? []))
<footer class="mt-auto store-footer border-t-4" style="background-color: {{ $ft['bg'] }}; color: {{ $ft['text'] }}; border-color: var(--color-primary, #059669);">
    <style>
        .store-footer a { text-decoration: none; }
        .store-footer a:hover { color: {{ $ft['hover'] }} !important; text-decoration: underline; }
        .store-footer .footer-heading { color: #ffffff; }
    </style>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div>
                @if(!$ft['hideName'])
                    <p class="footer-heading text-lg font-bold uppercase tracking-wide mb-2">{{ $ft['storeName'] }}</p>
                @elseif($ft['logo'])
                    <img src="{{ $ft['logo'] }}" alt="{{ $ft['storeName'] }}" class="h-8 w-auto mb-2">
                @endif
                @if($ft['showTagline'])
                    <p class="text-sm">{{ $ft['tagline'] }}</p>
                @endif
                <ul class="mt-4 space-y-1 text-sm">
                    @if($ft['address'])<li>{{ $ft['address'] }}</li>@endif
                    @if($ft['phone'])<li>{{ $ft['phone'] }}</li>@endif
                    @if($ft['email'])<li><a href="mailto:{{ $ft['email'] }}">{{ $ft['email'] }}</a></li>@endif
                </ul>
            </div>

            <div class="grid grid-cols-2 gap-6 text-sm">
                <div>
                    <p class="footer-heading font-semibold uppercase text-xs tracking-wider mb-3">Shop</p>
                    <ul class="space-y-1.5">
                        @foreach($ft['quick'] as $link)<li><a href="{{ $link['url'] }}">{{ $link['label'] }}</a></li>@endforeach
                    </ul>
                </div>
                <div>
                    <p class="footer-heading font-semibold uppercase text-xs tracking-wider mb-3">Service</p>
                    <ul class="space-y-1.5">
                        @foreach($ft['service'] as $link)<li><a href="{{ $link['url'] }}">{{ $link['label'] }}</a></li>@endforeach
                    </ul>
                </div>
            </div>

            <div class="text-sm">
                @if($ft['showNewsletter'])
                    <p class="footer-heading font-semibold uppercase text-xs tracking-wider mb-3">{{ __('messages.newsletter') ?? 'Newsletter' }}</p>
                    <form action="{{ route('newsletter.subscribe') }}" method="POST" class="flex">
                        @csrf
                        <input type="email" name="email" required placeholder="{{ __('messages.enter_email') ?? 'Your email address' }}" aria-label="Email address"
                            class="flex-1 min-w-0 px-3 py-2 bg-white text-gray-900 border-0 rounded-none focus:outline-none">
                        <button type="submit" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-semibold uppercase text-xs tracking-wide rounded-none">{{ __('messages.subscribe') ?? 'Subscribe' }}</button>
                    </form>
                @endif
                @if($ft['showSocial'] && count($ft['social']))
                    <p class="mt-5 flex flex-wrap gap-x-4 gap-y-1">
                        @foreach($ft['social'] as $s)
                            <a href="{{ $s['url'] }}" target="_blank" rel="noopener noreferrer">{{ ucfirst($s['network']) }}</a>
                        @endforeach
                    </p>
                @endif
            </div>
        </div>

        <p class="mt-8 pt-4 border-t border-white/20 text-xs">{{ $ft['copyright'] }}</p>
    </div>
</footer>
