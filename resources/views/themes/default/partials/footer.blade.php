{{-- Default theme footer: starts as a copy of the shared footer. Edit this file to change the default theme only. --}}
{{--
    Shared storefront footer. Themes can replace it with themes/<slug>/partials/footer.blade.php.
    Data (colors, links, socials, copyright...) comes from App\View\StoreFooter so every theme reads the same settings.
    Layouts: default (4 columns), minimal (centered), expanded (newsletter band + 4 columns).
--}}
@php($ft = \App\View\StoreFooter::data($appSettings ?? []))
<footer class="mt-auto store-footer" style="background-color: {{ $ft['bg'] }}; color: {{ $ft['text'] }};">
    <style>
        .store-footer a { transition: color .15s; }
        .store-footer a:hover { color: {{ $ft['hover'] }} !important; }
        .store-footer .footer-heading { color: #ffffff; }
    </style>

    @if($ft['style'] === 'expanded' && $ft['showNewsletter'])
        <div class="border-b border-white/10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div>
                    <h4 class="footer-heading text-xl font-bold">{{ __('messages.newsletter') ?? 'Newsletter' }}</h4>
                    <p class="text-sm mt-1">{{ __('messages.subscribe_newsletter_desc') ?? 'Get the latest offers and news in your inbox.' }}</p>
                </div>
                <form action="{{ route('newsletter.subscribe') }}" method="POST" class="flex w-full md:w-auto gap-2">
                    @csrf
                    <input type="email" name="email" required placeholder="{{ __('messages.enter_email') ?? 'Your email address' }}" aria-label="Email address"
                        class="flex-1 md:w-80 px-4 py-2.5 rounded-lg bg-white/10 border border-white/20 text-white placeholder-white/50 focus:outline-none focus:border-white/60">
                    <button type="submit" class="px-5 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white font-semibold">{{ __('messages.subscribe') ?? 'Subscribe' }}</button>
                </form>
            </div>
        </div>
    @endif

    @if($ft['style'] === 'minimal')
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 text-center">
            <div class="flex items-center justify-center gap-2 mb-4">
                @if($ft['logo'])
                    <img src="{{ $ft['logo'] }}" alt="{{ $ft['storeName'] }}" class="h-9 w-auto">
                @endif
                @if(!$ft['hideName'])
                    <span class="footer-heading text-lg font-bold">{{ $ft['storeName'] }}</span>
                @endif
            </div>
            @if($ft['showTagline'])
                <p class="text-sm max-w-xl mx-auto mb-5">{{ $ft['tagline'] }}</p>
            @endif
            <nav class="flex flex-wrap justify-center gap-x-6 gap-y-2 text-sm mb-5" aria-label="Footer">
                @foreach(array_merge($ft['quick'], $ft['service']) as $link)
                    <a href="{{ $link['url'] }}">{{ $link['label'] }}</a>
                @endforeach
            </nav>
            @if($ft['showSocial'] && count($ft['social']))
                <div class="flex justify-center gap-4 mb-5">
                    @foreach($ft['social'] as $s)
                        <a href="{{ $s['url'] }}" target="_blank" rel="noopener noreferrer" aria-label="{{ ucfirst($s['network']) }}"><i class="fab {{ $s['icon'] }}"></i></a>
                    @endforeach
                </div>
            @endif
            <p class="text-xs">{{ $ft['copyright'] }}</p>
        </div>
    @else
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- About -->
                <div>
                    <div class="flex items-center space-x-2 mb-4">
                        @if($ft['logo'])
                            <img src="{{ $ft['logo'] }}" alt="{{ $ft['storeName'] }}" class="h-10 w-auto">
                        @else
                            <div class="w-10 h-10 bg-primary-600 rounded-lg flex items-center justify-center">
                                <i class="fas fa-shopping-bag text-white text-xl"></i>
                            </div>
                        @endif
                        @if(!$ft['hideName'])
                            <span class="footer-heading text-xl font-bold">{{ $ft['storeName'] }}</span>
                        @endif
                    </div>
                    @if($ft['showTagline'])
                        <p class="text-sm">{{ $ft['tagline'] }}</p>
                    @endif
                    @if($ft['showSocial'] && count($ft['social']))
                        <div class="flex space-x-4 mt-4">
                            @foreach($ft['social'] as $s)
                                <a href="{{ $s['url'] }}" target="_blank" rel="noopener noreferrer" aria-label="{{ ucfirst($s['network']) }}"><i class="fab {{ $s['icon'] }}"></i></a>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="footer-heading text-lg font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2">
                        @foreach($ft['quick'] as $link)
                            <li><a href="{{ $link['url'] }}">{{ $link['label'] }}</a></li>
                        @endforeach
                    </ul>
                </div>

                <!-- Customer Service -->
                <div>
                    <h4 class="footer-heading text-lg font-semibold mb-4">Customer Service</h4>
                    <ul class="space-y-2">
                        @foreach($ft['service'] as $link)
                            <li><a href="{{ $link['url'] }}">{{ $link['label'] }}</a></li>
                        @endforeach
                    </ul>
                </div>

                <!-- Contact Info -->
                <div>
                    <h4 class="footer-heading text-lg font-semibold mb-4">Contact Us</h4>
                    <ul class="space-y-3">
                        @if($ft['address'])
                            <li class="flex items-start"><i class="fas fa-map-marker-alt mt-1 mr-3 text-primary-500"></i><span>{{ $ft['address'] }}</span></li>
                        @endif
                        @if($ft['phone'])
                            <li class="flex items-center"><i class="fas fa-phone-alt mr-3 text-primary-500"></i><span>{{ $ft['phone'] }}</span></li>
                        @endif
                        @if($ft['email'])
                            <li class="flex items-center"><i class="fas fa-envelope mr-3 text-primary-500"></i><span>{{ $ft['email'] }}</span></li>
                        @endif
                    </ul>
                </div>
            </div>

            <hr class="border-white/10 my-8">

            <div class="flex flex-col md:flex-row items-center justify-between">
                <p class="text-sm">{{ $ft['copyright'] }}</p>
                <div class="flex items-center space-x-4 mt-4 md:mt-0">
                    @foreach($ft['payments'] as $p)
                        <img src="{{ $p['src'] }}" alt="{{ $p['name'] }}" class="h-6">
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</footer>
