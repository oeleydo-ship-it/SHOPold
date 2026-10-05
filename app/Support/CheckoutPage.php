<?php

namespace App\Support;

use App\Models\Cart;
use App\Models\Order;
use App\Models\PickupLocation;
use App\Models\Product;
use App\Services\PaymentGatewayService;
use Illuminate\Support\Collection;

/** Props for the React cart, checkout and order confirmation pages. */
class CheckoutPage
{
    /** Gateways that take the customer to the provider's site after the order is created. */
    public const REDIRECT_GATEWAYS = [
        'paystack' => 'Paystack', 'flutterwave' => 'Flutterwave', 'razorpay' => 'Razorpay', 'paytm' => 'Paytm',
        'instamojo' => 'Instamojo', 'payfast' => 'PayFast', 'mercadopago' => 'Mercado Pago', 'iyzico' => 'iyzico',
        'sslcommerz' => 'SSLCommerz', 'bkash' => 'bKash', 'nagad' => 'Nagad',
    ];

    public static function cart(Cart $cart): array
    {
        $cart->loadMissing(['items.product.category.translations', 'items.product.translations', 'items.variation']);
        $coupon = session('coupon');
        $discount = (float) ($coupon['discount'] ?? 0);
        $inCart = $cart->items->pluck('product_id')->all();
        $crossIds = $cart->items->flatMap(fn ($i) => (array) ($i->product->cross_sell_products ?? []))
            ->map(fn ($id) => (int) $id)->reject(fn ($id) => in_array($id, $inCart, true))->unique()->values();
        $cross = $crossIds->isEmpty() ? collect() : ProductCards::with(Product::query()->active())->whereIn('id', $crossIds)->get()
            ->sortBy(fn ($p) => $crossIds->search($p->id))->take(4)->values();

        return [
            'items' => static::items($cart),
            'coupon' => $coupon ? ['code' => $coupon['code'], 'discount' => format_currency($discount)] : null,
            'totals' => [
                'subtotal' => format_currency($cart->total),
                'discount' => $discount > 0 ? format_currency($discount) : null,
                'total' => format_currency($cart->total - $discount),
            ],
            'crossSell' => ProductCards::map($cross),
            'guestCheckout' => (bool) setting('features.guest_checkout', false),
            'seo' => ['title' => __('messages.your_cart'), 'robots' => 'noindex'],
        ];
    }

    public static function items(Cart $cart): array
    {
        return $cart->items->map(function ($item) {
            $p = $item->product;
            $v = $item->variation;

            return [
                'id' => $item->id,
                'productId' => $p->id,
                'url' => route('shop.show', $p->slug),
                'name' => $p->translated_name,
                'detail' => $v ? trim(($v->variation_name ?: 'Selected variation') . ($v->sku ? " (SKU: {$v->sku})" : ''))
                    : ($p->category ? ($p->category->translated_name ?? $p->category->name) : null),
                'image' => $v && $v->image ? asset('storage/' . $v->image) : $p->image_url,
                'unit' => format_currency($v ? $v->current_price : $p->current_price),
                'quantity' => (int) $item->quantity,
                'max' => max((int) $item->quantity, min(10, (int) ($v ? $v->quantity : $p->quantity))),
                'subtotal' => format_currency($item->subtotal),
            ];
        })->values()->all();
    }

    /**
     * @param  array  $d  the values CheckoutController::index computes (cart, subtotal, discount, tax, shippingMethods, ...)
     */
    public static function checkout(array $d): array
    {
        $user = auth()->user();
        $base = $d['subtotal'] - $d['discount'] + $d['tax'];
        $old = fn ($k, $default = '') => old($k, $default) ?? '';
        $verifiedEmail = (string) session('guest_verified_email', '');
        $countries = Countries::forCheckout();
        $country = $old('shipping_country', $user->country ?? '') ?: Countries::defaultName();

        return [
            'items' => static::items($d['cart']),
            'totals' => [
                'subtotal' => format_currency($d['subtotal']),
                'discount' => $d['discount'] > 0 ? format_currency($d['discount']) : null,
                'tax' => format_currency($d['tax']),
                'pickupTotal' => format_currency($base), // also the total when no shipping method applies
                'zero' => format_currency(0),
            ],
            'shippingMethods' => static::methods($d['shippingMethods'], $d['subtotal'], $base),
            'pickupLocations' => $d['pickupLocations']->map(fn (PickupLocation $l) => [
                'id' => $l->id,
                'name' => $l->name,
                'address' => collect([$l->address, $l->city, $l->state, $l->country])->filter()->implode(', '),
                'phone' => $l->phone,
                'instructions' => $l->instructions,
            ])->values(),
            'countries' => ['list' => $countries, 'locked' => (bool) setting('shipping.single_country_mode')],
            'defaults' => [
                'shipping_name' => $old('shipping_name', $user->name ?? ''),
                'shipping_email' => $old('shipping_email', $user->email ?? $verifiedEmail),
                'shipping_phone' => $old('shipping_phone', $user->phone ?? ''),
                'shipping_address' => $old('shipping_address', $user->address ?? ''),
                'shipping_city' => $old('shipping_city', $user->city ?? ''),
                'shipping_state' => $old('shipping_state', $user->state ?? ''),
                'shipping_zip' => $old('shipping_zip', $user->zip_code ?? ''),
                'shipping_country' => in_array($country, $countries, true) ? $country : ($countries[0] ?? ''),
                'notes' => $old('notes'),
            ],
            'guest' => [
                'isGuest' => $d['isGuest'],
                'otpRequired' => $d['guestOtpRequired'],
                'verified' => (bool) $d['guestEmailVerified'],
                'email' => $verifiedEmail,
                'otpExpiry' => (int) setting('features.guest_otp_expiry', 10),
            ],
            'payments' => static::payments(),
            'seo' => ['title' => 'Checkout', 'robots' => 'noindex'],
        ];
    }

    /** Shipping choices with the order total each one gives (tax does not depend on shipping). */
    public static function methods(Collection $methods, float $subtotal, float $base): array
    {
        return $methods->map(function ($m) use ($subtotal, $base) {
            $cost = $m->calculateCost($subtotal);

            return [
                'id' => $m->id,
                'name' => $m->name,
                'description' => $m->description,
                'deliveryTime' => $m->delivery_time,
                'cost' => format_currency($cost),
                'total' => format_currency($base + $cost),
            ];
        })->values()->all();
    }

    /** Enabled payment methods with only the fields the page may show (never gateway keys other than Stripe's publishable key). */
    public static function payments(): array
    {
        $out = [];
        foreach (app(PaymentGatewayService::class)->getAvailableMethods() as $key => $m) {
            $details = match ($key) {
                'bank_transfer' => array_filter([
                    'Bank' => $m['bank_name'] ?? null, 'Account Name' => $m['account_name'] ?? null,
                    'Account Number' => $m['account_number'] ?? null, 'Routing/SWIFT' => $m['routing_number'] ?? null,
                ]),
                'check' => array_filter(['Make Payable To' => $m['payable_to'] ?? null, 'Mail To' => $m['mailing_address'] ?? null]),
                default => [],
            };
            $out[] = [
                'key' => $key,
                'name' => $m['name'],
                'description' => $m['description'] ?? '',
                'fee' => !empty($m['fee']) && $m['fee'] > 0 ? format_currency($m['fee']) : null,
                'details' => $details ?: null,
                'instructions' => $key === 'bank_transfer' ? ($m['instructions'] ?? null) : null,
                'redirect' => static::REDIRECT_GATEWAYS[$key] ?? null,
                'stripeKey' => $key === 'stripe' ? ($m['public_key'] ?? null) : null,
            ];
        }

        return $out;
    }

    public static function success(Order $order): array
    {
        return [
            'order' => [
                'number' => $order->order_number,
                'total' => format_currency($order->total),
                'payment' => $order->payment_method === 'cod' ? 'Cash on Delivery' : ucwords(str_replace('_', ' ', (string) $order->payment_method)),
                'destination' => $order->delivery_type === 'pickup'
                    ? 'Store pickup'
                    : collect([$order->shipping_city, $order->shipping_country])->filter()->implode(', '),
                'email' => $order->shipping_email,
                'url' => auth()->check() ? route('account.orders.show', $order) : null,
            ],
            'seo' => ['title' => 'Order Confirmed', 'robots' => 'noindex'],
        ];
    }
}
