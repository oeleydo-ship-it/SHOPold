<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class PaymentGatewayService
{
    /**
     * Decrypt a sensitive setting value
     */
    protected function decryptSetting(?string $value): string
    {
        if (empty($value) || $value === null) {
            return '';
        }
        
        try {
            // Check if value appears to be encrypted (base64 encoded JSON)
            if (str_starts_with($value, 'eyJ')) {
                return Crypt::decryptString($value);
            }
            return $value;
        } catch (\Exception $e) {
            Log::warning('Failed to decrypt setting: ' . $e->getMessage());
            return $value;
        }
    }
    
    /**
     * Get available payment methods based on settings
     */
    public function getAvailableMethods(): array
    {
        $methods = [];

        // Cash on Delivery
        if (Setting::get('payment.cod_enabled', false)) {
            $methods['cod'] = [
                'name' => 'Cash on Delivery',
                'description' => Setting::get('payment.cod_instructions', 'Pay with cash upon delivery.'),
                'icon' => 'fa-money-bill',
                'fee' => (float) Setting::get('payment.cod_fee', 0),
                'min_amount' => (float) Setting::get('payment.cod_min_amount', 0),
            ];
        }

        // Stripe
        if (Setting::get('payment.stripe_enabled', false)) {
            $methods['stripe'] = [
                'name' => 'Credit/Debit Card',
                'description' => 'Pay securely with your credit or debit card via Stripe.',
                'icon' => 'fa-credit-card',
                'public_key' => Setting::get('payment.stripe_public_key', ''),
            ];
        }

        // PayPal
        if (Setting::get('payment.paypal_enabled', false)) {
            $methods['paypal'] = [
                'name' => 'PayPal',
                'description' => 'Pay securely with your PayPal account.',
                'icon' => 'fa-paypal',
                'client_id' => Setting::get('payment.paypal_client_id', ''),
                'mode' => Setting::get('payment.paypal_mode', 'sandbox'),
            ];
        }

        // Razorpay
        if (Setting::get('payment.razorpay_enabled', false)) {
            $methods['razorpay'] = [
                'name' => 'Razorpay',
                'description' => 'Pay securely via Razorpay.',
                'icon' => 'fa-credit-card',
                'key_id' => Setting::get('payment.razorpay_key_id', ''),
            ];
        }

        // Paytm
        if (Setting::get('payment.paytm_enabled', false)) {
            $methods['paytm'] = [
                'name' => 'Paytm',
                'description' => 'Pay via Paytm wallet or UPI.',
                'icon' => 'fa-wallet',
                'merchant_id' => Setting::get('payment.paytm_merchant_id', ''),
            ];
        }

        // Instamojo
        if (Setting::get('payment.instamojo_enabled', false)) {
            $methods['instamojo'] = [
                'name' => 'Instamojo',
                'description' => 'Pay via Instamojo - Cards, UPI, Wallets.',
                'icon' => 'fa-credit-card',
                'api_key' => Setting::get('payment.instamojo_api_key', ''),
            ];
        }

        // PayFast
        if (Setting::get('payment.payfast_enabled', false)) {
            $methods['payfast'] = [
                'name' => 'PayFast',
                'description' => 'Pay securely via PayFast (South Africa).',
                'icon' => 'fa-credit-card',
                'merchant_id' => Setting::get('payment.payfast_merchant_id', ''),
            ];
        }

        // Mercado Pago
        if (Setting::get('payment.mercadopago_enabled', false)) {
            $methods['mercadopago'] = [
                'name' => 'Mercado Pago',
                'description' => 'Pay via Mercado Pago - Latin America.',
                'icon' => 'fa-handshake',
                'public_key' => Setting::get('payment.mercadopago_public_key', ''),
            ];
        }

        // Authorize.net
        if (Setting::get('payment.authorizenet_enabled', false)) {
            $methods['authorizenet'] = [
                'name' => 'Authorize.net',
                'description' => 'Pay securely with credit/debit card.',
                'icon' => 'fa-credit-card',
                'login_id' => Setting::get('payment.authorizenet_login_id', ''),
            ];
        }

        // iyzico
        if (Setting::get('payment.iyzico_enabled', false)) {
            $methods['iyzico'] = [
                'name' => 'iyzico',
                'description' => 'Pay via iyzico (Turkey).',
                'icon' => 'fa-credit-card',
                'api_key' => Setting::get('payment.iyzico_api_key', ''),
            ];
        }

        // Paystack
        if (Setting::get('payment.paystack_enabled', false)) {
            $methods['paystack'] = [
                'name' => 'Paystack',
                'description' => 'Pay via Paystack - Africa.',
                'icon' => 'fa-credit-card',
                'public_key' => Setting::get('payment.paystack_public_key', ''),
            ];
        }

        // Flutterwave
        if (Setting::get('payment.flutterwave_enabled', false)) {
            $methods['flutterwave'] = [
                'name' => 'Flutterwave',
                'description' => 'Pay via Flutterwave - Africa.',
                'icon' => 'fa-credit-card',
                'public_key' => Setting::get('payment.flutterwave_public_key', ''),
            ];
        }

        // SSLCommerz
        if (Setting::get('payment.sslcommerz_enabled', false)) {
            $methods['sslcommerz'] = [
                'name' => 'SSLCommerz',
                'description' => 'Pay via SSLCommerz (Bangladesh).',
                'icon' => 'fa-credit-card',
                'store_id' => Setting::get('payment.sslcommerz_store_id', ''),
            ];
        }

        // bKash
        if (Setting::get('payment.bkash_enabled', false)) {
            $methods['bkash'] = [
                'name' => 'bKash',
                'description' => 'Pay via bKash mobile wallet.',
                'icon' => 'fa-mobile-alt',
                'app_key' => Setting::get('payment.bkash_app_key', ''),
            ];
        }

        // Nagad
        if (Setting::get('payment.nagad_enabled', false)) {
            $methods['nagad'] = [
                'name' => 'Nagad',
                'description' => 'Pay via Nagad mobile wallet.',
                'icon' => 'fa-mobile-alt',
                'merchant_id' => Setting::get('payment.nagad_merchant_id', ''),
            ];
        }

        // Check/Money Order
        if (Setting::get('payment.check_enabled', false)) {
            $methods['check'] = [
                'name' => 'Check / Money Order',
                'description' => Setting::get('payment.check_instructions', 'Pay by check or money order.'),
                'icon' => 'fa-money-check',
                'payable_to' => Setting::get('payment.check_payable_to', ''),
                'mailing_address' => Setting::get('payment.check_mailing_address', ''),
            ];
        }

        // Bank Transfer
        if (Setting::get('payment.bank_enabled', false)) {
            $methods['bank_transfer'] = [
                'name' => 'Bank Transfer',
                'description' => 'Pay via direct bank transfer.',
                'icon' => 'fa-university',
                'bank_name' => Setting::get('payment.bank_name', ''),
                'account_name' => Setting::get('payment.bank_account_name', ''),
                'account_number' => Setting::get('payment.bank_account_number', ''),
                'routing_number' => Setting::get('payment.bank_routing_number', ''),
                'instructions' => Setting::get('payment.bank_details', ''),
            ];
        }

        return $methods;
    }

    /**
     * Get Stripe configuration
     */
    public function getStripeConfig(): array
    {
        return [
            'public_key' => Setting::get('payment.stripe_public_key', ''),
            'secret_key' => $this->decryptSetting(Setting::get('payment.stripe_secret_key', '')),
            'webhook_secret' => $this->decryptSetting(Setting::get('payment.stripe_webhook_secret', '')),
            'currency' => strtolower(Setting::get('payment.currency', 'usd')),
            'mode' => Setting::get('payment.mode', 'sandbox'),
        ];
    }

    /**
     * Get PayPal configuration
     */
    public function getPayPalConfig(): array
    {
        $mode = Setting::get('payment.paypal_mode', 'sandbox');
        
        return [
            'client_id' => Setting::get('payment.paypal_client_id', ''),
            'client_secret' => $this->decryptSetting(Setting::get('payment.paypal_client_secret', '')),
            'mode' => $mode,
            'currency' => Setting::get('payment.currency', 'USD'),
            'base_url' => $mode === 'live' 
                ? 'https://api-m.paypal.com' 
                : 'https://api-m.sandbox.paypal.com',
        ];
    }

    /**
     * Get Razorpay configuration
     */
    public function getRazorpayConfig(): array
    {
        return [
            'key_id' => Setting::get('payment.razorpay_key_id', ''),
            'key_secret' => $this->decryptSetting(Setting::get('payment.razorpay_key_secret', '')),
            'currency' => Setting::get('payment.currency', 'INR'),
        ];
    }

    /**
     * Get Paytm configuration
     */
    public function getPaytmConfig(): array
    {
        return [
            'merchant_id' => Setting::get('payment.paytm_merchant_id', ''),
            'merchant_key' => $this->decryptSetting(Setting::get('payment.paytm_merchant_key', '')),
            'website' => Setting::get('payment.paytm_website', 'WEBSTAGING'),
            'industry_type' => Setting::get('payment.paytm_industry_type', 'Retail'),
            'channel' => Setting::get('payment.paytm_channel', 'WEB'),
            'mode' => Setting::get('payment.paytm_mode', 'sandbox'),
        ];
    }

    /**
     * Get Instamojo configuration
     */
    public function getInstamojoConfig(): array
    {
        return [
            'api_key' => Setting::get('payment.instamojo_api_key', ''),
            'auth_token' => $this->decryptSetting(Setting::get('payment.instamojo_auth_token', '')),
            'mode' => Setting::get('payment.instamojo_mode', 'sandbox'),
            'base_url' => Setting::get('payment.instamojo_mode', 'sandbox') === 'live'
                ? 'https://www.instamojo.com/api/1.1'
                : 'https://test.instamojo.com/api/1.1',
        ];
    }

    /**
     * Get PayFast configuration
     */
    public function getPayFastConfig(): array
    {
        return [
            'merchant_id' => Setting::get('payment.payfast_merchant_id', ''),
            'merchant_key' => $this->decryptSetting(Setting::get('payment.payfast_merchant_key', '')),
            'passphrase' => $this->decryptSetting(Setting::get('payment.payfast_passphrase', '')),
            'mode' => Setting::get('payment.payfast_mode', 'sandbox'),
            'base_url' => Setting::get('payment.payfast_mode', 'sandbox') === 'live'
                ? 'https://www.payfast.co.za/eng/process'
                : 'https://sandbox.payfast.co.za/eng/process',
        ];
    }

    /**
     * Get Mercado Pago configuration
     */
    public function getMercadoPagoConfig(): array
    {
        return [
            'public_key' => Setting::get('payment.mercadopago_public_key', ''),
            'access_token' => $this->decryptSetting(Setting::get('payment.mercadopago_access_token', '')),
            'mode' => Setting::get('payment.mercadopago_mode', 'sandbox'),
        ];
    }

    /**
     * Get Authorize.net configuration
     */
    public function getAuthorizeNetConfig(): array
    {
        return [
            'login_id' => Setting::get('payment.authorizenet_login_id', ''),
            'transaction_key' => $this->decryptSetting(Setting::get('payment.authorizenet_transaction_key', '')),
            'mode' => Setting::get('payment.authorizenet_mode', 'sandbox'),
        ];
    }

    /**
     * Get iyzico configuration
     */
    public function getIyzicoConfig(): array
    {
        return [
            'api_key' => Setting::get('payment.iyzico_api_key', ''),
            'secret_key' => $this->decryptSetting(Setting::get('payment.iyzico_secret_key', '')),
            'mode' => Setting::get('payment.iyzico_mode', 'sandbox'),
            'base_url' => Setting::get('payment.iyzico_mode', 'sandbox') === 'live'
                ? 'https://api.iyzipay.com'
                : 'https://sandbox-api.iyzipay.com',
        ];
    }

    /**
     * Get Paystack configuration
     */
    public function getPaystackConfig(): array
    {
        return [
            'public_key' => Setting::get('payment.paystack_public_key', ''),
            'secret_key' => $this->decryptSetting(Setting::get('payment.paystack_secret_key', '')),
            'mode' => Setting::get('payment.paystack_mode', 'sandbox'),
        ];
    }

    /**
     * Get Flutterwave configuration
     */
    public function getFlutterwaveConfig(): array
    {
        return [
            'public_key' => Setting::get('payment.flutterwave_public_key', ''),
            'secret_key' => $this->decryptSetting(Setting::get('payment.flutterwave_secret_key', '')),
            'encryption_key' => $this->decryptSetting(Setting::get('payment.flutterwave_encryption_key', '')),
            'mode' => Setting::get('payment.flutterwave_mode', 'sandbox'),
        ];
    }

    /**
     * Get SSLCommerz configuration
     */
    public function getSSLCommerzConfig(): array
    {
        return [
            'store_id' => Setting::get('payment.sslcommerz_store_id', ''),
            'store_password' => $this->decryptSetting(Setting::get('payment.sslcommerz_store_password', '')),
            'mode' => Setting::get('payment.sslcommerz_mode', 'sandbox'),
            'base_url' => Setting::get('payment.sslcommerz_mode', 'sandbox') === 'live'
                ? 'https://securepay.sslcommerz.com'
                : 'https://sandbox.sslcommerz.com',
        ];
    }

    /**
     * Get bKash configuration
     */
    public function getBkashConfig(): array
    {
        return [
            'app_key' => Setting::get('payment.bkash_app_key', ''),
            'app_secret' => $this->decryptSetting(Setting::get('payment.bkash_app_secret', '')),
            'username' => Setting::get('payment.bkash_username', ''),
            'password' => $this->decryptSetting(Setting::get('payment.bkash_password', '')),
            'mode' => Setting::get('payment.bkash_mode', 'sandbox'),
            'base_url' => Setting::get('payment.bkash_mode', 'sandbox') === 'live'
                ? 'https://tokenized.pay.bka.sh/v1.2.0-beta'
                : 'https://tokenized.sandbox.bka.sh/v1.2.0-beta',
        ];
    }

    /**
     * Get Nagad configuration
     */
    public function getNagadConfig(): array
    {
        return [
            'merchant_id' => Setting::get('payment.nagad_merchant_id', ''),
            'merchant_number' => Setting::get('payment.nagad_merchant_number', ''),
            'public_key' => Setting::get('payment.nagad_public_key', ''),
            'private_key' => $this->decryptSetting(Setting::get('payment.nagad_private_key', '')),
            'mode' => Setting::get('payment.nagad_mode', 'sandbox'),
            'base_url' => Setting::get('payment.nagad_mode', 'sandbox') === 'live'
                ? 'https://api.mynagad.com'
                : 'https://sandbox.mynagad.com',
        ];
    }

    /**
     * Check if a payment method is enabled
     */
    public function isMethodEnabled(string $method): bool
    {
        return match ($method) {
            'cod' => (bool) Setting::get('payment.cod_enabled', false),
            'stripe' => (bool) Setting::get('payment.stripe_enabled', false),
            'paypal' => (bool) Setting::get('payment.paypal_enabled', false),
            'razorpay' => (bool) Setting::get('payment.razorpay_enabled', false),
            'paytm' => (bool) Setting::get('payment.paytm_enabled', false),
            'instamojo' => (bool) Setting::get('payment.instamojo_enabled', false),
            'payfast' => (bool) Setting::get('payment.payfast_enabled', false),
            'mercadopago' => (bool) Setting::get('payment.mercadopago_enabled', false),
            'authorizenet' => (bool) Setting::get('payment.authorizenet_enabled', false),
            'iyzico' => (bool) Setting::get('payment.iyzico_enabled', false),
            'paystack' => (bool) Setting::get('payment.paystack_enabled', false),
            'flutterwave' => (bool) Setting::get('payment.flutterwave_enabled', false),
            'sslcommerz' => (bool) Setting::get('payment.sslcommerz_enabled', false),
            'bkash' => (bool) Setting::get('payment.bkash_enabled', false),
            'nagad' => (bool) Setting::get('payment.nagad_enabled', false),
            'check' => (bool) Setting::get('payment.check_enabled', false),
            'bank_transfer' => (bool) Setting::get('payment.bank_enabled', false),
            default => false,
        };
    }

    /**
     * Get payment mode (sandbox/live)
     */
    public function getPaymentMode(): string
    {
        return Setting::get('payment.mode', 'sandbox');
    }

    /**
     * Check if we're in live/production mode
     */
    public function isLiveMode(): bool
    {
        return $this->getPaymentMode() === 'live';
    }

    /**
     * Get default currency
     */
    public function getCurrency(): string
    {
        return Setting::get('payment.currency', 'USD');
    }

    /**
     * Calculate COD fee if applicable
     */
    public function getCodFee(float $orderTotal): float
    {
        if (!Setting::get('payment.cod_enabled', false)) {
            return 0;
        }

        $minAmount = (float) Setting::get('payment.cod_min_amount', 0);
        
        if ($minAmount > 0 && $orderTotal < $minAmount) {
            return 0; // COD not available for this order
        }

        return (float) Setting::get('payment.cod_fee', 0);
    }

    /**
     * Validate if COD is available for order
     */
    public function isCodAvailable(float $orderTotal): bool
    {
        if (!Setting::get('payment.cod_enabled', false)) {
            return false;
        }

        $minAmount = (float) Setting::get('payment.cod_min_amount', 0);
        
        return $minAmount <= 0 || $orderTotal >= $minAmount;
    }

    /**
     * Initialize Stripe client
     */
    public function initStripe(): void
    {
        $config = $this->getStripeConfig();
        
        if (!empty($config['secret_key'])) {
            \Stripe\Stripe::setApiKey($config['secret_key']);
        }
    }

    /**
     * Create Stripe payment intent
     */
    public function createStripePaymentIntent(float $amount, array $metadata = []): ?\Stripe\PaymentIntent
    {
        try {
            $this->initStripe();
            $config = $this->getStripeConfig();

            return \Stripe\PaymentIntent::create([
                'amount' => (int) ($amount * 100), // Convert to cents
                'currency' => $config['currency'],
                'metadata' => $metadata,
            ]);
        } catch (\Exception $e) {
            Log::error('Stripe payment intent creation failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get PayPal access token
     */
    public function getPayPalAccessToken(): ?string
    {
        try {
            $config = $this->getPayPalConfig();
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $config['base_url'] . '/v1/oauth2/token');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
            curl_setopt($ch, CURLOPT_USERPWD, $config['client_id'] . ':' . $config['client_secret']);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                'Accept-Language: en_US',
            ]);

            $response = curl_exec($ch);
            curl_close($ch);

            $data = json_decode($response, true);
            
            return $data['access_token'] ?? null;
        } catch (\Exception $e) {
            Log::error('PayPal access token retrieval failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create PayPal order
     */
    public function createPayPalOrder(float $amount, string $orderId): ?array
    {
        try {
            $config = $this->getPayPalConfig();
            $accessToken = $this->getPayPalAccessToken();

            if (!$accessToken) {
                return null;
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $config['base_url'] . '/v2/checkout/orders');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken,
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => $orderId,
                    'amount' => [
                        'currency_code' => $config['currency'],
                        'value' => number_format($amount, 2, '.', ''),
                    ],
                ]],
            ]));

            $response = curl_exec($ch);
            curl_close($ch);

            return json_decode($response, true);
        } catch (\Exception $e) {
            Log::error('PayPal order creation failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if a payment method supports refunds
     */
    public function supportsRefund(string $paymentMethod): bool
    {
        $refundableGateways = ['stripe', 'paypal', 'razorpay', 'paystack', 'flutterwave'];
        return in_array($paymentMethod, $refundableGateways);
    }

    /**
     * Get payment method display name
     */
    public function getPaymentMethodName(string $method): string
    {
        return match($method) {
            'cod' => 'Cash on Delivery',
            'stripe' => 'Credit/Debit Card (Stripe)',
            'paypal' => 'PayPal',
            'razorpay' => 'Razorpay',
            'paystack' => 'Paystack',
            'flutterwave' => 'Flutterwave',
            'sslcommerz' => 'SSLCommerz',
            'bkash' => 'bKash',
            'nagad' => 'Nagad',
            'paytm' => 'Paytm',
            'instamojo' => 'Instamojo',
            'payfast' => 'PayFast',
            'mercadopago' => 'Mercado Pago',
            'authorizenet' => 'Authorize.net',
            'iyzico' => 'iyzico',
            'check' => 'Check / Money Order',
            'bank_transfer' => 'Bank Transfer',
            default => ucfirst($method),
        };
    }

    /**
     * Check if payment method is card-based (not cash)
     */
    public function isCardPayment(string $method): bool
    {
        $cardGateways = ['stripe', 'paypal', 'razorpay', 'paystack', 'flutterwave', 'sslcommerz', 'paytm', 'instamojo', 'payfast', 'mercadopago', 'authorizenet', 'iyzico'];
        return in_array($method, $cardGateways);
    }

    /**
     * Process refund for an order
     */
    public function processRefund(\App\Models\Order $order, ?string $reason = null): array
    {
        $paymentMethod = $order->payment_method;
        
        if (!$this->supportsRefund($paymentMethod)) {
            return [
                'success' => false,
                'message' => 'Refunds are not supported for this payment method.',
            ];
        }

        if ($order->payment_status !== 'paid') {
            return [
                'success' => false,
                'message' => 'Order must be paid before it can be refunded.',
            ];
        }

        if ($order->payment_status === 'refunded') {
            return [
                'success' => false,
                'message' => 'Order has already been refunded.',
            ];
        }

        try {
            $result = match($paymentMethod) {
                'stripe' => $this->refundStripe($order),
                'paypal' => $this->refundPayPal($order),
                'razorpay' => $this->refundRazorpay($order),
                'paystack' => $this->refundPaystack($order),
                'flutterwave' => $this->refundFlutterwave($order),
                default => ['success' => false, 'message' => 'Refund not implemented for this gateway.'],
            };

            if ($result['success']) {
                $order->update([
                    'payment_status' => 'refunded',
                    'refund_id' => $result['refund_id'] ?? null,
                    'refund_amount' => $order->total,
                    'refunded_at' => now(),
                    'refund_reason' => $reason,
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Refund failed for order ' . $order->order_number . ': ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Refund failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Refund via Stripe
     */
    protected function refundStripe(\App\Models\Order $order): array
    {
        $this->initStripe();
        
        try {
            $refund = \Stripe\Refund::create([
                'payment_intent' => $order->transaction_id,
            ]);

            return [
                'success' => true,
                'refund_id' => $refund->id,
                'message' => 'Refund processed successfully via Stripe.',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Stripe refund failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Refund via PayPal
     */
    protected function refundPayPal(\App\Models\Order $order): array
    {
        $config = $this->getPayPalConfig();
        $accessToken = $this->getPayPalAccessToken();

        if (!$accessToken) {
            return ['success' => false, 'message' => 'Failed to authenticate with PayPal.'];
        }

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $config['base_url'] . '/v2/payments/captures/' . $order->transaction_id . '/refund');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken,
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'amount' => [
                    'value' => number_format($order->total, 2, '.', ''),
                    'currency_code' => $config['currency'],
                ],
            ]));

            $response = curl_exec($ch);
            curl_close($ch);

            $result = json_decode($response, true);

            if (isset($result['id'])) {
                return [
                    'success' => true,
                    'refund_id' => $result['id'],
                    'message' => 'Refund processed successfully via PayPal.',
                ];
            }

            return ['success' => false, 'message' => 'PayPal refund failed: ' . ($result['message'] ?? 'Unknown error')];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'PayPal refund failed: ' . $e->getMessage()];
        }
    }

    /**
     * Refund via Razorpay
     */
    protected function refundRazorpay(\App\Models\Order $order): array
    {
        $config = $this->getRazorpayConfig();

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.razorpay.com/v1/payments/' . $order->transaction_id . '/refund');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_USERPWD, $config['key_id'] . ':' . $config['key_secret']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'amount' => (int) ($order->total * 100),
            ]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

            $response = curl_exec($ch);
            curl_close($ch);

            $result = json_decode($response, true);

            if (isset($result['id'])) {
                return [
                    'success' => true,
                    'refund_id' => $result['id'],
                    'message' => 'Refund processed successfully via Razorpay.',
                ];
            }

            return ['success' => false, 'message' => 'Razorpay refund failed: ' . ($result['error']['description'] ?? 'Unknown error')];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Razorpay refund failed: ' . $e->getMessage()];
        }
    }

    /**
     * Refund via Paystack
     */
    protected function refundPaystack(\App\Models\Order $order): array
    {
        $config = $this->getPaystackConfig();

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.paystack.co/refund');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'transaction' => $order->transaction_id,
            ]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $config['secret_key'],
                'Content-Type: application/json',
            ]);

            $response = curl_exec($ch);
            curl_close($ch);

            $result = json_decode($response, true);

            if (($result['status'] ?? false) === true) {
                return [
                    'success' => true,
                    'refund_id' => $result['data']['id'] ?? $order->transaction_id . '_refund',
                    'message' => 'Refund processed successfully via Paystack.',
                ];
            }

            return ['success' => false, 'message' => 'Paystack refund failed: ' . ($result['message'] ?? 'Unknown error')];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Paystack refund failed: ' . $e->getMessage()];
        }
    }

    /**
     * Refund via Flutterwave
     */
    protected function refundFlutterwave(\App\Models\Order $order): array
    {
        $config = $this->getFlutterwaveConfig();

        try {
            // First, we need to get the transaction ID from Flutterwave
            // The order's transaction_id is the tx_ref, we need the Flutterwave transaction_id
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.flutterwave.com/v3/transactions/verify_by_reference?tx_ref=' . $order->transaction_id);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $config['secret_key'],
            ]);

            $verifyResponse = curl_exec($ch);
            curl_close($ch);

            $verifyResult = json_decode($verifyResponse, true);
            
            if (($verifyResult['status'] ?? '') !== 'success') {
                return ['success' => false, 'message' => 'Could not verify Flutterwave transaction.'];
            }

            $flwTransactionId = $verifyResult['data']['id'];

            // Now process the refund
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.flutterwave.com/v3/transactions/' . $flwTransactionId . '/refund');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'amount' => $order->total,
            ]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $config['secret_key'],
                'Content-Type: application/json',
            ]);

            $response = curl_exec($ch);
            curl_close($ch);

            $result = json_decode($response, true);

            if (($result['status'] ?? '') === 'success') {
                return [
                    'success' => true,
                    'refund_id' => $result['data']['id'] ?? $flwTransactionId . '_refund',
                    'message' => 'Refund processed successfully via Flutterwave.',
                ];
            }

            return ['success' => false, 'message' => 'Flutterwave refund failed: ' . ($result['message'] ?? 'Unknown error')];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Flutterwave refund failed: ' . $e->getMessage()];
        }
    }
}
