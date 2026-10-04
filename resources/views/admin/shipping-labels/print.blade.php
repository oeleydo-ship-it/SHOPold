<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Shipping Label - {{ $order->order_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { font-family: Arial, sans-serif; color: #111827; }
        body { margin: 0; }
        .label { width: 100%; padding: 12px; }
        .row { display: flex; gap: 12px; }
        .col { flex: 1; }
        .box { border: 2px solid #111827; padding: 10px; border-radius: 10px; }
        .muted { color: #4b5563; font-size: 11px; }
        .title { font-weight: 700; font-size: 16px; letter-spacing: 0.5px; }
        .value { font-weight: 700; font-size: 13px; }
        .small { font-size: 12px; }
        .spacer { height: 10px; }
        .divider { height: 2px; background: #111827; margin: 10px 0; }
        .barcode-wrap { display: flex; justify-content: space-between; align-items: center; gap: 12px; }
        .barcode { width: 100%; max-width: 260px; height: 58px; }
        .order-number { font-size: 18px; font-weight: 800; }
        .pill { border: 2px solid #111827; border-radius: 9999px; padding: 6px 10px; font-weight: 800; }
        .items { margin-top: 8px; border: 1px solid #e5e7eb; border-radius: 10px; padding: 8px; }
        .items .head { display:flex; justify-content:space-between; align-items:center; margin-bottom:6px; }
        .items ul { list-style: none; }
        .items li { display:flex; justify-content:space-between; gap: 10px; padding: 3px 0; border-bottom: 1px dashed #e5e7eb; }
        .items li:last-child { border-bottom: 0; }
        .items .name { font-size: 11px; color: #111827; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .items .qty { font-size: 11px; font-weight: 800; }
        @page { size: 4in 6in; margin: 0; }
        @media print {
            html, body { width: 4in; height: 6in; }
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            .label { width: 4in; height: 6in; padding: 0.12in; overflow: hidden; }
            .label, .box { break-inside: avoid; page-break-inside: avoid; }
        }
    </style>
</head>
<body onload="window.print()">
    @php
        $isCod = ($order->payment_method ?? '') === 'cod';
        $amountLabel = $isCod ? 'COD AMOUNT' : 'PAID AMOUNT';
        $amountValue = $order->total ?? 0;
        $barcodeText = $order->order_number;
    @endphp
    <div class="label">
        <div class="box">
            <div class="barcode-wrap">
                <div>
                    <div class="title">{{ setting('store.name', 'Shop') }}</div>
                    <div class="muted">{{ setting('store.address', '') }}</div>
                    <div class="muted">{{ setting('store.phone', '') }}</div>
                </div>
                <div>
                    <svg id="barcode" class="barcode"></svg>
                </div>
            </div>

            <div class="divider"></div>

            <div class="row">
                <div class="col">
                    <div class="muted">ORDER</div>
                    <div class="order-number">{{ $order->order_number }}</div>
                </div>
                <div class="col" style="text-align:right;">
                    <div class="muted">DATE</div>
                    <div class="value">{{ $order->created_at->format('M d, Y') }}</div>
                </div>
            </div>

            <div class="spacer"></div>

            <div class="row">
                <div class="col">
                    <div class="muted">PAYMENT</div>
                    <div class="value">{{ strtoupper(str_replace('_', ' ', $order->payment_method ?? '')) }}</div>
                </div>
                <div class="col" style="text-align:right;">
                    <div class="muted">{{ $amountLabel }}</div>
                    <div class="pill">{{ format_currency($amountValue) }}</div>
                </div>
            </div>

            <div class="spacer"></div>

            <div class="box" style="border-width: 1px; border-color: #374151;">
                <div class="muted">SHIP TO</div>
                <div class="value">{{ $order->shipping_name }}</div>
                @if($order->shipping_phone)
                    <div class="small">{{ $order->shipping_phone }}</div>
                @endif
                @if($order->shipping_email)
                    <div class="small">{{ $order->shipping_email }}</div>
                @endif
                <div class="small">{{ $order->shipping_address }}</div>
                <div class="small">{{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_zip }}</div>
                <div class="small">{{ $order->shipping_country }}</div>
            </div>

            <div class="items">
                <div class="head">
                    <div class="muted" style="font-weight:700; letter-spacing:0.5px;">ITEMS</div>
                    <div class="muted">{{ $order->items->count() }} item(s)</div>
                </div>
                <ul>
                    @foreach($order->items as $item)
                        <li>
                            <span class="name">{{ $item->product_name }}</span>
                            <span class="qty">x{{ $item->quantity }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="spacer"></div>

            <div class="row">
                <div class="col">
                    <div class="muted">DELIVERY TYPE</div>
                    <div class="value">{{ strtoupper($order->delivery_type ?? 'delivery') }}</div>
                </div>
                <div class="col" style="text-align:right;">
                    <div class="muted">TRACKING</div>
                    <div class="value">{{ $order->tracking_number ?: '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <script>
        (function () {
            try {
                JsBarcode('#barcode', "{{ $barcodeText }}", {
                    format: 'CODE128',
                    displayValue: true,
                    fontSize: 12,
                    height: 44,
                    margin: 0
                });
            } catch (e) {}
        })();
    </script>
</body>
</html>
