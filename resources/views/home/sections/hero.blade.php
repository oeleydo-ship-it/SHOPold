@php
    $heroStyle = $appSettings['theme']['hero_style'] ?? 'gradient';
    $headerStyle = $appSettings['theme']['header_style'] ?? 'default';
    $isTransparentHeader = $headerStyle === 'transparent';
    $heroBadgeIcon = $appSettings['homepage']['hero_badge_icon'] ?? 'star';
    $heroButtonLink = $appSettings['homepage']['hero_button_link'] ?? '/shop';
    $heroSecondaryLink = $appSettings['homepage']['hero_secondary_link'] ?? '/about';
    $heroImage = !empty($appSettings['theme']['hero_image']) ? asset('storage/' . $appSettings['theme']['hero_image']) : 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=1200&h=600&fit=crop';

    // Always use translations for text content (allows dynamic language switching)
    $heroBadge = __('messages.new_collection') . ' ' . date('Y');
    $heroTitle = __('messages.discover_premium');
    $heroSubtitle = __('messages.hero_subtitle');
    $heroButtonText = __('messages.shop_now');
    $heroSecondaryText = __('messages.learn_more');

    // Slider settings
    $heroSlides = [];
    if (!empty($appSettings['theme']['hero_slides'])) {
        $slidesData = is_string($appSettings['theme']['hero_slides'])
            ? json_decode($appSettings['theme']['hero_slides'], true)
            : $appSettings['theme']['hero_slides'];
        if (is_array($slidesData)) {
            $heroSlides = $slidesData;
        }
    }
    $sliderInterval = (int) ($appSettings['theme']['slider_interval'] ?? 5000);
    $sliderTransition = $appSettings['theme']['slider_transition'] ?? 'fade';
    $sliderNavigation = $appSettings['theme']['slider_navigation'] ?? 'both';

    // Product showcase settings
    $showcaseProducts = $showcaseProducts ?? collect([]);

    // Padding for transparent header overlay
    $transparentHeaderPadding = $isTransparentHeader ? 'pt-20' : '';

    // Mobile hero settings
    $mobileHeroHeight = setting('products.mobile_hero_height', 'medium');
    $mobileHeroArrows = setting('products.mobile_hero_arrows', true);
    $mobileHeroSecondaryBtn = setting('products.mobile_hero_secondary_btn', false);
    $mobileHeroFeatures = setting('products.mobile_hero_features', false);

    // Separate mobile hero slides
    $mobileHeroEnabled = setting('products.mobile_hero_enabled', false);
    $mobileHeroSlides = [];
    if ($mobileHeroEnabled && !empty(setting('products.mobile_hero_slides'))) {
        $mobileSlidesData = is_string(setting('products.mobile_hero_slides'))
            ? json_decode(setting('products.mobile_hero_slides'), true)
            : setting('products.mobile_hero_slides');
        if (is_array($mobileSlidesData) && count($mobileSlidesData) > 0) {
            $mobileHeroSlides = $mobileSlidesData;
        }
    }
    $hasMobileHero = $mobileHeroEnabled && count($mobileHeroSlides) > 0;

    // Mobile height classes
    $mobileHeightClasses = [
        'small' => 'min-h-[320px]',
        'medium' => 'min-h-[380px]',
        'large' => 'min-h-[450px]',
        'full' => 'min-h-[100svh]',
    ];
    $mobileHeightClass = $mobileHeightClasses[$mobileHeroHeight] ?? $mobileHeightClasses['medium'];
@endphp

{{-- Mobile Hero Section (Separate from Desktop) --}}
@if($hasMobileHero)
    <section
        class="sm:hidden relative {{ $mobileHeightClass }} homepage-body overflow-hidden {{ $transparentHeaderPadding }}"
        x-data="mobileHeroSlider()" x-init="init()">
        {{-- Mobile Slides Container --}}
        <div class="absolute inset-0">
            @foreach($mobileHeroSlides as $index => $slide)
                @php
                    $slideImage = !empty($slide['image_path']) ? asset('storage/' . $slide['image_path']) : '';
                    $slideOverlay = $slide['overlay_opacity'] ?? '0.3';
                @endphp
                <div class="absolute inset-0 transition-opacity duration-700"
                    :class="currentSlide === {{ $index }} ? 'opacity-100 z-10' : 'opacity-0 z-0'"
                    style="{{ $index === 0 ? 'opacity: 1;' : 'opacity: 0;' }}">
                    @if($slideImage)
                        <img src="{{ $slideImage }}" alt="Mobile Slide {{ $index + 1 }}" class="w-full h-full object-cover">
                    @endif
                    @if(floatval($slideOverlay) > 0)
                        <div class="absolute inset-0 bg-black" style="opacity: {{ $slideOverlay }};"></div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Mobile Content --}}
        <div class="absolute inset-0 flex items-end z-20 pb-16">
            <div class="w-full px-4">
                @foreach($mobileHeroSlides as $index => $slide)
                    @php
                        $slideTextColor = $slide['text_color'] ?? '#ffffff';
                        $slideTextAlign = $slide['text_align'] ?? 'left';
                        $alignClass = $slideTextAlign === 'center' ? 'text-center' : ($slideTextAlign === 'right' ? 'text-right' : 'text-left');
                        $buttonAlignClass = $slideTextAlign === 'center' ? 'justify-center' : ($slideTextAlign === 'right' ? 'justify-end' : 'justify-start');
                    @endphp
                    <div class="transition-all duration-500 {{ $alignClass }}" x-show="currentSlide === {{ $index }}"
                        x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 translate-y-4"
                        x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-300"
                        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                        style="color: {{ $slideTextColor }}; {{ $index !== 0 ? 'display: none;' : '' }}">
                        @if(!empty($slide['badge']))
                            <span
                                class="inline-flex items-center px-3 py-1.5 bg-white/20 backdrop-blur-sm rounded-full text-xs font-medium mb-3 border border-white/20">
                                <i class="fas fa-star mr-1.5 text-yellow-400"></i>{{ $slide['badge'] }}
                            </span>
                        @endif
                        <h1 class="text-2xl font-bold leading-tight mb-2 homepage-heading">{{ $slide['title'] ?? '' }}</h1>
                        @if(!empty($slide['subtitle']))
                            <p class="text-sm mb-4 opacity-90 line-clamp-2">{{ $slide['subtitle'] }}</p>
                        @endif
                        <div class="flex flex-wrap gap-2 {{ $buttonAlignClass }}">
                            <a href="{{ $slide['button_link'] ?? '/shop' }}"
                                class="inline-flex items-center justify-center px-5 py-2.5 bg-primary-600 text-white text-sm font-semibold rounded-xl hover:bg-primary-700 transition-all duration-300 shadow-lg">
                                <i class="fas fa-shopping-bag mr-2"></i>{{ $slide['button_text'] ?? 'Shop Now' }}
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Mobile Navigation Arrows --}}
        @if(count($mobileHeroSlides) > 1 && $mobileHeroArrows)
            <button @click="prevSlide()"
                class="absolute left-2 top-1/2 -translate-y-1/2 z-30 w-9 h-9 bg-white/80 backdrop-blur-sm rounded-full flex items-center justify-center text-gray-700 shadow-md">
                <i class="fas fa-chevron-left text-sm"></i>
            </button>
            <button @click="nextSlide()"
                class="absolute right-2 top-1/2 -translate-y-1/2 z-30 w-9 h-9 bg-white/80 backdrop-blur-sm rounded-full flex items-center justify-center text-gray-700 shadow-md">
                <i class="fas fa-chevron-right text-sm"></i>
            </button>
        @endif

        {{-- Mobile Dots Navigation --}}
        @if(count($mobileHeroSlides) > 1)
            <div class="absolute bottom-4 left-1/2 -translate-x-1/2 z-30 flex items-center gap-1.5">
                @foreach($mobileHeroSlides as $index => $slide)
                    <button @click="goToSlide({{ $index }})" class="w-2 h-2 rounded-full transition-all duration-300"
                        :class="currentSlide === {{ $index }} ? 'bg-white w-6' : 'bg-white/50'"></button>
                @endforeach
            </div>
        @endif
    </section>

    <script>
        function mobileHeroSlider() {
            return {
                currentSlide: 0,
                totalSlides: {{ count($mobileHeroSlides) }},
                interval: null,
                autoplayInterval: {{ $sliderInterval }},

                init() {
                    if (this.totalSlides > 1) {
                        this.startAutoplay();
                    }
                },

                startAutoplay() {
                    this.stopAutoplay();
                    this.interval = setInterval(() => this.nextSlide(), this.autoplayInterval);
                },

                stopAutoplay() {
                    if (this.interval) clearInterval(this.interval);
                },

                nextSlide() {
                    this.currentSlide = (this.currentSlide + 1) % this.totalSlides;
                },

                prevSlide() {
                    this.currentSlide = (this.currentSlide - 1 + this.totalSlides) % this.totalSlides;
                },

                goToSlide(index) {
                    this.stopAutoplay();
                    this.currentSlide = index;
                    this.startAutoplay();
                }
            };
        }
    </script>
@endif

{{-- Desktop Hero Section (hidden on mobile when mobile hero is enabled) --}}
<div class="{{ $hasMobileHero ? 'hidden sm:block' : '' }}">
    @if($heroStyle === 'gradient')
        {{-- Gradient Hero Style --}}
        <section class="relative overflow-hidden homepage-body {{ $transparentHeaderPadding }}"
            style="{{ $heroBgColor ? 'background-color: ' . $heroBgColor . ';' : 'background-color: #2563eb;' }}">

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-16 md:py-20 lg:py-28 relative z-10">
                <div class="grid lg:grid-cols-2 gap-6 sm:gap-8 lg:gap-12 items-center">
                    <div class="text-white text-center lg:text-left">
                        <span
                            class="inline-flex items-center px-3 sm:px-4 py-1.5 sm:py-2 bg-white/20 rounded-full text-xs sm:text-sm font-medium mb-3 sm:mb-4 md:mb-6 border border-white/20">
                            <i
                                class="fas {{ $iconMap[$heroBadgeIcon] ?? 'fa-star' }} mr-1.5 sm:mr-2 text-yellow-400"></i>{{ $heroBadge }}
                        </span>
                        <h1
                            class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl xl:text-6xl font-bold leading-tight mb-3 sm:mb-4 md:mb-6 homepage-heading">
                            {{ $heroTitle }}
                        </h1>
                        <p
                            class="text-sm sm:text-base md:text-lg text-white/80 mb-4 sm:mb-6 md:mb-8 max-w-lg mx-auto lg:mx-0 leading-relaxed line-clamp-2 sm:line-clamp-none">
                            {{ $heroSubtitle }}
                        </p>
                        <div
                            class="flex flex-col sm:flex-row flex-wrap gap-2 sm:gap-3 md:gap-4 justify-center lg:justify-start">
                            <a href="{{ $heroButtonLink }}"
                                class="inline-flex items-center justify-center px-5 sm:px-6 md:px-8 py-2.5 sm:py-3 md:py-4 bg-white text-primary-600 text-sm sm:text-base font-semibold rounded-lg hover:bg-primary-50 transition-colors duration-200 group">
                                <i class="fas fa-shopping-bag mr-2"></i>{{ $heroButtonText }}
                            </a>
                            @if($heroSecondaryText)
                                <a href="{{ $heroSecondaryLink }}"
                                    class="{{ $mobileHeroSecondaryBtn ? 'inline-flex' : 'hidden sm:inline-flex' }} items-center justify-center px-5 sm:px-6 md:px-8 py-2.5 sm:py-3 md:py-4 border-2 border-white text-white text-sm sm:text-base font-semibold rounded-xl hover:bg-white/10 transition-all duration-300 group">
                                    {{ $heroSecondaryText }}
                                    <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                                </a>
                            @endif
                        </div>
                        <div
                            class="mt-4 sm:mt-8 md:mt-12 {{ $mobileHeroFeatures ? 'flex' : 'hidden sm:flex' }} flex-wrap items-center justify-center lg:justify-start gap-3 sm:gap-4 md:gap-6 text-white/60 text-xs sm:text-sm">
                            <span class="flex items-center gap-1.5 sm:gap-2"><i class="fas fa-truck text-white/80"></i>Free
                                Shipping</span>
                            <span class="flex items-center gap-1.5 sm:gap-2"><i
                                    class="fas fa-shield-alt text-white/80"></i>Secure Payment</span>
                            <span class="flex items-center gap-1.5 sm:gap-2"><i class="fas fa-undo text-white/80"></i>Easy
                                Returns</span>
                        </div>
                    </div>

                    <div class="hidden lg:block">
                        <div class="relative">
                            <div class="relative rounded-2xl overflow-hidden border border-white/20">
                                <img src="{{ $heroImage }}" alt="Hero" class="w-full h-[450px] object-cover">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    @elseif($heroStyle === 'image')
        {{-- Image Hero Style --}}
        @php
            $heroHeight = $appSettings['theme']['hero_height'] ?? 'medium';
            // Mobile-first height classes
            $heightClasses = [
                'small' => 'min-h-[320px] sm:min-h-[380px] md:min-h-[400px] lg:min-h-[450px]',
                'medium' => 'min-h-[380px] sm:min-h-[450px] md:min-h-[500px] lg:min-h-[600px]',
                'large' => 'min-h-[450px] sm:min-h-[520px] md:min-h-[600px] lg:min-h-[700px]',
                'full' => 'min-h-[100svh]'
            ];
            $currentHeightClass = $heightClasses[$heroHeight] ?? $heightClasses['medium'];
            $textColor = $appSettings['theme']['hero_text_color'] ?? '#1f2937';
            $textAlign = $appSettings['theme']['hero_text_align'] ?? 'left';
        @endphp
        <section class="relative {{ $currentHeightClass }} homepage-body {{ $transparentHeaderPadding }}">
            {{-- Background Image - Clean, no overlay --}}
            <div class="absolute inset-0">
                <img src="{{ $heroImage }}" alt="Hero" class="w-full h-full object-cover">
            </div>

            {{-- Content --}}
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-16 md:py-24 relative z-10 flex items-end sm:items-center {{ $currentHeightClass }}"
                style="color: {{ $textColor }};">
                <div
                    class="max-w-2xl pb-8 sm:pb-0 {{ $textAlign === 'center' ? 'mx-auto text-center' : ($textAlign === 'right' ? 'ml-auto text-right' : '') }}">
                    <span
                        class="inline-flex items-center px-3 sm:px-4 py-1.5 sm:py-2 bg-primary-100 text-primary-700 rounded-full text-xs sm:text-sm font-medium mb-3 sm:mb-4 md:mb-6">
                        <i
                            class="fas {{ $iconMap[$heroBadgeIcon] ?? 'fa-star' }} mr-1.5 sm:mr-2 text-yellow-500"></i>{{ $heroBadge }}
                    </span>
                    <h1
                        class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl xl:text-6xl font-bold leading-tight mb-3 sm:mb-4 md:mb-6 homepage-heading">
                        {{ $heroTitle }}
                    </h1>
                    <p
                        class="text-sm sm:text-base md:text-lg text-gray-600 mb-4 sm:mb-6 md:mb-8 leading-relaxed line-clamp-2 sm:line-clamp-none">
                        {{ $heroSubtitle }}
                    </p>
                    <div
                        class="flex flex-col sm:flex-row flex-wrap gap-2 sm:gap-3 md:gap-4 {{ $textAlign === 'center' ? 'justify-center' : ($textAlign === 'right' ? 'justify-end' : '') }}">
                        <a href="{{ $heroButtonLink }}"
                            class="inline-flex items-center justify-center px-5 sm:px-6 md:px-8 py-2.5 sm:py-3 md:py-4 bg-primary-600 text-white text-sm sm:text-base font-semibold rounded-xl hover:bg-primary-700 transition-all duration-300 shadow-lg hover:shadow-xl hover:-translate-y-1 group">
                            <i class="fas fa-shopping-bag mr-2 group-hover:animate-bounce"></i>{{ $heroButtonText }}
                        </a>
                        @if($heroSecondaryText)
                            <a href="{{ $heroSecondaryLink }}"
                                class="{{ $mobileHeroSecondaryBtn ? 'inline-flex' : 'hidden sm:inline-flex' }} items-center justify-center px-5 sm:px-6 md:px-8 py-2.5 sm:py-3 md:py-4 border-2 border-gray-300 text-gray-700 text-sm sm:text-base font-semibold rounded-xl hover:border-primary-500 hover:text-primary-600 transition-all duration-300 group">
                                {{ $heroSecondaryText }}
                                <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                            </a>
                        @endif
                    </div>
                    <div
                        class="mt-4 sm:mt-8 md:mt-12 {{ $mobileHeroFeatures ? 'flex' : 'hidden sm:flex' }} flex-wrap items-center gap-3 sm:gap-4 md:gap-6 text-gray-500 text-xs sm:text-sm {{ $textAlign === 'center' ? 'justify-center' : ($textAlign === 'right' ? 'justify-end' : '') }}">
                        <span class="flex items-center gap-1.5 sm:gap-2"><i class="fas fa-truck"></i>Free Shipping</span>
                        <span class="flex items-center gap-1.5 sm:gap-2"><i class="fas fa-shield-alt"></i>Secure
                            Payment</span>
                        <span class="flex items-center gap-1.5 sm:gap-2"><i class="fas fa-undo"></i>Easy Returns</span>
                    </div>
                </div>
            </div>
        </section>

    @elseif($heroStyle === 'slider')
        {{-- Slider Hero Style --}}
        @php
            $heroHeight = $appSettings['theme']['hero_height'] ?? 'medium';
            // Mobile-first height classes with proper responsive breakpoints
            // Mobile height is controlled by admin mobile settings
            $mobileHeightMap = [
                'small' => '320px',
                'medium' => '380px',
                'large' => '450px',
                'full' => '100svh',
            ];
            $heightClasses = [
                'small' => 'sm:min-h-[380px] md:min-h-[400px] lg:min-h-[450px]',
                'medium' => 'sm:min-h-[450px] md:min-h-[500px] lg:min-h-[600px]',
                'large' => 'sm:min-h-[520px] md:min-h-[600px] lg:min-h-[700px]',
                'full' => 'min-h-[100svh]'
            ];
            $currentHeightClass = $heightClasses[$heroHeight] ?? $heightClasses['medium'];
            $mobileMinHeight = $mobileHeightMap[$mobileHeroHeight] ?? '380px';
            $textColor = $appSettings['theme']['hero_text_color'] ?? '#1f2937';
            $textAlign = $appSettings['theme']['hero_text_align'] ?? 'left';
        @endphp
        <style>
            .hero-slider-section {
                min-height:
                    {{ $mobileMinHeight }}
                ;
            }

            @media (min-width: 640px) {
                .hero-slider-section {
                    min-height: auto;
                }
            }
        </style>
        <style>
            [x-cloak] {
                display: none !important;
            }

            /* Smooth image transitions */
            .hero-slide-image {
                position: absolute;
                inset: 0;
                opacity: 0;
                transform: scale(1.05);
                transition: opacity 800ms ease-in-out, transform 6000ms ease-out;
            }

            .hero-slide-image.active {
                opacity: 1;
                transform: scale(1);
            }

            /* Text animation classes */
            .hero-text-animate {
                opacity: 0;
                transform: translateY(30px);
                transition: opacity 600ms ease-out, transform 600ms ease-out;
            }

            .hero-text-animate.active {
                opacity: 1;
                transform: translateY(0);
            }

            .hero-text-animate.delay-1 {
                transition-delay: 100ms;
            }

            .hero-text-animate.delay-2 {
                transition-delay: 200ms;
            }

            .hero-text-animate.delay-3 {
                transition-delay: 300ms;
            }

            .hero-text-animate.delay-4 {
                transition-delay: 400ms;
            }

            .hero-text-animate.delay-5 {
                transition-delay: 500ms;
            }

            /* Mobile hero optimizations */
            @media (max-width: 639px) {
                .hero-mobile-content {
                    padding-bottom: 60px;
                }
            }
        </style>
        <section
            class="relative hero-slider-section {{ $currentHeightClass }} homepage-body overflow-hidden {{ $transparentHeaderPadding }}"
            x-data="heroSlider()" x-init="init()">
            {{-- Slides Container - Background Images --}}
            <div class="absolute inset-0">
                @if(count($heroSlides) > 0)
                    @foreach($heroSlides as $index => $slide)
                        @php
                            $slideImage = !empty($slide['image_path']) ? asset('storage/' . $slide['image_path']) : ($slide['image'] ?? '');
                            $slideOverlay = $slide['overlay_opacity'] ?? '0';
                        @endphp
                        <div class="hero-slide-image" :class="{ 'active': currentSlide === {{ $index }} }"
                            style="{{ $index === 0 ? 'opacity: 1; transform: scale(1);' : '' }}">
                            <img src="{{ $slideImage }}" alt="Slide {{ $index + 1 }}" class="w-full h-full object-cover">
                            @if($slideOverlay > 0)
                                <div class="absolute inset-0 bg-black" style="opacity: {{ $slideOverlay }};"></div>
                            @endif
                        </div>
                    @endforeach
                @else
                    <img src="{{ $heroImage }}" alt="Hero" class="w-full h-full object-cover">
                @endif
            </div>

            {{-- Content --}}
            <div class="absolute inset-0 flex items-end sm:items-center z-10 hero-mobile-content">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full relative pb-16 sm:pb-0">
                    @if(count($heroSlides) > 0)
                        @foreach($heroSlides as $index => $slide)
                            @php
                                $slideTextColor = $slide['text_color'] ?? $textColor;
                                $slideTextAlign = $slide['text_align'] ?? 'left';
                                $alignClass = $slideTextAlign === 'center' ? 'mx-auto text-center' : ($slideTextAlign === 'right' ? 'ml-auto text-right' : '');
                                $buttonAlignClass = $slideTextAlign === 'center' ? 'justify-center' : ($slideTextAlign === 'right' ? 'justify-end' : '');
                            @endphp
                            <div class="max-w-2xl {{ $alignClass }} {{ $index !== 0 ? 'absolute left-0 right-0' : '' }}"
                                x-show="currentSlide === {{ $index }}" x-cloak="{{ $index !== 0 ? 'true' : '' }}"
                                style="color: {{ $slideTextColor }};">
                                @if(!empty($slide['badge']))
                                    <span
                                        class="hero-text-animate delay-1 inline-flex items-center px-4 py-2 bg-primary-100 text-primary-700 rounded-full text-xs sm:text-sm font-medium mb-4 sm:mb-6"
                                        :class="{ 'active': currentSlide === {{ $index }} }"
                                        style="{{ $index === 0 ? 'opacity: 1; transform: translateY(0);' : '' }}">
                                        <i class="fas fa-star mr-2 text-yellow-500"></i>{{ $slide['badge'] }}
                                    </span>
                                @endif
                                <h1 class="hero-text-animate delay-2 text-2xl sm:text-3xl md:text-4xl lg:text-5xl xl:text-6xl font-bold leading-tight mb-3 sm:mb-4 md:mb-6 homepage-heading"
                                    :class="{ 'active': currentSlide === {{ $index }} }"
                                    style="{{ $index === 0 ? 'opacity: 1; transform: translateY(0);' : '' }}">
                                    {{ $slide['title'] ?? $heroTitle }}
                                </h1>
                                <p class="hero-text-animate delay-3 text-sm sm:text-base md:text-lg mb-4 sm:mb-6 md:mb-8 leading-relaxed opacity-80 line-clamp-2 sm:line-clamp-none"
                                    :class="{ 'active': currentSlide === {{ $index }} }"
                                    style="{{ $index === 0 ? 'opacity: 0.8; transform: translateY(0);' : '' }}">
                                    {{ $slide['subtitle'] ?? $heroSubtitle }}
                                </p>
                                <div class="hero-text-animate delay-4 flex flex-col sm:flex-row flex-wrap gap-2 sm:gap-3 md:gap-4 {{ $buttonAlignClass }}"
                                    :class="{ 'active': currentSlide === {{ $index }} }"
                                    style="{{ $index === 0 ? 'opacity: 1; transform: translateY(0);' : '' }}">
                                    <a href="{{ $slide['button_link'] ?? '/shop' }}"
                                        class="inline-flex items-center justify-center px-5 sm:px-6 md:px-8 py-2.5 sm:py-3 md:py-4 bg-primary-600 text-white text-sm sm:text-base font-semibold rounded-xl hover:bg-primary-700 transition-all duration-300 shadow-lg hover:shadow-xl hover:-translate-y-1 group">
                                        <i
                                            class="fas fa-shopping-bag mr-2 group-hover:animate-bounce"></i>{{ $slide['button_text'] ?? 'Shop Now' }}
                                    </a>
                                    @if(!empty($slide['secondary_button_text']))
                                        <a href="{{ $slide['secondary_button_link'] ?? '/about' }}"
                                            class="{{ $mobileHeroSecondaryBtn ? 'inline-flex' : 'hidden sm:inline-flex' }} items-center justify-center px-5 sm:px-6 md:px-8 py-2.5 sm:py-3 md:py-4 border-2 border-current text-sm sm:text-base font-semibold rounded-xl hover:bg-white/10 transition-all duration-300 group">
                                            {{ $slide['secondary_button_text'] }}
                                            <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                                        </a>
                                    @endif
                                </div>
                                @php
                                    $showFeatures = !empty($slide['show_features']) && ($slide['show_features'] === true || $slide['show_features'] === 'true' || $slide['show_features'] === 1 || $slide['show_features'] === '1');
                                @endphp
                                @if($showFeatures || $mobileHeroFeatures)
                                    <div class="hero-text-animate delay-5 mt-4 sm:mt-8 md:mt-12 {{ $mobileHeroFeatures ? 'flex' : 'hidden sm:flex' }} flex-wrap items-center gap-3 sm:gap-4 md:gap-6 text-xs sm:text-sm {{ $buttonAlignClass }}"
                                        :class="{ 'active': currentSlide === {{ $index }} }"
                                        style="opacity: {{ $index === 0 ? '0.7' : '0' }}; {{ $index === 0 ? 'transform: translateY(0);' : '' }}">
                                        @if(!empty($slide['feature_1']))
                                            <span class="flex items-center gap-1.5 sm:gap-2"><i
                                                    class="fas fa-check-circle"></i>{{ $slide['feature_1'] }}</span>
                                        @endif
                                        @if(!empty($slide['feature_2']))
                                            <span class="flex items-center gap-1.5 sm:gap-2"><i
                                                    class="fas fa-check-circle"></i>{{ $slide['feature_2'] }}</span>
                                        @endif
                                        @if(!empty($slide['feature_3']))
                                            <span class="flex items-center gap-2"><i
                                                    class="fas fa-check-circle"></i>{{ $slide['feature_3'] }}</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <div class="max-w-2xl {{ $textAlign === 'center' ? 'mx-auto text-center' : ($textAlign === 'right' ? 'ml-auto text-right' : '') }}"
                            style="color: {{ $textColor }};">
                            <span
                                class="inline-flex items-center px-4 py-2 bg-primary-100 text-primary-700 rounded-full text-xs sm:text-sm font-medium mb-4 sm:mb-6">
                                <i
                                    class="fas {{ $iconMap[$heroBadgeIcon] ?? 'fa-star' }} mr-2 text-yellow-500"></i>{{ $heroBadge }}
                            </span>
                            <h1
                                class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-bold leading-tight mb-4 sm:mb-6 homepage-heading">
                                {{ $heroTitle }}
                            </h1>
                            <p class="text-base sm:text-lg mb-6 sm:mb-8 leading-relaxed" style="opacity: 0.8;">
                                {{ $heroSubtitle }}
                            </p>
                            <a href="{{ $heroButtonLink }}"
                                class="inline-flex items-center justify-center px-6 sm:px-8 py-3 sm:py-4 bg-primary-600 text-white font-semibold rounded-xl hover:bg-primary-700 transition-all duration-300 shadow-lg hover:shadow-xl hover:-translate-y-1 group">
                                <i class="fas fa-shopping-bag mr-2 group-hover:animate-bounce"></i>{{ $heroButtonText }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Navigation Arrows - Controlled by mobile settings on mobile, always show on desktop --}}
            @if(count($heroSlides) > 1 && in_array($sliderNavigation, ['both', 'arrows']))
                <button @click="prevSlide()"
                    class="absolute left-2 sm:left-4 top-1/2 -translate-y-1/2 z-20 w-9 h-9 sm:w-10 sm:h-10 md:w-12 md:h-12 bg-white/80 sm:bg-white/90 backdrop-blur-sm rounded-full items-center justify-center text-gray-700 hover:bg-white hover:shadow-lg transition-all duration-300 group shadow-md {{ $mobileHeroArrows ? 'flex' : 'hidden sm:flex' }}">
                    <i class="fas fa-chevron-left text-sm sm:text-base group-hover:-translate-x-0.5 transition-transform"></i>
                </button>
                <button @click="nextSlide()"
                    class="absolute right-2 sm:right-4 top-1/2 -translate-y-1/2 z-20 w-9 h-9 sm:w-10 sm:h-10 md:w-12 md:h-12 bg-white/80 sm:bg-white/90 backdrop-blur-sm rounded-full items-center justify-center text-gray-700 hover:bg-white hover:shadow-lg transition-all duration-300 group shadow-md {{ $mobileHeroArrows ? 'flex' : 'hidden sm:flex' }}">
                    <i class="fas fa-chevron-right text-sm sm:text-base group-hover:translate-x-0.5 transition-transform"></i>
                </button>
            @endif

            {{-- Dots Navigation --}}
            @if(count($heroSlides) > 1 && in_array($sliderNavigation, ['both', 'dots']))
                <div
                    class="absolute bottom-4 sm:bottom-6 md:bottom-8 left-1/2 -translate-x-1/2 z-20 flex items-center gap-1.5 sm:gap-2">
                    @foreach($heroSlides as $index => $slide)
                        <button @click="goToSlide({{ $index }})"
                            class="w-2 h-2 sm:w-3 sm:h-3 rounded-full transition-all duration-300"
                            :class="currentSlide === {{ $index }} ? 'bg-primary-600 w-6 sm:w-8' : 'bg-gray-400 hover:bg-gray-500'"></button>
                    @endforeach
                </div>
            @endif
        </section>

        <script>
            function heroSlider() {
                return {
                    currentSlide: 0,
                    totalSlides: {{ count($heroSlides) }},
                    interval: null,
                    autoplayInterval: {{ $sliderInterval }},
                    isTransitioning: false,

                    init() {
                        if (this.totalSlides > 1) {
                            this.startAutoplay();
                        }
                    },

                    startAutoplay() {
                        this.stopAutoplay();
                        this.interval = setInterval(() => {
                            this.nextSlide();
                        }, this.autoplayInterval);
                    },

                    stopAutoplay() {
                        if (this.interval) {
                            clearInterval(this.interval);
                            this.interval = null;
                        }
                    },

                    nextSlide() {
                        if (this.isTransitioning) return;
                        this.isTransitioning = true;
                        this.currentSlide = (this.currentSlide + 1) % this.totalSlides;
                        setTimeout(() => { this.isTransitioning = false; }, 800);
                    },

                    prevSlide() {
                        if (this.isTransitioning) return;
                        this.isTransitioning = true;
                        this.currentSlide = (this.currentSlide - 1 + this.totalSlides) % this.totalSlides;
                        setTimeout(() => { this.isTransitioning = false; }, 800);
                    },

                    goToSlide(index) {
                        if (this.isTransitioning || index === this.currentSlide) return;
                        this.stopAutoplay();
                        this.isTransitioning = true;
                        this.currentSlide = index;
                        setTimeout(() => {
                            this.isTransitioning = false;
                            this.startAutoplay();
                        }, 800);
                    }
                };
            }
        </script>

    @elseif($heroStyle === 'product_showcase')
        {{-- Product Showcase Hero Style --}}
        @php
            $showcaseBgColor = $appSettings['theme']['showcase_bg_color'] ?? '#f9fafb';
            $showcaseCardBgColor = $appSettings['theme']['showcase_card_bg_color'] ?? '#ffffff';
            $showcaseImageBgColor = $appSettings['theme']['showcase_image_bg_color'] ?? '#f3f4f6';
        @endphp
        <style>
            [x-cloak] {
                display: none !important;
            }
        </style>
        <section class="homepage-body py-6 sm:py-8 {{ $transparentHeaderPadding }}"
            style="background-color: {{ $showcaseBgColor }};" x-data="productShowcase()" x-init="init()">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="relative rounded-3xl overflow-hidden"
                    style="background-color: {{ $showcaseCardBgColor }};">
                    <div class="grid lg:grid-cols-2 gap-0 min-h-[350px] sm:min-h-[400px] lg:min-h-[500px]">
                        {{-- Content Side --}}
                        <div class="flex flex-col justify-center p-6 sm:p-8 lg:p-12 order-2 lg:order-1 relative">
                            @if($showcaseProducts->count() > 0)
                                @foreach($showcaseProducts as $index => $product)
                                    <div x-show="currentProduct === {{ $index }}" {!! $index !== 0 ? 'x-cloak' : '' !!}
                                        x-transition:enter="transition-all ease-[cubic-bezier(0.4,0,0.2,1)] duration-700"
                                        x-transition:enter-start="opacity-0 translate-y-6"
                                        x-transition:enter-end="opacity-100 translate-y-0"
                                        x-transition:leave="transition-all ease-[cubic-bezier(0.4,0,0.2,1)] duration-500 absolute inset-0 p-6 sm:p-8 lg:p-12"
                                        x-transition:leave-start="opacity-100 translate-y-0"
                                        x-transition:leave-end="opacity-0 -translate-y-4" class="flex flex-col justify-center">
                                        <span
                                            class="inline-flex items-center w-fit px-3 py-1.5 bg-primary-100 text-primary-700 text-xs font-semibold uppercase tracking-wider rounded-full mb-4">
                                            @if($product->isOnSale())
                                                <i class="fas fa-tag mr-1.5 text-red-500"></i>{{ $product->discount_percent }}% OFF
                                            @elseif($product->is_featured)
                                                <i class="fas fa-star mr-1.5 text-yellow-500"></i>Featured
                                            @else
                                                <i class="fas fa-fire mr-1.5 text-orange-500"></i>Trending
                                            @endif
                                        </span>
                                        <h1
                                            class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold text-gray-900 leading-tight mb-4 homepage-heading">
                                            {{ $product->name }}
                                        </h1>
                                        <p class="text-gray-600 mb-4 max-w-md text-sm sm:text-base leading-relaxed line-clamp-2">
                                            {{ $product->short_description ?: Str::limit(strip_tags($product->description), 120) }}
                                        </p>

                                        {{-- Price --}}
                                        <div class="flex items-center gap-3 mb-6">
                                            @if($product->isOnSale())
                                                <span
                                                    class="text-3xl font-bold text-primary-600">{{ format_currency($product->sale_price) }}</span>
                                                <span
                                                    class="text-xl text-gray-400 line-through">{{ format_currency($product->price) }}</span>
                                            @else
                                                <span
                                                    class="text-3xl font-bold text-primary-600">{{ format_currency($product->price) }}</span>
                                            @endif
                                        </div>

                                        <div class="flex flex-col sm:flex-row gap-3">
                                            <a href="{{ route('shop.show', $product->slug) }}"
                                                class="inline-flex items-center justify-center px-6 sm:px-8 py-3 sm:py-4 bg-primary-600 text-white font-semibold rounded-xl hover:bg-primary-700 transition-all duration-300 shadow-lg hover:shadow-xl hover:-translate-y-1 group">
                                                <i
                                                    class="fas fa-shopping-bag mr-2 group-hover:animate-bounce"></i>{{ __('messages.shop_now') }}
                                            </a>
                                            <a href="{{ route('shop.index') }}"
                                                class="inline-flex items-center justify-center px-6 sm:px-8 py-3 sm:py-4 border-2 border-gray-200 text-gray-700 font-semibold rounded-xl hover:border-primary-500 hover:text-primary-600 transition-all duration-300 group">
                                                {{ __('messages.view_all') ?? 'View All' }}
                                                <i
                                                    class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                {{-- Fallback content when no products --}}
                                <span
                                    class="inline-flex items-center w-fit px-3 py-1.5 bg-primary-100 text-primary-700 text-xs font-semibold uppercase tracking-wider rounded-full mb-4">
                                    <i class="fas fa-star mr-1.5 text-yellow-500"></i>{{ $heroBadge }}
                                </span>
                                <h1
                                    class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold text-gray-900 leading-tight mb-4 homepage-heading">
                                    {{ $heroTitle }}
                                </h1>
                                <p class="text-gray-600 mb-6 sm:mb-8 max-w-md text-sm sm:text-base leading-relaxed">
                                    {{ $heroSubtitle }}
                                </p>
                                <a href="{{ $heroButtonLink }}"
                                    class="inline-flex items-center justify-center px-6 sm:px-8 py-3 sm:py-4 bg-primary-600 text-white font-semibold rounded-xl hover:bg-primary-700 transition-all duration-300 shadow-lg hover:shadow-xl hover:-translate-y-1 group">
                                    <i class="fas fa-shopping-bag mr-2 group-hover:animate-bounce"></i>{{ $heroButtonText }}
                                </a>
                            @endif

                            <div
                                class="mt-6 sm:mt-8 flex flex-wrap items-center gap-4 sm:gap-6 text-gray-500 text-xs sm:text-sm">
                                <span class="flex items-center gap-1.5"><i
                                        class="fas fa-check-circle text-green-500"></i>Free Shipping</span>
                                <span class="flex items-center gap-1.5"><i
                                        class="fas fa-check-circle text-green-500"></i>Secure Payment</span>
                            </div>
                        </div>

                        {{-- Image Side --}}
                        <div class="relative flex items-center justify-center p-6 sm:p-8 order-1 lg:order-2 min-h-[200px] sm:min-h-[250px] lg:min-h-full overflow-hidden"
                            style="background-color: {{ $showcaseImageBgColor }};">
    

                            @if($showcaseProducts->count() > 0)
                                @foreach($showcaseProducts as $index => $product)
                                    <div x-show="currentProduct === {{ $index }}" {!! $index !== 0 ? 'x-cloak' : '' !!}
                                        x-transition:enter="transition-all ease-[cubic-bezier(0.34,1.56,0.64,1)] duration-700 delay-150"
                                        x-transition:enter-start="opacity-0 scale-75 rotate-2"
                                        x-transition:enter-end="opacity-100 scale-100 rotate-0"
                                        x-transition:leave="transition-all ease-[cubic-bezier(0.4,0,0.2,1)] duration-400 absolute inset-0 flex items-center justify-center p-6 sm:p-8"
                                        x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-90"
                                        class="relative flex items-center justify-center">
                                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                                            class="relative max-h-[180px] sm:max-h-[250px] lg:max-h-[400px] w-auto object-contain hover:scale-105 transition-transform duration-500 ease-out"
                                            loading="eager">
                                    </div>
                                @endforeach
                            @else
                                <img src="{{ $heroImage }}" alt="Product"
                                    class="relative max-h-[180px] sm:max-h-[250px] lg:max-h-[400px] w-auto object-contain hover:scale-105 transition-transform duration-700">
                            @endif

                            {{-- Floating badge --}}
                            <div
                                class="absolute top-4 right-4 sm:top-6 sm:right-6 bg-white rounded-xl p-3 shadow-lg hidden sm:block">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-star text-yellow-500 text-sm"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Top Rated</p>
                                        <p class="font-bold text-gray-900 text-sm">4.9/5</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Product Navigation Dots --}}
                    @if($showcaseProducts->count() > 1)
                        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex items-center gap-2.5 z-10">
                            @foreach($showcaseProducts as $index => $product)
                                <button @click="goToProduct({{ $index }})"
                                    class="h-2.5 rounded-full transition-all duration-500 ease-out hover:scale-110"
                                    :class="currentProduct === {{ $index }} ? 'bg-primary-600 w-8 shadow-md' : 'bg-gray-300/80 w-2.5 hover:bg-gray-400'"></button>
                            @endforeach
                        </div>
                    @endif

                    {{-- Navigation Arrows --}}
                    @if($showcaseProducts->count() > 1)
                        <button @click="prevProduct()"
                            class="absolute left-4 top-1/2 -translate-y-1/2 z-10 w-11 h-11 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center text-gray-600 hover:bg-white hover:shadow-xl hover:scale-110 transition-all duration-300 ease-out group hidden lg:flex">
                            <i class="fas fa-chevron-left group-hover:-translate-x-1 transition-transform duration-300"></i>
                        </button>
                        <button @click="nextProduct()"
                            class="absolute right-4 top-1/2 -translate-y-1/2 z-10 w-11 h-11 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center text-gray-600 hover:bg-white hover:shadow-xl hover:scale-110 transition-all duration-300 ease-out group hidden lg:flex">
                            <i class="fas fa-chevron-right group-hover:translate-x-1 transition-transform duration-300"></i>
                        </button>
                    @endif
                </div>
            </div>
        </section>

        <script>
            function productShowcase() {
                return {
                    currentProduct: 0,
                    totalProducts: {{ $showcaseProducts->count() }},
                    interval: null,
                    autoplayInterval: {{ $sliderInterval }},
                    isTransitioning: false,
                    transitionDuration: 800,

                    init() {
                        this.$nextTick(() => {
                            if (this.totalProducts > 1) {
                                this.startAutoplay();
                            }
                        });
                    },

                    startAutoplay() {
                        this.stopAutoplay();
                        this.interval = setInterval(() => {
                            this.nextProduct();
                        }, this.autoplayInterval);
                    },

                    stopAutoplay() {
                        if (this.interval) {
                            clearInterval(this.interval);
                            this.interval = null;
                        }
                    },

                    nextProduct() {
                        if (this.isTransitioning) return;
                        this.isTransitioning = true;
                        this.currentProduct = (this.currentProduct + 1) % this.totalProducts;
                        setTimeout(() => { this.isTransitioning = false; }, this.transitionDuration);
                    },

                    prevProduct() {
                        if (this.isTransitioning) return;
                        this.isTransitioning = true;
                        this.currentProduct = (this.currentProduct - 1 + this.totalProducts) % this.totalProducts;
                        setTimeout(() => { this.isTransitioning = false; }, this.transitionDuration);
                    },

                    goToProduct(index) {
                        if (this.isTransitioning || index === this.currentProduct) return;
                        this.stopAutoplay();
                        this.isTransitioning = true;
                        this.currentProduct = index;
                        setTimeout(() => {
                            this.isTransitioning = false;
                            this.startAutoplay();
                        }, this.transitionDuration);
                    }
                };
            }
        </script>

    @elseif($heroStyle === 'fullwidth')
        {{-- Fullwidth Banner Hero Style --}}
        @php
            $heroHeight = $appSettings['theme']['hero_height'] ?? 'large';
            $heightClasses = [
                'auto' => '', // Auto height based on image
                'small' => 'min-h-[400px] lg:min-h-[450px]',
                'medium' => 'min-h-[500px] lg:min-h-[600px]',
                'large' => 'min-h-[600px] lg:min-h-[700px]',
                'full' => 'min-h-screen'
            ];
            $currentHeightClass = $heightClasses[$heroHeight] ?? $heightClasses['large'];
            $contentPosition = $appSettings['theme']['fullwidth_content_position'] ?? 'left';
            $contentWidth = $appSettings['theme']['fullwidth_content_width'] ?? 'medium';
            $showOverlay = $appSettings['theme']['fullwidth_show_overlay'] ?? false; // Default to no overlay
            $showBadge = $appSettings['theme']['fullwidth_show_badge'] ?? true;
            $parallaxEnabled = $appSettings['theme']['fullwidth_parallax'] ?? false;
            $overlayStart = $appSettings['theme']['fullwidth_overlay_start'] ?? '#000000';
            $overlayEnd = $appSettings['theme']['fullwidth_overlay_end'] ?? 'transparent';
            $overlayOpacity = $appSettings['theme']['fullwidth_overlay_opacity'] ?? '0.3';

            $contentWidthClass = match ($contentWidth) {
                'narrow' => 'max-w-md',
                'wide' => 'max-w-3xl',
                'full' => 'max-w-full',
                default => 'max-w-2xl',
            };

            $positionClasses = match ($contentPosition) {
                'center' => 'items-center justify-center text-center',
                'right' => 'items-center justify-end text-right',
                'bottom-left' => 'items-end justify-start text-left pb-16',
                'bottom-center' => 'items-end justify-center text-center pb-16',
                default => 'items-center justify-start text-left',
            };

            // Check if auto height mode
            $isAutoHeight = $heroHeight === 'auto';
        @endphp
        <style>
            [x-cloak] {
                display: none !important;
            }

            .fullwidth-slide-image {
                position: absolute;
                inset: 0;
                opacity: 0;
                transition: opacity 1000ms ease-in-out;
            }

            .fullwidth-slide-image.active {
                opacity: 1;
            }

            .fullwidth-slide-image img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                {{ $parallaxEnabled ? 'transform: scale(1.1);' : '' }}
            }

            @if($isAutoHeight)
                .fullwidth-auto-height {
                    position: relative;
                }

                .fullwidth-auto-height .fullwidth-slide-image {
                    position: relative;
                }

                .fullwidth-auto-height .fullwidth-slide-image:not(:first-child) {
                    position: absolute;
                    inset: 0;
                }

                .fullwidth-auto-height .fullwidth-slide-image img {
                    height: auto;
                    max-height: 80vh;
                    object-fit: contain;
                }

            @endif .fullwidth-content-animate {
                opacity: 0;
                transform: translateY(40px);
                transition: opacity 700ms ease-out, transform 700ms ease-out;
            }

            .fullwidth-content-animate.active {
                opacity: 1;
                transform: translateY(0);
            }

            .fullwidth-content-animate.delay-1 {
                transition-delay: 150ms;
            }

            .fullwidth-content-animate.delay-2 {
                transition-delay: 300ms;
            }

            .fullwidth-content-animate.delay-3 {
                transition-delay: 450ms;
            }

            .fullwidth-content-animate.delay-4 {
                transition-delay: 600ms;
            }
        </style>
        <section
            class="relative {{ $currentHeightClass }} {{ $isAutoHeight ? 'fullwidth-auto-height' : '' }} homepage-body overflow-hidden {{ $transparentHeaderPadding }}"
            x-data="fullwidthSlider()" x-init="init()">
            {{-- Background Images - Clean, no default overlay --}}
            <div class="{{ $isAutoHeight ? 'relative' : 'absolute inset-0' }}">
                @if(count($heroSlides) > 0)
                    @foreach($heroSlides as $index => $slide)
                        @php
                            $slideImage = !empty($slide['image_path']) ? asset('storage/' . $slide['image_path']) : ($slide['image'] ?? '');
                            $slideOverlay = $slide['overlay_opacity'] ?? '0'; // Per-slide overlay control
                        @endphp
                        <div class="fullwidth-slide-image" :class="{ 'active': currentSlide === {{ $index }} }"
                            style="{{ $index === 0 ? 'opacity: 1;' : '' }}">
                            <img src="{{ $slideImage }}" alt="Slide {{ $index + 1 }}"
                                loading="{{ $index === 0 ? 'eager' : 'lazy' }}">
                            @if(floatval($slideOverlay) > 0)
                                <div class="absolute inset-0 bg-black" style="opacity: {{ $slideOverlay }};"></div>
                            @endif
                        </div>
                    @endforeach
                @else
                    <img src="{{ $heroImage }}" alt="Hero" class="w-full h-full object-cover">
                @endif
            </div>

            {{-- Optional Gradient Overlay (only if explicitly enabled) --}}
            @if($showOverlay && floatval($overlayOpacity) > 0)
                <div class="absolute inset-0 pointer-events-none"
                    style="background: linear-gradient(to right, {{ $overlayStart }}{{ str_pad(dechex(intval(floatval($overlayOpacity) * 255)), 2, '0', STR_PAD_LEFT) }} 0%, {{ $overlayEnd }} 70%);">
                </div>
            @endif

            {{-- Content Container --}}
            <div class="relative z-10 h-full flex {{ $positionClasses }} {{ $currentHeightClass }}">
                <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                    @if(count($heroSlides) > 0)
                        @foreach($heroSlides as $index => $slide)
                            @php
                                $slideTextColor = $slide['text_color'] ?? '#ffffff';
                                $slideTextAlign = $slide['text_align'] ?? 'left';
                                $alignClass = $slideTextAlign === 'center' ? 'mx-auto text-center' : ($slideTextAlign === 'right' ? 'ml-auto text-right' : '');
                                $buttonAlignClass = $slideTextAlign === 'center' ? 'justify-center' : ($slideTextAlign === 'right' ? 'justify-end' : '');
                            @endphp
                            <div class="{{ $contentWidthClass }} {{ $alignClass }} {{ $index === 0 ? '' : 'absolute' }}"
                                x-show="currentSlide === {{ $index }}" x-cloak="{{ $index !== 0 ? 'true' : '' }}"
                                style="color: {{ $slideTextColor }};">

                                @if($showBadge && !empty($slide['badge']))
                                    <div class="fullwidth-content-animate delay-1 mb-4"
                                        :class="{ 'active': currentSlide === {{ $index }} }"
                                        style="{{ $index === 0 ? 'opacity: 1; transform: translateY(0);' : '' }}">
                                        <span
                                            class="inline-flex items-center px-4 py-2 bg-white/20 backdrop-blur-sm text-white text-xs sm:text-sm font-semibold uppercase tracking-wider rounded-full border border-white/30">
                                            <i class="fas fa-star mr-2 text-yellow-400"></i>{{ $slide['badge'] }}
                                        </span>
                                    </div>
                                @endif

                                <h1 class="fullwidth-content-animate delay-2 text-3xl sm:text-4xl md:text-5xl lg:text-6xl xl:text-7xl font-bold leading-tight mb-4 sm:mb-6 homepage-heading"
                                    :class="{ 'active': currentSlide === {{ $index }} }"
                                    style="{{ $index === 0 ? 'opacity: 1; transform: translateY(0);' : '' }}">
                                    {{ $slide['title'] ?? $heroTitle }}
                                </h1>

                                <p class="fullwidth-content-animate delay-3 text-base sm:text-lg lg:text-xl mb-6 sm:mb-8 leading-relaxed opacity-90 {{ $contentWidthClass }}"
                                    :class="{ 'active': currentSlide === {{ $index }} }"
                                    style="{{ $index === 0 ? 'opacity: 0.9; transform: translateY(0);' : '' }}">
                                    {{ $slide['subtitle'] ?? $heroSubtitle }}
                                </p>

                                <div class="fullwidth-content-animate delay-4 flex flex-col sm:flex-row flex-wrap gap-3 sm:gap-4 {{ $buttonAlignClass }}"
                                    :class="{ 'active': currentSlide === {{ $index }} }"
                                    style="{{ $index === 0 ? 'opacity: 1; transform: translateY(0);' : '' }}">
                                    <a href="{{ $slide['button_link'] ?? '/shop' }}"
                                        class="inline-flex items-center justify-center px-8 py-4 bg-primary-600 text-white font-semibold rounded-lg hover:bg-primary-700 transition-all duration-300 shadow-lg hover:shadow-xl hover:-translate-y-1 group text-base">
                                        <i
                                            class="fas fa-shopping-bag mr-2 group-hover:animate-bounce"></i>{{ $slide['button_text'] ?? 'Shop Now' }}
                                    </a>
                                    @if(!empty($slide['secondary_button_text']))
                                        <a href="{{ $slide['secondary_button_link'] ?? '/about' }}"
                                            class="inline-flex items-center justify-center px-8 py-4 border-2 border-white/80 text-white font-semibold rounded-lg hover:bg-white/10 transition-all duration-300 group text-base">
                                            {{ $slide['secondary_button_text'] }}
                                            <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @else
                        {{-- Default content when no slides --}}
                        <div class="{{ $contentWidthClass }}" style="color: #ffffff;">
                            @if($showBadge)
                                <span
                                    class="inline-flex items-center px-4 py-2 bg-white/20 backdrop-blur-sm text-white text-xs sm:text-sm font-semibold uppercase tracking-wider rounded-full border border-white/30 mb-4">
                                    <i class="fas fa-star mr-2 text-yellow-400"></i>{{ $heroBadge }}
                                </span>
                            @endif
                            <h1
                                class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl xl:text-7xl font-bold leading-tight mb-4 sm:mb-6 homepage-heading">
                                {{ $heroTitle }}
                            </h1>
                            <p class="text-base sm:text-lg lg:text-xl mb-6 sm:mb-8 leading-relaxed opacity-90">
                                {{ $heroSubtitle }}
                            </p>
                            <a href="{{ $heroButtonLink }}"
                                class="inline-flex items-center justify-center px-8 py-4 bg-primary-600 text-white font-semibold rounded-lg hover:bg-primary-700 transition-all duration-300 shadow-lg hover:shadow-xl hover:-translate-y-1 group text-base">
                                <i class="fas fa-shopping-bag mr-2 group-hover:animate-bounce"></i>{{ $heroButtonText }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Navigation Arrows --}}
            @if(count($heroSlides) > 1 && in_array($sliderNavigation, ['both', 'arrows']))
                <button @click="prevSlide()"
                    class="absolute left-4 lg:left-8 top-1/2 -translate-y-1/2 z-20 w-12 h-12 lg:w-14 lg:h-14 bg-white/10 backdrop-blur-sm rounded-full flex items-center justify-center text-white hover:bg-white/20 transition-all duration-300 group border border-white/20">
                    <i class="fas fa-chevron-left text-lg group-hover:-translate-x-0.5 transition-transform"></i>
                </button>
                <button @click="nextSlide()"
                    class="absolute right-4 lg:right-8 top-1/2 -translate-y-1/2 z-20 w-12 h-12 lg:w-14 lg:h-14 bg-white/10 backdrop-blur-sm rounded-full flex items-center justify-center text-white hover:bg-white/20 transition-all duration-300 group border border-white/20">
                    <i class="fas fa-chevron-right text-lg group-hover:translate-x-0.5 transition-transform"></i>
                </button>
            @endif

            {{-- Progress Bar Navigation --}}
            @if(count($heroSlides) > 1 && in_array($sliderNavigation, ['both', 'dots']))
                <div class="absolute bottom-8 left-1/2 -translate-x-1/2 z-20 flex items-center gap-3">
                    @foreach($heroSlides as $index => $slide)
                        <button @click="goToSlide({{ $index }})" class="group relative">
                            <div class="w-12 h-1 bg-white/30 rounded-full overflow-hidden">
                                <div class="h-full bg-white rounded-full transition-all duration-300"
                                    :class="currentSlide === {{ $index }} ? 'w-full' : 'w-0 group-hover:w-1/3'"></div>
                            </div>
                        </button>
                    @endforeach
                </div>
            @endif
        </section>

        <script>
            function fullwidthSlider() {
                return {
                    currentSlide: 0,
                    totalSlides: {{ count($heroSlides) }},
                    interval: null,
                    autoplayInterval: {{ $sliderInterval }},
                    isTransitioning: false,

                    init() {
                        if (this.totalSlides > 1) {
                            this.startAutoplay();
                        }
                    },

                    startAutoplay() {
                        this.stopAutoplay();
                        this.interval = setInterval(() => {
                            this.nextSlide();
                        }, this.autoplayInterval);
                    },

                    stopAutoplay() {
                        if (this.interval) {
                            clearInterval(this.interval);
                            this.interval = null;
                        }
                    },

                    nextSlide() {
                        if (this.isTransitioning) return;
                        this.isTransitioning = true;
                        this.currentSlide = (this.currentSlide + 1) % this.totalSlides;
                        setTimeout(() => { this.isTransitioning = false; }, 1000);
                    },

                    prevSlide() {
                        if (this.isTransitioning) return;
                        this.isTransitioning = true;
                        this.currentSlide = (this.currentSlide - 1 + this.totalSlides) % this.totalSlides;
                        setTimeout(() => { this.isTransitioning = false; }, 1000);
                    },

                    goToSlide(index) {
                        if (this.isTransitioning || index === this.currentSlide) return;
                        this.stopAutoplay();
                        this.isTransitioning = true;
                        this.currentSlide = index;
                        setTimeout(() => {
                            this.isTransitioning = false;
                            this.startAutoplay();
                        }, 1000);
                    }
                };
            }
        </script>

    @elseif($heroStyle === 'video')
        {{-- Video Background Hero Style --}}
        @php
            $heroHeight = $appSettings['theme']['hero_height'] ?? 'large';
            $heightClasses = ['small' => 'min-h-[400px] lg:min-h-[450px]', 'medium' => 'min-h-[500px] lg:min-h-[600px]', 'large' => 'min-h-[600px] lg:min-h-[700px]', 'full' => 'min-h-screen'];
            $currentHeightClass = $heightClasses[$heroHeight] ?? $heightClasses['large'];
            $videoUrl = $appSettings['theme']['video_url'] ?? '';
            $videoEmbedId = $appSettings['theme']['video_embed_id'] ?? '';
            $videoLoop = $appSettings['theme']['video_loop'] ?? true;
            $videoMuted = $appSettings['theme']['video_muted'] ?? true;
            $showControls = $appSettings['theme']['video_show_controls'] ?? false;
            $fallbackImage = $appSettings['theme']['video_fallback_image'] ?? $heroImage;
            $textColor = $appSettings['theme']['hero_text_color'] ?? '#ffffff';
            $textAlign = $appSettings['theme']['hero_text_align'] ?? 'left';
        @endphp
        <section class="relative {{ $currentHeightClass }} homepage-body overflow-hidden {{ $transparentHeaderPadding }}"
            x-data="{ videoPlaying: true, videoLoaded: false }">
            {{-- Video Background --}}
            <div class="absolute inset-0">
                @if($videoUrl)
                    <video autoplay {{ $videoMuted ? 'muted' : '' }} {{ $videoLoop ? 'loop' : '' }} playsinline
                        class="w-full h-full object-cover" poster="{{ $fallbackImage }}" @loadeddata="videoLoaded = true"
                        x-ref="heroVideo">
                        <source src="{{ $videoUrl }}" type="video/mp4">
                    </video>
                @elseif($videoEmbedId)
                    <div class="w-full h-full">
                        <iframe
                            src="https://www.youtube.com/embed/{{ $videoEmbedId }}?autoplay=1&mute=1&loop=1&playlist={{ $videoEmbedId }}&controls=0&showinfo=0&rel=0&modestbranding=1"
                            class="w-full h-full object-cover scale-150" frameborder="0" allow="autoplay; encrypted-media"
                            allowfullscreen></iframe>
                    </div>
                @else
                    <img src="{{ $fallbackImage }}" alt="Hero" class="w-full h-full object-cover">
                @endif
            </div>

            {{-- Overlay --}}
            <div class="absolute inset-0 bg-black/50"></div>

            {{-- Content --}}
            <div class="relative z-10 h-full flex items-center {{ $currentHeightClass }}">
                <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="max-w-2xl {{ $textAlign === 'center' ? 'mx-auto text-center' : ($textAlign === 'right' ? 'ml-auto text-right' : '') }}"
                        style="color: {{ $textColor }};">
                        <span
                            class="inline-flex items-center px-4 py-2 bg-white/20 backdrop-blur-sm text-white text-xs sm:text-sm font-semibold uppercase tracking-wider rounded-full border border-white/30 mb-4">
                            <i class="fas fa-play-circle mr-2"></i>{{ $heroBadge }}
                        </span>
                        <h1
                            class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-bold leading-tight mb-4 sm:mb-6 homepage-heading">
                            {{ $heroTitle }}
                        </h1>
                        <p class="text-base sm:text-lg lg:text-xl mb-6 sm:mb-8 leading-relaxed opacity-90">
                            {{ $heroSubtitle }}
                        </p>
                        <div
                            class="flex flex-col sm:flex-row flex-wrap gap-3 sm:gap-4 {{ $textAlign === 'center' ? 'justify-center' : ($textAlign === 'right' ? 'justify-end' : '') }}">
                            <a href="{{ $heroButtonLink }}"
                                class="inline-flex items-center justify-center px-8 py-4 bg-primary-600 text-white font-semibold rounded-lg hover:bg-primary-700 transition-all duration-300 shadow-lg hover:shadow-xl hover:-translate-y-1 group">
                                <i class="fas fa-shopping-bag mr-2 group-hover:animate-bounce"></i>{{ $heroButtonText }}
                            </a>
                            @if($heroSecondaryText)
                                <a href="{{ $heroSecondaryLink }}"
                                    class="inline-flex items-center justify-center px-8 py-4 border-2 border-white/80 text-white font-semibold rounded-lg hover:bg-white/10 transition-all duration-300 group">
                                    {{ $heroSecondaryText }}
                                    <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Video Controls --}}
            @if($showControls && $videoUrl)
                <button @click="videoPlaying = !videoPlaying; videoPlaying ? $refs.heroVideo.play() : $refs.heroVideo.pause()"
                    class="absolute bottom-8 right-8 z-20 w-12 h-12 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center text-white hover:bg-white/30 transition-all duration-300 border border-white/30">
                    <i class="fas" :class="videoPlaying ? 'fa-pause' : 'fa-play'"></i>
                </button>
            @endif
        </section>

    @else
        {{-- Default fallback --}}
        <section class="homepage-body bg-gradient-to-b from-gray-50 to-white py-6 sm:py-8 {{ $transparentHeaderPadding }}">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="relative rounded-3xl shadow-xl overflow-hidden bg-white border border-gray-100">
                    <div class="grid lg:grid-cols-2 gap-0 min-h-[350px] sm:min-h-[400px] lg:min-h-[500px]">
                        <div class="flex flex-col justify-center p-6 sm:p-8 lg:p-12 order-2 lg:order-1">
                            <span
                                class="inline-flex items-center w-fit px-3 py-1.5 bg-primary-100 text-primary-700 text-xs font-semibold uppercase tracking-wider rounded-full mb-4">
                                <i class="fas fa-star mr-1.5 text-yellow-500"></i>{{ $heroBadge }}
                            </span>
                            <h1
                                class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold text-gray-900 leading-tight mb-4 homepage-heading">
                                {{ $heroTitle }}
                            </h1>
                            <p class="text-gray-600 mb-6 sm:mb-8 max-w-md text-sm sm:text-base leading-relaxed">
                                {{ $heroSubtitle }}
                            </p>
                            <a href="{{ $heroButtonLink }}"
                                class="inline-flex items-center justify-center w-fit px-6 sm:px-8 py-3 sm:py-4 bg-primary-600 text-white font-semibold rounded-xl hover:bg-primary-700 transition-all duration-300 shadow-lg hover:shadow-xl hover:-translate-y-1 group">
                                <i class="fas fa-shopping-bag mr-2 group-hover:animate-bounce"></i>{{ $heroButtonText }}
                            </a>
                        </div>
                        <div
                            class="relative flex items-center justify-center p-6 sm:p-8 bg-gradient-to-br from-gray-50 via-gray-100 to-gray-50 order-1 lg:order-2 min-h-[200px] sm:min-h-[250px] lg:min-h-full overflow-hidden">
                            <img src="{{ $heroImage }}" alt="Product"
                                class="relative max-h-[180px] sm:max-h-[250px] lg:max-h-[400px] w-auto object-contain drop-shadow-2xl hover:scale-105 transition-transform duration-700">
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif
</div>{{-- End of desktop hero wrapper --}}