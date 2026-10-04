{{--
    Modern theme footer: newsletter card, brand column with social pills, link columns and a slim bottom bar.
    Uses the same footer settings as every theme (colors, links, socials, toggles, copyright) via App\View\StoreFooter.
--}}
@php($ft = \App\View\StoreFooter::data($appSettings))
<footer class="mt-auto store-footer relative" style="background-color: {{ $ft['bg'] }}; color: {{ $ft['text'] }};">
    <style>
        .store-footer a { transition: color .15s; }
        .store-footer a:hover { color: {{ $ft['hover'] }} !important; }
        .store-footer .footer-heading { color: #ffffff; }
    </style>
    <div class="h-1 w-full" style="background: linear-gradient(90deg, var(--color-primary, #7c3aed), var(--color-accent, #f472b6));"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-12 pb-8">
        @if($ft['showNewsletter'])
            <div class="mb-12 rounded-3xl p-6 md:p-8 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6"
                style="background: linear-gradient(135deg, var(--color-primary, #7c3aed), var(--color-secondary, #5b21b6));">
                <div class="text-white">
                    <h4 class="text-2xl font-extrabold">{{ __('messages.newsletter') ?? 'Newsletter' }}</h4>
                    <p class="text-white/80 mt-1">{{ __('messages.subscribe_newsletter_desc') ?? 'Get the latest offers and news in your inbox.' }}</p>
                </div>
                <form action="{{ route('newsletter.subscribe') }}" method="POST" class="flex w-full lg:w-auto bg-white rounded-full p-1.5 shadow-lg">
                    @csrf
                    <input type="email" name="email" required placeholder="{{ __('messages.enter_email') ?? 'Your email address' }}" aria-label="Email address"
                        class="flex-1 lg:w-80 px-4 py-2 bg-transparent text-gray-900 placeholder-gray-400 focus:outline-none">
                    <button type="submit" class="px-6 py-2 rounded-full bg-gray-900 hover:bg-black text-white font-semibold">{{ __('messages.subscribe') ?? 'Subscribe' }}</button>
                </form>
            </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-10">
            <div class="lg:col-span-5">
                <div class="flex items-center gap-3 mb-4">
                    @if($ft['logo'])
                        <img src="{{ $ft['logo'] }}" alt="{{ $ft['storeName'] }}" class="h-10 w-auto">
                    @else
                        <span class="w-11 h-11 rounded-2xl flex items-center justify-center text-white shadow-lg"
                            style="background: linear-gradient(135deg, var(--color-primary, #7c3aed), var(--color-accent, #f472b6));"><i class="fas fa-shopping-bag"></i></span>
                    @endif
                    @if(!$ft['hideName'])
                        <span class="footer-heading text-2xl font-extrabold tracking-tight">{{ $ft['storeName'] }}</span>
                    @endif
                </div>
                @if($ft['showTagline'])
                    <p class="text-sm leading-relaxed max-w-sm">{{ $ft['tagline'] }}</p>
                @endif
                @if($ft['showSocial'] && count($ft['social']))
                    <div class="flex flex-wrap gap-2 mt-5">
                        @foreach($ft['social'] as $s)
                            <a href="{{ $s['url'] }}" target="_blank" rel="noopener noreferrer" aria-label="{{ ucfirst($s['network']) }}"
                                class="w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center"><i class="fab {{ $s['icon'] }}"></i></a>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="lg:col-span-2">
                <h4 class="footer-heading text-sm font-bold uppercase tracking-widest mb-4">Shop</h4>
                <ul class="space-y-2.5 text-sm">
                    @foreach($ft['quick'] as $link)
                        <li><a href="{{ $link['url'] }}">{{ $link['label'] }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div class="lg:col-span-2">
                <h4 class="footer-heading text-sm font-bold uppercase tracking-widest mb-4">Help</h4>
                <ul class="space-y-2.5 text-sm">
                    @foreach($ft['service'] as $link)
                        <li><a href="{{ $link['url'] }}">{{ $link['label'] }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div class="lg:col-span-3">
                <h4 class="footer-heading text-sm font-bold uppercase tracking-widest mb-4">Contact</h4>
                <ul class="space-y-3 text-sm">
                    @if($ft['address'])<li class="flex gap-3"><i class="fas fa-location-dot mt-0.5 text-primary-400"></i><span>{{ $ft['address'] }}</span></li>@endif
                    @if($ft['phone'])<li class="flex gap-3"><i class="fas fa-phone mt-0.5 text-primary-400"></i><span>{{ $ft['phone'] }}</span></li>@endif
                    @if($ft['email'])<li class="flex gap-3"><i class="fas fa-envelope mt-0.5 text-primary-400"></i><span>{{ $ft['email'] }}</span></li>@endif
                </ul>
            </div>
        </div>

        <div class="mt-12 pt-6 border-t border-white/10 flex flex-col md:flex-row items-center justify-between gap-4">
            <p class="text-xs">{{ $ft['copyright'] }}</p>
            <div class="flex items-center gap-2">
                @foreach($ft['payments'] as $p)
                    <span class="bg-white rounded-lg px-2 py-1"><img src="{{ $p['src'] }}" alt="{{ $p['name'] }}" class="h-5"></span>
                @endforeach
            </div>
        </div>
    </div>
</footer>
