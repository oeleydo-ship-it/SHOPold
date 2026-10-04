<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Models\Order;

class AiFeatureService
{
    protected string $provider;
    protected string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->provider = setting('ai.provider') ?: setting('features.ai_chat_provider', 'openai');
        $this->model = setting('ai.model') ?: setting('features.ai_model', 'gpt-4o-mini');
        $apiKey = setting('ai.api_key') ?: setting('features.ai_api_key', '');
        $this->apiKey = $this->decryptSensitiveValue($apiKey);
    }

    /**
     * Check if AI features are available
     */
    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Smart Search - AI-enhanced product search with natural language understanding
     */
    public function smartSearch(string $query, int $limit = 20): array
    {
        if (!$this->isAvailable() || !setting('ai.smart_search')) {
            return $this->fallbackSearch($query, $limit);
        }

        try {
            // First, use AI to understand the search intent and extract keywords
            $searchIntent = $this->analyzeSearchIntent($query);
            
            if (!$searchIntent) {
                return $this->fallbackSearch($query, $limit);
            }

            // Build smart query based on AI analysis
            $productQuery = Product::active()->inStock()->with(['category', 'brand']);

            // Apply keyword search
            if (!empty($searchIntent['keywords'])) {
                $keywords = $searchIntent['keywords'];
                $productQuery->where(function ($q) use ($keywords) {
                    foreach ($keywords as $keyword) {
                        $q->orWhere('name', 'like', "%{$keyword}%")
                          ->orWhere('description', 'like', "%{$keyword}%")
                          ->orWhere('short_description', 'like', "%{$keyword}%")
                          ->orWhere('sku', 'like', "%{$keyword}%");
                    }
                });
            }

            // Apply category filter if detected
            if (!empty($searchIntent['category'])) {
                $productQuery->whereHas('category', function ($q) use ($searchIntent) {
                    $q->where('name', 'like', "%{$searchIntent['category']}%")
                      ->orWhere('slug', 'like', "%{$searchIntent['category']}%");
                });
            }

            // Apply price range if detected
            if (!empty($searchIntent['min_price'])) {
                $productQuery->where('price', '>=', $searchIntent['min_price']);
            }
            if (!empty($searchIntent['max_price'])) {
                $productQuery->where('price', '<=', $searchIntent['max_price']);
            }

            // Apply sorting based on intent
            if (!empty($searchIntent['sort'])) {
                switch ($searchIntent['sort']) {
                    case 'price_low':
                        $productQuery->orderBy('price', 'asc');
                        break;
                    case 'price_high':
                        $productQuery->orderBy('price', 'desc');
                        break;
                    case 'popular':
                        $productQuery->withCount('orderItems')->orderByDesc('order_items_count');
                        break;
                    case 'newest':
                        $productQuery->orderBy('created_at', 'desc');
                        break;
                    case 'rating':
                        $productQuery->withAvg('reviews', 'rating')->orderByDesc('reviews_avg_rating');
                        break;
                }
            }

            $products = $productQuery->take($limit)->get();

            return [
                'success' => true,
                'products' => $products,
                'intent' => $searchIntent,
                'ai_enhanced' => true,
            ];

        } catch (\Exception $e) {
            Log::error('Smart Search Error: ' . $e->getMessage());
            return $this->fallbackSearch($query, $limit);
        }
    }

    /**
     * Analyze search query intent using AI
     */
    protected function analyzeSearchIntent(string $query): ?array
    {
        $cacheKey = 'search_intent_' . md5($query);
        
        return Cache::remember($cacheKey, 300, function () use ($query) {
            $systemPrompt = <<<PROMPT
You are a search query analyzer for an e-commerce store. Analyze the user's search query and extract structured information.

Return a JSON object with:
- keywords: array of relevant product keywords to search for
- category: detected product category (if any)
- min_price: minimum price (number, if mentioned)
- max_price: maximum price (number, if mentioned)  
- sort: suggested sort order (price_low, price_high, popular, newest, rating)
- attributes: any specific attributes mentioned (color, size, brand, etc.)

Examples:
"cheap laptops under $500" -> {"keywords":["laptop","laptops"],"category":"electronics","max_price":500,"sort":"price_low","attributes":{}}
"best rated headphones" -> {"keywords":["headphones"],"category":"electronics","sort":"rating","attributes":{}}
"red dress for party" -> {"keywords":["dress","party dress"],"category":"clothing","attributes":{"color":"red","occasion":"party"}}

Only return valid JSON, no explanation.
PROMPT;

            try {
                $response = $this->callAI($systemPrompt, "Analyze this search query: \"{$query}\"");
                
                if ($response) {
                    // Clean the response - remove markdown code blocks if present
                    $response = preg_replace('/```json\s*/', '', $response);
                    $response = preg_replace('/```\s*/', '', $response);
                    $response = trim($response);
                    
                    $decoded = json_decode($response, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        return $decoded;
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Search intent analysis failed: ' . $e->getMessage());
            }

            // Fallback to basic keyword extraction
            return [
                'keywords' => array_filter(explode(' ', strtolower($query))),
                'category' => null,
                'sort' => null,
            ];
        });
    }

    /**
     * Fallback search without AI
     */
    protected function fallbackSearch(string $query, int $limit): array
    {
        $products = Product::active()->inStock()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhere('sku', 'like', "%{$query}%");
            })
            ->with(['category', 'brand'])
            ->take($limit)
            ->get();

        return [
            'success' => true,
            'products' => $products,
            'ai_enhanced' => false,
        ];
    }

    /**
     * AI Recommendations - Get personalized product recommendations
     */
    public function getRecommendations(?int $userId = null, ?int $productId = null, int $limit = 8): array
    {
        if (!$this->isAvailable() || !setting('ai.recommendations')) {
            return $this->getFallbackRecommendations($productId, $limit);
        }

        try {
            $context = $this->buildRecommendationContext($userId, $productId);
            
            $systemPrompt = <<<PROMPT
You are a product recommendation engine for an e-commerce store. Based on the customer context and available products, recommend the most relevant products.

Return a JSON object with:
- product_ids: array of recommended product IDs (from the available products list)
- reasons: array of brief reasons for each recommendation

Consider:
- Customer's purchase history and preferences
- Currently viewed product (if any)
- Cart items
- Popular and trending products
- Complementary products

Only return valid JSON, no explanation.
PROMPT;

            $response = $this->callAI($systemPrompt, json_encode($context));
            
            if ($response) {
                $response = preg_replace('/```json\s*/', '', $response);
                $response = preg_replace('/```\s*/', '', $response);
                $decoded = json_decode(trim($response), true);
                
                if (json_last_error() === JSON_ERROR_NONE && !empty($decoded['product_ids'])) {
                    $products = Product::whereIn('id', $decoded['product_ids'])
                        ->active()
                        ->inStock()
                        ->with(['category', 'brand'])
                        ->take($limit)
                        ->get();

                    return [
                        'success' => true,
                        'products' => $products,
                        'reasons' => $decoded['reasons'] ?? [],
                        'ai_powered' => true,
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('AI Recommendations Error: ' . $e->getMessage());
        }

        return $this->getFallbackRecommendations($productId, $limit);
    }

    /**
     * Build context for AI recommendations
     */
    protected function buildRecommendationContext(?int $userId, ?int $productId): array
    {
        $context = [
            'available_products' => [],
            'customer' => null,
            'current_product' => null,
        ];

        // Get available products (sample)
        $products = Product::active()->inStock()
            ->select(['id', 'name', 'price', 'category_id'])
            ->with('category:id,name')
            ->take(50)
            ->get();

        $context['available_products'] = $products->map(fn($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'price' => $p->price,
            'category' => $p->category?->name,
        ])->toArray();

        // Current product context
        if ($productId) {
            $product = Product::find($productId);
            if ($product) {
                $context['current_product'] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'category' => $product->category?->name,
                    'price' => $product->price,
                ];
            }
        }

        // Customer context
        if ($userId) {
            $user = User::find($userId);
            if ($user) {
                // Get purchase history
                $purchasedProductIds = Order::where('user_id', $userId)
                    ->where('status', '!=', 'cancelled')
                    ->with('items')
                    ->get()
                    ->pluck('items')
                    ->flatten()
                    ->pluck('product_id')
                    ->unique()
                    ->toArray();

                $context['customer'] = [
                    'purchased_product_ids' => $purchasedProductIds,
                    'cart_product_ids' => [], // Could add cart items here
                ];
            }
        }

        return $context;
    }

    /**
     * Fallback recommendations without AI
     */
    protected function getFallbackRecommendations(?int $productId, int $limit): array
    {
        $query = Product::active()->inStock()->with(['category', 'brand']);

        if ($productId) {
            $product = Product::find($productId);
            if ($product && $product->category_id) {
                // Related products from same category
                $query->where('category_id', $product->category_id)
                      ->where('id', '!=', $productId);
            }
        } else {
            // Featured or popular products
            $query->where('is_featured', true);
        }

        $products = $query->take($limit)->get();

        // If not enough, fill with random products
        if ($products->count() < $limit) {
            $additionalIds = $products->pluck('id')->toArray();
            $additional = Product::active()->inStock()
                ->whereNotIn('id', $additionalIds)
                ->inRandomOrder()
                ->take($limit - $products->count())
                ->get();
            $products = $products->concat($additional);
        }

        return [
            'success' => true,
            'products' => $products,
            'ai_powered' => false,
        ];
    }

    /**
     * Review Analysis - Analyze review sentiment and extract insights
     */
    public function analyzeReview(string $reviewText): array
    {
        if (!$this->isAvailable() || !setting('ai.review_analysis')) {
            return $this->getBasicSentiment($reviewText);
        }

        try {
            $systemPrompt = <<<PROMPT
Analyze this product review and provide:
1. sentiment: "positive", "negative", or "neutral"
2. sentiment_score: number from -1 (very negative) to 1 (very positive)
3. key_points: array of main points mentioned (pros and cons)
4. topics: array of topics discussed (quality, price, shipping, etc.)
5. recommendation: boolean - would the reviewer recommend this product?

Return only valid JSON.
PROMPT;

            $response = $this->callAI($systemPrompt, "Review: \"{$reviewText}\"");
            
            if ($response) {
                $response = preg_replace('/```json\s*/', '', $response);
                $response = preg_replace('/```\s*/', '', $response);
                $decoded = json_decode(trim($response), true);
                
                if (json_last_error() === JSON_ERROR_NONE) {
                    return [
                        'success' => true,
                        'analysis' => $decoded,
                        'ai_powered' => true,
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('Review Analysis Error: ' . $e->getMessage());
        }

        return $this->getBasicSentiment($reviewText);
    }

    /**
     * Analyze multiple reviews for a product
     */
    public function analyzeProductReviews(int $productId): array
    {
        $reviews = Review::where('product_id', $productId)
            ->where('is_approved', true)
            ->get();

        if ($reviews->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No reviews to analyze',
            ];
        }

        if (!$this->isAvailable() || !setting('ai.review_analysis')) {
            return $this->getBasicProductAnalysis($reviews);
        }

        try {
            $reviewTexts = $reviews->map(fn($r) => [
                'rating' => $r->rating,
                'text' => $r->comment,
            ])->toArray();

            $systemPrompt = <<<PROMPT
Analyze these product reviews and provide a summary:
1. overall_sentiment: "positive", "negative", or "mixed"
2. average_sentiment_score: number from -1 to 1
3. common_pros: array of frequently mentioned positive points
4. common_cons: array of frequently mentioned negative points
5. summary: 2-3 sentence summary of customer feedback
6. recommendation_rate: percentage of reviewers who would recommend

Return only valid JSON.
PROMPT;

            $response = $this->callAI($systemPrompt, "Reviews: " . json_encode($reviewTexts));
            
            if ($response) {
                $response = preg_replace('/```json\s*/', '', $response);
                $response = preg_replace('/```\s*/', '', $response);
                $decoded = json_decode(trim($response), true);
                
                if (json_last_error() === JSON_ERROR_NONE) {
                    return [
                        'success' => true,
                        'analysis' => $decoded,
                        'review_count' => $reviews->count(),
                        'ai_powered' => true,
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('Product Reviews Analysis Error: ' . $e->getMessage());
        }

        return $this->getBasicProductAnalysis($reviews);
    }

    /**
     * Basic sentiment analysis without AI
     */
    protected function getBasicSentiment(string $text): array
    {
        $positiveWords = ['good', 'great', 'excellent', 'amazing', 'love', 'perfect', 'best', 'awesome', 'fantastic', 'wonderful', 'recommend'];
        $negativeWords = ['bad', 'terrible', 'awful', 'hate', 'worst', 'poor', 'disappointing', 'horrible', 'waste', 'broken'];

        $text = strtolower($text);
        $positiveCount = 0;
        $negativeCount = 0;

        foreach ($positiveWords as $word) {
            $positiveCount += substr_count($text, $word);
        }
        foreach ($negativeWords as $word) {
            $negativeCount += substr_count($text, $word);
        }

        $total = $positiveCount + $negativeCount;
        $score = $total > 0 ? ($positiveCount - $negativeCount) / $total : 0;

        $sentiment = 'neutral';
        if ($score > 0.3) $sentiment = 'positive';
        if ($score < -0.3) $sentiment = 'negative';

        return [
            'success' => true,
            'analysis' => [
                'sentiment' => $sentiment,
                'sentiment_score' => round($score, 2),
            ],
            'ai_powered' => false,
        ];
    }

    /**
     * Basic product analysis without AI
     */
    protected function getBasicProductAnalysis($reviews): array
    {
        $avgRating = $reviews->avg('rating');
        $sentiment = $avgRating >= 4 ? 'positive' : ($avgRating >= 3 ? 'mixed' : 'negative');

        return [
            'success' => true,
            'analysis' => [
                'overall_sentiment' => $sentiment,
                'average_rating' => round($avgRating, 1),
                'review_count' => $reviews->count(),
            ],
            'ai_powered' => false,
        ];
    }

    /**
     * Call AI API
     */
    protected function callAI(string $systemPrompt, string $userMessage): ?string
    {
        @set_time_limit(120); // the AI provider can take longer than PHP's default 30 s limit
        $http = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->connectTimeout(10)->timeout(15);

        if (!setting('features.ai_ssl_verify', true) || app()->environment('local')) {
            $http = $http->withOptions(['verify' => false]);
        }

        try {
            $response = $http->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userMessage],
                ],
                'max_tokens' => 500,
                'temperature' => 0.3,
            ]);

            if ($response->successful()) {
                return $response->json('choices.0.message.content');
            }
        } catch (\Exception $e) {
            Log::error('AI API Call Error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Decrypt sensitive API key value
     */
    protected function decryptSensitiveValue(?string $value): string
    {
        if (empty($value)) {
            return '';
        }

        if (str_starts_with($value, 'eyJ')) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return $value;
            }
        }

        return $value;
    }
}
