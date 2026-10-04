<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class ShippingLabelController extends Controller
{
    public function print(Order $order)
    {
        $order->load(['user', 'items']);

        return view('admin.shipping-labels.print', compact('order'));
    }

    public function resolve(Request $request)
    {
        $code = trim((string) $request->query('code', ''));

        if ($code === '') {
            return redirect()->back()->with('error', 'Invalid QR code.');
        }

        $orderId = null;

        if (preg_match('/^https?:\/\//i', $code)) {
            if (preg_match('~\/shipping-labels\/(\d+)\/print~', $code, $m)) {
                $orderId = (int) $m[1];
            }
        } elseif (ctype_digit($code)) {
            $orderId = (int) $code;
        }

        if ($orderId) {
            $order = Order::find($orderId);
            if ($order) {
                return redirect()->route('admin.shipping-labels.print', $order);
            }
        }

        $order = Order::where('order_number', $code)->first();
        if ($order) {
            return redirect()->route('admin.shipping-labels.print', $order);
        }

        return redirect()->back()->with('error', 'Order not found for this QR code.');
    }
}
