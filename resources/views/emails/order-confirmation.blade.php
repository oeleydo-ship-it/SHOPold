<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4F46E5; color: white; padding: 20px; text-align: center; }
        .content { background: #f9f9f9; padding: 20px; }
        .order-details { background: white; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .item { border-bottom: 1px solid #eee; padding: 10px 0; }
        .total { font-size: 18px; font-weight: bold; margin-top: 15px; }
        .button { display: inline-block; padding: 12px 30px; background: #4F46E5; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Order Confirmation</h1>
        </div>
        
        <div class="content">
            <p>Hi {{ $order->shipping_name ?? $order->user->name ?? 'Customer' }},</p>
            
            <p>Thank you for your order! We've received your order and will process it shortly.</p>
            
            <div class="order-details">
                <h2>Order #{{ $order->order_number }}</h2>
                <p><strong>Order Date:</strong> {{ $order->created_at->format('F j, Y') }}</p>
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
                    <p>Subtotal: ${{ number_format($order->subtotal, 2) }}</p>
                    @if($order->tax > 0)
                    <p>Tax: ${{ number_format($order->tax, 2) }}</p>
                    @endif
                    @if($order->shipping_cost > 0)
                    <p>Shipping: ${{ number_format($order->shipping_cost, 2) }}</p>
                    @endif
                    @if($order->discount > 0)
                    <p>Discount: -${{ number_format($order->discount, 2) }}</p>
                    @endif
                    <p style="color: #4F46E5;">Total: ${{ number_format($order->total, 2) }}</p>
                </div>
                
                <h3>Shipping Address:</h3>
                <p>
                    {{ $order->shipping_name }}<br>
                    {{ $order->shipping_address }}<br>
                    {{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_zip }}<br>
                    {{ $order->shipping_country }}
                </p>
            </div>
            
            <center>
                <a href="{{ route('account.order', $order->id) }}" class="button">View Order Details</a>
            </center>
            
            <p>If you have any questions, please don't hesitate to contact us.</p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ setting('store.name', config('app.name')) }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
