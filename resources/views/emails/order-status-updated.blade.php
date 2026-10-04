<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Status Updated</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; }
        .header { background: #1f2937; color: #fff; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { padding: 30px; }
        .status-badge { display: inline-block; padding: 8px 16px; border-radius: 20px; font-weight: bold; text-transform: uppercase; font-size: 14px; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-processing { background: #dbeafe; color: #1e40af; }
        .status-shipped { background: #d1fae5; color: #065f46; }
        .status-delivered { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        .order-details { background: #f9fafb; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .order-details h3 { margin-top: 0; color: #374151; }
        .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e5e7eb; }
        .detail-row:last-child { border-bottom: none; }
        .btn { display: inline-block; padding: 12px 24px; background: #3b82f6; color: #fff; text-decoration: none; border-radius: 6px; font-weight: bold; }
        .footer { background: #f9fafb; padding: 20px; text-align: center; color: #6b7280; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ setting('store.name', 'Our Store') }}</h1>
        </div>
        
        <div class="content">
            <h2>Order Status Updated</h2>
            
            <p>Hello {{ $order->user->name }},</p>
            
            <p>Your order <strong>{{ $order->order_number }}</strong> status has been updated:</p>
            
            <p style="text-align: center; margin: 30px 0;">
                <span class="status-badge status-{{ $order->status }}">
                    {{ ucfirst($order->status) }}
                </span>
            </p>
            
            @if($order->status === 'shipped' && $order->tracking_number)
            <div class="order-details">
                <h3>Tracking Information</h3>
                <p><strong>Tracking Number:</strong> {{ $order->tracking_number }}</p>
                @if($order->tracking_url)
                <p><a href="{{ $order->tracking_url }}" class="btn">Track Your Package</a></p>
                @endif
            </div>
            @endif
            
            <div class="order-details">
                <h3>Order Summary</h3>
                <div class="detail-row">
                    <span>Order Number</span>
                    <strong>{{ $order->order_number }}</strong>
                </div>
                <div class="detail-row">
                    <span>Order Date</span>
                    <span>{{ $order->created_at->format('M d, Y') }}</span>
                </div>
                <div class="detail-row">
                    <span>Total</span>
                    <strong>${{ number_format($order->total, 2) }}</strong>
                </div>
            </div>
            
            <p style="text-align: center;">
                <a href="{{ route('account.orders.show', $order) }}" class="btn">View Order Details</a>
            </p>
            
            <p>If you have any questions, please don't hesitate to contact us.</p>
            
            <p>Thank you for shopping with us!</p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ setting('store.name', 'Our Store') }}. All rights reserved.</p>
            <p>{{ setting('store.address', '') }}</p>
        </div>
    </div>
</body>
</html>
