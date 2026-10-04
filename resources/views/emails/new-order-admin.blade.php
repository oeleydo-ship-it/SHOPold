<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Order Alert</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #EF4444; color: white; padding: 20px; text-align: center; }
        .content { background: #f9f9f9; padding: 20px; }
        .order-details { background: white; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .item { border-bottom: 1px solid #eee; padding: 10px 0; }
        .total { font-size: 18px; font-weight: bold; margin-top: 15px; color: #EF4444; }
        .button { display: inline-block; padding: 12px 30px; background: #EF4444; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔔 New Order Received!</h1>
        </div>
        
        <div class="content">
            <p>A new order has been placed on your store.</p>
            
            <div class="order-details">
                <h2>Order #{{ $order->order_number }}</h2>
                <p><strong>Order Date:</strong> {{ $order->created_at->format('F j, Y g:i A') }}</p>
                <p><strong>Customer:</strong> {{ $order->user ? $order->user->name . ' (' . $order->user->email . ')' : $order->shipping_email . ' (Guest)' }}</p>
                <p><strong>Payment Method:</strong> {{ ucfirst($order->payment_method) }}</p>
                <p><strong>Payment Status:</strong> {{ ucfirst($order->payment_status) }}</p>
                
                <h3>Order Items:</h3>
                @foreach($order->items as $item)
                <div class="item">
                    <strong>{{ $item->product_name }}</strong><br>
                    Quantity: {{ $item->quantity }} × ${{ number_format($item->price, 2) }}
                    = ${{ number_format($item->quantity * $item->price, 2) }}
                </div>
                @endforeach
                
                <div class="total">
                    <p>Order Total: ${{ number_format($order->total, 2) }}</p>
                </div>
                
                <h3>Shipping Address:</h3>
                <p>
                    {{ $order->shipping_name }}<br>
                    {{ $order->shipping_address }}<br>
                    {{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_zip }}<br>
                    {{ $order->shipping_country }}<br>
                    Phone: {{ $order->shipping_phone }}
                </p>
            </div>
            
            <center>
                <a href="{{ route('admin.orders.show', $order->id) }}" class="button">View Order in Admin</a>
            </center>
        </div>
        
        <div class="footer">
            <p>This is an automated notification from {{ setting('store.name', config('app.name')) }}</p>
        </div>
    </div>
</body>
</html>
