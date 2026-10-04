<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Delivered</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #10B981; color: white; padding: 20px; text-align: center; }
        .content { background: #f9f9f9; padding: 20px; }
        .order-details { background: white; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .button { display: inline-block; padding: 12px 30px; background: #10B981; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✓ Order Delivered!</h1>
        </div>
        
        <div class="content">
            <p>Hi {{ $order->shipping_name ?? $order->user->name ?? 'Customer' }},</p>
            
            <p>Great news! Your order has been successfully delivered.</p>
            
            <div class="order-details">
                <h2>Order #{{ $order->order_number }}</h2>
                <p><strong>Delivered On:</strong> {{ $order->delivered_at->format('F j, Y g:i A') }}</p>
                
                @if($order->driver)
                <p><strong>Delivered By:</strong> {{ $order->driver->name }}</p>
                @endif
                
                @if($order->tracking_number)
                <p><strong>Tracking Number:</strong> {{ $order->tracking_number }}</p>
                @endif
            </div>
            
            <center>
                <a href="{{ route('account.order', $order->id) }}" class="button">View Order Details</a>
            </center>
            
            <p>We hope you enjoy your purchase! If you have any issues with your order, please contact us.</p>
            
            <p>Thank you for shopping with us!</p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ setting('store.name', config('app.name')) }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
