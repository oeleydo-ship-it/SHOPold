<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Verification Code</title>
</head>
<body style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f5f5f5;">
    <div style="background-color: #ffffff; border-radius: 12px; padding: 40px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <!-- Logo/Header -->
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="color: {{ setting('theme.primary_color', '#2563eb') }}; margin: 0; font-size: 24px;">
                {{ setting('store.name', config('app.name')) }}
            </h1>
        </div>
        
        <!-- Main Content -->
        <div style="text-align: center;">
            <div style="background-color: #f0f9ff; border-radius: 8px; padding: 20px; margin-bottom: 25px;">
                <i style="font-size: 48px; color: {{ setting('theme.primary_color', '#2563eb') }};">🔐</i>
            </div>
            
            <h2 style="color: #1f2937; margin-bottom: 15px; font-size: 22px;">Verify Your Email</h2>
            
            <p style="color: #6b7280; margin-bottom: 25px;">
                You're checking out as a guest. Please use the verification code below to continue with your order.
            </p>
            
            <!-- OTP Code -->
            <div style="background: linear-gradient(135deg, {{ setting('theme.primary_color', '#2563eb') }}, {{ setting('theme.secondary_color', '#1e40af') }}); border-radius: 12px; padding: 25px; margin: 25px 0;">
                <p style="color: rgba(255,255,255,0.9); margin: 0 0 10px 0; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">
                    Your Verification Code
                </p>
                <p style="color: #ffffff; font-size: 36px; font-weight: bold; letter-spacing: 8px; margin: 0; font-family: 'Courier New', monospace;">
                    {{ $otp }}
                </p>
            </div>
            
            <p style="color: #9ca3af; font-size: 14px; margin-bottom: 25px;">
                <strong>⏱️ This code expires in {{ $expiry }} minutes.</strong>
            </p>
            
            <div style="background-color: #fef3c7; border-radius: 8px; padding: 15px; margin-top: 25px;">
                <p style="color: #92400e; margin: 0; font-size: 13px;">
                    <strong>🔒 Security Notice:</strong> If you didn't request this code, please ignore this email. Never share this code with anyone.
                </p>
            </div>
        </div>
        
        <!-- Footer -->
        <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #e5e7eb; text-align: center;">
            <p style="color: #9ca3af; font-size: 12px; margin: 0;">
                © {{ date('Y') }} {{ setting('store.name', config('app.name')) }}. All rights reserved.
            </p>
            <p style="color: #9ca3af; font-size: 12px; margin: 5px 0 0 0;">
                This is an automated message. Please do not reply to this email.
            </p>
        </div>
    </div>
</body>
</html>
