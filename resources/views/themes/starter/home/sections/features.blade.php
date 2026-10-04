{{-- Starter theme: features as a plain bordered strip with thin dividers. --}}
@if($appSettings['homepage']['show_features'] ?? $appSettings['theme']['show_features'] ?? true)
    <section class="py-6 md:py-8 border-y border-gray-300 homepage-body"
        style="{{ $featuresBgColor ? 'background-color: ' . $featuresBgColor . ';' : '' }}">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 lg:grid-cols-4 lg:divide-x divide-gray-300">
                @for($i = 1; $i <= 4; $i++)
                    @php
                        $defaultIcons = ['truck', 'undo', 'shield-alt', 'headset'];
                        $defaultTitles = [__('messages.free_shipping'), __('messages.easy_returns'), __('messages.secure_payment'), __('messages.support_24_7')];
                        $defaultDescs = [__('messages.on_orders_over') . ' $50', '30-' . __('messages.day_return_policy'), __('messages.secure_checkout'), __('messages.dedicated_support')];
                        $featureIcon = $appSettings['homepage']["feature_{$i}_icon"] ?? $defaultIcons[$i - 1];
                        $featureTitle = $appSettings['homepage']["feature_{$i}_title"] ?? $defaultTitles[$i - 1];
                        $featureDesc = $appSettings['homepage']["feature_{$i}_desc"] ?? $defaultDescs[$i - 1];
                    @endphp
                    <div class="flex items-center gap-3 px-3 py-3 lg:px-6">
                        <i class="fas {{ $iconMap[$featureIcon] ?? 'fa-check' }} text-primary-600 text-2xl w-8 text-center shrink-0"></i>
                        <div class="min-w-0">
                            <h3 class="font-semibold text-gray-900 text-sm leading-tight">{{ $featureTitle }}</h3>
                            <p class="text-xs text-gray-500 leading-snug">{{ $featureDesc }}</p>
                        </div>
                    </div>
                @endfor
            </div>
        </div>
    </section>
@endif
