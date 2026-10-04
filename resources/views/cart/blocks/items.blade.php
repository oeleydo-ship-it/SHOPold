{{-- Cart page: list of cart lines with quantity select, line total and remove. Shared by every theme; replace at themes/<slug>/cart/blocks/items.blade.php --}}
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="divide-y">
                    @foreach($cart->items as $item)
                    <div class="p-6 flex gap-4">
                        <div class="w-24 h-24 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                            @php
                                $thumbImage = $item->variation && $item->variation->image
                                    ? asset('storage/' . $item->variation->image)
                                    : $item->product->image_url;
                            @endphp
                            @if($thumbImage)
                                <img src="{{ $thumbImage }}" alt="{{ $item->product->translated_name }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <i class="fas fa-image text-gray-400"></i>
                                </div>
                            @endif
                        </div>
                        <div class="flex-1">
                            <a href="{{ route('shop.show', $item->product->slug) }}" class="font-semibold text-gray-900 hover:text-primary-600">
                                {{ $item->product->translated_name }}
                            </a>
                            @if($item->variation)
                                <p class="text-sm text-gray-500 mt-1">
                                    {{ $item->variation->variation_name ?? 'Selected variation' }} (SKU: {{ $item->variation->sku }})
                                </p>
                            @else
                                <p class="text-sm text-gray-500 mt-1">{{ $item->product->category->translated_name ?? $item->product->category->name }}</p>
                            @endif
                            @php
                                $unitPrice = $item->variation
                                    ? $item->variation->current_price
                                    : $item->product->current_price;
                            @endphp
                            <p class="text-primary-600 font-semibold mt-2">{{ format_currency($unitPrice) }}</p>
                        </div>
                        <div class="flex flex-col items-end gap-2">
                            <form action="{{ route('cart.update') }}" method="POST" class="flex items-center gap-2">
                                @csrf
                                <input type="hidden" name="item_id" value="{{ $item->id }}">
                                @php
                                    $maxQty = $item->variation ? $item->variation->quantity : $item->product->quantity;
                                @endphp
                                <select name="quantity" onchange="this.form.submit()" class="px-3 py-2 border rounded-lg text-sm">
                                    @for($i = 1; $i <= min(10, $maxQty); $i++)
                                        <option value="{{ $i }}" {{ $item->quantity == $i ? 'selected' : '' }}>{{ $i }}</option>
                                    @endfor
                                </select>
                            </form>
                            <p class="font-semibold">{{ format_currency($item->subtotal) }}</p>
                            <form action="{{ route('cart.remove') }}" method="POST">
                                @csrf
                                <input type="hidden" name="item_id" value="{{ $item->id }}">
                                <button type="submit" class="text-red-500 hover:text-red-700 text-sm">
                                    <i class="fas fa-trash mr-1"></i>Remove
                                </button>
                            </form>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
