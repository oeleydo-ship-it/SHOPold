<!DOCTYPE html>
{{-- Invoice as a PDF (dompdf). Same content as print.blade.php, laid out with tables because dompdf has no flexbox. --}}
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $order->order_number }}</title>
    <style>
        @page { margin: 36px 40px; }
        * { box-sizing: border-box; }
        /* DejaVu Sans ships with dompdf and covers currency symbols beyond Latin-1 */
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #333; line-height: 1.45; margin: 0; }
        .muted { color: #6b7280; }
        .label { color: #9ca3af; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }
        table.layout { width: 100%; border-collapse: collapse; }
        table.layout td { vertical-align: top; padding: 0; }
        h1 { font-size: 20px; color: #1f2937; margin: 0 0 4px; }
        .title { font-size: 26px; color: #9ca3af; font-weight: normal; margin: 0; text-align: right; }
        .number { font-size: 15px; font-weight: bold; text-align: right; margin-top: 6px; }
        table.items { width: 100%; border-collapse: collapse; margin: 28px 0 20px; }
        table.items th { text-align: left; padding: 9px 0; border-bottom: 2px solid #e5e7eb; color: #6b7280; font-size: 10px; text-transform: uppercase; }
        table.items td { padding: 11px 0; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
        table.items .c { text-align: center; } table.items .r { text-align: right; }
        .sku { font-size: 10px; color: #9ca3af; }
        .opts { font-size: 10px; color: #2563eb; }
        table.totals { width: 260px; margin-left: auto; border-collapse: collapse; }
        table.totals td { padding: 5px 0; }
        table.totals td.r { text-align: right; }
        table.totals tr.total td { border-top: 2px solid #e5e7eb; padding-top: 10px; font-size: 15px; font-weight: bold; }
        .footer { margin-top: 32px; padding-top: 14px; border-top: 1px solid #e5e7eb; }
        .status { padding: 2px 9px; border-radius: 10px; font-size: 10px; }
        .status-paid { background: #d1fae5; color: #065f46; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-failed, .status-refunded { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <table class="layout">
        <tr>
            <td>
                <h1>{{ setting('store.name', 'Shop') }}</h1>
                @foreach (['store.address', 'store.email', 'store.phone'] as $key)
                    @if(setting($key))<div class="muted">{{ setting($key) }}</div>@endif
                @endforeach
            </td>
            <td style="width: 45%;">
                <p class="title">INVOICE</p>
                <div class="number">#{{ $order->order_number }}</div>
                <div class="muted" style="text-align: right;">Date: {{ $order->created_at->format('M d, Y') }}</div>
            </td>
        </tr>
    </table>

    <table class="layout" style="margin-top: 28px;">
        <tr>
            <td style="width: 50%; padding-right: 20px;">
                <div class="label">Bill To</div>
                <div><strong>{{ $order->user->name ?? $order->shipping_name }}</strong></div>
                <div>{{ $order->user->email ?? $order->shipping_email }}</div>
            </td>
            <td>
                <div class="label">Ship To</div>
                <div><strong>{{ $order->shipping_name }}</strong></div>
                <div>{{ $order->shipping_address }}</div>
                <div>{{ trim($order->shipping_city . ', ' . $order->shipping_state . ' ' . $order->shipping_zip, ', ') }}</div>
                <div>{{ $order->shipping_country }}</div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr><th>Item</th><th class="c">Qty</th><th class="r">Price</th><th class="r">Total</th></tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td>
                    <div><strong>{{ $item->product_name }}</strong></div>
                    @if($item->variation_options)
                        <div class="opts">
                            @foreach($item->variation_options as $attr => $val){{ $attr }}: {{ $val }}{{ !$loop->last ? ', ' : '' }}@endforeach
                        </div>
                    @endif
                    @if($item->variation && $item->variation->sku)
                        <div class="sku">SKU: {{ $item->variation->sku }}</div>
                    @elseif($item->product)
                        <div class="sku">SKU: {{ $item->product->sku }}</div>
                    @endif
                </td>
                <td class="c">{{ $item->quantity }}</td>
                <td class="r">{{ format_currency($item->price) }}</td>
                <td class="r">{{ format_currency($item->total) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td class="muted">Subtotal</td><td class="r">{{ format_currency($order->subtotal) }}</td></tr>
        @if($order->discount > 0)
        <tr style="color: #059669;"><td>Discount</td><td class="r">-{{ format_currency($order->discount) }}</td></tr>
        @endif
        <tr><td class="muted">Shipping</td><td class="r">{{ format_currency($order->shipping_cost) }}</td></tr>
        <tr><td class="muted">Tax</td><td class="r">{{ format_currency($order->tax) }}</td></tr>
        <tr class="total"><td>Total</td><td class="r">{{ format_currency($order->total) }}</td></tr>
    </table>

    <table class="layout footer">
        <tr>
            <td>
                <div style="font-weight: bold; margin-bottom: 4px;">Payment Information</div>
                <div class="muted">Method: {{ $order->payment_method === 'cod' ? 'Cash on Delivery' : ucwords(str_replace('_', ' ', (string) $order->payment_method)) }}</div>
                <div class="muted">Status: <span class="status status-{{ $order->payment_status }}">{{ ucfirst((string) $order->payment_status) }}</span></div>
            </td>
            <td style="text-align: right; vertical-align: bottom;">Thank you for your business!</td>
        </tr>
    </table>
</body>
</html>
