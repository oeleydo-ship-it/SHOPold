<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();
        $recentOrders = $user->orders()->latest()->take(5)->get();
        $orderCount = $user->orders()->count();
        $wishlistCount = $user->wishlist()->count();

        return \Inertia\Inertia::render('Storefront/Account/Dashboard', \App\Support\AccountPage::dashboard($user, $recentOrders, $orderCount, $wishlistCount));
    }

    public function orders()
    {
        $orders = auth()->user()->orders()->with('items.product')->latest()->paginate(10);
        return \Inertia\Inertia::render('Storefront/Account/Orders', \App\Support\AccountPage::orders($orders));
    }

    public function orderDetail(Order $order)
    {
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }
        
        $order->load(['items.product', 'items.variation', 'driver', 'pickupLocation']);
        return \Inertia\Inertia::render('Storefront/Account/OrderDetail', \App\Support\AccountPage::orderDetail($order));
    }

    public function profile()
    {
        $user = auth()->user();
        return \Inertia\Inertia::render('Storefront/Account/Profile', \App\Support\AccountPage::profile($user));
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'company' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:255',
        ]);

        $user->update($request->only([
            'name', 'email', 'phone', 'date_of_birth', 'gender', 'company',
            'address', 'city', 'state', 'zip_code', 'country'
        ]));

        return back()->with('success', 'Profile updated successfully!');
    }

    public function changePassword()
    {
        return \Inertia\Inertia::render('Storefront/Account/Password', ['seo' => \App\Support\AccountPage::seo('Change Password')]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return back()->with('success', 'Password changed successfully!');
    }

    public function wishlist()
    {
        $wishlist = auth()->user()->wishlist()->with('product.translations')->paginate(12);
        return \Inertia\Inertia::render('Storefront/Account/Wishlist', \App\Support\AccountPage::wishlist($wishlist));
    }

    public function addToWishlist(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $exists = Wishlist::where('user_id', auth()->id())
            ->where('product_id', $request->product_id)
            ->exists();

        if ($exists) {
            return $request->wantsJson()
                ? response()->json(['success' => true, 'message' => 'Product is already in your wishlist.'])
                : back()->with('info', 'Product is already in your wishlist.');
        }

        Wishlist::create([
            'user_id' => auth()->id(),
            'product_id' => $request->product_id,
        ]);

        return $request->wantsJson()
            ? response()->json(['success' => true, 'message' => 'Product added to wishlist!'])
            : back()->with('success', 'Product added to wishlist!');
    }

    public function removeFromWishlist(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        Wishlist::where('user_id', auth()->id())
            ->where('product_id', $request->product_id)
            ->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Product removed from wishlist!']);
        }

        return back()->with('success', 'Product removed from wishlist!');
    }

    /**
     * Show invoice for a paid order
     */
    public function invoice(Order $order)
    {
        // Ensure user owns this order
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        // Only allow invoice for paid orders
        if ($order->payment_status !== 'paid') {
            return back()->with('error', 'Invoice is only available for paid orders.');
        }

        $order->load(['user', 'items.product', 'items.variation']);

        return \Inertia\Inertia::render('Storefront/Account/Invoice', [
            'store' => array_filter([
                'name' => setting('store.name', 'Shop'),
                'address' => setting('store.address'),
                'email' => setting('store.email'),
                'phone' => setting('store.phone'),
            ]),
            'invoice' => [
                'number' => $order->order_number,
                'date' => $order->created_at?->format('M d, Y'),
                'billTo' => ['name' => $order->user?->name, 'email' => $order->user?->email],
                'shipTo' => [
                    'name' => $order->shipping_name,
                    'lines' => array_values(array_filter([$order->shipping_address, trim("{$order->shipping_city}, {$order->shipping_state} {$order->shipping_zip}", ' ,'), $order->shipping_country])),
                ],
                'items' => $order->items->map(fn ($i) => [
                    'id' => $i->id,
                    'name' => $i->product_name,
                    'options' => collect(is_array($i->variation_options) ? $i->variation_options : [])->map(fn ($v, $k) => "{$k}: {$v}")->values(),
                    'sku' => $i->variation?->sku ?: $i->product?->sku,
                    'quantity' => (int) $i->quantity,
                    'price' => format_currency($i->price),
                    'total' => format_currency($i->total),
                ])->values(),
                'totals' => [
                    'subtotal' => format_currency($order->subtotal),
                    'discount' => $order->discount > 0 ? format_currency($order->discount) : null,
                    'shipping' => format_currency($order->shipping_cost),
                    'tax' => format_currency($order->tax),
                    'total' => format_currency($order->total),
                ],
                'paymentMethod' => ucfirst(str_replace('_', ' ', (string) $order->payment_method)),
                'transactionId' => $order->transaction_id,
                'printUrl' => route('account.orders.invoice.print', $order),
            ],
            'seo' => \App\Support\AccountPage::seo('Invoice ' . $order->order_number),
        ]);
    }

    /**
     * Print invoice for a paid order
     */
    public function invoicePrint(Order $order)
    {
        // Ensure user owns this order
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        // Only allow invoice for paid orders
        if ($order->payment_status !== 'paid') {
            return back()->with('error', 'Invoice is only available for paid orders.');
        }

        $order->load(['user', 'items.product', 'items.variation']);
        return view('account.invoice-print', compact('order'));
    }

    /**
     * Rate the driver for a delivered order
     */
    public function rateDriver(Request $request, Order $order)
    {
        // Ensure user owns this order
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        // Ensure order is delivered and has a driver
        if ($order->status !== 'delivered' || !$order->driver_id) {
            return back()->with('error', 'Cannot rate driver for this order.');
        }

        // Ensure not already rated
        if ($order->driver_rating) {
            return back()->with('error', 'You have already rated this delivery.');
        }

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        $driverService = new \App\Services\DriverService();
        $driverService->rateDriver($order, $request->rating, $request->comment);

        return back()->with('success', 'Thank you for your feedback!');
    }

    /**
     * Tip the driver for a delivered order
     */
    public function tipDriver(Request $request, Order $order)
    {
        // Ensure user owns this order
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        // Ensure order is delivered and has a driver
        if ($order->status !== 'delivered' || !$order->driver_id) {
            return back()->with('error', 'Cannot tip driver for this order.');
        }

        $request->validate([
            'amount' => 'required|numeric|min:1|max:1000',
        ]);

        $driverService = new \App\Services\DriverService();
        $driverService->addTip($order, $request->amount);

        return back()->with('success', 'Thank you for your generosity! The driver has been notified.');
    }
}
