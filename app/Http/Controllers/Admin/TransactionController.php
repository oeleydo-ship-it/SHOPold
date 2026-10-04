<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TransactionController extends Controller
{
    protected PaymentGatewayService $paymentService;

    public function __construct(PaymentGatewayService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function index(Request $request)
    {
        $query = Order::with(['user'])
            ->whereNotNull('payment_method')
            ->where('payment_method', '!=', '');

        // Filter by payment type (card/cash)
        $type = $request->get('type', 'all');
        
        $cardGateways = ['stripe', 'paypal', 'razorpay', 'paystack', 'flutterwave', 'sslcommerz', 'paytm', 'instamojo', 'payfast', 'mercadopago', 'authorizenet', 'iyzico'];
        $cashGateways = ['cod', 'check', 'bank_transfer'];

        if ($type === 'card') {
            $query->whereIn('payment_method', $cardGateways);
        } elseif ($type === 'cash') {
            $query->whereIn('payment_method', $cashGateways);
        }

        // Filter by payment status
        if ($request->filled('status')) {
            $query->where('payment_status', $request->status);
        }

        // Filter by payment method
        if ($request->filled('method')) {
            $query->where('payment_method', $request->method);
        }

        // Search by order number or transaction ID
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('transaction_id', 'like', "%{$search}%");
            });
        }

        // Date range filter
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        // Get statistics
        $stats = [
            'total' => Order::whereNotNull('payment_method')->count(),
            'card' => Order::whereIn('payment_method', $cardGateways)->count(),
            'cash' => Order::whereIn('payment_method', $cashGateways)->count(),
            'paid' => Order::where('payment_status', 'paid')->count(),
            'pending' => Order::where('payment_status', 'pending')->count(),
            'refunded' => Order::where('payment_status', 'refunded')->count(),
            'total_revenue' => Order::where('payment_status', 'paid')->sum('total'),
            'total_refunded' => Order::where('payment_status', 'refunded')->sum('refund_amount'),
        ];

        // Get unique payment methods for filter
        $paymentMethods = Order::whereNotNull('payment_method')
            ->distinct()
            ->pluck('payment_method')
            ->filter()
            ->values();

        $transactions = $transactions->through(function ($t) {
            $method = $t->payment_method ?? 'cod';

            return [
                'id' => $t->id,
                'number' => $t->order_number,
                'customer' => $t->user?->name ?? $t->shipping_name ?? 'Guest',
                'email' => $t->user?->email,
                'is_card' => $this->paymentService->isCardPayment($method),
                'method' => $this->paymentService->getPaymentMethodName($method),
                'transaction_id' => $t->transaction_id ? \Illuminate\Support\Str::limit($t->transaction_id, 16) : null,
                'total' => format_currency($t->total),
                'refund_amount' => $t->payment_status === 'refunded' && $t->refund_amount ? format_currency($t->refund_amount) : null,
                'payment_status' => $t->payment_status,
                'date' => $t->created_at->format('M d, Y'),
                'time' => $t->created_at->format('h:i A'),
                'can_refund' => $t->payment_status === 'paid' && $this->paymentService->supportsRefund($t->payment_method ?? ''),
            ];
        });

        return Inertia::render('Admin/Transactions/Index', [
            'transactions' => $transactions,
            'stats' => collect($stats)->map(fn ($v, $k) => str_starts_with($k, 'total_') ? format_currency($v ?? 0) : $v),
            'type' => $type,
            'paymentMethods' => $paymentMethods->map(fn ($m) => ['value' => $m, 'label' => ucfirst($m)])->values(),
            'filters' => $request->only('search', 'status', 'method', 'from', 'to'),
        ]);
    }

    public function show(Order $order)
    {
        $order->load(['user', 'items.product']);
        
        $svc = $this->paymentService;
        $method = $order->payment_method ?? 'cod';
        $fmt = fn ($d, $f = 'M d, Y h:i A') => $d?->format($f);

        return Inertia::render('Admin/Transactions/Show', [
            'order' => [
                'id' => $order->id,
                'number' => $order->order_number,
                'created_at' => $fmt($order->created_at, 'F d, Y'),
                'created_time' => $fmt($order->created_at, 'h:i:s A'),
                'created_full' => $fmt($order->created_at),
                'customer' => $order->user?->name ?? $order->shipping_name ?? 'Guest',
                'email' => $order->user?->email ?? $order->shipping_email ?? 'N/A',
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'total' => format_currency($order->total),
                'subtotal' => format_currency($order->subtotal),
                'discount' => $order->discount > 0 ? format_currency($order->discount) : null,
                'shipping_cost' => format_currency($order->shipping_cost),
                'tax' => format_currency($order->tax),
                'transaction_id' => $order->transaction_id,
                'payment' => [
                    'is_card' => $svc->isCardPayment($method),
                    'gateway' => $svc->getPaymentMethodName($method),
                    'supports_refund' => $svc->supportsRefund($order->payment_method ?? ''),
                ],
                'refund' => $order->payment_status === 'refunded' ? [
                    'id' => $order->refund_id,
                    'amount' => format_currency($order->refund_amount ?? $order->total),
                    'at' => $fmt($order->refunded_at, 'F d, Y h:i A'),
                    'at_short' => $fmt($order->refunded_at),
                    'reason' => $order->refund_reason,
                ] : null,
                'items' => $order->items->map(fn ($i) => [
                    'id' => $i->id,
                    'name' => $i->product_name,
                    'variant' => $i->variant_name ?? null,
                    'image' => $i->product && $i->product->image ? asset('storage/' . $i->product->image) : null,
                    'price' => format_currency($i->price),
                    'quantity' => $i->quantity,
                    'subtotal' => format_currency($i->price * $i->quantity),
                ])->values(),
            ],
        ]);
    }

    public function refund(Request $request, Order $order)
    {
        $request->validate([
            'refund_reason' => 'nullable|string|max:500',
        ]);

        $result = $this->paymentService->processRefund($order, $request->refund_reason);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }
}
