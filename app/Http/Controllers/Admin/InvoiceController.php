<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    /**
     * Generate and display invoice for an order
     */
    public function show(Order $order)
    {
        $order->load(['user', 'items.product', 'items.variation']);
        return view('admin.invoices.show', compact('order'));
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
