{{-- Vendor store page: social links (only real web links). $class styles each icon. Replace at themes/<slug>/shop/vendor-page/social.blade.php --}}
@php
    $links = collect(['facebook' => 'fa-facebook-f', 'twitter' => 'fa-twitter', 'instagram' => 'fa-instagram'])
        ->mapWithKeys(fn ($icon, $k) => [$k => ['icon' => $icon, 'url' => trim((string) ($vendor->social_links[$k] ?? ''))]])
        ->filter(fn ($l) => preg_match('#^https?://#i', $l['url']));
@endphp
@if($links->isNotEmpty())
    <div class="flex gap-2">
        @foreach($links as $network => $l)
            <a href="{{ $l['url'] }}" target="_blank" rel="noopener noreferrer nofollow" aria-label="{{ $vendor->store_name }} on {{ ucfirst($network) }}"
               class="{{ $class ?? 'w-10 h-10 rounded-lg flex items-center justify-center bg-white/10 text-white hover:bg-white/20 transition' }}">
                <i class="fab {{ $l['icon'] }}" aria-hidden="true"></i>
            </a>
        @endforeach
    </div>
@endif
