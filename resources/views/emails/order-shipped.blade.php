<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Order Has Been Shipped</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; }
        .header { background: #059669; color: #fff; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .header .icon { font-size: 48px; margin-bottom: 10px; }
        .content { padding: 30px; }
        .tracking-box { background: #ecfdf5; border: 2px solid #059669; border-radius: 8px; padding: 20px; margin: 20px 0; text-align: center; }
        .tracking-number { font-size: 24px; font-weight: bold; color: #059669; letter-spacing: 2px; }
        .order-items { margin: 20px 0; }
        .item { display: flex; align-items: center; padding: 15px 0; border-bottom: 1px solid #e5e7eb; }
        .item:last-child { border-bottom: none; }
        .item-image { width: 60px; height: 60px; background: #f3f4f6; border-radius: 8px; margin-right: 15px; }
        .item-details { flex: 1; }
        .item-name { font-weight: bold; }
        .item-qty { color: #6b7280; font-size: 14px; }
        .shipping-address { background: #f9fafb; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .btn { display: inline-block; padding: 14px 28px; background: #059669; color: #fff; text-decoration: none; border-radius: 6px; font-weight: bold; }
        .btn-outline { background: transparent; border: 2px solid #059669; color: #059669; }
        .footer { background: #f9fafb; padding: 20px; text-align: center; color: #6b7280; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="icon">📦</div>
            <h1>Your Order Is On Its Way!</h1>
        </div>
        
        <div class="content">
            <p>Hello {{ $order->user->name }},</p>
            
            <p>Great news! Your order <strong>{{ $order->order_number }}</strong> has been shipped and is on its way to you.</p>
            
            @if($order->tracking_number)
            <div class="tracking-box">
                <p style="margin: 0 0 10px 0; color: #6b7280;">Tracking Number</p>
                <div class="tracking-number">{{ $order->tracking_number }}</div>
                @if($order->tracking_url)
                <p style="margin: 15px 0 0 0;">
                    <a href="{{ $order->tracking_url }}" class="btn">Track Your Package</a>
                </p>
                @endif
            </div>
            @endif
            
            <h3>Items in Your Order</h3>
            <div class="order-items">
                @foreach($order->items as $item)
                <div class="item">
                    <div class="item-image"></div>
                    <div class="item-details">
                        <div class="item-name">{{ $item->product_name }}</div>
                        @if($item->variation_options)
                            <div style="color: #2563eb; font-size: 12px; margin: 4px 0;">
                                @foreach($item->variation_options as $attr => $val)
                                    <span>{{ $attr }}: {{ $val }}{{ !$loop->last ? ', ' : '' }}</span>
                                @endforeach
                            </div>
                        @endif
                        <div class="item-qty">Qty: {{ $item->quantity }} × ${{ number_format($item->price, 2) }}</div>
                    </div>
                    <div class="item-price">${{ number_format($item->total, 2) }}</div>
                </div>
                @endforeach
            </div>
            
            <div class="shipping-address">
                <h4 style="margin-top: 0;">Shipping To</h4>
                <p style="margin: 0;">
                    {{ $order->shipping_name }}<br>
                    {{ $order->shipping_address }}<br>
                    {{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_zip }}<br>
                    {{ $order->shipping_country }}
                </p>
            </div>
            
            <p style="text-align: center; margin-top: 30px;">
                <a href="{{ route('account.orders.show', $order) }}" class="btn btn-outline">View Order Details</a>
            </p>
            
            <p>If you have any questions about your delivery, please contact us.</p>
            
            <p>Thank you for shopping with us!</p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ setting('store.name', 'Our Store') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
