{{-- Product page: Mobile sticky add-to-cart bar plus the page scripts (variation selector, cart actions, AI context). Every product page layout must include this.. Shared by every theme; a theme can replace it at themes/<slug>/shop/product/mobile-bar.blade.php --}}
{{-- Mobile Sticky Add to Cart Bar --}}
@if(setting('products.mobile_sticky_buttons', true) && $product->isInStock())
@php
    $stickyUseCustomColors = setting('products.use_custom_button_colors', false);
    $stickyAtcBgColor = $stickyUseCustomColors ? setting('products.add_to_cart_bg_color', '#ffffff') : null;
    $stickyAtcTextColor = $stickyUseCustomColors ? setting('products.add_to_cart_text_color', '#2563eb') : null;
    $stickyAtcBorderColor = $stickyUseCustomColors ? setting('products.add_to_cart_border_color', '#2563eb') : null;
    $stickyBuyNowBgColor = $stickyUseCustomColors ? setting('products.buy_now_bg_color', '#2563eb') : null;
    $stickyBuyNowTextColor = $stickyUseCustomColors ? setting('products.buy_now_text_color', '#ffffff') : null;
    $mobileButtonLayout = setting('products.mobile_button_layout', 'full');
    $showMobileQuantity = setting('products.mobile_show_quantity', false);
@endphp
<div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-lg z-40 md:hidden transition-transform duration-300" 
     x-data="mobileStickyBar()" 
     x-init="initStickyVisibility()"
     :class="stickyVisible ? 'translate-y-0' : 'translate-y-full'"
     id="mobile-sticky-bar">
    <div class="px-4 py-3 safe-area-inset-bottom">
        {{-- Price Display --}}
        <div class="flex items-center justify-between mb-2">
            <div>
                @if($product->isOnSale())
                    <span class="text-lg font-bold text-primary-600">{{ format_currency($product->sale_price) }}</span>
                    <span class="text-sm text-gray-400 line-through ml-1">{{ format_currency($product->price) }}</span>
                @else
                    <span class="text-lg font-bold text-gray-900" x-text="variationPrice || '{{ format_currency($product->price) }}'">{{ format_currency($product->price) }}</span>
                @endif
            </div>
            @if($showMobileQuantity)
            <div class="flex items-center border border-gray-300 rounded-md">
                <button type="button" @click="decrementMobileQty()" class="px-3 py-1.5 text-gray-400 hover:text-gray-600">
                    <span class="text-sm">−</span>
                </button>
                <input type="number" x-model="mobileQty" min="1" class="w-10 text-center border-0 focus:ring-0 text-gray-800 text-sm py-1">
                <button type="button" @click="incrementMobileQty()" class="px-3 py-1.5 text-gray-400 hover:text-gray-600">
                    <span class="text-sm">+</span>
                </button>
            </div>
            @endif
        </div>
        
        {{-- Buttons --}}
        <div class="flex gap-3">
            @if($mobileButtonLayout === 'with_icons')
            {{-- Layout with Wishlist & Compare Icons --}}
            @if(setting('products.show_wishlist', true))
            <button type="button" onclick="document.querySelector('form[action*=wishlist]')?.submit()" class="w-10 h-10 flex items-center justify-center border border-gray-300 rounded-lg text-gray-600 hover:text-red-500 hover:border-red-500 transition">
                <i class="far fa-heart"></i>
            </button>
            @endif
            @if(setting('products.show_compare', true))
            <button type="button" onclick="addToCompare({{ $product->id }})" class="w-10 h-10 flex items-center justify-center border border-gray-300 rounded-lg text-gray-600 hover:text-primary-600 hover:border-primary-600 transition">
                <i class="fas fa-exchange-alt"></i>
            </button>
            @endif
            @endif
            
            @if($mobileButtonLayout === 'cart_only')
            {{-- Cart Only Layout - Single full-width Add to Cart button --}}
            <form action="{{ route('cart.add') }}" method="POST" class="mobile-sticky-cart-form flex-1" @submit.prevent="submitStickyCart($event, $el)">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="quantity" class="qty-input" :value="mobileQty">
                @if($product->product_type === 'variable')
                <input type="hidden" name="variation_id" x-bind:value="selectedVariationId">
                @endif
                @if($stickyUseCustomColors)
                <button type="submit" 
                        :disabled="submitting{{ $product->product_type === 'variable' ? ' || !selectedVariationId' : '' }}"
                        class="w-full py-3 font-medium rounded-lg transition text-sm"
                        :class="submitting{{ $product->product_type === 'variable' ? ' || !selectedVariationId' : '' }} ? 'opacity-50' : ''"
                        style="background-color: {{ $stickyBuyNowBgColor }}; color: {{ $stickyBuyNowTextColor }};">
                    <i class="fas fa-shopping-cart mr-1"></i>
                    <span x-text="submitting ? 'Adding...' : 'Add to cart'"></span>
                </button>
                @else
                <button type="submit" 
                        :disabled="submitting{{ $product->product_type === 'variable' ? ' || !selectedVariationId' : '' }}"
                        :class="submitting{{ $product->product_type === 'variable' ? ' || !selectedVariationId' : '' }} ? 'bg-gray-400' : 'bg-primary-600'"
                        class="w-full py-3 text-white font-medium rounded-lg transition text-sm">
                    <i class="fas fa-shopping-cart mr-1"></i>
                    <span x-text="submitting ? 'Adding...' : 'Add to cart'"></span>
                </button>
                @endif
            </form>
            @else
            {{-- Full or With Icons Layout - Add to Cart + Buy Now buttons --}}
            @if(setting('products.show_add_to_cart', true))
            <form action="{{ route('cart.add') }}" method="POST" class="mobile-sticky-cart-form flex-1" @submit.prevent="submitStickyCart($event, $el)">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="quantity" class="qty-input" :value="mobileQty">
                @if($product->product_type === 'variable')
                <input type="hidden" name="variation_id" x-bind:value="selectedVariationId">
                @endif
                @if($stickyUseCustomColors)
                <button type="submit" 
                        :disabled="submitting{{ $product->product_type === 'variable' ? ' || !selectedVariationId' : '' }}"
                        class="w-full py-3 border-2 font-medium rounded-lg transition text-sm"
                        :class="submitting{{ $product->product_type === 'variable' ? ' || !selectedVariationId' : '' }} ? 'opacity-50' : ''"
                        style="background-color: {{ $stickyAtcBgColor }}; color: {{ $stickyAtcTextColor }}; border-color: {{ $stickyAtcBorderColor }};">
                    <i class="fas fa-shopping-cart mr-1"></i>
                    <span x-text="submitting ? 'Adding...' : 'Add to cart'"></span>
                </button>
                @else
                <button type="submit" 
                        :disabled="submitting{{ $product->product_type === 'variable' ? ' || !selectedVariationId' : '' }}"
                        :class="submitting{{ $product->product_type === 'variable' ? ' || !selectedVariationId' : '' }} ? 'border-gray-300 text-gray-400' : 'border-primary-600 text-primary-600'"
                        class="w-full py-3 border-2 font-medium rounded-lg transition text-sm">
                    <i class="fas fa-shopping-cart mr-1"></i>
                    <span x-text="submitting ? 'Adding...' : 'Add to cart'"></span>
                </button>
                @endif
            </form>
            @endif
            
            @if(setting('products.show_buy_now', true))
            {{-- Buy Now Button --}}
            <form action="{{ route('cart.buy-now') }}" method="POST" class="mobile-sticky-buy-form flex-1" @submit="submitStickyBuyNow($event, $el)">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="quantity" class="qty-input" :value="mobileQty">
                @if($product->product_type === 'variable')
                <input type="hidden" name="variation_id" x-bind:value="selectedVariationId">
                @endif
                @if($stickyUseCustomColors)
                <button type="submit" 
                        :disabled="buyingNow{{ $product->product_type === 'variable' ? ' || !selectedVariationId' : '' }}"
                        class="w-full py-3 font-medium rounded-lg transition text-sm"
                        :class="buyingNow{{ $product->product_type === 'variable' ? ' || !selectedVariationId' : '' }} ? 'opacity-50' : ''"
                        style="background-color: {{ $stickyBuyNowBgColor }}; color: {{ $stickyBuyNowTextColor }};">
                    <span x-text="buyingNow ? 'Processing...' : 'Buy it now'"></span>
                </button>
                @else
                <button type="submit" 
                        :disabled="buyingNow{{ $product->product_type === 'variable' ? ' || !selectedVariationId' : '' }}"
                        :class="buyingNow{{ $product->product_type === 'variable' ? ' || !selectedVariationId' : '' }} ? 'bg-gray-400' : 'bg-primary-600'"
                        class="w-full py-3 text-white font-medium rounded-lg transition text-sm">
                    <span x-text="buyingNow ? 'Processing...' : 'Buy it now'"></span>
                </button>
                @endif
            </form>
            @endif
            @endif
        </div>
    </div>
</div>
@endif

<script>
// Helper function to get current quantity
function getQuantity() {
    const input = document.getElementById('quantity');
    return input ? parseInt(input.value) || 1 : 1;
}

// Update all hidden quantity inputs
function syncQuantityInputs() {
    const qty = getQuantity();
    document.querySelectorAll('.qty-input').forEach(input => {
        input.value = qty;
    });
}

function incrementQty() {
    let input = document.getElementById('quantity');
    let max = parseInt(input.getAttribute('max'));
    if (parseInt(input.value) < max) {
        input.value = parseInt(input.value) + 1;
        syncQuantityInputs();
    }
}

function decrementQty() {
    let input = document.getElementById('quantity');
    if (parseInt(input.value) > 1) {
        input.value = parseInt(input.value) - 1;
        syncQuantityInputs();
    }
}

// AJAX Add to Cart function
function ajaxAddToCart(form, callback) {
    // Sync quantity before submit
    syncQuantityInputs();
    
    const formData = new FormData(form);
    
    fetch(form.action, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update cart count badges in header
            const cartBadges = document.querySelectorAll('.cart-count-badge');
            cartBadges.forEach(badge => {
                badge.textContent = data.cart_count;
                badge.classList.remove('hidden');
            });
            
            // Show cross-sell modal if there are cross-sell products
            if (data.cross_sell_products && data.cross_sell_products.length > 0) {
                window.dispatchEvent(new CustomEvent('cross-sell-modal', { 
                    detail: { products: data.cross_sell_products }
                }));
            } else {
                window.dispatchEvent(new CustomEvent('toast', { 
                    detail: { type: 'success', message: data.message }
                }));
            }
        } else {
            window.dispatchEvent(new CustomEvent('toast', { 
                detail: { type: 'error', message: data.message || 'Failed to add to cart' }
            }));
        }
        if (callback) callback(data.success);
    })
    .catch(error => {
        console.error('Error:', error);
        window.dispatchEvent(new CustomEvent('toast', { 
            detail: { type: 'error', message: 'Failed to add to cart. Please try again.' }
        }));
        if (callback) callback(false);
    });
}

// Mobile Sticky Bar Component
function mobileStickyBar() {
    return {
        mobileQty: 1,
        submitting: false,
        buyingNow: false,
        selectedVariationId: null,
        variationPrice: null,
        stickyVisible: false,
        
        init() {
            // Watch for variation changes from the main variation selector
            if (typeof Alpine !== 'undefined' && Alpine.store('variation')) {
                this.$watch(() => Alpine.store('variation').selectedVariation, (variation) => {
                    this.selectedVariationId = variation?.id || null;
                    if (variation?.price) {
                        this.variationPrice = this.formatCurrency(variation.sale_price || variation.price);
                    } else {
                        this.variationPrice = null;
                    }
                });
            }
            
            // Listen for variation changes from window events
            window.addEventListener('variation-changed', (e) => {
                const variation = e.detail.variation;
                this.selectedVariationId = variation?.id || null;
                if (variation?.price) {
                    this.variationPrice = this.formatCurrency(variation.sale_price || variation.price);
                } else {
                    this.variationPrice = null;
                }
            });
            
            // Sync with main quantity input
            const mainQtyInput = document.getElementById('quantity');
            if (mainQtyInput) {
                this.mobileQty = parseInt(mainQtyInput.value) || 1;
                mainQtyInput.addEventListener('input', () => {
                    this.mobileQty = parseInt(mainQtyInput.value) || 1;
                });
            }
        },
        
        initStickyVisibility() {
            // Find the inline cart buttons element
            const inlineButtons = document.getElementById('inline-cart-buttons') || document.getElementById('inline-cart-buttons-simple');
            if (!inlineButtons) {
                this.stickyVisible = true;
                return;
            }
            
            // Check visibility on scroll
            const checkVisibility = () => {
                const rect = inlineButtons.getBoundingClientRect();
                const windowHeight = window.innerHeight;
                // Show sticky bar when inline buttons are above the viewport (scrolled past)
                // or when they're below the visible area
                this.stickyVisible = rect.bottom < 0 || rect.top > windowHeight;
            };
            
            // Initial check
            checkVisibility();
            
            // Listen for scroll events with throttling
            let ticking = false;
            window.addEventListener('scroll', () => {
                if (!ticking) {
                    window.requestAnimationFrame(() => {
                        checkVisibility();
                        ticking = false;
                    });
                    ticking = true;
                }
            }, { passive: true });
            
            // Also check on resize
            window.addEventListener('resize', checkVisibility, { passive: true });
        },
        
        incrementMobileQty() {
            const mainQtyInput = document.getElementById('quantity');
            const max = mainQtyInput ? parseInt(mainQtyInput.getAttribute('max')) : 999;
            if (this.mobileQty < max) {
                this.mobileQty++;
                if (mainQtyInput) mainQtyInput.value = this.mobileQty;
                syncQuantityInputs();
            }
        },
        
        decrementMobileQty() {
            if (this.mobileQty > 1) {
                this.mobileQty--;
                const mainQtyInput = document.getElementById('quantity');
                if (mainQtyInput) mainQtyInput.value = this.mobileQty;
                syncQuantityInputs();
            }
        },
        
        submitStickyCart(e, form) {
            e.preventDefault();
            this.submitting = true;
            syncQuantityInputs();
            
            ajaxAddToCart(form, (success) => {
                this.submitting = false;
            });
        },
        
        submitStickyBuyNow(e, form) {
            this.buyingNow = true;
            syncQuantityInputs();
            // Let the form submit normally for buy now
        },
        
        formatCurrency(amount) {
            const symbol = '{{ setting("payment.currency_symbol", setting("store.currency_symbol", "AED")) }}';
            const position = '{{ setting("payment.currency_position", "before") }}';
            const formatted = parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            
            if (position === 'after') {
                return formatted + symbol;
            }
            return symbol + formatted;
        }
    }
}

// Sale Countdown Timer
function saleCountdown(endDate) {
    return {
        days: '00',
        hours: '00',
        minutes: '00',
        seconds: '00',
        endTime: new Date(endDate).getTime(),
        interval: null,
        
        init() {
            this.updateCountdown();
            this.interval = setInterval(() => this.updateCountdown(), 1000);
        },
        
        updateCountdown() {
            const now = new Date().getTime();
            const distance = this.endTime - now;
            
            if (distance < 0) {
                clearInterval(this.interval);
                this.days = '00';
                this.hours = '00';
                this.minutes = '00';
                this.seconds = '00';
                return;
            }
            
            this.days = String(Math.floor(distance / (1000 * 60 * 60 * 24))).padStart(2, '0');
            this.hours = String(Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))).padStart(2, '0');
            this.minutes = String(Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60))).padStart(2, '0');
            this.seconds = String(Math.floor((distance % (1000 * 60)) / 1000)).padStart(2, '0');
        },
        
        destroy() {
            if (this.interval) clearInterval(this.interval);
        }
    }
}

// Variation Selector
@if($product->product_type === 'variable' && $product->variations->count() > 0)
    @php
        $variationData = $product->variations->map(function ($v) {
            return [
                'id' => $v->id,
                'sku' => $v->sku,
                'price' => $v->price,
                'sale_price' => $v->sale_price,
                'quantity' => $v->quantity,
                'variation_name' => $v->variation_name,
                'image' => $v->image ? asset('storage/' . $v->image) : null,
                'attribute_values' => $v->attributeValues->pluck('id')->toArray(),
            ];
        })->values();
    @endphp

    const productVariations = @json($variationData);

    // Alpine store for sharing variation state
    document.addEventListener('alpine:init', () => {
        Alpine.store('variation', {
            selectedVariation: null,
        });
    });

function variationSelector() {
    return {
        selectedAttributes: {},
        selectedVariation: null,
        variations: productVariations,
        submitting: false,
        buyingNow: false,
        
        selectAttribute(attrName, valueId) {
            this.selectedAttributes[attrName] = valueId;
            this.findMatchingVariation();
        },
        
        findMatchingVariation() {
            const selectedValueIds = Object.values(this.selectedAttributes);
            
            // Find variation that has all selected attribute values
            this.selectedVariation = this.variations.find(variation => {
                return selectedValueIds.every(valueId => 
                    variation.attribute_values.includes(valueId)
                );
            }) || null;
            
            // Update Alpine store
            Alpine.store('variation').selectedVariation = this.selectedVariation;
            
            // Dispatch event to update main product image
            window.dispatchEvent(new CustomEvent('variation-changed', {
                detail: {
                    variation: this.selectedVariation,
                    image: this.selectedVariation?.image || null
                }
            }));
        },
        
        submitAddToCart(e, form) {
            e.preventDefault();
            if (!this.selectedVariation) return;
            
            this.submitting = true;
            syncQuantityInputs();
            
            ajaxAddToCart(form, (success) => {
                this.submitting = false;
            });
        },
        
        submitBuyNow(e, form) {
            if (!this.selectedVariation) {
                e.preventDefault();
                return;
            }
            this.buyingNow = true;
            syncQuantityInputs();
            // Let the form submit normally
        },
        
        formatCurrency(amount) {
            const symbol = '{{ setting("payment.currency_symbol", setting("store.currency_symbol", "AED")) }}';
            const position = '{{ setting("payment.currency_position", "before") }}';
            const formatted = parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            
            if (position === 'after') {
                return formatted + symbol;
            }
            return symbol + formatted;
        }
    }
}
@endif

// Simple product form handlers (used when product is not variable)
function submitSimpleAddToCart(e, form) {
    e.preventDefault();
    const alpineData = Alpine.$data(form.closest('[x-data]'));
    if (alpineData) alpineData.submitting = true;
    syncQuantityInputs();
    
    ajaxAddToCart(form, (success) => {
        if (alpineData) alpineData.submitting = false;
    });
}

function submitSimpleBuyNow(e, form) {
    const alpineData = Alpine.$data(form.closest('[x-data]'));
    if (alpineData) alpineData.buyingNow = true;
    syncQuantityInputs();
    // Let the form submit normally
}

// Initialize on DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {
    // Sync quantity on input change
    const qtyInput = document.getElementById('quantity');
    if (qtyInput) {
        qtyInput.addEventListener('input', syncQuantityInputs);
    }
    
    // Cross-sell buttons on product page
    const crossSellButtons = document.querySelectorAll('.cross-sell-add-to-cart');
    
    crossSellButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const originalContent = this.innerHTML;
            const btn = this;
            
            // Disable button and show loading state
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Adding...';
            
            // Send AJAX request
            fetch('{{ route("cart.add") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: 1
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success state
                    btn.innerHTML = '<i class="fas fa-check mr-2"></i>Added!';
                    btn.classList.remove('bg-primary-600', 'hover:bg-primary-700');
                    btn.classList.add('bg-green-600');
                    
                    // Update cart count badges in header
                    const cartBadges = document.querySelectorAll('.cart-count-badge');
                    cartBadges.forEach(badge => {
                        badge.textContent = data.cart_count;
                        badge.classList.remove('hidden');
                    });
                    
                    // Show toast notification
                    window.dispatchEvent(new CustomEvent('toast', { 
                        detail: { type: 'success', message: data.message }
                    }));
                    
                    // Reset button after 2 seconds
                    setTimeout(() => {
                        btn.innerHTML = originalContent;
                        btn.classList.remove('bg-green-600');
                        btn.classList.add('bg-primary-600', 'hover:bg-primary-700');
                        btn.disabled = false;
                    }, 2000);
                } else {
                    // Show error state
                    btn.innerHTML = '<i class="fas fa-times mr-2"></i>Error';
                    btn.classList.remove('bg-primary-600', 'hover:bg-primary-700');
                    btn.classList.add('bg-red-600');
                    
                    // Show toast notification
                    window.dispatchEvent(new CustomEvent('toast', { 
                        detail: { type: 'error', message: data.message || 'Failed to add to cart' }
                    }));
                    
                    // Reset button after 2 seconds
                    setTimeout(() => {
                        btn.innerHTML = originalContent;
                        btn.classList.remove('bg-red-600');
                        btn.classList.add('bg-primary-600', 'hover:bg-primary-700');
                        btn.disabled = false;
                    }, 2000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                btn.innerHTML = '<i class="fas fa-times mr-2"></i>Error';
                btn.classList.remove('bg-primary-600', 'hover:bg-primary-700');
                btn.classList.add('bg-red-600');
                
                // Show toast notification
                window.dispatchEvent(new CustomEvent('toast', { 
                    detail: { type: 'error', message: 'Failed to add to cart. Please try again.' }
                }));
                
                // Reset button after 2 seconds
                setTimeout(() => {
                    btn.innerHTML = originalContent;
                    btn.classList.remove('bg-red-600');
                    btn.classList.add('bg-primary-600', 'hover:bg-primary-700');
                    btn.disabled = false;
                }, 2000);
            });
        });
    });
});

// Copy product link to clipboard
function copyProductLink() {
    navigator.clipboard.writeText(window.location.href).then(() => {
        window.dispatchEvent(new CustomEvent('toast', { 
            detail: { type: 'success', message: 'Link copied to clipboard!' }
        }));
    }).catch(err => {
        console.error('Failed to copy:', err);
    });
}
</script>

@php
    $aiCurrentProduct = [
        'id' => $product->id,
        'slug' => $product->slug,
        'name' => $product->translated_name,
        'url' => url('/shop/' . $product->slug),
        'category' => $product->category?->name,
        'brand' => $product->brand?->name,
        'price_value' => (float) $product->price,
        'sale_price_value' => $product->sale_price !== null ? (float) $product->sale_price : null,
        'price' => setting('store.currency_symbol', '$') . number_format((float) $product->price, 2),
        'sale_price' => $product->sale_price !== null ? (setting('store.currency_symbol', '$') . number_format((float) $product->sale_price, 2)) : null,
        'short_description' => strip_tags($product->translated_short_description ?? $product->short_description ?? ''),
        'features' => is_array($product->features ?? null) 
            ? array_values(array_filter($product->features, fn($f) => is_string($f) && trim($f) !== '')) 
            : [],
    ];
@endphp

<script>
window.aiCurrentProduct = @json($aiCurrentProduct);
</script>
