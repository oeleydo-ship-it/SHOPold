<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Coupon;
use App\Models\ShippingZone;
use App\Models\PickupLocation;
use App\Models\User;
use App\Services\PaymentGatewayService;
use App\Mail\OrderConfirmation;
use App\Mail\NewOrderAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;

class CheckoutController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentGatewayService $paymentService)
    {
        $this->paymentService = $paymentService;
    }
    
    /**
     * Check if guest checkout is allowed
     */
    protected function isGuestCheckoutAllowed(): bool
    {
        return (bool) setting('features.guest_checkout', false);
    }
    
    /**
     * Check if guest OTP verification is required
     */
    protected function isGuestOtpRequired(): bool
    {
        return (bool) setting('features.guest_checkout_otp', true);
    }
    
    public function index()
    {
        // Check if user is logged in or guest checkout is allowed
        if (!auth()->check() && !$this->isGuestCheckoutAllowed()) {
            return redirect()->route('login')->with('error', 'Please login to continue checkout.');
        }
        
        $cart = Cart::getCart();
        $cart->load('items.product.translations');
        
        if ($cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $coupon = session('coupon');
        $subtotal = $cart->total;
        $discount = $coupon ? $coupon['discount'] : 0;
        
        // Get default country
        $defaultCountry = setting('shipping.default_country', 'US');
        $userCountry = auth()->check() ? (auth()->user()->country ?? null) : null;
        $countryCode = $this->getCountryCode($userCountry) ?? $defaultCountry;
        
        // Get shipping methods for country
        $shippingMethods = ShippingZone::getMethodsForCountry($countryCode);
        $defaultShipping = $shippingMethods->first();
        $shipping = $defaultShipping ? $defaultShipping->calculateCost($subtotal) : 0;
        
        // Get tax rate from settings
        $taxRate = (float) setting('payment.tax_rate', 10) / 100;
        $tax = ($subtotal - $discount) * $taxRate;
        $total = $subtotal - $discount + $shipping + $tax;
        
        // Guest checkout flags
        $isGuest = !auth()->check();
        $guestOtpRequired = $isGuest && $this->isGuestOtpRequired();
        $guestEmailVerified = session('guest_email_verified', false);
        
        // Get active pickup locations
        $pickupLocations = PickupLocation::active()->get();

        $data = compact(
            'cart', 'subtotal', 'discount', 'shipping', 'tax', 'total', 
            'coupon', 'shippingMethods', 'defaultShipping',
            'isGuest', 'guestOtpRequired', 'guestEmailVerified', 'pickupLocations'
        );

        return \Inertia\Inertia::render('Storefront/Checkout', \App\Support\CheckoutPage::checkout($data));
    }
    
    /**
     * Get shipping methods for a country via AJAX
     */
    public function getShippingMethods(Request $request)
    {
        $countryName = $request->input('country');
        $countryCode = $this->getCountryCode($countryName);
        
        $cart = Cart::getCart();
        $subtotal = $cart->total;
        $coupon = session('coupon');
        $discount = $coupon ? $coupon['discount'] : 0;
        
        $shippingMethods = ShippingZone::getMethodsForCountry($countryCode);
        
        $taxRate = (float) setting('payment.tax_rate', 10) / 100;
        $methods = $shippingMethods->map(function($method) use ($subtotal, $discount, $taxRate) {
            return [
                'id' => $method->id,
                'deliveryTime' => $method->delivery_time,
                'total_formatted' => format_currency($subtotal - $discount + $method->calculateCost($subtotal) + ($subtotal - $discount) * $taxRate),
                'name' => $method->name,
                'description' => $method->description,
                'cost' => $method->calculateCost($subtotal),
                'cost_formatted' => format_currency($method->calculateCost($subtotal)),
                'delivery_time' => $method->delivery_time,
            ];
        });
        
        // Calculate tax
        $taxRate = (float) setting('payment.tax_rate', 10) / 100;
        $firstMethod = $shippingMethods->first();
        $shippingCost = $firstMethod ? $firstMethod->calculateCost($subtotal) : 0;
        $tax = ($subtotal - $discount) * $taxRate;
        $total = $subtotal - $discount + $shippingCost + $tax;
        
        return response()->json([
            'methods' => $methods,
            'has_methods' => $methods->isNotEmpty(),
            'tax' => $tax,
            'tax_formatted' => format_currency($tax),
            'shipping' => $shippingCost,
            'shipping_formatted' => format_currency($shippingCost),
            'total' => $total,
            'total_formatted' => format_currency($total),
        ]);
    }

    public function process(Request $request)
    {
        // Check if user is logged in or guest checkout is allowed
        $isGuest = !auth()->check();
        
        if ($isGuest && !$this->isGuestCheckoutAllowed()) {
            return redirect()->route('login')->with('error', 'Please login to continue checkout.');
        }
        
        // For guest checkout with OTP required, verify email is verified
        if ($isGuest && $this->isGuestOtpRequired()) {
            if (!session('guest_email_verified')) {
                return back()->with('error', 'Please verify your email address before checkout.');
            }
            
            // Ensure the verified email matches the shipping email
            $verifiedEmail = session('guest_verified_email');
            if ($verifiedEmail && $verifiedEmail !== $request->shipping_email) {
                return back()->with('error', 'Please use the verified email address: ' . $verifiedEmail);
            }
        }
        
        // Check if this is a pickup order
        $isPickup = $request->input('delivery_type') === 'pickup';
        
        // Conditional validation based on delivery type
        $validationRules = [
            'shipping_name' => 'required|string|max:255',
            'shipping_email' => 'required|email',
            'shipping_phone' => 'nullable|string|max:20',
            'payment_method' => 'required|string',
            'notes' => 'nullable|string',
            'delivery_type' => 'nullable|in:shipping,pickup',
        ];
        
        if ($isPickup) {
            $validationRules['pickup_location_id'] = 'required|exists:pickup_locations,id';
            $validationRules['shipping_address'] = 'nullable|string';
            $validationRules['shipping_city'] = 'nullable|string|max:255';
            $validationRules['shipping_zip'] = 'nullable|string|max:20';
            $validationRules['shipping_country'] = 'nullable|string|max:255';
        } else {
            $validationRules['shipping_address'] = 'required|string';
            $validationRules['shipping_city'] = 'required|string|max:255';
            $validationRules['shipping_state'] = 'nullable|string|max:255';
            $validationRules['shipping_zip'] = 'required|string|max:20';
            $validationRules['shipping_country'] = 'required|string|max:255';
            $validationRules['shipping_method'] = 'nullable|integer';
        }
        
        $request->validate($validationRules);

        $cart = Cart::getCart();
        $cart->load(['items.product.translations', 'items.variation.attributeValues.attribute']);

        if ($cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        // Calculate totals
        $coupon = session('coupon');
        $subtotal = $cart->total;
        $discount = $coupon ? $coupon['discount'] : 0;
        
        // Get shipping cost - FREE for pickup orders
        if ($isPickup) {
            $shipping = 0;
        } else {
            // Get shipping cost from selected method or zone
            $countryCode = $this->getCountryCode($request->shipping_country);
            $shippingMethodId = $request->input('shipping_method');
            
            if ($shippingMethodId) {
                $shippingMethod = \App\Models\ShippingMethod::find($shippingMethodId);
                $shipping = $shippingMethod ? $shippingMethod->calculateCost($subtotal) : 0;
            } else {
                $shippingMethods = ShippingZone::getMethodsForCountry($countryCode);
                $defaultMethod = $shippingMethods->first();
                $shipping = $defaultMethod ? $defaultMethod->calculateCost($subtotal) : 0;
            }
        }
        
        // Get tax rate from settings
        $taxRate = (float) setting('payment.tax_rate', 10) / 100;
        $tax = ($subtotal - $discount) * $taxRate;
        $total = $subtotal - $discount + $shipping + $tax;

        try {
            DB::beginTransaction();

            // Handle Stripe payment if selected
            $paymentStatus = 'pending';
            $stripePaymentIntentId = null;
            
            if ($request->payment_method === 'stripe') {
                $stripePaymentMethodId = $request->input('stripe_payment_method');
                
                if (!$stripePaymentMethodId) {
                    return back()->with('error', 'Payment method not provided. Please enter your card details.');
                }
                
                try {
                    $stripeConfig = $this->paymentService->getStripeConfig();
                    
                    // Validate Stripe configuration
                    if (empty($stripeConfig['secret_key'])) {
                        Log::error('Stripe secret key is not configured');
                        return back()->with('error', 'Stripe is not properly configured. Please contact the administrator.');
                    }
                    
                    $this->paymentService->initStripe();
                    
                    // Get currency (ensure lowercase)
                    $currency = strtolower($stripeConfig['currency'] ?: 'usd');
                    
                    $stripeAmount = \App\Services\PaymentGatewayService::stripeAmount($total, $currency);

                    Log::info('Stripe Payment Attempt', [
                        'amount' => $stripeAmount,
                        'currency' => $currency,
                        'payment_method_id' => $stripePaymentMethodId,
                    ]);
                    
                    // Create payment intent and confirm it
                    $paymentIntent = \Stripe\PaymentIntent::create([
                        'amount' => $stripeAmount, // smallest currency unit, rounded
                        'currency' => $currency,
                        'payment_method' => $stripePaymentMethodId,
                        'confirm' => true,
                        'automatic_payment_methods' => [
                            'enabled' => true,
                            'allow_redirects' => 'never',
                        ],
                        'metadata' => [
                            'user_id' => auth()->id(), // null for guest checkout
                            'user_email' => auth()->user()?->email ?? $request->shipping_email,
                        ],
                    ]);
                    
                    if ($paymentIntent->status === 'succeeded') {
                        $paymentStatus = 'paid';
                        $stripePaymentIntentId = $paymentIntent->id;
                    } else {
                        return back()->with('error', 'Payment failed. Please try again.');
                    }
                } catch (\Stripe\Exception\CardException $e) {
                    DB::rollBack();
                    Log::error('Stripe Card Error: ' . $e->getMessage());
                    return back()->with('error', 'Card declined: ' . $e->getMessage());
                } catch (\Stripe\Exception\InvalidRequestException $e) {
                    DB::rollBack();
                    Log::error('Stripe Invalid Request: ' . $e->getMessage());
                    return back()->with('error', 'Invalid payment request. Please check your Stripe configuration.');
                } catch (\Stripe\Exception\AuthenticationException $e) {
                    DB::rollBack();
                    Log::error('Stripe Auth Error: ' . $e->getMessage() . ' - Check if Stripe Secret Key is valid');
                    return back()->with('error', 'Payment authentication failed. The Stripe API key may be invalid. Please contact support.');
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Stripe Error: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
                    return back()->with('error', 'Payment processing failed. Please try again or choose another payment method.');
                }
            }

            // Get pickup location details if pickup order
            $pickupLocation = null;
            if ($isPickup && $request->pickup_location_id) {
                $pickupLocation = PickupLocation::find($request->pickup_location_id);
            }
            
            // Create order
            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'user_id' => auth()->id(), // Will be null for guest orders
                'subtotal' => $subtotal,
                'tax' => $tax,
                'shipping_cost' => $shipping,
                'discount' => $discount,
                'total' => $total,
                'status' => 'pending',
                'payment_status' => $paymentStatus,
                'payment_method' => $request->payment_method,
                'transaction_id' => $stripePaymentIntentId,
                'shipping_name' => $request->shipping_name,
                'shipping_email' => $request->shipping_email,
                'shipping_phone' => $request->shipping_phone,
                'shipping_address' => $isPickup && $pickupLocation ? $pickupLocation->full_address : $request->shipping_address,
                'shipping_city' => $isPickup && $pickupLocation ? $pickupLocation->city : $request->shipping_city,
                'shipping_state' => $isPickup && $pickupLocation ? $pickupLocation->state : $request->shipping_state,
                'shipping_zip' => $isPickup && $pickupLocation ? $pickupLocation->zip : $request->shipping_zip,
                'shipping_country' => $isPickup && $pickupLocation ? $pickupLocation->country : $request->shipping_country,
                'notes' => $request->notes,
                'is_guest_order' => $isGuest,
                'delivery_type' => $isPickup ? 'pickup' : 'shipping',
                'pickup_location_id' => $isPickup ? $request->pickup_location_id : null,
            ]);

            // Create order items and update stock
            foreach ($cart->items as $item) {
                // Get price from variation if exists, otherwise from product
                $price = $item->product->current_price;
                if ($item->variation) {
                    $price = $item->variation->current_price ?? $price;
                }

                // Build variation options array for display
                $variationOptions = null;
                if ($item->variation && $item->variation->attributeValues) {
                    $variationOptions = $item->variation->attributeValues
                        ->mapWithKeys(fn($av) => [$av->attribute->name ?? 'Option' => $av->value])
                        ->toArray();
                }

                // Build product name with variation
                $productName = $item->product->name;
                if ($item->variation && $item->variation->variation_name) {
                    $productName .= ' - ' . $item->variation->variation_name;
                }

                // Build order item data
                $orderItemData = [
                    'product_id' => $item->product_id,
                    'product_name' => $productName,
                    'price' => $price,
                    'quantity' => $item->quantity,
                    'total' => $price * $item->quantity,
                ];

                // Add variation fields if they exist in the database
                if (\Illuminate\Support\Facades\Schema::hasColumn('order_items', 'product_variation_id')) {
                    $orderItemData['product_variation_id'] = $item->product_variation_id;
                    $orderItemData['variation_options'] = $variationOptions;
                }

                $order->items()->create($orderItemData);

                // Decrease stock (from variation or product)
                if ($item->variation && $item->variation->manage_stock) {
                    $item->variation->decrement('quantity', $item->quantity);
                } else {
                    $item->product->decrement('quantity', $item->quantity);
                }
            }

            // Update coupon usage
            if ($coupon) {
                Coupon::where('code', $coupon['code'])->increment('used_count');
                session()->forget('coupon');
            }

            // Setup digital downloads for digital products
            \App\Http\Controllers\DownloadController::setupOrderDownloads($order);

            // Clear cart
            $cart->clear();
            
            // For guest orders, store order ID in session for access
            if ($isGuest) {
                session(['guest_order_id' => $order->id]);
                // Clear guest verification session
                session()->forget(['guest_email_verified', 'guest_verified_email']);
            }

            DB::commit();

            // Send order confirmation email to customer
            $this->sendOrderConfirmationEmail($order);
            
            // Send new order notification to admin
            $this->sendAdminOrderNotification($order);
            \App\Support\PartnerMailer::vendorNewOrder($order);

            // Handle redirect-based payment gateways
            $redirectGateways = ['paystack', 'flutterwave', 'sslcommerz', 'bkash', 'nagad', 'paytm', 'instamojo', 'payfast', 'mercadopago', 'iyzico', 'razorpay'];
            
            if (in_array($request->payment_method, $redirectGateways)) {
                // Store order in session for payment callback
                session(['pending_payment_order_id' => $order->id]);
                
                $gateway = match($request->payment_method) {
                    'paystack' => route('payment.paystack.init', $order),
                    'flutterwave' => route('payment.flutterwave.init', $order),
                    'sslcommerz' => route('payment.sslcommerz.init', $order),
                    'bkash' => route('payment.bkash.init', $order),
                    'razorpay' => route('payment.razorpay.init', $order),
                    default => null,
                };
                if (!$gateway) {
                    return redirect()->route('checkout.success', $order)->with('info', 'Please complete payment using ' . ucfirst($request->payment_method));
                }

                // The React checkout posts with Inertia: leave the app with a full page load so the gateway page can run.
                return $request->header('X-Inertia') ? \Inertia\Inertia::location($gateway) : redirect($gateway);
            }

            return redirect()->route('checkout.success', $order)->with('success', 'Order placed successfully!');

        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Checkout Error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());

            // The card was already charged but the order could not be saved: give the money back.
            if (!empty($stripePaymentIntentId)) {
                try {
                    \Stripe\Refund::create(['payment_intent' => $stripePaymentIntentId]);
                    \Log::warning("Checkout failed after payment; refunded Stripe payment {$stripePaymentIntentId}");
                    return back()->with('error', 'Something went wrong while saving your order. Your payment has been refunded; please try again.');
                } catch (\Throwable $refundError) {
                    \Log::critical("Checkout failed after payment and the refund failed for {$stripePaymentIntentId}: " . $refundError->getMessage());
                    return back()->with('error', 'Something went wrong while saving your order after payment. Please contact us with this reference: ' . $stripePaymentIntentId);
                }
            }

            return back()->with('error', 'Something went wrong. Please try again.');
        }
    }

    public function success(Order $order)
    {
        // For guest orders, check by session or email
        if (auth()->check()) {
            if ($order->user_id !== auth()->id()) {
                abort(403);
            }
        } else {
            // Guest user - verify by session
            $guestOrderId = session('guest_order_id');
            if ($order->id !== $guestOrderId) {
                abort(403);
            }
        }

        return \Inertia\Inertia::render('Storefront/CheckoutSuccess', \App\Support\CheckoutPage::success($order));
    }
    
    /**
     * Send OTP to guest email
     */
    public function sendGuestOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);
        
        if (!$this->isGuestCheckoutAllowed()) {
            return response()->json(['success' => false, 'message' => 'Guest checkout is not enabled.'], 403);
        }
        
        $email = $request->email;
        $otpLength = (int) setting('features.guest_otp_length', 6);
        $otpExpiry = (int) setting('features.guest_otp_expiry', 10);
        
        // Generate OTP
        $otp = str_pad(random_int(0, pow(10, $otpLength) - 1), $otpLength, '0', STR_PAD_LEFT);
        
        // Store OTP in cache with expiry
        $cacheKey = 'guest_checkout_otp_' . md5($email);
        Cache::put($cacheKey, [
            'otp' => $otp,
            'email' => $email,
            'attempts' => 0,
        ], now()->addMinutes($otpExpiry));
        
        // Send OTP email
        try {
            Mail::send('emails.guest-checkout-otp', ['otp' => $otp, 'expiry' => $otpExpiry], function ($message) use ($email) {
                $message->to($email)
                        ->subject('Your Checkout Verification Code - ' . setting('store.name', config('app.name')));
            });
            
            Log::info('Guest checkout OTP sent', ['email' => $email]);
            
            return response()->json([
                'success' => true,
                'message' => 'Verification code sent to your email.',
                'expiry' => $otpExpiry,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send guest OTP email', ['email' => $email, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to send verification code. Please try again.',
            ], 500);
        }
    }
    
    /**
     * Verify guest OTP
     */
    public function verifyGuestOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string',
        ]);
        
        $email = $request->email;
        $inputOtp = $request->otp;
        
        $cacheKey = 'guest_checkout_otp_' . md5($email);
        $otpData = Cache::get($cacheKey);
        
        if (!$otpData) {
            return response()->json([
                'success' => false,
                'message' => 'Verification code expired. Please request a new one.',
            ], 400);
        }
        
        // Check attempts
        if ($otpData['attempts'] >= 5) {
            Cache::forget($cacheKey);
            return response()->json([
                'success' => false,
                'message' => 'Too many failed attempts. Please request a new code.',
            ], 429);
        }
        
        // Verify OTP
        if ($otpData['otp'] !== $inputOtp) {
            // Increment attempts
            $otpData['attempts']++;
            Cache::put($cacheKey, $otpData, now()->addMinutes((int) setting('features.guest_otp_expiry', 10)));
            
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification code. Please try again.',
                'attempts_remaining' => 5 - $otpData['attempts'],
            ], 400);
        }
        
        // OTP verified - store in session
        session(['guest_email_verified' => true, 'guest_verified_email' => $email]);
        Cache::forget($cacheKey);
        
        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully!',
        ]);
    }
    
    /**
     * Get country code from country name
     */
    private function getCountryCode($countryName)
    {
        $countries = [
            'Afghanistan' => 'AF', 'Albania' => 'AL', 'Algeria' => 'DZ', 'Andorra' => 'AD',
            'Angola' => 'AO', 'Argentina' => 'AR', 'Armenia' => 'AM', 'Australia' => 'AU', 
            'Austria' => 'AT', 'Azerbaijan' => 'AZ', 'Bahamas' => 'BS', 'Bahrain' => 'BH', 
            'Bangladesh' => 'BD', 'Barbados' => 'BB', 'Belarus' => 'BY', 'Belgium' => 'BE', 
            'Brazil' => 'BR', 'Brunei' => 'BN', 'Bulgaria' => 'BG', 'Cambodia' => 'KH', 
            'Cameroon' => 'CM', 'Canada' => 'CA', 'Chile' => 'CL', 'China' => 'CN', 
            'Colombia' => 'CO', 'Costa Rica' => 'CR', 'Croatia' => 'HR', 'Cuba' => 'CU', 
            'Cyprus' => 'CY', 'Czech Republic' => 'CZ', 'Denmark' => 'DK', 'Ecuador' => 'EC', 
            'Egypt' => 'EG', 'Estonia' => 'EE', 'Ethiopia' => 'ET', 'Fiji' => 'FJ', 
            'Finland' => 'FI', 'France' => 'FR', 'Georgia' => 'GE', 'Germany' => 'DE', 
            'Ghana' => 'GH', 'Greece' => 'GR', 'Hong Kong' => 'HK', 'Hungary' => 'HU', 
            'Iceland' => 'IS', 'India' => 'IN', 'Indonesia' => 'ID', 'Iran' => 'IR', 
            'Iraq' => 'IQ', 'Ireland' => 'IE', 'Israel' => 'IL', 'Italy' => 'IT', 
            'Jamaica' => 'JM', 'Japan' => 'JP', 'Jordan' => 'JO', 'Kazakhstan' => 'KZ', 
            'Kenya' => 'KE', 'Kuwait' => 'KW', 'Latvia' => 'LV', 'Lebanon' => 'LB', 
            'Lithuania' => 'LT', 'Luxembourg' => 'LU', 'Malaysia' => 'MY', 'Maldives' => 'MV', 
            'Malta' => 'MT', 'Mexico' => 'MX', 'Monaco' => 'MC', 'Morocco' => 'MA', 
            'Myanmar' => 'MM', 'Nepal' => 'NP', 'Netherlands' => 'NL', 'New Zealand' => 'NZ', 
            'Nigeria' => 'NG', 'Norway' => 'NO', 'Oman' => 'OM', 'Pakistan' => 'PK', 
            'Panama' => 'PA', 'Peru' => 'PE', 'Philippines' => 'PH', 'Poland' => 'PL', 
            'Portugal' => 'PT', 'Qatar' => 'QA', 'Romania' => 'RO', 'Russia' => 'RU', 
            'Saudi Arabia' => 'SA', 'Serbia' => 'RS', 'Singapore' => 'SG', 'Slovakia' => 'SK', 
            'Slovenia' => 'SI', 'South Africa' => 'ZA', 'South Korea' => 'KR', 'Spain' => 'ES', 
            'Sri Lanka' => 'LK', 'Sweden' => 'SE', 'Switzerland' => 'CH', 'Taiwan' => 'TW', 
            'Thailand' => 'TH', 'Turkey' => 'TR', 'Ukraine' => 'UA', 
            'United Arab Emirates' => 'AE', 'United Kingdom' => 'GB', 'United States' => 'US',
            'Uruguay' => 'UY', 'Venezuela' => 'VE', 'Vietnam' => 'VN', 'Zimbabwe' => 'ZW'
        ];
        
        return $countries[$countryName] ?? null;
    }

    /**
     * Send order confirmation email to customer
     */
    protected function sendOrderConfirmationEmail(Order $order): void
    {
        try {
            if (!setting('email.order_confirmation', true)) {
                return;
            }

            $recipientEmail = $order->user ? $order->user->email : $order->shipping_email;
            
            if ($recipientEmail) {
                Mail::to($recipientEmail)->send(new OrderConfirmation($order));
                Log::info('Order confirmation email sent for order: ' . $order->order_number);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send order confirmation email for order ' . $order->order_number . ': ' . $e->getMessage());
        }
    }

    /**
     * Send new order notification to admin
     */
    protected function sendAdminOrderNotification(Order $order): void
    {
        try {
            if (!setting('email.admin_new_order', true)) {
                return;
            }

            // Get admin email from settings or use first admin user
            $adminEmail = setting('email.admin_email');
            
            if (!$adminEmail) {
                $admin = User::where('is_admin', true)->first();
                $adminEmail = $admin ? $admin->email : null;
            }
            
            if ($adminEmail) {
                Mail::to($adminEmail)->send(new NewOrderAdmin($order));
                Log::info('Admin order notification sent for order: ' . $order->order_number);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send admin order notification for order ' . $order->order_number . ': ' . $e->getMessage());
        }
    }
}
