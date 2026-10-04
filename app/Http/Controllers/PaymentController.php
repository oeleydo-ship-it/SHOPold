<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentGatewayService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Initialize Paystack payment
     */
    public function paystackInit(Order $order)
    {
        $config = $this->paymentService->getPaystackConfig();
        
        if (empty($config['secret_key'])) {
            return back()->with('error', 'Paystack is not configured properly.');
        }

        $reference = 'PS_' . $order->order_number . '_' . time();
        
        $data = [
            'email' => $order->shipping_email,
            'amount' => (int) ($order->total * 100), // Convert to kobo
            'reference' => $reference,
            'callback_url' => route('payment.paystack.callback'),
            'metadata' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ],
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.paystack.co/transaction/initialize');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $config['secret_key'],
            'Content-Type: application/json',
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);

        if ($result['status'] ?? false) {
            $order->update(['transaction_id' => $reference]);
            return redirect($result['data']['authorization_url']);
        }

        Log::error('Paystack initialization failed', ['response' => $result]);
        return back()->with('error', 'Failed to initialize payment. Please try again.');
    }

    /**
     * Handle Paystack callback
     */
    public function paystackCallback(Request $request)
    {
        $reference = $request->get('reference');
        
        if (!$reference) {
            return redirect()->route('cart.index')->with('error', 'Invalid payment reference.');
        }

        $config = $this->paymentService->getPaystackConfig();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.paystack.co/transaction/verify/' . $reference);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $config['secret_key'],
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);

        if (($result['status'] ?? false) && ($result['data']['status'] ?? '') === 'success') {
            $order = Order::where('transaction_id', $reference)->first();
            
            if ($order) {
                $order->update(['payment_status' => 'paid']);
                return redirect()->route('checkout.success', $order)->with('success', 'Payment successful!');
            }
        }

        Log::error('Paystack verification failed', ['response' => $result]);
        return redirect()->route('cart.index')->with('error', 'Payment verification failed.');
    }

    /**
     * Initialize Flutterwave payment
     */
    public function flutterwaveInit(Order $order)
    {
        $config = $this->paymentService->getFlutterwaveConfig();
        
        if (empty($config['secret_key'])) {
            return back()->with('error', 'Flutterwave is not configured properly.');
        }

        $txRef = 'FLW_' . $order->order_number . '_' . time();

        $data = [
            'tx_ref' => $txRef,
            'amount' => $order->total,
            'currency' => strtoupper(setting('payment.currency', 'NGN')),
            'redirect_url' => route('payment.flutterwave.callback'),
            'customer' => [
                'email' => $order->shipping_email,
                'name' => $order->shipping_name,
                'phonenumber' => $order->shipping_phone ?? '',
            ],
            'meta' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ],
            'customizations' => [
                'title' => setting('store.name', config('app.name')),
            ],
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.flutterwave.com/v3/payments');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $config['secret_key'],
            'Content-Type: application/json',
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);

        if (($result['status'] ?? '') === 'success') {
            $order->update(['transaction_id' => $txRef]);
            return redirect($result['data']['link']);
        }

        Log::error('Flutterwave initialization failed', ['response' => $result]);
        return back()->with('error', 'Failed to initialize payment. Please try again.');
    }

    /**
     * Handle Flutterwave callback
     */
    public function flutterwaveCallback(Request $request)
    {
        $txRef = $request->get('tx_ref');
        $transactionId = $request->get('transaction_id');
        $status = $request->get('status');

        if ($status !== 'successful' || !$transactionId) {
            return redirect()->route('cart.index')->with('error', 'Payment was not successful.');
        }

        $config = $this->paymentService->getFlutterwaveConfig();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.flutterwave.com/v3/transactions/' . $transactionId . '/verify');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $config['secret_key'],
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);

        if (($result['status'] ?? '') === 'success' && ($result['data']['status'] ?? '') === 'successful') {
            $order = Order::where('transaction_id', $txRef)->first();
            
            if ($order) {
                $order->update(['payment_status' => 'paid']);
                return redirect()->route('checkout.success', $order)->with('success', 'Payment successful!');
            }
        }

        Log::error('Flutterwave verification failed', ['response' => $result]);
        return redirect()->route('cart.index')->with('error', 'Payment verification failed.');
    }

    /**
     * Initialize SSLCommerz payment
     */
    public function sslcommerzInit(Order $order)
    {
        $config = $this->paymentService->getSSLCommerzConfig();
        
        if (empty($config['store_id']) || empty($config['store_password'])) {
            return back()->with('error', 'SSLCommerz is not configured properly.');
        }

        $tranId = 'SSL_' . $order->order_number . '_' . time();

        $data = [
            'store_id' => $config['store_id'],
            'store_passwd' => $config['store_password'],
            'total_amount' => $order->total,
            'currency' => strtoupper(setting('payment.currency', 'BDT')),
            'tran_id' => $tranId,
            'success_url' => route('payment.sslcommerz.success'),
            'fail_url' => route('payment.sslcommerz.fail'),
            'cancel_url' => route('payment.sslcommerz.cancel'),
            'cus_name' => $order->shipping_name,
            'cus_email' => $order->shipping_email,
            'cus_phone' => $order->shipping_phone ?? '01700000000',
            'cus_add1' => $order->shipping_address,
            'cus_city' => $order->shipping_city,
            'cus_country' => $order->shipping_country,
            'shipping_method' => 'NO',
            'product_name' => 'Order #' . $order->order_number,
            'product_category' => 'General',
            'product_profile' => 'general',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $config['base_url'] . '/gwprocess/v4/api.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);

        if (($result['status'] ?? '') === 'SUCCESS') {
            $order->update(['transaction_id' => $tranId]);
            return redirect($result['GatewayPageURL']);
        }

        Log::error('SSLCommerz initialization failed', ['response' => $result]);
        return back()->with('error', 'Failed to initialize payment. Please try again.');
    }

    /**
     * Handle SSLCommerz success
     */
    public function sslcommerzSuccess(Request $request)
    {
        $tranId = $request->get('tran_id');
        $valId = $request->get('val_id');

        $order = Order::where('transaction_id', $tranId)->first();

        if ($order && $request->get('status') === 'VALID') {
            $order->update(['payment_status' => 'paid']);
            return redirect()->route('checkout.success', $order)->with('success', 'Payment successful!');
        }

        return redirect()->route('cart.index')->with('error', 'Payment verification failed.');
    }

    /**
     * Handle SSLCommerz failure
     */
    public function sslcommerzFail(Request $request)
    {
        return redirect()->route('cart.index')->with('error', 'Payment failed. Please try again.');
    }

    /**
     * Handle SSLCommerz cancellation
     */
    public function sslcommerzCancel(Request $request)
    {
        return redirect()->route('cart.index')->with('info', 'Payment was cancelled.');
    }

    /**
     * Initialize bKash payment
     */
    public function bkashInit(Order $order)
    {
        $config = $this->paymentService->getBkashConfig();
        
        if (empty($config['app_key']) || empty($config['app_secret'])) {
            return back()->with('error', 'bKash is not configured properly.');
        }

        // Get bKash token first
        $token = $this->getBkashToken($config);
        
        if (!$token) {
            return back()->with('error', 'Failed to authenticate with bKash.');
        }

        $invoiceId = 'BK_' . $order->order_number . '_' . time();

        $data = [
            'mode' => '0011',
            'payerReference' => $order->shipping_phone ?? $order->shipping_email,
            'callbackURL' => route('payment.bkash.callback'),
            'amount' => number_format($order->total, 2, '.', ''),
            'currency' => 'BDT',
            'intent' => 'sale',
            'merchantInvoiceNumber' => $invoiceId,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $config['base_url'] . '/tokenized/checkout/create');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . $token,
            'X-APP-Key: ' . $config['app_key'],
            'Content-Type: application/json',
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);

        if (!empty($result['bkashURL'])) {
            $order->update(['transaction_id' => $invoiceId]);
            session(['bkash_payment_id' => $result['paymentID'] ?? null]);
            return redirect($result['bkashURL']);
        }

        Log::error('bKash initialization failed', ['response' => $result]);
        return back()->with('error', 'Failed to initialize payment. Please try again.');
    }

    /**
     * Get bKash authentication token
     */
    protected function getBkashToken(array $config): ?string
    {
        $data = [
            'app_key' => $config['app_key'],
            'app_secret' => $config['app_secret'],
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $config['base_url'] . '/tokenized/checkout/token/grant');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'username: ' . $config['username'],
            'password: ' . $config['password'],
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);

        return $result['id_token'] ?? null;
    }

    /**
     * Handle bKash callback
     */
    public function bkashCallback(Request $request)
    {
        $paymentId = $request->get('paymentID');
        $status = $request->get('status');

        if ($status !== 'success' || !$paymentId) {
            return redirect()->route('cart.index')->with('error', 'Payment was not successful.');
        }

        $config = $this->paymentService->getBkashConfig();
        $token = $this->getBkashToken($config);

        if (!$token) {
            return redirect()->route('cart.index')->with('error', 'Failed to verify payment.');
        }

        // Execute the payment
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $config['base_url'] . '/tokenized/checkout/execute');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['paymentID' => $paymentId]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . $token,
            'X-APP-Key: ' . $config['app_key'],
            'Content-Type: application/json',
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);

        if (($result['statusCode'] ?? '') === '0000') {
            $invoiceNumber = $result['merchantInvoiceNumber'] ?? '';
            $order = Order::where('transaction_id', $invoiceNumber)->first();
            
            if ($order) {
                $order->update(['payment_status' => 'paid']);
                return redirect()->route('checkout.success', $order)->with('success', 'Payment successful!');
            }
        }

        Log::error('bKash execution failed', ['response' => $result]);
        return redirect()->route('cart.index')->with('error', 'Payment verification failed.');
    }

    /**
     * Initialize Razorpay payment
     */
    public function razorpayInit(Order $order)
    {
        $config = $this->paymentService->getRazorpayConfig();
        
        if (empty($config['key_id']) || empty($config['key_secret'])) {
            return back()->with('error', 'Razorpay is not configured properly.');
        }

        $receiptId = 'RZP_' . $order->order_number;

        // Create Razorpay order
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.razorpay.com/v1/orders');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_USERPWD, $config['key_id'] . ':' . $config['key_secret']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'amount' => (int) ($order->total * 100), // Convert to paise
            'currency' => strtoupper($config['currency'] ?: 'INR'),
            'receipt' => $receiptId,
            'notes' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ],
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);

        if (!empty($result['id'])) {
            $order->update(['transaction_id' => $result['id']]);
            
            // Return view with Razorpay checkout
            return view('payment.razorpay', [
                'order' => $order,
                'razorpayOrderId' => $result['id'],
                'keyId' => $config['key_id'],
                'amount' => (int) ($order->total * 100),
                'currency' => strtoupper($config['currency'] ?: 'INR'),
                'storeName' => setting('store.name', config('app.name')),
            ]);
        }

        Log::error('Razorpay order creation failed', ['response' => $result]);
        return back()->with('error', 'Failed to initialize payment. Please try again.');
    }

    /**
     * Handle Razorpay callback
     */
    public function razorpayCallback(Request $request)
    {
        $razorpayPaymentId = $request->get('razorpay_payment_id');
        $razorpayOrderId = $request->get('razorpay_order_id');
        $razorpaySignature = $request->get('razorpay_signature');

        if (!$razorpayPaymentId || !$razorpayOrderId || !$razorpaySignature) {
            return redirect()->route('cart.index')->with('error', 'Invalid payment response.');
        }

        $config = $this->paymentService->getRazorpayConfig();

        // Verify signature
        $expectedSignature = hash_hmac('sha256', $razorpayOrderId . '|' . $razorpayPaymentId, $config['key_secret']);

        if ($expectedSignature === $razorpaySignature) {
            $order = Order::where('transaction_id', $razorpayOrderId)->first();
            
            if ($order) {
                $order->update([
                    'payment_status' => 'paid',
                    'transaction_id' => $razorpayPaymentId,
                ]);
                return redirect()->route('checkout.success', $order)->with('success', 'Payment successful!');
            }
        }

        Log::error('Razorpay signature verification failed');
        return redirect()->route('cart.index')->with('error', 'Payment verification failed.');
    }

    /**
     * Generic payment success handler for redirect-based gateways
     */
    public function paymentSuccess(Request $request, Order $order)
    {
        if ($order->payment_status !== 'paid') {
            $order->update(['payment_status' => 'paid']);
        }
        
        return redirect()->route('checkout.success', $order)->with('success', 'Payment successful!');
    }

    /**
     * Generic payment failure handler
     */
    public function paymentFailed(Request $request)
    {
        return redirect()->route('cart.index')->with('error', 'Payment failed. Please try again.');
    }

    /**
     * Generic payment cancel handler
     */
    public function paymentCancelled(Request $request)
    {
        return redirect()->route('cart.index')->with('info', 'Payment was cancelled.');
    }
}
