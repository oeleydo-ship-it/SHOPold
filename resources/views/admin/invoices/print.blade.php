<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $order->order_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Arial, sans-serif; font-size: 14px; color: #333; line-height: 1.5; }
        .invoice { max-width: 800px; margin: 0 auto; padding: 40px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 40px; }
        .company h1 { font-size: 24px; color: #1f2937; margin-bottom: 5px; }
        .company p { color: #6b7280; font-size: 13px; }
        .invoice-info { text-align: right; }
        .invoice-info h2 { font-size: 32px; color: #9ca3af; font-weight: 300; }
        .invoice-info .number { font-size: 18px; font-weight: 600; margin-top: 10px; }
        .invoice-info .date { color: #6b7280; font-size: 13px; }
        .addresses { display: flex; gap: 40px; margin-bottom: 40px; }
        .address { flex: 1; }
        .address h3 { font-size: 11px; text-transform: uppercase; color: #9ca3af; margin-bottom: 8px; letter-spacing: 1px; }
        .address p { color: #374151; }
        .address .name { font-weight: 600; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { text-align: left; padding: 12px 0; border-bottom: 2px solid #e5e7eb; color: #6b7280; font-size: 12px; text-transform: uppercase; }
        th:last-child, td:last-child { text-align: right; }
        th:nth-child(2), td:nth-child(2) { text-align: center; }
        th:nth-child(3), td:nth-child(3) { text-align: right; }
        td { padding: 16px 0; border-bottom: 1px solid #f3f4f6; }
        .item-name { font-weight: 500; }
        .item-sku { font-size: 12px; color: #9ca3af; }
        .totals { display: flex; justify-content: flex-end; }
        .totals-table { width: 250px; }
        .totals-table .row { display: flex; justify-content: space-between; padding: 8px 0; }
        .totals-table .row.total { border-top: 2px solid #e5e7eb; margin-top: 8px; padding-top: 16px; font-size: 18px; font-weight: 600; }
        .totals-table .label { color: #6b7280; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #e5e7eb; display: flex; justify-content: space-between; }
        .payment-info h4 { font-size: 13px; font-weight: 600; margin-bottom: 8px; }
        .payment-info p { color: #6b7280; font-size: 13px; }
        .status { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; }
        .status-paid { background: #d1fae5; color: #065f46; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .thank-you { color: #9ca3af; font-size: 13px; }
        @media print {
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            .invoice { padding: 0; }
            @page { margin: 1cm; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="invoice">
        <div class="header">
            <div class="company">
                <h1>{{ setting('store.name', 'Shop') }}</h1>
                <p>{{ setting('store.address', '') }}</p>
                <p>{{ setting('store.email', '') }}</p>
                <p>{{ setting('store.phone', '') }}</p>
            </div>
            <div class="invoice-info">
                <h2>INVOICE</h2>
                <p class="number">#{{ $order->order_number }}</p>
                <p class="date">Date: {{ $order->created_at->format('M d, Y') }}</p>
            </div>
        </div>

        <div class="addresses">
            <div class="address">
                <h3>Bill To</h3>
                <p class="name">{{ $order->user->name }}</p>
                <p>{{ $order->user->email }}</p>
            </div>
            <div class="address">
                <h3>Ship To</h3>
                <p class="name">{{ $order->shipping_name }}</p>
                <p>{{ $order->shipping_address }}</p>
                <p>{{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_zip }}</p>
                <p>{{ $order->shipping_country }}</p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td>
                        <div class="item-name">{{ $item->product_name }}</div>
                        @if($item->variation_options)
                            <div style="color: #2563eb; font-size: 12px;">
                                @foreach($item->variation_options as $attr => $val)
                                    <span>{{ $attr }}: {{ $val }}{{ !$loop->last ? ', ' : '' }}</span>
                                @endforeach
                            </div>
                        @endif
                        @if($item->variation && $item->variation->sku)
                            <div class="item-sku">SKU: {{ $item->variation->sku }}</div>
                        @elseif($item->product)
                            <div class="item-sku">SKU: {{ $item->product->sku }}</div>
                        @endif
                    </td>
                    <td>{{ $item->quantity }}</td>
                    <td>${{ number_format($item->price, 2) }}</td>
                    <td>${{ number_format($item->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div class="totals-table">
                <div class="row">
                    <span class="label">Subtotal</span>
                    <span>${{ number_format($order->subtotal, 2) }}</span>
                </div>
                @if($order->discount > 0)
                <div class="row" style="color: #059669;">
                    <span>Discount</span>
                    <span>-${{ number_format($order->discount, 2) }}</span>
                </div>
                @endif
                <div class="row">
                    <span class="label">Shipping</span>
                    <span>${{ number_format($order->shipping_cost, 2) }}</span>
                </div>
                <div class="row">
                    <span class="label">Tax</span>
                    <span>${{ number_format($order->tax, 2) }}</span>
                </div>
                <div class="row total">
                    <span>Total</span>
                    <span>${{ number_format($order->total, 2) }}</span>
                </div>
            </div>
        </div>

        <div class="footer">
            <div class="payment-info">
                <h4>Payment Information</h4>
                <p>Method: {{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}</p>
                <p>Status: <span class="status status-{{ $order->payment_status }}">{{ ucfirst($order->payment_status) }}</span></p>
            </div>
            <div class="thank-you">
                Thank you for your business!
            </div>
        </div>
    </div>
</body>
</html>
