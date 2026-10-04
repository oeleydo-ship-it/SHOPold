<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Order;
use App\Models\Page;
use App\Models\Coupon;
use App\Models\Setting;
use App\Models\User;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\AiUsage;

class AiChatService
{
    protected string $provider;
    protected string $apiKey;
    protected string $model;
    protected array $storeKnowledge;
    protected ?int $lastPromptTokens = null;
    protected ?int $lastCompletionTokens = null;

    public function __construct()
    {
        // Use centralized AI settings first, fall back to features settings for backward compatibility
        $this->provider = setting('ai.provider') ?: setting('features.ai_chat_provider', 'openai');
        $this->model = setting('ai.model') ?: setting('features.ai_model', 'gpt-4o-mini');
        $apiKey = setting('ai.api_key') ?: setting('features.ai_api_key', '');
        $this->apiKey = $this->decryptSensitiveValue($apiKey);
        $this->storeKnowledge = $this->buildStoreKnowledge();
    }

    /**
     * Get bot configuration from settings
     */
    public static function getBotConfig(): array
    {
        return [
            'name' => setting('ai.chat_name', 'Shop Assistant'),
            'welcome_message' => setting('ai.chat_welcome', 'Hello! How can I help you today?'),
            'system_prompt' => setting('ai.chat_system_prompt', ''),
        ];
    }

    /**
     * Check if usage limit is exceeded
     */
    protected function checkUsageLimit(): bool
    {
        return AiUsage::isMonthlyLimitExceeded();
    }

    /**
     * Track usage after API call
     */
    protected function trackUsage(?int $userId = null, ?int $conversationId = null): void
    {
        if ($this->lastPromptTokens !== null && $this->lastCompletionTokens !== null) {
            try {
                AiUsage::log(
                    $this->provider,
                    $this->model,
                    'chat',
                    $this->lastPromptTokens,
                    $this->lastCompletionTokens,
                    $userId,
                    $conversationId
                );
            } catch (\Exception $e) {
                Log::warning('Failed to track AI usage: ' . $e->getMessage());
            }
        }
    }

    /**
     * Build comprehensive store knowledge base
     */
    protected function buildStoreKnowledge(): array
    {
        return Cache::remember('ai_chat_knowledge', 300, function () {
            return [
                'store' => $this->getStoreInfo(),
                'products' => $this->getProductKnowledge(),
                'categories' => $this->getCategoryKnowledge(),
                'shipping' => $this->getShippingInfo(),
                'payment' => $this->getPaymentInfo(),
                'policies' => $this->getPoliciesInfo(),
                'faqs' => $this->getFAQs(),
                'promotions' => $this->getPromotions(),
            ];
        });
    }

    protected function getStoreInfo(): array
    {
        return [
            'name' => setting('store.name', 'Our Store'),
            'description' => setting('store.description', ''),
            'email' => setting('store.email', ''),
            'phone' => setting('store.phone', ''),
            'address' => setting('store.address', ''),
            'currency' => setting('store.currency', 'USD'),
            'currency_symbol' => setting('store.currency_symbol', '$'),
            'business_hours' => setting('store.business_hours', 'Mon-Fri 9AM-6PM'),
            'support_response_time' => 'Usually within 24 hours',
        ];
    }

    protected function getProductKnowledge(): array
    {
        $columns = [
            'id',
            'name',
            'slug',
            'description',
            'short_description',
            'price',
            'sale_price',
            'sku',
            'category_id',
            'is_featured',
        ];

        $optionalColumns = [
            'brand_id',
            'product_type',
            'type',
            'quantity',
            'stock',
            'manage_stock',
            'stock_status',
        ];

        foreach ($optionalColumns as $col) {
            if (Schema::hasColumn('products', $col)) {
                $columns[] = $col;
            }
        }

        $products = Product::where('is_active', true)
            ->with(['category', 'brand'])
            ->select($columns)
            ->take(100)
            ->get();

        return $products->map(function ($product) {
            $qty = null;
            if (isset($product->quantity)) {
                $qty = (int) $product->quantity;
            } elseif (isset($product->stock)) {
                $qty = (int) $product->stock;
            }

            $manageStock = isset($product->manage_stock) ? (bool) $product->manage_stock : true;
            $stockStatusRaw = isset($product->stock_status) ? (string) $product->stock_status : null;

            $inStock = $manageStock
                ? (($qty ?? 0) > 0)
                : ($stockStatusRaw ? $stockStatusRaw === 'in_stock' : true);

            $stockLabel = $inStock
                ? (((int)($qty ?? 0)) > 10 ? 'In Stock' : (((int)($qty ?? 0)) > 0 ? 'Low Stock' : 'In Stock'))
                : 'Out of Stock';

            return [
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => strip_tags($product->short_description ?: substr($product->description ?? '', 0, 200)),
                'price' => $product->sale_price ?: $product->price,
                'original_price' => $product->price,
                'on_sale' => $product->sale_price && $product->sale_price < $product->price,
                'in_stock' => $inStock,
                'stock_status' => $stockLabel,
                'category' => $product->category?->name,
                'brand' => $product->brand?->name,
                'is_featured' => $product->is_featured,
                'type' => $product->product_type ?? ($product->type ?? null),
                'url' => "/shop/{$product->slug}",
            ];
        })->toArray();
    }

    protected function getCouponSuggestionsForAmount(float $amount): array
    {
        $currencySymbol = setting('store.currency_symbol', '$');

        $coupons = Coupon::where('is_active', true)
            ->where(function($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->select(['code', 'type', 'value', 'minimum_amount'])
            ->get();

        return $coupons->map(function($coupon) use ($currencySymbol, $amount) {
            $discount = $coupon->type === 'percent'
                ? "{$coupon->value}% off"
                : $currencySymbol . $coupon->value . ' off';

            $canUse = !$coupon->minimum_amount || $amount >= $coupon->minimum_amount;
            $message = $canUse
                ? 'Eligible for this item price'
                : 'Requires minimum ' . $currencySymbol . number_format((float)$coupon->minimum_amount, 2);

            return [
                'code' => $coupon->code,
                'discount' => $discount,
                'minimum' => $coupon->minimum_amount ? $currencySymbol . number_format((float)$coupon->minimum_amount, 2) : null,
                'can_use' => $canUse,
                'message' => $message,
            ];
        })->toArray();
    }

    protected function getCategoryKnowledge(): array
    {
        return Category::where('is_active', true)
            ->select(['name', 'slug', 'description'])
            ->get()
            ->map(fn($cat) => [
                'name' => $cat->name,
                'url' => "/category/{$cat->slug}",
                'description' => strip_tags($cat->description ?? ''),
            ])->toArray();
    }

    protected function getShippingInfo(): array
    {
        return [
            'free_shipping_threshold' => setting('shipping.free_shipping_threshold', 0),
            'standard_rate' => setting('shipping.standard_rate', 0),
            'express_rate' => setting('shipping.express_rate', 0),
            'processing_time' => setting('shipping.processing_time', '1-2 business days'),
            'standard_delivery' => setting('shipping.standard_delivery', '5-7 business days'),
            'express_delivery' => setting('shipping.express_delivery', '2-3 business days'),
            'international' => setting('shipping.international', false) ? 'Available' : 'Not available',
            'store_pickup' => setting('shipping.store_pickup', false) ? 'Available' : 'Not available',
            'tracking' => 'All orders include tracking information sent via email',
            'carriers' => 'We ship via major carriers including USPS, UPS, FedEx, and DHL',
        ];
    }

    protected function getPaymentInfo(): array
    {
        $methods = [];
        if (setting('payment.cod_enabled')) $methods[] = 'Cash on Delivery';
        if (setting('payment.stripe_enabled')) $methods[] = 'Credit/Debit Cards (Visa, Mastercard, Amex)';
        if (setting('payment.paypal_enabled')) $methods[] = 'PayPal';
        if (setting('payment.bank_enabled')) $methods[] = 'Bank Transfer';
        if (setting('payment.razorpay_enabled')) $methods[] = 'Razorpay (UPI, Netbanking)';

        return [
            'accepted_methods' => $methods,
            'security' => 'All payments are processed securely with SSL encryption',
            'currency' => setting('store.currency', 'USD'),
        ];
    }

    protected function getPoliciesInfo(): array
    {
        $returnDays = setting('store.return_days', 30);
        
        // Try to get policy pages
        $returnPolicy = Page::where('slug', 'return-policy')->first();
        $privacyPolicy = Page::where('slug', 'privacy-policy')->first();
        $termsPage = Page::where('slug', 'terms-conditions')->first();

        return [
            'return_policy' => [
                'days' => $returnDays,
                'summary' => "Items can be returned within {$returnDays} days of delivery",
                'conditions' => 'Items must be unused, in original packaging with tags attached',
                'refund_method' => 'Refunds processed to original payment method within 5-7 business days',
                'exchanges' => 'Exchanges available for different sizes/colors subject to availability',
            ],
            'warranty' => setting('store.warranty_info', 'Standard manufacturer warranty applies'),
            'privacy' => 'We protect your personal information and never share it with third parties',
        ];
    }

    protected function getFAQs(): array
    {
        return [
            [
                'question' => 'How do I track my order?',
                'answer' => 'You can track your order by logging into your account and viewing your order history, or use the tracking number sent to your email.',
            ],
            [
                'question' => 'How long does shipping take?',
                'answer' => "Standard shipping takes " . setting('shipping.standard_delivery', '5-7 business days') . ". Express shipping takes " . setting('shipping.express_delivery', '2-3 business days') . ".",
            ],
            [
                'question' => 'What is your return policy?',
                'answer' => "We accept returns within " . setting('store.return_days', 30) . " days of delivery. Items must be unused and in original packaging.",
            ],
            [
                'question' => 'Do you ship internationally?',
                'answer' => setting('shipping.international', false) ? 'Yes, we ship to many countries worldwide.' : 'Currently we only ship domestically.',
            ],
            [
                'question' => 'How can I contact customer support?',
                'answer' => "You can reach us at " . setting('store.email', 'support@store.com') . " or call " . setting('store.phone', 'our support line') . ".",
            ],
            [
                'question' => 'Is my payment information secure?',
                'answer' => 'Yes, all payments are processed through secure, encrypted connections. We never store your full card details.',
            ],
            [
                'question' => 'Can I cancel my order?',
                'answer' => 'Orders can be cancelled before they are shipped. Please contact us immediately if you need to cancel.',
            ],
            [
                'question' => 'Do you offer gift wrapping?',
                'answer' => 'Gift wrapping options may be available at checkout for select items.',
            ],
        ];
    }

    protected function getPromotions(): array
    {
        $activeCoupons = Coupon::where('is_active', true)
            ->where(function($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->select(['code', 'type', 'value', 'minimum_amount'])
            ->take(10)
            ->get();

        $freeShipping = setting('shipping.free_shipping_threshold', 0);

        return [
            'free_shipping' => $freeShipping > 0 ? "Free shipping on orders over " . setting('store.currency_symbol', '$') . $freeShipping : null,
            'active_coupons' => $activeCoupons->map(fn($c) => [
                'code' => $c->code,
                'discount' => $c->type === 'percent' ? "{$c->value}% off" : setting('store.currency_symbol', '$') . $c->value . ' off',
                'minimum' => $c->minimum_amount ? "Min. order " . setting('store.currency_symbol', '$') . $c->minimum_amount : null,
            ])->toArray(),
        ];
    }

    /**
     * Get customer-specific information for logged-in users
     */
    public function getCustomerContext(?int $userId): ?array
    {
        if (!$userId) {
            return null;
        }

        $user = User::find($userId);
        if (!$user) {
            return null;
        }

        $currencySymbol = setting('store.currency_symbol', '$');

        // Get recent orders
        $recentOrders = Order::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(fn($order) => [
                'order_number' => $order->order_number,
                'date' => $order->created_at->format('M d, Y'),
                'status' => ucfirst(str_replace('_', ' ', $order->status)),
                'total' => $currencySymbol . number_format($order->total, 2),
                'item_count' => $order->items()->count(),
            ])->toArray();

        // Get cart items
        $cartItems = [];
        $cartTotal = 0;
        $cart = Cart::where('user_id', $userId)->first();
        if ($cart) {
            $cartItems = $cart->items()->with('product')->get()->map(function($item) use ($currencySymbol) {
                $price = $item->product->sale_price ?: $item->product->price;
                return [
                    'name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'price' => $currencySymbol . number_format($price, 2),
                    'subtotal' => $currencySymbol . number_format($price * $item->quantity, 2),
                ];
            })->toArray();
            $cartTotal = $cart->items()->with('product')->get()->sum(function($item) {
                return ($item->product->sale_price ?: $item->product->price) * $item->quantity;
            });
        }

        // Get wishlist items
        $wishlistItems = [];
        if (method_exists($user, 'wishlist')) {
            $wishlistItems = $user->wishlist()->with('product')->take(5)->get()->map(fn($item) => [
                'name' => $item->product->name,
                'price' => $currencySymbol . number_format($item->product->sale_price ?: $item->product->price, 2),
                'url' => "/shop/{$item->product->slug}",
            ])->toArray();
        }

        // Get applicable coupons for this customer
        $applicableCoupons = $this->getApplicableCoupons($userId, $cartTotal);

        // Order stats
        $orderStats = [
            'total_orders' => Order::where('user_id', $userId)->count(),
            'total_spent' => $currencySymbol . number_format(Order::where('user_id', $userId)->where('status', '!=', 'cancelled')->sum('total'), 2),
            'pending_orders' => Order::where('user_id', $userId)->whereIn('status', ['pending', 'processing', 'shipped'])->count(),
        ];

        return [
            'name' => $user->name,
            'first_name' => explode(' ', $user->name)[0],
            'email' => $user->email,
            'member_since' => $user->created_at->format('M Y'),
            'recent_orders' => $recentOrders,
            'order_stats' => $orderStats,
            'cart_items' => $cartItems,
            'cart_total' => $currencySymbol . number_format($cartTotal, 2),
            'cart_item_count' => count($cartItems),
            'wishlist_items' => $wishlistItems,
            'applicable_coupons' => $applicableCoupons,
        ];
    }

    /**
     * Get applicable coupons for customer
     */
    protected function getApplicableCoupons(int $userId, float $cartTotal): array
    {
        $currencySymbol = setting('store.currency_symbol', '$');
        
        $coupons = Coupon::where('is_active', true)
            ->where(function($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->select(['code', 'type', 'value', 'minimum_amount'])
            ->get();

        return $coupons->map(function($coupon) use ($currencySymbol, $cartTotal) {
            $discount = $coupon->type === 'percent' 
                ? "{$coupon->value}% off" 
                : $currencySymbol . $coupon->value . ' off';
            
            $canUse = !$coupon->minimum_amount || $cartTotal >= $coupon->minimum_amount;
            $message = $canUse 
                ? "You can use this now!" 
                : "Add " . $currencySymbol . number_format($coupon->minimum_amount - $cartTotal, 2) . " more to use";

            return [
                'code' => $coupon->code,
                'discount' => $discount,
                'minimum' => $coupon->minimum_amount ? $currencySymbol . number_format($coupon->minimum_amount, 2) : null,
                'can_use' => $canUse,
                'message' => $message,
            ];
        })->toArray();
    }

    /**
     * Get order information (sanitized - no sensitive data)
     */
    public function getOrderInfo(string $orderNumber, ?string $email = null): ?array
    {
        $query = Order::where('order_number', $orderNumber);
        
        // If email provided, verify it matches for security
        if ($email) {
            $query->where('email', $email);
        }
        
        $order = $query->with(['items.product'])->first();
        
        if (!$order) {
            return null;
        }

        // Return sanitized order info - NO sensitive data
        return [
            'order_number' => $order->order_number,
            'status' => $order->status,
            'status_label' => ucfirst(str_replace('_', ' ', $order->status)),
            'placed_date' => $order->created_at->format('M d, Y'),
            'total' => number_format($order->total, 2),
            'item_count' => $order->items->count(),
            'items' => $order->items->map(fn($item) => [
                'name' => $item->product?->name ?? $item->product_name,
                'quantity' => $item->quantity,
                'price' => number_format($item->price, 2),
            ])->toArray(),
            'shipping_method' => $order->shipping_method ?? 'Standard',
            'tracking_number' => $order->tracking_number,
            'tracking_url' => $order->tracking_url,
            'estimated_delivery' => $order->estimated_delivery?->format('M d, Y'),
            'can_cancel' => in_array($order->status, ['pending', 'processing']),
            'can_return' => $order->status === 'delivered' && $order->delivered_at && 
                           $order->delivered_at->diffInDays(now()) <= setting('store.return_days', 30),
        ];
    }

    /**
     * Search products based on query
     */
    public function searchProducts(string $query, int $limit = 5): array
    {
        return Product::where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhere('short_description', 'like', "%{$query}%")
                  ->orWhere('sku', 'like', "%{$query}%");
            })
            ->with('category')
            ->take($limit)
            ->get()
            ->map(fn($p) => [
                'name' => $p->name,
                'price' => number_format($p->sale_price ?: $p->price, 2),
                'url' => "/shop/{$p->slug}",
                'category' => $p->category?->name,
                'in_stock' => ((int)($p->quantity ?? ($p->stock ?? 0))) > 0,
            ])->toArray();
    }

    public function getProductContextBySlug(string $slug): ?array
    {
        $product = Product::where('slug', $slug)
            ->where('is_active', true)
            ->with(['category', 'brand'])
            ->first();

        if (!$product) {
            return null;
        }

        return $this->buildProductContext($product);
    }

    protected function buildProductContext(Product $product): array
    {
        $currencySymbol = setting('store.currency_symbol', '$');
        $features = [];
        if (is_array($product->features ?? null)) {
            $features = array_values(array_filter(array_map(fn($v) => is_string($v) ? trim($v) : '', $product->features)));
        }

        return [
            'id' => $product->id,
            'name' => $product->translated_name ?? $product->name,
            'slug' => $product->slug,
            'category' => $product->category?->name,
            'brand' => $product->brand?->name,
            'price' => $currencySymbol . number_format((float)($product->price ?? 0), 2),
            'sale_price' => $product->sale_price ? $currencySymbol . number_format((float)$product->sale_price, 2) : null,
            'on_sale' => (bool)($product->sale_price && $product->sale_price < $product->price),
            'short_description' => strip_tags($product->translated_short_description ?? $product->short_description ?? ''),
            'features' => array_slice($features, 0, 8),
            'url' => "/shop/{$product->slug}",
        ];
    }

    /**
     * Main chat method
     */
    public function chat(string $message, array $conversationHistory = [], ?string $orderNumber = null, ?string $email = null, ?int $userId = null, ?array $productContext = null): array
    {
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'message' => 'AI chat is not configured. Please contact support.',
            ];
        }

        // Check usage limits
        if ($this->checkUsageLimit()) {
            return [
                'success' => false,
                'message' => 'Monthly AI usage limit reached. Please contact support or try again next month.',
            ];
        }

        // Get customer context if logged in
        $customerContext = $this->getCustomerContext($userId);

        // Check if user is asking about a specific order
        $orderInfo = null;
        if ($orderNumber || preg_match('/order\s*#?\s*([A-Z0-9-]+)/i', $message, $matches)) {
            $orderNum = $orderNumber ?: ($matches[1] ?? null);
            if ($orderNum) {
                $orderInfo = $this->getOrderInfo($orderNum, $email);
            }
        }

        // Check if user is searching for products
        $productResults = null;
        if (preg_match('/(?:looking for|find|search|show me|want|need|buy)\s+(.+?)(?:\?|$)/i', $message, $matches)) {
            $searchQuery = trim($matches[1]);
            if (strlen($searchQuery) > 2) {
                $productResults = $this->searchProducts($searchQuery);
            }
        }

        // Build the system prompt with full knowledge
        $systemPrompt = $this->buildSystemPrompt($orderInfo, $productResults, $customerContext, $productContext);
        
        try {
            $response = match ($this->provider) {
                'openai' => $this->chatWithOpenAI($message, $conversationHistory, $systemPrompt),
                'anthropic' => $this->chatWithAnthropic($message, $conversationHistory, $systemPrompt),
                'google' => $this->chatWithGoogle($message, $conversationHistory, $systemPrompt),
                default => $this->chatWithOpenAI($message, $conversationHistory, $systemPrompt),
            };

            // Track usage after successful API call
            $this->trackUsage($userId);

            return [
                'success' => true,
                'message' => $response,
                'products' => $productResults,
                'order' => $orderInfo ? ['status' => $orderInfo['status_label'], 'tracking' => $orderInfo['tracking_number']] : null,
            ];
        } catch (\Exception $e) {
            Log::error('AI Chat Error: ' . $e->getMessage());

            $msg = $e->getMessage();
            if (str_contains($msg, 'cURL error 60') || str_contains($msg, 'SSL certificate')) {
                return [
                    'success' => false,
                    'message' => 'SSL certificate error connecting to the AI provider. In Admin → Settings → Features → AI-Powered Chat Assistant, disable “Verify SSL Certificates” for local testing, or install a proper CA bundle for production.',
                ];
            }

            if (str_contains($msg, 'OpenAI API key is invalid')) {
                return [
                    'success' => false,
                    'message' => 'Your OpenAI API key is invalid. Please generate a valid OpenAI API key (starts with "sk-") and save it in Admin → Settings → Features → AI-Powered Chat Assistant.',
                ];
            }

            return [
                'success' => false,
                'message' => 'Sorry, I encountered an error. Please try again or contact our support team.',
            ];
        }
    }

    /**
     * Build comprehensive system prompt with store knowledge
     */
    protected function buildSystemPrompt(?array $orderInfo = null, ?array $productResults = null, ?array $customerContext = null, ?array $productContext = null): string
    {
        $store = $this->storeKnowledge['store'];
        $shipping = $this->storeKnowledge['shipping'];
        $payment = $this->storeKnowledge['payment'];
        $policies = $this->storeKnowledge['policies'];
        $faqs = $this->storeKnowledge['faqs'];
        $promotions = $this->storeKnowledge['promotions'];
        $categories = $this->storeKnowledge['categories'];
        $products = $this->storeKnowledge['products'];

        // Format product catalog (limited for context)
        $featuredProducts = collect($products)->where('is_featured', true)->take(10)->values();
        $productList = $featuredProducts->map(fn($p) => "- {$p['name']}: {$store['currency_symbol']}{$p['price']} ({$p['stock_status']}) - {$p['url']}")->implode("\n");
        
        // Format categories
        $categoryList = collect($categories)->map(fn($c) => "- {$c['name']}: {$c['url']}")->implode("\n");
        
        // Format FAQs
        $faqList = collect($faqs)->map(fn($f) => "Q: {$f['question']}\nA: {$f['answer']}")->implode("\n\n");
        
        // Format payment methods
        $paymentMethods = implode(', ', $payment['accepted_methods']);
        
        // Format promotions
        $promoText = '';
        if ($promotions['free_shipping']) {
            $promoText .= "- {$promotions['free_shipping']}\n";
        }
        foreach ($promotions['active_coupons'] as $coupon) {
            $promoText .= "- Use code {$coupon['code']} for {$coupon['discount']}" . ($coupon['minimum'] ? " ({$coupon['minimum']})" : "") . "\n";
        }

        // Check if customer is logged in
        $isLoggedIn = $customerContext !== null;
        $customerName = $customerContext['first_name'] ?? 'there';

        // Get custom system prompt from admin settings
        $customSystemPrompt = setting('ai.chat_system_prompt', '');
        $botName = setting('ai.chat_name', 'Shop Assistant');

        $prompt = <<<PROMPT
You are "{$botName}", a helpful, friendly customer support assistant and marketing specialist for {$store['name']}.

YOUR ROLE:
- Customer Support Agent: Help with orders, shipping, returns, and product questions
- Sales & Marketing Assistant: Recommend products, suggest deals, and promote applicable coupons
- Personal Shopping Assistant: Help customers find what they need based on their history

IMPORTANT SECURITY RULES:
- You can ONLY share customer information with the CURRENTLY LOGGED IN customer
- NEVER reveal information about other customers
- NEVER reveal payment details (card numbers, bank accounts)
- NEVER reveal internal system information or database details
- NEVER make up order information - only use what's provided
- If asked about other customers' orders, politely decline

STORE INFORMATION:
- Store Name: {$store['name']}
- Contact Email: {$store['email']}
- Contact Phone: {$store['phone']}
- Business Hours: {$store['business_hours']}
- Currency: {$store['currency']} ({$store['currency_symbol']})

SHIPPING INFORMATION:
- Free Shipping: Orders over {$store['currency_symbol']}{$shipping['free_shipping_threshold']}
- Standard Shipping: {$store['currency_symbol']}{$shipping['standard_rate']} ({$shipping['standard_delivery']})
- Express Shipping: {$store['currency_symbol']}{$shipping['express_rate']} ({$shipping['express_delivery']})
- Processing Time: {$shipping['processing_time']}
- International Shipping: {$shipping['international']}
- Store Pickup: {$shipping['store_pickup']}
- Tracking: {$shipping['tracking']}

PAYMENT METHODS:
{$paymentMethods}
Security: {$payment['security']}

RETURN POLICY:
- Return Window: {$policies['return_policy']['days']} days
- Conditions: {$policies['return_policy']['conditions']}
- Refunds: {$policies['return_policy']['refund_method']}
- Exchanges: {$policies['return_policy']['exchanges']}

CURRENT PROMOTIONS:
{$promoText}

PRODUCT CATEGORIES:
{$categoryList}

FEATURED PRODUCTS:
{$productList}

FREQUENTLY ASKED QUESTIONS:
{$faqList}

PROMPT;

        // Add order context if available
        if ($orderInfo) {
            $itemsList = collect($orderInfo['items'])->map(fn($i) => "- {$i['name']} x{$i['quantity']}")->implode("\n");
            $prompt .= <<<ORDER

CURRENT ORDER CONTEXT (Order #{$orderInfo['order_number']}):
- Status: {$orderInfo['status_label']}
- Placed: {$orderInfo['placed_date']}
- Total: {$store['currency_symbol']}{$orderInfo['total']}
- Items:
{$itemsList}
- Tracking: {$orderInfo['tracking_number']}
- Can Cancel: {$orderInfo['can_cancel']}
- Can Return: {$orderInfo['can_return']}
ORDER;
        }

        // Add product search results if available
        if ($productResults && count($productResults) > 0) {
            $searchResults = collect($productResults)->map(fn($p) => "- {$p['name']}: {$store['currency_symbol']}{$p['price']} - {$p['url']}")->implode("\n");
            $prompt .= <<<SEARCH

PRODUCT SEARCH RESULTS:
{$searchResults}
SEARCH;
        }

        if ($productContext) {
            $amount = null;
            if (isset($productContext['sale_price_value']) && is_numeric($productContext['sale_price_value'])) {
                $amount = (float) $productContext['sale_price_value'];
            } elseif (isset($productContext['price_value']) && is_numeric($productContext['price_value'])) {
                $amount = (float) $productContext['price_value'];
            }

            $featuresList = collect($productContext['features'] ?? [])->map(fn($f) => "- {$f}")->implode("\n");
            $couponSuggestionsText = '';
            if ($amount !== null) {
                $couponSuggestions = $this->getCouponSuggestionsForAmount($amount);
                $couponSuggestionsText = collect($couponSuggestions)
                    ->map(fn($c) => "- Code: {$c['code']} - {$c['discount']}" . ($c['minimum'] ? " (Min: {$c['minimum']})" : "") . " - {$c['message']}")
                    ->implode("\n");
            }
            $prompt .= "\n\n=== CURRENTLY VIEWING PRODUCT ===\n";
            $prompt .= "Name: " . ($productContext['name'] ?? '') . "\n";
            $prompt .= "URL: " . ($productContext['url'] ?? '') . "\n";
            $prompt .= "Category: " . ($productContext['category'] ?? '') . "\n";
            $prompt .= "Brand: " . ($productContext['brand'] ?? '') . "\n";
            $prompt .= "Price: " . ($productContext['price'] ?? '') . "\n";
            if (!empty($productContext['sale_price'])) {
                $prompt .= "Sale Price: " . ($productContext['sale_price'] ?? '') . "\n";
            }
            if (!empty($productContext['short_description'])) {
                $prompt .= "Summary: " . ($productContext['short_description'] ?? '') . "\n";
            }
            if (!empty($featuresList)) {
                $prompt .= "Key Features:\n{$featuresList}\n";
            }
            if (!empty($couponSuggestionsText)) {
                $prompt .= "Coupon Options For This Item Price:\n{$couponSuggestionsText}\n";
            }

            $prompt .= "\nINSTRUCTIONS FOR THIS PRODUCT:\n";
            $prompt .= "- Proactively mention 3-6 key features in a short, friendly way\n";
            $prompt .= "- Mention the best active coupon/promotion that can apply at checkout (if any)\n";
            $prompt .= "- Include the product link (URL) and encourage adding to cart\n";
        }

        // Add customer context if logged in
        if ($customerContext) {
            $recentOrdersList = collect($customerContext['recent_orders'])->map(fn($o) => "- Order #{$o['order_number']} ({$o['date']}): {$o['status']} - {$o['total']}")->implode("\n");
            $cartItemsList = collect($customerContext['cart_items'])->map(fn($i) => "- {$i['name']} x{$i['quantity']} = {$i['subtotal']}")->implode("\n");
            $wishlistList = collect($customerContext['wishlist_items'])->map(fn($i) => "- {$i['name']}: {$i['price']} - {$i['url']}")->implode("\n");
            $couponsList = collect($customerContext['applicable_coupons'])->map(fn($c) => "- Code: {$c['code']} - {$c['discount']}" . ($c['minimum'] ? " (Min: {$c['minimum']})" : "") . " - {$c['message']}")->implode("\n");

            $prompt .= "\n\n=== CURRENT CUSTOMER CONTEXT (LOGGED IN) ===\n";
            $prompt .= "Customer Name: {$customerContext['name']}\n";
            $prompt .= "First Name (use this for greeting): {$customerContext['first_name']}\n";
            $prompt .= "Member Since: {$customerContext['member_since']}\n\n";
            $prompt .= "ORDER HISTORY:\n";
            $prompt .= "- Total Orders: {$customerContext['order_stats']['total_orders']}\n";
            $prompt .= "- Total Spent: {$customerContext['order_stats']['total_spent']}\n";
            $prompt .= "- Pending Orders: {$customerContext['order_stats']['pending_orders']}\n\n";
            $prompt .= "RECENT ORDERS:\n{$recentOrdersList}\n\n";
            $prompt .= "CURRENT CART ({$customerContext['cart_item_count']} items - Total: {$customerContext['cart_total']}):\n{$cartItemsList}\n\n";
            $prompt .= "WISHLIST ITEMS:\n{$wishlistList}\n\n";
            $prompt .= "AVAILABLE COUPONS FOR THIS CUSTOMER:\n{$couponsList}\n\n";
            $prompt .= "CUSTOMER ENGAGEMENT TIPS:\n";
            $prompt .= "- Address the customer by their first name ({$customerContext['first_name']})\n";
            $prompt .= "- If they have items in cart, encourage checkout and suggest applicable coupons\n";
            $prompt .= "- If their cart qualifies for a coupon, proactively mention it!\n";
            $prompt .= "- Recommend products based on their order history and wishlist\n";
            $prompt .= "- For returning customers, thank them for their loyalty\n";
            $prompt .= "- If they have pending orders, offer to help track them\n";
        } else {
            $prompt .= "\n\n=== GUEST USER (NOT LOGGED IN) ===\n";
            $prompt .= "- This customer is NOT logged in\n";
            $prompt .= "- You do NOT have access to their personal information, orders, or cart\n";
            $prompt .= "- Encourage them to log in or create an account to get personalized assistance\n";
            $prompt .= "- If they ask about their orders or account info, politely ask them to log in first\n";
            $prompt .= "- You can still help with general product questions, shipping info, and store policies\n";
        }

        $prompt .= <<<GUIDELINES

RESPONSE GUIDELINES:
1. Be friendly, helpful, and professional
2. Keep responses concise but informative
3. **CRITICAL**: When recommending products, ALWAYS include the product URL in markdown format: [Product Name](/shop/product-slug)
4. For order issues, offer to help or suggest contacting support
5. Recommend products when appropriate
6. If you don't know something specific, suggest contacting support
7. Use the store's currency symbol when mentioning prices
8. Proactively mention current promotions and applicable coupons when relevant
9. For logged-in customers: personalize responses, suggest relevant products, and offer applicable discounts
10. Act as both support agent AND marketing specialist - help customers AND drive sales
11. When listing products, format each as: **[Product Name](/shop/slug)** - Price - Description
GUIDELINES;

        // Append custom system prompt from admin settings if provided
        if (!empty($customSystemPrompt)) {
            $prompt .= "\n\nADDITIONAL INSTRUCTIONS FROM STORE ADMIN:\n" . $customSystemPrompt;
        }

        return $prompt;
    }

    protected function chatWithOpenAI(string $message, array $history, string $systemPrompt): string
    {
        if (!str_starts_with($this->apiKey, 'sk-')) {
            throw new \Exception('OpenAI API key is invalid');
        }

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        foreach ($history as $msg) {
            $messages[] = [
                'role' => $msg['role'],
                'content' => $msg['content'],
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $message];

        @set_time_limit(120); // the AI provider can take longer than PHP's default 30 s limit
        $http = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->connectTimeout(10)->timeout(30);

        if (!setting('features.ai_ssl_verify', true) || app()->environment('local')) {
            $http = $http->withOptions(['verify' => false]);
        }

        $maxTokens = (int) setting('ai.max_tokens', 1000);
        $temperature = (float) setting('ai.temperature', 0.7);

        $response = $http->post('https://api.openai.com/v1/chat/completions', [
            'model' => $this->model,
            'messages' => $messages,
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
        ]);

        if ($response->failed()) {
            Log::error('OpenAI API Error', ['response' => $response->body()]);

            $code = $response->json('error.code');
            if (in_array($code, ['invalid_jwt', 'invalid_api_key'], true) || $response->status() === 401) {
                throw new \Exception('OpenAI API key is invalid');
            }

            throw new \Exception('OpenAI API error');
        }

        // Capture token usage for tracking
        $usage = $response->json('usage');
        if ($usage) {
            $this->lastPromptTokens = (int) ($usage['prompt_tokens'] ?? 0);
            $this->lastCompletionTokens = (int) ($usage['completion_tokens'] ?? 0);
        }

        return $response->json('choices.0.message.content', 'Sorry, I could not process your request.');
    }

    protected function chatWithAnthropic(string $message, array $history, string $systemPrompt): string
    {
        $messages = [];

        foreach ($history as $msg) {
            $messages[] = [
                'role' => $msg['role'],
                'content' => $msg['content'],
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $message];

        @set_time_limit(120); // the AI provider can take longer than PHP's default 30 s limit
        $http = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'Content-Type' => 'application/json',
            'anthropic-version' => '2023-06-01',
        ])->connectTimeout(10)->timeout(30);

        if (!setting('features.ai_ssl_verify', true) || app()->environment('local')) {
            $http = $http->withOptions(['verify' => false]);
        }

        $maxTokens = (int) setting('ai.max_tokens', 1000);

        $response = $http->post('https://api.anthropic.com/v1/messages', [
            'model' => $this->model,
            'max_tokens' => $maxTokens,
            'system' => $systemPrompt,
            'messages' => $messages,
        ]);

        if ($response->failed()) {
            Log::error('Anthropic API Error', ['response' => $response->body()]);
            throw new \Exception('Anthropic API error');
        }

        // Capture token usage for tracking
        $usage = $response->json('usage');
        if ($usage) {
            $this->lastPromptTokens = (int) ($usage['input_tokens'] ?? 0);
            $this->lastCompletionTokens = (int) ($usage['output_tokens'] ?? 0);
        }

        return $response->json('content.0.text', 'Sorry, I could not process your request.');
    }

    protected function chatWithGoogle(string $message, array $history, string $systemPrompt): string
    {
        $contents = [];

        // System context as first exchange
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $systemPrompt . "\n\nPlease acknowledge you understand your role and the store information."]],
        ];
        $contents[] = [
            'role' => 'model',
            'parts' => [['text' => "I understand. I'm the customer support assistant for " . $this->storeKnowledge['store']['name'] . ". I'm ready to help with product inquiries, orders, shipping, returns, and general questions. How can I assist you today?"]],
        ];

        foreach ($history as $msg) {
            $contents[] = [
                'role' => $msg['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $msg['content']]],
            ];
        }

        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $message]],
        ];

        $apiKey = $this->apiKey;
        $model = $this->model;
        $maxTokens = (int) setting('ai.max_tokens', 1000);
        $temperature = (float) setting('ai.temperature', 0.7);
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        @set_time_limit(120); // the AI provider can take longer than PHP's default 30 s limit
        $http = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->connectTimeout(10)->timeout(30);

        if (!setting('features.ai_ssl_verify', true) || app()->environment('local')) {
            $http = $http->withOptions(['verify' => false]);
        }

        $response = $http->post($url, [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => $temperature,
                'maxOutputTokens' => $maxTokens,
            ]
        ]);

        if ($response->failed()) {
            Log::error('Google AI API Error', ['response' => $response->body()]);
            throw new \Exception('Google AI API error');
        }

        // Capture token usage for tracking (Google returns token count in usageMetadata)
        $usageMetadata = $response->json('usageMetadata');
        if ($usageMetadata) {
            $this->lastPromptTokens = (int) ($usageMetadata['promptTokenCount'] ?? 0);
            $this->lastCompletionTokens = (int) ($usageMetadata['candidatesTokenCount'] ?? 0);
        }

        return $response->json('candidates.0.content.parts.0.text', 'Sorry, I could not process your request.');
    }

    /**
     * Clear the knowledge cache (call when store data changes)
     */
    public static function clearKnowledgeCache(): void
    {
        Cache::forget('ai_chat_knowledge');
    }

    protected function decryptSensitiveValue(?string $value): string
    {
        if (empty($value)) {
            return '';
        }

        if (str_starts_with($value, 'eyJ')) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                Log::warning('Failed to decrypt AI API key: ' . $e->getMessage());
                return $value;
            }
        }

        return $value;
    }
}
