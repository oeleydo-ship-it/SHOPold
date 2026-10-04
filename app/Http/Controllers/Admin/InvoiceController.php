<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    /**
     * Generate and display invoice for an order
     */
    public function show(Order $order)
    {
        $order->load(['user', 'items.product', 'items.variation']);
        $money = fn ($v) => format_currency((float) $v);

        return Inertia::render('Admin/Invoices/Show', [
            'store' => [
                'name' => setting('store.name', config('app.name')),
                'address' => setting('store.address', ''),
                'email' => setting('store.email', ''),
                'phone' => setting('store.phone', ''),
            ],
            'invoice' => [
                'id' => $order->id,
                'number' => $order->order_number,
                'date' => $order->created_at->format('M d, Y'),
                'due' => $order->created_at->copy()->addDays(30)->format('M d, Y'),
                'bill_to' => ['name' => $order->user->name ?? $order->shipping_name, 'email' => $order->user->email ?? $order->shipping_email],
                'ship_to' => [
                    'name' => $order->shipping_name,
                    'lines' => array_values(array_filter([
                        $order->shipping_address,
                        trim("{$order->shipping_city}, {$order->shipping_state} {$order->shipping_zip}", ', '),
                        $order->shipping_country,
                    ])),
                ],
                'items' => $order->items->map(fn ($i) => [
                    'id' => $i->id,
                    'name' => $i->product_name,
                    'options' => (object) ($i->variation_options ?? []),
                    'sku' => $i->variation?->sku ?: $i->product?->sku,
                    'quantity' => (int) $i->quantity,
                    'price' => $money($i->price),
                    'total' => $money($i->total),
                ])->values(),
                'subtotal' => $money($order->subtotal),
                'discount' => $order->discount > 0 ? $money($order->discount) : null,
                'shipping' => $money($order->shipping_cost),
                'tax' => $money($order->tax),
                'total' => $money($order->total),
                'payment_method' => $order->payment_method,
                'payment_status' => $order->payment_status,
            ],
        ]);
    }

    /**
     * Download the invoice as a PDF file (invoice-<order number>.pdf).
     */
    public function download(Order $order)
    {
        $order->load(['user', 'items.product', 'items.variation']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.invoices.pdf', compact('order'))
            ->setPaper('a4')
            // never fetch remote resources while rendering (the template has none; this keeps it that way)
            ->setOption('isRemoteEnabled', false)
            ->setOption('isFontSubsettingEnabled', true); // embed only the glyphs used: ~880 KB -> a few dozen KB

        return $pdf->download('invoice-' . preg_replace('/[^A-Za-z0-9_-]/', '', (string) $order->order_number) . '.pdf');
    }

    /**
     * Print invoice
     */
    public function print(Order $order)
    {
        $order->load(['user', 'items.product', 'items.variation']);
        return view('admin.invoices.print', compact('order'));
    }
}
