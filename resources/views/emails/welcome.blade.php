<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4F46E5; color: white; padding: 30px; text-align: center; }
        .content { background: #f9f9f9; padding: 30px; }
        .features { background: white; padding: 20px; margin: 20px 0; border-radius: 5px; }
        .feature { padding: 10px 0; }
        .button { display: inline-block; padding: 12px 30px; background: #4F46E5; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to {{ setting('store.name', config('app.name')) }}! 🎉</h1>
        </div>
        
        <div class="content">
            <p>Hi {{ $user->name }},</p>
            
            <p>Thank you for joining {{ setting('store.name', config('app.name')) }}! We're excited to have you as part of our community.</p>
            
            <div class="features">
                <h2>What You Can Do:</h2>
                <div class="feature">✓ Browse our wide selection of products</div>
                <div class="feature">✓ Track your orders in real-time</div>
                <div class="feature">✓ Save items to your wishlist</div>
                <div class="feature">✓ Get exclusive deals and offers</div>
                <div class="feature">✓ Manage your account and preferences</div>
            </div>
            
            <center>
                <a href="{{ route('shop.index') }}" class="button">Start Shopping</a>
            </center>
            
            <p>If you have any questions or need assistance, our support team is here to help!</p>
            
            <p>Happy shopping!</p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ setting('store.name', config('app.name')) }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
