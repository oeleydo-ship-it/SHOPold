<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; }
        .header { background: #1f2937; color: #fff; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { padding: 30px; }
        .btn { display: inline-block; padding: 14px 28px; background: #3b82f6; color: #fff; text-decoration: none; border-radius: 6px; font-weight: bold; margin: 20px 0; }
        .btn:hover { background: #2563eb; }
        .note { background: #f9fafb; border-radius: 8px; padding: 15px; margin: 20px 0; font-size: 14px; color: #6b7280; }
        .footer { background: #f9fafb; padding: 20px; text-align: center; color: #6b7280; font-size: 14px; }
        .link-text { word-break: break-all; font-size: 12px; color: #6b7280; background: #f3f4f6; padding: 10px; border-radius: 4px; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ setting('store.name', config('app.name')) }}</h1>
        </div>
        
        <div class="content">
            <h2>Verify Your Email Address</h2>
            
            <p>Hello {{ $user->name }},</p>
            
            <p>Thank you for registering! Please click the button below to verify your email address and activate your account.</p>
            
            <p style="text-align: center;">
                <a href="{{ $verificationUrl }}" class="btn">Verify Email Address</a>
            </p>
            
            <div class="note">
                <p style="margin: 0;"><strong>Note:</strong> This verification link will expire in 24 hours. If you did not create an account, no further action is required.</p>
            </div>
            
            <p>If you're having trouble clicking the button, copy and paste the URL below into your web browser:</p>
            
            <div class="link-text">
                {{ $verificationUrl }}
            </div>
            
            <p style="margin-top: 30px;">Best regards,<br>{{ setting('store.name', config('app.name')) }} Team</p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ setting('store.name', config('app.name')) }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
