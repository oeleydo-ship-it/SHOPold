{{-- Order lines with options; @include('emails.partials.items', ['order' => $order, 'totals' => true]) --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:16px 0;font-size:14px;">
    @foreach($order->items as $item)
        <tr>
            <td style="padding:10px 0;border-bottom:1px solid #e5e7eb;">
                <strong>{{ $item->product_name }}</strong>
                @if($item->variation_options)
                    <br><span style="color:#6b7280;font-size:12px;">
                        @foreach($item->variation_options as $attr => $val){{ $attr }}: {{ $val }}{{ !$loop->last ? ', ' : '' }}@endforeach
                    </span>
                @endif
                <br><span style="color:#6b7280;font-size:13px;">{{ $item->quantity }} × {{ format_currency($item->price) }}</span>
            </td>
            <td align="right" style="padding:10px 0;border-bottom:1px solid #e5e7eb;white-space:nowrap;vertical-align:top;">{{ format_currency($item->total ?: $item->price * $item->quantity) }}</td>
        </tr>
    @endforeach
    @if($totals ?? false)
        <tr><td style="padding:10px 0 2px;color:#6b7280;">Subtotal</td><td align="right" style="padding:10px 0 2px;">{{ format_currency($order->subtotal) }}</td></tr>
        @if($order->discount > 0)
            <tr><td style="padding:2px 0;color:#059669;">Discount</td><td align="right" style="padding:2px 0;color:#059669;">−{{ format_currency($order->discount) }}</td></tr>
        @endif
        @if($order->shipping_cost > 0)
            <tr><td style="padding:2px 0;color:#6b7280;">Shipping</td><td align="right" style="padding:2px 0;">{{ format_currency($order->shipping_cost) }}</td></tr>
        @endif
        @if($order->tax > 0)
            <tr><td style="padding:2px 0;color:#6b7280;">Tax</td><td align="right" style="padding:2px 0;">{{ format_currency($order->tax) }}</td></tr>
        @endif
        <tr><td style="padding:10px 0;border-top:2px solid #e5e7eb;font-weight:bold;font-size:16px;">Total</td><td align="right" style="padding:10px 0;border-top:2px solid #e5e7eb;font-weight:bold;font-size:16px;">{{ format_currency($order->total) }}</td></tr>
    @endif
</table>
