<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use App\Models\Coupon;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * Validate session coupon after cart changes and remove if no longer valid
     */
    private function validateSessionCoupon($cart)
    {
        $couponData = session('coupon');
        if ($couponData) {
            $coupon = Coupon::where('code', $couponData['code'])->first();
            if (!$coupon || !$coupon->isValid($cart->total)) {
                session()->forget('coupon');
                session()->flash('info', 'Coupon was removed because your cart no longer meets the minimum order requirement.');
            }
        }
    }

    public function index()
    {
        $cart = Cart::getCart();

        return \Inertia\Inertia::render('Storefront/Cart', \App\Support\CheckoutPage::cart($cart));
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'variation_id' => 'nullable|integer',
            'quantity' => 'nullable|integer|min:1',
        ]);

        $product = Product::with('variations')->findOrFail($request->product_id);
        $cart = Cart::getCart();
        $quantity = $request->quantity ?? 1;

        // Handle variable products using variation stock
        if ($product->product_type === 'variable') {
            if (!$request->filled('variation_id')) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'Please select a variation before adding to cart.'], 400);
                }
                return back()->with('error', 'Please select a variation before adding to cart.');
            }

            $variation = $product->variations()->where('id', $request->variation_id)->first();

            if (!$variation) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'Selected variation is invalid.'], 400);
                }
                return back()->with('error', 'Selected variation is invalid.');
            }

            if ($variation->quantity < 1) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'Sorry, this variation is out of stock.'], 400);
                }
                return back()->with('error', 'Sorry, this variation is out of stock.');
            }

            if ($quantity > $variation->quantity) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'Sorry, we only have ' . $variation->quantity . ' of this variation in stock.'], 400);
                }
                return back()->with('error', 'Sorry, we only have ' . $variation->quantity . ' of this variation in stock.');
            }

            // Store both product and variation in the cart item
            $cart->addItem($product->id, $quantity, $variation->id);

            if ($request->ajax() || $request->wantsJson()) {
                // Get cross-sell products for the added product
                $crossSellProducts = [];
                if (!empty($product->cross_sell_products)) {
                    $crossSellProducts = Product::whereIn('id', $product->cross_sell_products)
                        ->active()
                        ->with('translations')
                        ->take(4)
                        ->get()
                        ->map(function ($p) {
                            return [
                                'id' => $p->id,
                                'name' => $p->translated_name,
                                'slug' => $p->slug,
                                'image' => $p->image_url,
                                'price' => $p->price,
                                'sale_price' => $p->sale_price,
                                'current_price' => $p->current_price,
                                'formatted_price' => format_currency($p->price),
                                'formatted_sale_price' => $p->sale_price ? format_currency($p->sale_price) : null,
                                'formatted_current_price' => format_currency($p->current_price),
                                'product_type' => $p->product_type,
                                'is_in_stock' => $p->isInStock(),
                            ];
                        });
                }
                
                return response()->json([
                    'success' => true,
                    'message' => 'Product variation added to cart successfully!',
                    'cart_count' => $cart->item_count,
                    'cross_sell_products' => $crossSellProducts
                ]);
            }

            return back()->with('success', 'Product variation added to cart successfully!');
        }

        // Simple products keep existing behavior
        if (!$product->isInStock()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Sorry, this product is out of stock.'], 400);
            }
            return back()->with('error', 'Sorry, this product is out of stock.');
        }

        if ($quantity > $product->quantity) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Sorry, we only have ' . $product->quantity . ' items in stock.'], 400);
            }
            return back()->with('error', 'Sorry, we only have ' . $product->quantity . ' items in stock.');
        }

        $cart->addItem($product->id, $quantity);

        if ($request->ajax() || $request->wantsJson()) {
            // Get cross-sell products for the added product
            $crossSellProducts = [];
            if (!empty($product->cross_sell_products)) {
                $crossSellProducts = Product::whereIn('id', $product->cross_sell_products)
                    ->active()
                    ->with('translations')
                    ->take(4)
                    ->get()
                    ->map(function ($p) {
                        return [
                            'id' => $p->id,
                            'name' => $p->translated_name,
                            'slug' => $p->slug,
                            'image' => $p->image_url,
                            'price' => $p->price,
                            'sale_price' => $p->sale_price,
                            'current_price' => $p->current_price,
                            'formatted_price' => format_currency($p->price),
                            'formatted_sale_price' => $p->sale_price ? format_currency($p->sale_price) : null,
                            'formatted_current_price' => format_currency($p->current_price),
                            'product_type' => $p->product_type,
                            'is_in_stock' => $p->isInStock(),
                        ];
                    });
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Product added to cart successfully!',
                'cart_count' => $cart->item_count,
                'cross_sell_products' => $crossSellProducts
            ]);
        }

        return back()->with('success', 'Product added to cart successfully!');
    }

    public function buyNow(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'variation_id' => 'nullable|integer',
            'quantity' => 'nullable|integer|min:1',
        ]);

        $product = Product::with('variations')->findOrFail($request->product_id);
        $cart = Cart::getCart();
        $quantity = $request->quantity ?? 1;

        // Handle variable products
        if ($product->product_type === 'variable') {
            if (!$request->filled('variation_id')) {
                return back()->with('error', 'Please select a variation before purchasing.');
            }

            $variation = $product->variations()->where('id', $request->variation_id)->first();

            if (!$variation) {
                return back()->with('error', 'Selected variation is invalid.');
            }

            if ($variation->quantity < 1) {
                return back()->with('error', 'Sorry, this variation is out of stock.');
            }

            if ($quantity > $variation->quantity) {
                return back()->with('error', 'Sorry, we only have ' . $variation->quantity . ' of this variation in stock.');
            }

            // Clear cart and add only this item for buy now
            $cart->clear();
            $cart->addItem($product->id, $quantity, $variation->id);

            return redirect()->route('checkout.index');
        }

        // Simple products
        if (!$product->isInStock()) {
            return back()->with('error', 'Sorry, this product is out of stock.');
        }

        if ($quantity > $product->quantity) {
            return back()->with('error', 'Sorry, we only have ' . $product->quantity . ' items in stock.');
        }

        // Clear cart and add only this item for buy now
        $cart->clear();
        $cart->addItem($product->id, $quantity);

        return redirect()->route('checkout.index');
    }

    public function update(Request $request)
    {
        $request->validate([
            'item_id' => 'required|exists:cart_items,id',
            'quantity' => 'required|integer|min:0',
        ]);

        $cart = Cart::getCart();
        $cart->updateItemQuantity($request->item_id, $request->quantity);
        
        // Validate coupon after cart update
        $this->validateSessionCoupon($cart);

        return back()->with('success', 'Cart updated successfully!');
    }

    public function remove(Request $request)
    {
        $request->validate([
            'item_id' => 'required|exists:cart_items,id',
        ]);

        $cart = Cart::getCart();
        $cart->removeItem($request->item_id);
        
        // Validate coupon after item removal
        $this->validateSessionCoupon($cart);

        return back()->with('success', 'Item removed from cart!');
    }

    public function clear()
    {
        $cart = Cart::getCart();
        $cart->clear();
        
        // Remove coupon when cart is cleared
        session()->forget('coupon');

        return back()->with('success', 'Cart cleared successfully!');
    }

    public function applyCoupon(Request $request)
    {
        $request->validate([
            'coupon_code' => 'required|string',
        ]);

        $coupon = Coupon::where('code', $request->coupon_code)->first();
        
        if (!$coupon) {
            return back()->with('error', 'Invalid coupon code.');
        }

        $cart = Cart::getCart();
        
        // Check specific conditions for better error messages
        if (!$coupon->is_active) {
            return back()->with('error', 'This coupon is not active.');
        }
        if ($coupon->starts_at && $coupon->starts_at > now()) {
            return back()->with('error', 'This coupon is not yet valid.');
        }
        if ($coupon->expires_at && $coupon->expires_at < now()) {
            return back()->with('error', 'This coupon has expired.');
        }
        if ($coupon->max_uses && $coupon->used_count >= $coupon->max_uses) {
            return back()->with('error', 'This coupon has reached its usage limit.');
        }
        if ($coupon->minimum_amount && $cart->total < $coupon->minimum_amount) {
            return back()->with('error', 'Your order must be at least ' . format_currency($coupon->minimum_amount) . ' to use this coupon.');
        }

        session(['coupon' => [
            'code' => $coupon->code,
            'discount' => $coupon->calculateDiscount($cart->total),
            'type' => $coupon->type,
            'value' => $coupon->value,
        ]]);

        return back()->with('success', 'Coupon applied successfully!');
    }

    public function removeCoupon()
    {
        session()->forget('coupon');
        return back()->with('success', 'Coupon removed.');
    }

    public function getCartCount()
    {
        $cart = Cart::getCart();
        return response()->json(['count' => $cart->item_count]);
    }
}
