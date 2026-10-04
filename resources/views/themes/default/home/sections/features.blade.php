{{-- Default theme override: starts as a copy of the shared view. Edit this file to change the default theme only. --}}
@if($appSettings['homepage']['show_features'] ?? $appSettings['theme']['show_features'] ?? true)
    <section class="py-8 md:py-12 border-b border-gray-100 homepage-body"
        style="{{ $featuresBgColor ? 'background-color: ' . $featuresBgColor . ';' : '' }}">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 lg:gap-8">
                @for($i = 1; $i <= 4; $i++)
                    @php
                        $defaultIcons = ['truck', 'undo', 'shield-alt', 'headset'];
                        $defaultTitles = [__('messages.free_shipping'), __('messages.easy_returns'), __('messages.secure_payment'), __('messages.support_24_7')];
                        $defaultDescs = [__('messages.on_orders_over') . ' $50', '30-' . __('messages.day_return_policy'), __('messages.secure_checkout'), __('messages.dedicated_support')];
                        $featureIcon = $appSettings['homepage']["feature_{$i}_icon"] ?? $defaultIcons[$i - 1];
                        $featureTitle = $appSettings['homepage']["feature_{$i}_title"] ?? $defaultTitles[$i - 1];
                        $featureDesc = $appSettings['homepage']["feature_{$i}_desc"] ?? $defaultDescs[$i - 1];
                    @endphp
                    <div
                        class="group text-center p-4 md:p-6 rounded-2xl transition-colors duration-200 cursor-default">
                        <div
                            class="w-14 h-14 md:w-16 md:h-16 bg-primary-600 rounded-xl flex items-center justify-center mx-auto mb-3 md:mb-4 group-hover:bg-primary-700 transition-colors duration-200 shadow-sm">
                            <i class="fas {{ $iconMap[$featureIcon] ?? 'fa-check' }} text-white text-lg md:text-xl"></i>
                        </div>
                        <h3 class="font-semibold text-gray-900 text-sm md:text-base mb-1">{{ $featureTitle }}</h3>
                        <p class="text-xs md:text-sm text-gray-500 leading-relaxed">{{ $featureDesc }}</p>
                    </div>
                @endfor
            </div>
        </div>
    </section>
@endif