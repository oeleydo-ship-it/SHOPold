<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ShippingZoneController;
use App\Http\Controllers\Admin\DeliveryController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\AiController;
use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\Admin\LoyaltyController;
use App\Http\Controllers\Admin\HelpdeskController as AdminHelpdeskController;
use App\Http\Controllers\Admin\AiConversationController;
use App\Http\Controllers\Admin\ShippingLabelController;
use App\Http\Controllers\HelpdeskController;

use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\StockController;
use App\Http\Controllers\BlogController as FrontBlogController;
use App\Http\Controllers\PageController as FrontPageController;

// Installation detection and redirect
Route::get('/check-installation', function () {
    $detector = new \App\Services\InstallationDetectionService();
    
    if (!$detector->isInstalled()) {
        return redirect('/install');
    }
    
    return redirect('/');
});

// Installation Routes (remove after installation for security)
Route::prefix('install')->name('install.')->group(function () {
    Route::get('/', [InstallController::class, 'welcome'])->name('welcome');
    Route::get('/requirements', [InstallController::class, 'requirements'])->name('requirements');
    Route::get('/database', [InstallController::class, 'database'])->name('database');
    Route::post('/database', [InstallController::class, 'saveDatabase'])->name('database.save');
    Route::get('/admin', [InstallController::class, 'admin'])->name('admin');
    Route::post('/install', [InstallController::class, 'install'])->name('run');
    Route::get('/complete', [InstallController::class, 'complete'])->name('complete');
    
    // Auto-install API endpoints for control panel integration
    Route::get('/status', [InstallController::class, 'status'])->name('status');
    Route::post('/auto', [InstallController::class, 'autoInstall'])->name('auto');
    
    // Installation detection endpoint
    Route::get('/detect', function () {
        $detector = new \App\Services\InstallationDetectionService();
        return response()->json($detector->getInstallationStatus());
    })->name('detect');
});

// Locale Switching
Route::get('/locale/{locale}', [\App\Http\Controllers\LocaleController::class, 'switch'])->name('locale.switch');

// Currency Switching
Route::get('/currency/{currency}', function (string $currency) {
    $baseCurrency = strtoupper((string) setting('payment.currency', 'USD'));
    $currency = strtoupper($currency);

    if (!setting('features.multi_currency', false)) {
        return redirect()->back();
    }

    $enabled = setting('payment.enabled_currencies', [$baseCurrency]);
    if (is_string($enabled)) {
        $enabled = json_decode($enabled, true) ?? [$baseCurrency];
    }
    if (!is_array($enabled) || empty($enabled)) {
        $enabled = [$baseCurrency];
    }
    $enabled = array_values(array_unique(array_map(fn ($c) => strtoupper((string) $c), $enabled)));
    if (!in_array($baseCurrency, $enabled, true)) {
        $enabled[] = $baseCurrency;
    }

    if (in_array($currency, $enabled, true)) {
        session(['currency' => $currency]);
    }

    return redirect()->back();
})->name('currency.switch');

// Auto-detection for fresh installations
Route::get('/auto-detect', function () {
    $detector = new \App\Services\InstallationDetectionService();
    
    if (!$detector->isInstalled()) {
        // Check if installer files exist
        if (file_exists(public_path('install.php'))) {
            return redirect('/install.php');
        } elseif (file_exists(base_path('install.php'))) {
            return redirect('/install.php');
        } else {
            return redirect('/install');
        }
    }
    
    return redirect('/');
});

// Translation API (spends the AI key, so admins only and rate limited)
Route::middleware(['auth', 'admin', 'throttle:30,1'])->group(function () {
    Route::post('/api/translate', [\App\Http\Controllers\TranslationController::class, 'translate'])->name('translate');
    Route::post('/api/translate/batch', [\App\Http\Controllers\TranslationController::class, 'translateBatch'])->name('translate.batch');
    Route::get('/api/translate/status', [\App\Http\Controllers\TranslationController::class, 'status'])->name('translate.status');
});

// AI Chat API
Route::post('/api/ai-chat', [\App\Http\Controllers\AiChatController::class, 'chat'])->name('ai.chat')->middleware('module:ai_chat');
Route::post('/api/ai-chat/search', [\App\Http\Controllers\AiChatController::class, 'searchProducts'])->name('ai.chat.search')->middleware('module:ai_chat');
Route::post('/api/ai-chat/order-status', [\App\Http\Controllers\AiChatController::class, 'orderStatus'])->name('ai.chat.order-status')->middleware('module:ai_chat');
Route::post('/api/ai-chat/clear-cache', [\App\Http\Controllers\AiChatController::class, 'clearCache'])->name('ai.chat.clear-cache')->middleware(['module:ai_chat', 'auth']);
Route::post('/api/ai-chat/store-message', [\App\Http\Controllers\AiChatController::class, 'storeMessage'])->name('ai.chat.store-message')->middleware('module:ai_chat');
Route::post('/api/ai-chat/create-ticket', [\App\Http\Controllers\AiChatController::class, 'createTicket'])->name('ai.chat.create-ticket')->middleware(['module:ai_chat', 'auth']);
Route::post('/api/ai-chat/end-conversation', [\App\Http\Controllers\AiChatController::class, 'endConversation'])->name('ai.chat.end-conversation')->middleware('module:ai_chat');
Route::get('/api/ai-chat/history', [\App\Http\Controllers\AiChatController::class, 'loadHistory'])->name('ai.chat.history')->middleware('module:ai_chat');
Route::post('/api/ai-chat/new-conversation', [\App\Http\Controllers\AiChatController::class, 'startNewConversation'])->name('ai.chat.new-conversation')->middleware('module:ai_chat');

// Stripe Webhook (must be outside auth middleware)
Route::post('/stripe/webhook', [\App\Http\Controllers\StripeWebhookController::class, 'handle'])->name('stripe.webhook');

// Public Routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/sitemap.xml', [\App\Http\Controllers\SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [\App\Http\Controllers\SeoController::class, 'robots'])->name('robots');
// Resized WebP copies of public-storage images (fixed widths only)
Route::get('/img/{width}/{path}', [\App\Http\Controllers\ImageController::class, 'show'])
    ->whereNumber('width')->where('path', '.+')->name('image.resized')
    ->withoutMiddleware('web'); // no session or cookies, so browsers and CDNs can cache the files
Route::post('/contact', [HomeController::class, 'contactSubmit'])->name('contact.submit');
Route::post('/newsletter/subscribe', [HomeController::class, 'newsletterSubscribe'])->name('newsletter.subscribe')->middleware('module:newsletter');

// Dynamic Pages - About and Contact now use the pages system
Route::get('/about', [FrontPageController::class, 'about'])->name('about');
Route::get('/contact', [FrontPageController::class, 'contact'])->name('contact');
Route::get('/page/{slug}', [FrontPageController::class, 'show'])->name('page.show');

// Shop Routes
Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
Route::get('/shop/category/{slug}', [ShopController::class, 'category'])->name('shop.category');
Route::get('/shop/vendor/{slug}', [ShopController::class, 'vendor'])->name('shop.vendor')->middleware('module:multi_vendor');
Route::get('/shop/{slug}', [ShopController::class, 'show'])->name('shop.show');

// Blog Routes (Frontend)
Route::get('/blog', [FrontBlogController::class, 'index'])->name('blog.index')->middleware('module:blog');
Route::get('/blog/category/{slug}', [FrontBlogController::class, 'category'])->name('blog.category')->middleware('module:blog');
Route::get('/blog/{slug}', [FrontBlogController::class, 'show'])->name('blog.show')->middleware('module:blog');
Route::post('/blog/{slug}/comments', [FrontBlogController::class, 'storeComment'])->name('blog.comments.store')->middleware(['module:blog', 'throttle:5,1']);

// Cart Routes
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/buy-now', [CartController::class, 'buyNow'])->name('cart.buy-now');
Route::post('/cart/update', [CartController::class, 'update'])->name('cart.update');
Route::post('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');
Route::post('/cart/coupon', [CartController::class, 'applyCoupon'])->name('cart.coupon');
Route::delete('/cart/coupon', [CartController::class, 'removeCoupon'])->name('cart.coupon.remove');
Route::get('/cart/count', [CartController::class, 'getCartCount'])->name('cart.count');

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Email Verification Routes
Route::get('/email/verify', [AuthController::class, 'showVerificationNotice'])
    ->middleware('auth')
    ->name('verification.notice');

Route::get('/email/verify/{token}', [AuthController::class, 'verifyEmail'])
    ->name('verification.verify');

Route::post('/email/verification-notification', [AuthController::class, 'resendVerificationEmail'])
    ->middleware(['auth', 'throttle:6,1'])
    ->name('verification.send');

// Auth Routes
// Checkout routes (accessible by guests if guest checkout is enabled)
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'process'])->name('checkout.process');
Route::post('/checkout/shipping-methods', [CheckoutController::class, 'getShippingMethods'])->name('checkout.shipping-methods');
Route::get('/checkout/success/{order}', [CheckoutController::class, 'success'])->name('checkout.success');

// Guest checkout OTP routes
Route::post('/checkout/guest/send-otp', [CheckoutController::class, 'sendGuestOtp'])->name('checkout.guest.send-otp');
Route::post('/checkout/guest/verify-otp', [CheckoutController::class, 'verifyGuestOtp'])->name('checkout.guest.verify-otp');

// Payment Gateway Callbacks
Route::prefix('payment')->name('payment.')->group(function () {
    // Paystack
    Route::get('/paystack/init/{order}', [\App\Http\Controllers\PaymentController::class, 'paystackInit'])->name('paystack.init');
    Route::get('/paystack/callback', [\App\Http\Controllers\PaymentController::class, 'paystackCallback'])->name('paystack.callback');
    
    // Flutterwave
    Route::get('/flutterwave/init/{order}', [\App\Http\Controllers\PaymentController::class, 'flutterwaveInit'])->name('flutterwave.init');
    Route::get('/flutterwave/callback', [\App\Http\Controllers\PaymentController::class, 'flutterwaveCallback'])->name('flutterwave.callback');
    
    // SSLCommerz
    Route::get('/sslcommerz/init/{order}', [\App\Http\Controllers\PaymentController::class, 'sslcommerzInit'])->name('sslcommerz.init');
    Route::post('/sslcommerz/success', [\App\Http\Controllers\PaymentController::class, 'sslcommerzSuccess'])->name('sslcommerz.success');
    Route::post('/sslcommerz/fail', [\App\Http\Controllers\PaymentController::class, 'sslcommerzFail'])->name('sslcommerz.fail');
    Route::post('/sslcommerz/cancel', [\App\Http\Controllers\PaymentController::class, 'sslcommerzCancel'])->name('sslcommerz.cancel');
    
    // bKash
    Route::get('/bkash/init/{order}', [\App\Http\Controllers\PaymentController::class, 'bkashInit'])->name('bkash.init');
    Route::get('/bkash/callback', [\App\Http\Controllers\PaymentController::class, 'bkashCallback'])->name('bkash.callback');
    
    // Razorpay
    Route::get('/razorpay/init/{order}', [\App\Http\Controllers\PaymentController::class, 'razorpayInit'])->name('razorpay.init');
    Route::post('/razorpay/callback', [\App\Http\Controllers\PaymentController::class, 'razorpayCallback'])->name('razorpay.callback');
    
    // Generic handlers
    Route::get('/success/{order}', [\App\Http\Controllers\PaymentController::class, 'paymentSuccess'])->name('success');
    Route::get('/failed', [\App\Http\Controllers\PaymentController::class, 'paymentFailed'])->name('failed');
    Route::get('/cancelled', [\App\Http\Controllers\PaymentController::class, 'paymentCancelled'])->name('cancelled');
});

// Digital Downloads (requires auth)
Route::get('/download/{token}/{file}', [\App\Http\Controllers\DownloadController::class, 'download'])
    ->name('download.file')
    ->middleware('module:digital_products')
    ->middleware('auth');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    // Account
    Route::prefix('account')->name('account.')->group(function () {
        Route::get('/', [AccountController::class, 'dashboard'])->name('dashboard');
        Route::get('/orders', [AccountController::class, 'orders'])->name('orders');
        Route::get('/orders/{order}', [AccountController::class, 'orderDetail'])->name('orders.show');
        Route::get('/orders/{order}/invoice', [AccountController::class, 'invoice'])->name('orders.invoice');
        Route::get('/orders/{order}/invoice/print', [AccountController::class, 'invoicePrint'])->name('orders.invoice.print');
        Route::get('/orders/{order}/downloads', [\App\Http\Controllers\DownloadController::class, 'orderDownloads'])->name('orders.downloads')->middleware('module:digital_products');
        Route::post('/orders/{order}/rate-driver', [AccountController::class, 'rateDriver'])->name('orders.rate-driver')->middleware('module:driver_portal');
        Route::post('/orders/{order}/tip-driver', [AccountController::class, 'tipDriver'])->name('orders.tip-driver')->middleware('module:driver_portal');
        Route::get('/profile', [AccountController::class, 'profile'])->name('profile');
        Route::put('/profile', [AccountController::class, 'updateProfile'])->name('profile.update');
        Route::get('/password', [AccountController::class, 'changePassword'])->name('password');
        Route::put('/password', [AccountController::class, 'updatePassword'])->name('password.update');
        Route::middleware('module:wishlist')->group(function () {
            Route::get('/wishlist', [AccountController::class, 'wishlist'])->name('wishlist');
            Route::post('/wishlist', [AccountController::class, 'addToWishlist'])->name('wishlist.add');
            Route::delete('/wishlist', [AccountController::class, 'removeFromWishlist'])->name('wishlist.remove');
        });
    });
    
    // Product Reviews
    Route::post('/shop/{product}/review', [ShopController::class, 'storeReview'])->name('shop.review')->middleware('module:reviews');

    // Helpdesk - Customer Tickets
    Route::prefix('helpdesk')->name('helpdesk.')->group(function () {
        Route::get('/', [HelpdeskController::class, 'index'])->name('index');
        Route::get('/create', [HelpdeskController::class, 'create'])->name('create');
        Route::post('/', [HelpdeskController::class, 'store'])->name('store');
        Route::get('/{ticket}', [HelpdeskController::class, 'show'])->name('show');
        Route::post('/{ticket}/reply', [HelpdeskController::class, 'reply'])->name('reply');
        Route::patch('/{ticket}/close', [HelpdeskController::class, 'close'])->name('close');
    });
});

// Vendor Routes
Route::prefix('vendor')->name('vendor.')->middleware(['module:multi_vendor', 'auth', 'vendor'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Vendor\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/register', [\App\Http\Controllers\Vendor\DashboardController::class, 'register'])->name('register');
    Route::post('/register', [\App\Http\Controllers\Vendor\DashboardController::class, 'storeRegistration'])->name('register.store');
    
    // Products
    Route::resource('products', \App\Http\Controllers\Vendor\ProductController::class);
    Route::patch('/products/{product}/toggle', [\App\Http\Controllers\Vendor\ProductController::class, 'toggleStatus'])->name('products.toggle');
    
    // Product Variations
    Route::post('/products/{product}/variations', [\App\Http\Controllers\Vendor\ProductController::class, 'storeVariation'])->name('products.variations.store');
    Route::get('/products/{product}/variations/{variationId}/edit', [\App\Http\Controllers\Vendor\ProductController::class, 'editVariation'])->name('products.variations.edit');
    Route::put('/products/{product}/variations/{variationId}', [\App\Http\Controllers\Vendor\ProductController::class, 'updateVariation'])->name('products.variations.update');
    Route::delete('/products/{product}/variations/{variationId}', [\App\Http\Controllers\Vendor\ProductController::class, 'destroyVariation'])->name('products.variations.destroy');
    
    // Digital Files
    Route::post('/products/{product}/digital-files', [\App\Http\Controllers\Vendor\DigitalFileController::class, 'store'])->name('products.digital-files.store');
    Route::put('/products/{product}/digital-files/{file}', [\App\Http\Controllers\Vendor\DigitalFileController::class, 'update'])->name('products.digital-files.update');
    Route::delete('/products/{product}/digital-files/{file}', [\App\Http\Controllers\Vendor\DigitalFileController::class, 'destroy'])->name('products.digital-files.destroy');
    
    // Orders
    Route::get('/orders', [\App\Http\Controllers\Vendor\OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [\App\Http\Controllers\Vendor\OrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/items/{item}/status', [\App\Http\Controllers\Vendor\OrderController::class, 'updateItemStatus'])->name('orders.items.status');
    Route::post('/orders/{order}/bulk-status', [\App\Http\Controllers\Vendor\OrderController::class, 'bulkUpdateStatus'])->name('orders.bulk-status');
    
    // Settings
    Route::get('/settings', [\App\Http\Controllers\Vendor\SettingController::class, 'index'])->name('settings');
    Route::put('/settings', [\App\Http\Controllers\Vendor\SettingController::class, 'update'])->name('settings.update');
    
    // Payouts
    Route::get('/payouts', [\App\Http\Controllers\Vendor\SettingController::class, 'payouts'])->name('payouts');
    Route::post('/payouts/request', [\App\Http\Controllers\Vendor\SettingController::class, 'requestPayout'])->name('payouts.request');
    
    // Payment Settings
    Route::get('/payment-settings', [\App\Http\Controllers\Vendor\SettingController::class, 'paymentSettings'])->name('payment-settings');
    Route::put('/payment-settings', [\App\Http\Controllers\Vendor\SettingController::class, 'updatePaymentSettings'])->name('payment-settings.update');
    
    // Stock Management
    Route::prefix('stock')->name('stock.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Vendor\StockController::class, 'index'])->name('index');
        Route::post('/update', [\App\Http\Controllers\Vendor\StockController::class, 'update'])->name('update');
        Route::get('/history/{product}', [\App\Http\Controllers\Vendor\StockController::class, 'history'])->name('history');
    });
});

// Driver Routes
Route::prefix('driver')->name('driver.')->middleware(['module:driver_portal', 'auth', 'driver'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Driver\DashboardController::class, 'index'])->name('dashboard');
    Route::post('/status', [\App\Http\Controllers\Driver\DashboardController::class, 'updateStatus'])->name('status');
    
    // Orders
    Route::get('/orders', [\App\Http\Controllers\Driver\OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/history', [\App\Http\Controllers\Driver\OrderController::class, 'history'])->name('orders.history');
    Route::get('/orders/{order}', [\App\Http\Controllers\Driver\OrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{order}/status', [\App\Http\Controllers\Driver\OrderController::class, 'updateStatus'])->name('orders.update-status');
    Route::post('/orders/{order}/note', [\App\Http\Controllers\Driver\OrderController::class, 'addNote'])->name('orders.add-note');
    Route::post('/orders/{order}/proof', [\App\Http\Controllers\Driver\OrderController::class, 'uploadProof'])->name('orders.upload-proof');
    Route::post('/orders/{order}/complete', [\App\Http\Controllers\Driver\OrderController::class, 'completeDelivery'])->name('orders.complete');
    Route::get('/orders/stats/api', [\App\Http\Controllers\Driver\OrderController::class, 'getStats'])->name('orders.stats');
    
    // Earnings
    Route::get('/earnings', [\App\Http\Controllers\Driver\EarningsController::class, 'index'])->name('earnings.index');
    Route::get('/earnings/history', [\App\Http\Controllers\Driver\EarningsController::class, 'history'])->name('earnings.history');
    
    // Profile
    Route::get('/profile', [\App\Http\Controllers\Driver\ProfileController::class, 'index'])->name('profile');
    Route::put('/profile', [\App\Http\Controllers\Driver\ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [\App\Http\Controllers\Driver\ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::put('/profile/schedule', [\App\Http\Controllers\Driver\ProfileController::class, 'updateSchedule'])->name('profile.schedule');
    
    // Notifications
    Route::get('/notifications', [\App\Http\Controllers\Driver\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [\App\Http\Controllers\Driver\NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [\App\Http\Controllers\Driver\NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::get('/notifications/unread-count', [\App\Http\Controllers\Driver\NotificationController::class, 'getUnreadCount'])->name('notifications.unread-count');
    Route::get('/notifications/recent', [\App\Http\Controllers\Driver\NotificationController::class, 'getRecent'])->name('notifications.recent');
});

// Admin Routes
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    
    // Products
    Route::get('/products/search', [ProductController::class, 'searchProducts'])->name('products.search');
    Route::resource('products', ProductController::class);
    
    // Product Variations
    Route::post('/products/{product}/variations', [ProductController::class, 'storeVariation'])->name('products.variations.store');
    Route::get('/products/{product}/variations/{variationId}/edit', [ProductController::class, 'editVariation'])->name('products.variations.edit');
    Route::put('/products/{product}/variations/{variationId}', [ProductController::class, 'updateVariation'])->name('products.variations.update');
    Route::delete('/products/{product}/variations/{variationId}', [ProductController::class, 'destroyVariation'])->name('products.variations.destroy');
    
    // Digital Files
    Route::post('/products/{product}/digital-files', [\App\Http\Controllers\Admin\DigitalFileController::class, 'store'])->name('products.digital-files.store');
    Route::put('/products/{product}/digital-files/{file}', [\App\Http\Controllers\Admin\DigitalFileController::class, 'update'])->name('products.digital-files.update');
    Route::delete('/products/{product}/digital-files/{file}', [\App\Http\Controllers\Admin\DigitalFileController::class, 'destroy'])->name('products.digital-files.destroy');
    Route::post('/products/{product}/digital-files/reorder', [\App\Http\Controllers\Admin\DigitalFileController::class, 'reorder'])->name('products.digital-files.reorder');
    
    // Stock Management
    Route::prefix('stock')->name('stock.')->group(function () {
        Route::get('/', [StockController::class, 'index'])->name('index');
        Route::post('/update', [StockController::class, 'update'])->name('update');
        Route::post('/bulk-update', [StockController::class, 'bulkUpdate'])->name('bulk-update');
        Route::get('/history/{product}', [StockController::class, 'history'])->name('history');
        Route::get('/export', [StockController::class, 'export'])->name('export');
    });
    
    // Brands
    Route::resource('brands', BrandController::class);
    Route::patch('/brands/{brand}/toggle-status', [BrandController::class, 'toggleStatus'])->name('brands.toggle-status');
    
    // Tags
    Route::resource('tags', TagController::class);
    Route::post('/tags/bulk-delete', [TagController::class, 'bulkDelete'])->name('tags.bulk-delete');
    Route::get('/tags/search', [TagController::class, 'search'])->name('tags.search');
    
    // Attributes
    Route::resource('attributes', AttributeController::class);
    Route::post('/attributes/{attribute}/values', [AttributeController::class, 'storeValue'])->name('attributes.values.store');
    Route::put('/attributes/values/{value}', [AttributeController::class, 'updateValue'])->name('attributes.values.update');
    Route::delete('/attributes/values/{value}', [AttributeController::class, 'destroyValue'])->name('attributes.values.destroy');
    Route::post('/attributes/{attribute}/reorder-values', [AttributeController::class, 'reorderValues'])->name('attributes.values.reorder');
    Route::get('/attributes/{attribute}/values', [AttributeController::class, 'getValues'])->name('attributes.values.get');
    
    // AI Content Generation
    Route::post('/ai/generate', [AiController::class, 'generate'])->name('ai.generate');
    
    // Translation Management
    Route::middleware('admin.role:super_admin')->group(function () {
        Route::prefix('translations')->name('translations.')->middleware('module:localization')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\TranslationController::class, 'index'])->name('index');
            Route::get('/stats', [\App\Http\Controllers\Admin\TranslationController::class, 'stats'])->name('stats');
            Route::get('/{type}/{id}/edit', [\App\Http\Controllers\Admin\TranslationController::class, 'edit'])->name('edit');
            Route::put('/{type}/{id}', [\App\Http\Controllers\Admin\TranslationController::class, 'update'])->name('update');
            Route::post('/{type}/{id}/auto-translate', [\App\Http\Controllers\Admin\TranslationController::class, 'autoTranslate'])->name('auto-translate');
            Route::post('/bulk-auto-translate', [\App\Http\Controllers\Admin\TranslationController::class, 'bulkAutoTranslate'])->name('bulk-auto-translate');
        });
    });
    
    // Categories
    Route::resource('categories', CategoryController::class);
    
    // Orders
    Route::middleware('admin.role:manager')->group(function () {
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create');
        Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
        Route::patch('/orders/{order}/payment', [OrderController::class, 'updatePaymentStatus'])->name('orders.payment');
        Route::patch('/orders/{order}/tracking', [OrderController::class, 'updateTracking'])->name('orders.tracking');
        Route::patch('/orders/{order}/assign-driver', [OrderController::class, 'assignDriver'])->name('orders.assign-driver');
        Route::post('/orders/{order}/note', [OrderController::class, 'addNote'])->name('orders.note');
        Route::post('/orders/{order}/refund', [OrderController::class, 'refund'])->name('orders.refund');
        Route::delete('/orders/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');
    });

    // Invoices
    Route::middleware('admin.role:manager')->group(function () {
        Route::get('/invoices/{order}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::get('/invoices/{order}/print', [InvoiceController::class, 'print'])->name('invoices.print');
        Route::get('/invoices/{order}/download', [InvoiceController::class, 'download'])->name('invoices.download');
    });

    // Shipping Labels
    Route::middleware('admin.role:manager')->group(function () {
        Route::get('/shipping-labels/{order}/print', [ShippingLabelController::class, 'print'])->name('shipping-labels.print');
        Route::get('/shipping-labels/resolve', [ShippingLabelController::class, 'resolve'])->name('shipping-labels.resolve');
    });

    // Transactions
    Route::middleware('admin.role:manager')->group(function () {
        Route::get('/transactions', [\App\Http\Controllers\Admin\TransactionController::class, 'index'])->name('transactions.index');
        Route::get('/transactions/{order}', [\App\Http\Controllers\Admin\TransactionController::class, 'show'])->name('transactions.show');
        Route::post('/transactions/{order}/refund', [\App\Http\Controllers\Admin\TransactionController::class, 'refund'])->name('transactions.refund');
    });
    

    
    // Users: managers and super admins only
    Route::middleware('admin.role:manager')->group(function () {
        Route::resource('users', UserController::class);
    });
    
    // Coupons
    Route::middleware('admin.role:manager')->group(function () {
        Route::resource('coupons', CouponController::class);
    });
    
    // Reviews
    Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');
    Route::patch('/reviews/{review}/approve', [ReviewController::class, 'approve'])->name('reviews.approve');
    Route::patch('/reviews/{review}/reject', [ReviewController::class, 'reject'])->name('reviews.reject');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

    // Newsletter Subscribers
    Route::middleware('admin.role:manager')->group(function () {
        Route::prefix('newsletters')->name('newsletters.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\NewsletterController::class, 'index'])->name('index');
            Route::delete('/{newsletter}', [\App\Http\Controllers\Admin\NewsletterController::class, 'destroy'])->name('destroy');
            Route::get('/export', [\App\Http\Controllers\Admin\NewsletterController::class, 'export'])->name('export');
        });
    });

    // Settings: super admins only, except the Shipping page, which managers also use.
    // (The save handler additionally limits non-super admins to shipping.* keys.)
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::middleware('admin.role:manager')->group(function () {
            Route::get('/shipping', [SettingController::class, 'shipping'])->name('shipping');
            Route::post('/', [SettingController::class, 'update'])->name('update');
        });

        // These include System Updates, which install and overwrite application code
        Route::middleware('admin.role:super_admin')->group(function () {
            Route::get('/', [SettingController::class, 'index'])->name('index');
            Route::get('/homepage', [SettingController::class, 'homepage'])->name('homepage');
            Route::get('/appearance', [SettingController::class, 'appearance'])->name('appearance');
            Route::get('/payment', [SettingController::class, 'payment'])->name('payment');
            Route::get('/seo', [SettingController::class, 'seo'])->name('seo');
            Route::get('/social', [SettingController::class, 'social'])->name('social');
            Route::get('/features', [SettingController::class, 'features'])->name('features');
            Route::get('/email', [SettingController::class, 'email'])->name('email');
            Route::get('/email/failed', [\App\Http\Controllers\Admin\FailedEmailController::class, 'index'])->name('failed-emails.index');
            Route::post('/email/failed/retry', [\App\Http\Controllers\Admin\FailedEmailController::class, 'retry'])->name('failed-emails.retry');
            Route::delete('/email/failed', [\App\Http\Controllers\Admin\FailedEmailController::class, 'destroy'])->name('failed-emails.destroy');
            Route::get('/system', [SettingController::class, 'system'])->name('system');
            Route::get('/navigation', [SettingController::class, 'navigation'])->name('navigation');
            Route::get('/ai', [SettingController::class, 'ai'])->name('ai');
            Route::get('/shop', [SettingController::class, 'shop'])->name('shop');
            Route::get('/products', [SettingController::class, 'products'])->name('products');
            Route::get('/mobile', [SettingController::class, 'mobile'])->name('mobile');
            Route::get('/localization', [SettingController::class, 'localization'])->name('localization');
            Route::post('/clear-cache', [SettingController::class, 'clearCache'])->name('clear-cache');
            Route::post('/refresh-exchange-rates', [SettingController::class, 'refreshExchangeRates'])->name('refresh-exchange-rates');
            Route::post('/save-manual-rates', [SettingController::class, 'saveManualRates'])->name('save-manual-rates');
            Route::post('/test-email', [SettingController::class, 'testEmail'])->name('test-email');
            Route::get('/debug-cache', [SettingController::class, 'debugCache'])->name('debug-cache');
            Route::get('/debug', [SettingController::class, 'debug'])->name('debug');
            Route::post('/force-refresh', [SettingController::class, 'forceRefresh'])->name('force-refresh');
            Route::get('/updates', [\App\Http\Controllers\Admin\UpdateController::class, 'index'])->name('updates');
            Route::get('/updates/check', [\App\Http\Controllers\Admin\UpdateController::class, 'check'])->name('updates.check');
            Route::post('/updates/install', [\App\Http\Controllers\Admin\UpdateController::class, 'install'])->name('updates.install');
            Route::post('/updates/upload', [\App\Http\Controllers\Admin\UpdateController::class, 'uploadUpdate'])->name('updates.upload');
            Route::post('/updates/restore', [\App\Http\Controllers\Admin\UpdateController::class, 'restore'])->name('updates.restore');
            Route::delete('/updates/backup', [\App\Http\Controllers\Admin\UpdateController::class, 'deleteBackup'])->name('updates.delete-backup');
            Route::post('/updates/fix-permissions', [\App\Http\Controllers\Admin\UpdateController::class, 'fixPermissions'])->name('updates.fix-permissions');
        });
    });

    // AI Routes - connection test for the AI settings
    Route::middleware('admin.role:super_admin')->group(function () {
        Route::post('/ai/test', [AiController::class, 'test'])->name('ai.test');
    });

    // Blog Management
    Route::middleware('admin.role:editor')->group(function () {
        Route::prefix('blog')->name('blog.')->middleware('module:blog')->group(function () {
            Route::get('/', [BlogController::class, 'index'])->name('index');
            Route::get('/create', [BlogController::class, 'create'])->name('create');
            Route::post('/', [BlogController::class, 'store'])->name('store');
            // Comment moderation (fixed paths, so they sit before '/{blog}')
            Route::patch('/comments/{comment}', [BlogController::class, 'updateComment'])->name('comments.update');
            Route::delete('/comments/{comment}', [BlogController::class, 'destroyComment'])->name('comments.destroy');
            // Categories must be registered before '/{blog}', otherwise GET /blog/categories is read as a post slug
            Route::get('/categories', [BlogController::class, 'categories'])->name('categories');
            Route::post('/categories', [BlogController::class, 'storeCategory'])->name('categories.store');
            Route::put('/categories/{category}', [BlogController::class, 'updateCategory'])->name('categories.update');
            Route::delete('/categories/{category}', [BlogController::class, 'destroyCategory'])->name('categories.destroy');
            Route::get('/{blog}', [BlogController::class, 'show'])->name('show');
            Route::get('/{blog}/edit', [BlogController::class, 'edit'])->name('edit');
            Route::put('/{blog}', [BlogController::class, 'update'])->name('update');
            Route::delete('/{blog}', [BlogController::class, 'destroy'])->name('destroy');
        });
    });

    // Themes & modules (super admin)
    Route::middleware('admin.role:super_admin')->group(function () {
        Route::prefix('themes')->name('themes.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\ThemeController::class, 'index'])->name('index');
            Route::delete('/preview', [\App\Http\Controllers\Admin\ThemeController::class, 'stopPreview'])->name('preview.stop');
            Route::get('/{theme}/customize', [\App\Http\Controllers\Admin\ThemeController::class, 'customize'])->name('customize');
            Route::put('/{theme}/customize', [\App\Http\Controllers\Admin\ThemeController::class, 'update'])->name('update');
            Route::delete('/{theme}/customize', [\App\Http\Controllers\Admin\ThemeController::class, 'reset'])->name('reset');
            Route::post('/{theme}/activate', [\App\Http\Controllers\Admin\ThemeController::class, 'activate'])->name('activate');
            Route::get('/{theme}/preview', [\App\Http\Controllers\Admin\ThemeController::class, 'preview'])->name('preview');
            Route::post('/{theme}/draft', [\App\Http\Controllers\Admin\ThemeController::class, 'draft'])->name('draft');
        });
        Route::prefix('modules')->name('modules.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\ModuleController::class, 'index'])->name('index');
            Route::post('/{module}/toggle', [\App\Http\Controllers\Admin\ModuleController::class, 'toggle'])->name('toggle');
            Route::put('/{module}/options', [\App\Http\Controllers\Admin\ModuleController::class, 'options'])->name('options');
        });
    });

    // Vendor Management
    Route::middleware('admin.role:super_admin')->group(function () {
        Route::prefix('vendors')->name('vendors.')->group(function () {
            Route::get('/', [VendorController::class, 'index'])->name('index');
            Route::get('/{vendor}', [VendorController::class, 'show'])->name('show');
            Route::patch('/{vendor}/status', [VendorController::class, 'updateStatus'])->name('status');
            Route::get('/{vendor}/payouts', [VendorController::class, 'payouts'])->name('payouts');
            Route::post('/{vendor}/payouts', [VendorController::class, 'createPayout'])->name('payouts.store');
        });
    });

    // Payout Requests Management
    Route::middleware('admin.role:super_admin')->group(function () {
        Route::get('/payouts', [VendorController::class, 'allPayouts'])->name('payouts.index');
        Route::patch('/payouts/{payout}/status', [VendorController::class, 'updatePayoutStatus'])->name('payouts.status');
    });

    // Loyalty Points Management
    Route::middleware('admin.role:super_admin')->group(function () {
        Route::prefix('loyalty')->name('loyalty.')->middleware('module:loyalty')->group(function () {
            Route::get('/', [LoyaltyController::class, 'index'])->name('index');
            Route::get('/transactions', [LoyaltyController::class, 'transactions'])->name('transactions');
            Route::post('/adjust', [LoyaltyController::class, 'adjustPoints'])->name('adjust');
        });
    });

    // Drivers Management
    Route::middleware('admin.role:manager')->group(function () {
        Route::prefix('drivers')->name('drivers.')->middleware('module:driver_portal')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\DriverController::class, 'index'])->name('index');
            Route::get('/{driver}', [\App\Http\Controllers\Admin\DriverController::class, 'show'])->name('show');
            Route::patch('/{driver}/commission', [\App\Http\Controllers\Admin\DriverController::class, 'updateCommission'])->name('commission');
            Route::post('/{driver}/bonus', [\App\Http\Controllers\Admin\DriverController::class, 'addBonus'])->name('bonus');
        });
    });

    // Shipping Zones
    Route::middleware('admin.role:manager')->group(function () {
        Route::prefix('shipping')->name('shipping.')->group(function () {
            Route::get('/', [ShippingZoneController::class, 'index'])->name('index');
            Route::get('/create', [ShippingZoneController::class, 'create'])->name('create');
            Route::post('/', [ShippingZoneController::class, 'store'])->name('store');
            Route::get('/{zone}/edit', [ShippingZoneController::class, 'edit'])->name('edit');
            Route::put('/{zone}', [ShippingZoneController::class, 'update'])->name('update');
            Route::delete('/{zone}', [ShippingZoneController::class, 'destroy'])->name('destroy');
            Route::post('/{zone}/methods', [ShippingZoneController::class, 'storeMethod'])->name('method.store');
            Route::put('/methods/{method}', [ShippingZoneController::class, 'updateMethod'])->name('method.update');
            Route::delete('/methods/{method}', [ShippingZoneController::class, 'destroyMethod'])->name('method.destroy');
        });
    });

    // Delivery Management
    Route::middleware('admin.role:manager')->group(function () {
        Route::prefix('delivery')->name('delivery.')->group(function () {
            Route::get('/', [DeliveryController::class, 'index'])->name('index');
            Route::post('/slots', [DeliveryController::class, 'storeSlot'])->name('slot.store');
            Route::put('/slots/{slot}', [DeliveryController::class, 'updateSlot'])->name('slot.update');
            Route::patch('/slots/{slot}/toggle', [DeliveryController::class, 'toggleSlot'])->name('slot.toggle');
            Route::delete('/slots/{slot}', [DeliveryController::class, 'destroySlot'])->name('slot.destroy');
            Route::post('/locations', [DeliveryController::class, 'storeLocation'])->name('location.store');
            Route::put('/locations/{location}', [DeliveryController::class, 'updateLocation'])->name('location.update');
            Route::patch('/locations/{location}/toggle', [DeliveryController::class, 'toggleLocation'])->name('location.toggle');
            Route::delete('/locations/{location}', [DeliveryController::class, 'destroyLocation'])->name('location.destroy');
        });
    });

    // Reports
    Route::middleware('admin.role:manager')->group(function () {
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [ReportController::class, 'index'])->name('index');
            Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
            Route::get('/products', [ReportController::class, 'products'])->name('products');
            Route::get('/customers', [ReportController::class, 'customers'])->name('customers');
        });
    });

    // Pages Management
    Route::middleware('admin.role:editor')->group(function () {
        Route::resource('pages', PageController::class);
    });

    // Media Library
    // Open to every admin: product, category and settings forms pick and upload images through these.
    Route::post('media', [MediaController::class, 'store'])->name('media.store');
    Route::get('media/picker', [MediaController::class, 'picker'])->name('media.picker');
    Route::get('media/json', [MediaController::class, 'json'])->name('media.json');
    // Managing the library itself is for content roles (super admin and editors)
    Route::middleware('admin.role:editor')->group(function () {
        Route::get('media', [MediaController::class, 'index'])->name('media.index');
        Route::post('media/bulk-upload', [MediaController::class, 'bulkUpload'])->name('media.bulk-upload');
        Route::post('media/bulk-delete', [MediaController::class, 'bulkDelete'])->name('media.bulk-delete');
        Route::get('media/{media}', [MediaController::class, 'show'])->name('media.show');
        Route::put('media/{media}', [MediaController::class, 'update'])->name('media.update');
        Route::delete('media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');
    });

    // Helpdesk Management
    Route::middleware('admin.role:manager')->group(function () {
        Route::prefix('helpdesk')->name('helpdesk.')->group(function () {
            Route::get('/', [AdminHelpdeskController::class, 'index'])->name('index');
            Route::get('/{ticket}', [AdminHelpdeskController::class, 'show'])->name('show');
            Route::post('/{ticket}/reply', [AdminHelpdeskController::class, 'reply'])->name('reply');
            Route::patch('/{ticket}/status', [AdminHelpdeskController::class, 'updateStatus'])->name('status');
            Route::patch('/{ticket}/priority', [AdminHelpdeskController::class, 'updatePriority'])->name('priority');
            Route::patch('/{ticket}/assign', [AdminHelpdeskController::class, 'assign'])->name('assign');
            Route::delete('/{ticket}', [AdminHelpdeskController::class, 'destroy'])->name('destroy');
        });
    });

    // AI Conversations
    Route::middleware('admin.role:manager')->group(function () {
        Route::prefix('ai-conversations')->name('ai-conversations.')->group(function () {
            Route::get('/', [AiConversationController::class, 'index'])->name('index');
            Route::get('/{conversation}', [AiConversationController::class, 'show'])->name('show');
            Route::delete('/{conversation}', [AiConversationController::class, 'destroy'])->name('destroy');
        });
    });
});
