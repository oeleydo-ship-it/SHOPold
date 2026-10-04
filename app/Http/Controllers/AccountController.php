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

        return view('account.dashboard', compact('user', 'recentOrders', 'orderCount', 'wishlistCount'));
    }

    public function orders()
    {
        $orders = auth()->user()->orders()->with('items')->latest()->paginate(10);
        return view('account.orders', compact('orders'));
    }

    public function orderDetail(Order $order)
    {
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }
        
        $order->load(['items.product', 'items.variation']);
        return view('account.order-detail', compact('order'));
    }

    public function profile()
    {
        $user = auth()->user();
        return view('account.profile', compact('user'));
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
        return view('account.change-password');
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
        return view('account.wishlist', compact('wishlist'));
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
            return back()->with('info', 'Product is already in your wishlist.');
        }

        Wishlist::create([
            'user_id' => auth()->id(),
            'product_id' => $request->product_id,
        ]);

        return back()->with('success', 'Product added to wishlist!');
    }

    public function removeFromWishlist(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        Wishlist::where('user_id', auth()->id())
            ->where('product_id', $request->product_id)
            ->delete();

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
        return view('account.invoice', compact('order'));
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
