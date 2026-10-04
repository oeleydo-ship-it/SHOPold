{{-- Modern theme: features strip as raised cards with a gradient icon tile. --}}
@if($appSettings['homepage']['show_features'] ?? $appSettings['theme']['show_features'] ?? true)
    <section class="py-10 md:py-14 homepage-body"
        style="{{ $featuresBgColor ? 'background-color: ' . $featuresBgColor . ';' : '' }}">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
                @for($i = 1; $i <= 4; $i++)
                    @php
                        $defaultIcons = ['truck', 'undo', 'shield-alt', 'headset'];
                        $defaultTitles = [__('messages.free_shipping'), __('messages.easy_returns'), __('messages.secure_payment'), __('messages.support_24_7')];
                        $defaultDescs = [__('messages.on_orders_over') . ' $50', '30-' . __('messages.day_return_policy'), __('messages.secure_checkout'), __('messages.dedicated_support')];
                        $featureIcon = $appSettings['homepage']["feature_{$i}_icon"] ?? $defaultIcons[$i - 1];
                        $featureTitle = $appSettings['homepage']["feature_{$i}_title"] ?? $defaultTitles[$i - 1];
                        $featureDesc = $appSettings['homepage']["feature_{$i}_desc"] ?? $defaultDescs[$i - 1];
                    @endphp
                    <div class="group flex items-center gap-4 p-5 bg-white rounded-3xl shadow-sm border border-gray-100 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 cursor-default">
                        <div class="w-14 h-14 shrink-0 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center shadow-md shadow-primary-600/30 group-hover:rotate-6 transition-transform duration-300">
                            <i class="fas {{ $iconMap[$featureIcon] ?? 'fa-check' }} text-white text-xl"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="font-bold text-gray-900 text-sm md:text-base leading-tight">{{ $featureTitle }}</h3>
                            <p class="text-xs md:text-sm text-gray-500 leading-snug mt-0.5">{{ $featureDesc }}</p>
                        </div>
                    </div>
                @endfor
            </div>
        </div>
    </section>
@endif
