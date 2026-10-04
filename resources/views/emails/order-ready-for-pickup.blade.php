<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Order Is Ready for Pickup</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; }
        .header { background: #7c3aed; color: #fff; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .header .icon { font-size: 48px; margin-bottom: 10px; }
        .content { padding: 30px; }
        .pickup-box { background: #f3e8ff; border: 2px solid #7c3aed; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .pickup-title { font-size: 18px; font-weight: bold; color: #7c3aed; margin-bottom: 15px; }
        .pickup-location { font-size: 16px; }
        .pickup-address { color: #6b7280; margin-top: 5px; }
        .pickup-phone { color: #7c3aed; margin-top: 10px; }
        .pickup-instructions { background: #fef3c7; border-radius: 6px; padding: 12px; margin-top: 15px; font-size: 14px; color: #92400e; }
        .order-items { margin: 20px 0; }
        .item { display: flex; align-items: center; padding: 15px 0; border-bottom: 1px solid #e5e7eb; }
        .item:last-child { border-bottom: none; }
        .item-image { width: 60px; height: 60px; background: #f3f4f6; border-radius: 8px; margin-right: 15px; }
        .item-details { flex: 1; }
        .item-name { font-weight: bold; }
        .item-qty { color: #6b7280; font-size: 14px; }
        .btn { display: inline-block; padding: 14px 28px; background: #7c3aed; color: #fff; text-decoration: none; border-radius: 6px; font-weight: bold; }
        .btn-outline { background: transparent; border: 2px solid #7c3aed; color: #7c3aed; }
        .footer { background: #f9fafb; padding: 20px; text-align: center; color: #6b7280; font-size: 14px; }
        .highlight { background: #7c3aed; color: #fff; padding: 3px 8px; border-radius: 4px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="icon">🏪</div>
            <h1>Your Order Is Ready for Pickup!</h1>
        </div>
        
        <div class="content">
            <p>Hello {{ $order->shipping_name }},</p>
            
            <p>Great news! Your order <strong>{{ $order->order_number }}</strong> is now <span class="highlight">ready for pickup</span> at our store.</p>
            
            <div class="pickup-box">
                <div class="pickup-title">📍 Pickup Location</div>
                @if($order->pickupLocation)
                    <div class="pickup-location">
                        <strong>{{ $order->pickupLocation->name }}</strong>
                    </div>
                    <div class="pickup-address">
                        {{ $order->pickupLocation->address }}<br>
                        {{ $order->pickupLocation->city }}, {{ $order->pickupLocation->state }} {{ $order->pickupLocation->zip }}<br>
                        {{ $order->pickupLocation->country }}
                    </div>
                    @if($order->pickupLocation->phone)
                        <div class="pickup-phone">
                            📞 {{ $order->pickupLocation->phone }}
                        </div>
                    @endif
                    @if($order->pickupLocation->instructions)
                        <div class="pickup-instructions">
                            <strong>ℹ️ Pickup Instructions:</strong><br>
                            {{ $order->pickupLocation->instructions }}
                        </div>
                    @endif
                @else
                    <div class="pickup-address">{{ $order->shipping_address }}</div>
                @endif
            </div>
            
            <p><strong>Please bring:</strong></p>
            <ul>
                <li>A valid photo ID</li>
                <li>Your order number: <strong>{{ $order->order_number }}</strong></li>
            </ul>
            
            <h3>Items Ready for Pickup</h3>
            <div class="order-items">
                @foreach($order->items as $item)
                <div class="item">
                    <div class="item-image"></div>
                    <div class="item-details">
                        <div class="item-name">{{ $item->product_name }}</div>
                        @if($item->variation_options)
                            <div style="color: #7c3aed; font-size: 12px; margin: 4px 0;">
                                @foreach($item->variation_options as $attr => $val)
                                    <span>{{ $attr }}: {{ $val }}{{ !$loop->last ? ', ' : '' }}</span>
                                @endforeach
                            </div>
                        @endif
                        <div class="item-qty">Qty: {{ $item->quantity }} × {{ format_currency($item->price) }}</div>
                    </div>
                    <div class="item-price">{{ format_currency($item->total) }}</div>
                </div>
                @endforeach
            </div>
            
            <p style="text-align: center; margin-top: 30px;">
                <a href="{{ route('account.orders.show', $order) }}" class="btn">View Order Details</a>
            </p>
            
            <p>If you have any questions about your pickup, please contact us.</p>
            
            <p>Thank you for shopping with us!</p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ setting('store.name', 'Our Store') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
