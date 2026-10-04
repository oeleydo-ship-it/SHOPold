<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Secrets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiController extends Controller
{
    /**
     * Test AI connection
     */
    public function test(Request $request)
    {
        $apiKey = Secrets::decrypt(setting('ai.api_key'));
        $provider = setting('ai.provider', 'openai');

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'error' => 'API key not configured. Please add your API key first.'
            ]);
        }

        try {
            // Disable SSL verification in development/local environment
            @set_time_limit(120); // the AI provider can take longer than PHP's default 30 s limit
            $httpClient = Http::connectTimeout(10)->timeout(30);
            if (app()->environment('local', 'development')) {
                $httpClient = $httpClient->withOptions(['verify' => false]);
            }

            switch ($provider) {
                case 'anthropic':
                    $response = $httpClient->withHeaders([
                        'x-api-key' => $apiKey,
                        'Content-Type' => 'application/json',
                        'anthropic-version' => '2023-06-01'
                    ])->post('https://api.anthropic.com/v1/messages', [
                        'model' => 'claude-3-haiku-20240307',
                        'max_tokens' => 10,
                        'messages' => [['role' => 'user', 'content' => 'Hi']]
                    ]);
                    break;

                case 'google':
                    $response = $httpClient->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key={$apiKey}", [
                        'contents' => [['parts' => [['text' => 'Hi']]]],
                        'generationConfig' => ['maxOutputTokens' => 10]
                    ]);
                    break;

                case 'openai':
                default:
                    $response = $httpClient->withHeaders([
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Content-Type' => 'application/json',
                    ])->post('https://api.openai.com/v1/chat/completions', [
                        'model' => 'gpt-4o-mini',
                        'messages' => [['role' => 'user', 'content' => 'Hi']],
                        'max_tokens' => 10
                    ]);
                    break;
            }

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Connection successful! Your ' . ucfirst($provider) . ' API key is working.'
                ]);
            } else {
                $error = $response->json('error.message') ?? $response->body();
                return response()->json([
                    'success' => false,
                    'error' => 'API Error: ' . $error
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Connection failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Generate product content using AI
     */
    public function generate(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string|max:1000',
            'type' => 'required|in:title,short,desc,all',
            'category' => 'nullable|string'
        ]);

        // Try AI settings first, then fall back to features settings
        $apiKey = Secrets::decrypt(setting('ai.api_key') ?: setting('features.ai_api_key'));
        $provider = setting('ai.provider') ?: setting('features.ai_chat_provider', 'openai');

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'error' => 'AI API key not configured. Go to Settings → AI Settings to add your API key.'
            ], 400);
        }

        try {
            $result = $this->callAiProvider($provider, $apiKey, $request->prompt, $request->type, $request->category);
            return response()->json(array_merge(['success' => true], $result));
        } catch (\Exception $e) {
            Log::error('AI Generation Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate content: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Call the appropriate AI provider
     */
    protected function callAiProvider(string $provider, string $apiKey, string $prompt, string $type, ?string $category): array
    {
        $systemPrompt = $this->buildSystemPrompt($type, $category);
        $userPrompt = $this->buildUserPrompt($prompt, $type);

        switch ($provider) {
            case 'anthropic':
                return $this->callAnthropic($apiKey, $systemPrompt, $userPrompt, $type);
            case 'google':
                return $this->callGoogle($apiKey, $systemPrompt, $userPrompt, $type);
            case 'openai':
            default:
                return $this->callOpenAI($apiKey, $systemPrompt, $userPrompt, $type);
        }
    }

    /**
     * Build system prompt based on generation type
     */
    protected function buildSystemPrompt(string $type, ?string $category): string
    {
        $categoryContext = $category ? " in the {$category} category" : "";
        
        $prompts = [
            'title' => "You are an expert e-commerce copywriter. Generate a compelling, SEO-friendly product title{$categoryContext}. The title should be concise (under 80 characters), descriptive, and include key features. Return ONLY the plain text title - no HTML tags, no quotes, no extra formatting.",
            
            'short' => "You are an expert e-commerce copywriter. Generate a compelling short product description{$categoryContext}. The description should be 1-2 sentences (under 160 characters), highlight key benefits, and encourage purchase. Return ONLY plain text - no HTML tags, no quotes, no formatting.",
            
            'desc' => "You are an expert e-commerce copywriter. Generate a detailed, persuasive product description{$categoryContext}. Include key features, benefits, and a call to action. Write in plain text with natural paragraphs. Use line breaks between paragraphs. Do NOT use any HTML tags. Do NOT use markdown. Just write clean, readable text that flows naturally.",
            
            'all' => "You are an expert e-commerce copywriter. Generate complete product content{$categoryContext}. Return a JSON object with exactly these keys:
- \"title\": A compelling plain text product title (under 80 characters, NO HTML)
- \"short_description\": A brief 1-2 sentence plain text summary (under 160 characters, NO HTML)
- \"description\": A detailed plain text description with features and benefits. Use natural paragraphs separated by line breaks. NO HTML tags, NO markdown.

Return ONLY valid JSON, no markdown code blocks or extra text."
        ];

        return $prompts[$type] ?? $prompts['all'];
    }

    /**
     * Build user prompt
     */
    protected function buildUserPrompt(string $prompt, string $type): string
    {
        return "Product details: {$prompt}";
    }

    /**
     * Call OpenAI API
     */
    protected function callOpenAI(string $apiKey, string $systemPrompt, string $userPrompt, string $type): array
    {
        @set_time_limit(120); // the AI provider can take longer than PHP's default 30 s limit
        $httpClient = Http::connectTimeout(10)->timeout(60);
        if (app()->environment('local', 'development')) {
            $httpClient = $httpClient->withOptions(['verify' => false]);
        }

        $response = $httpClient->withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt]
            ],
            'temperature' => 0.7,
            'max_tokens' => $type === 'desc' || $type === 'all' ? 1000 : 200
        ]);

        if (!$response->successful()) {
            throw new \Exception('OpenAI API error: ' . $response->body());
        }

        $content = $response->json('choices.0.message.content');
        return $this->parseResponse($content, $type);
    }

    /**
     * Call Anthropic (Claude) API
     */
    protected function callAnthropic(string $apiKey, string $systemPrompt, string $userPrompt, string $type): array
    {
        @set_time_limit(120); // the AI provider can take longer than PHP's default 30 s limit
        $httpClient = Http::connectTimeout(10)->timeout(60);
        if (app()->environment('local', 'development')) {
            $httpClient = $httpClient->withOptions(['verify' => false]);
        }

        $response = $httpClient->withHeaders([
            'x-api-key' => $apiKey,
            'Content-Type' => 'application/json',
            'anthropic-version' => '2023-06-01'
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => 'claude-3-haiku-20240307',
            'max_tokens' => $type === 'desc' || $type === 'all' ? 1000 : 200,
            'system' => $systemPrompt,
            'messages' => [
                ['role' => 'user', 'content' => $userPrompt]
            ]
        ]);

        if (!$response->successful()) {
            throw new \Exception('Anthropic API error: ' . $response->body());
        }

        $content = $response->json('content.0.text');
        return $this->parseResponse($content, $type);
    }

    /**
     * Call Google (Gemini) API
     */
    protected function callGoogle(string $apiKey, string $systemPrompt, string $userPrompt, string $type): array
    {
        @set_time_limit(120); // the AI provider can take longer than PHP's default 30 s limit
        $httpClient = Http::connectTimeout(10)->timeout(60);
        if (app()->environment('local', 'development')) {
            $httpClient = $httpClient->withOptions(['verify' => false]);
        }

        $response = $httpClient->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key={$apiKey}", [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $systemPrompt . "\n\n" . $userPrompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => $type === 'desc' || $type === 'all' ? 1000 : 200
            ]
        ]);

        if (!$response->successful()) {
            throw new \Exception('Google API error: ' . $response->body());
        }

        $content = $response->json('candidates.0.content.parts.0.text');
        return $this->parseResponse($content, $type);
    }

    /**
     * Parse AI response based on type
     */
    protected function parseResponse(string $content, string $type): array
    {
        $content = trim($content);

        if ($type === 'all') {
            // Try to parse as JSON
            $content = preg_replace('/^```json\s*/', '', $content);
            $content = preg_replace('/\s*```$/', '', $content);
            
            $json = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return [
                    'title' => $this->cleanText($json['title'] ?? ''),
                    'short_description' => $this->cleanText($json['short_description'] ?? ''),
                    'description' => $this->cleanText($json['description'] ?? '')
                ];
            }
            
            // Fallback: return content as description
            return [
                'title' => '',
                'short_description' => '',
                'description' => $this->cleanText($content)
            ];
        }

        // Single field responses
        $fieldMap = [
            'title' => 'title',
            'short' => 'short_description',
            'desc' => 'description'
        ];

        return [$fieldMap[$type] => $this->cleanText($content)];
    }

    /**
     * Clean text by removing HTML tags and normalizing whitespace
     */
    protected function cleanText(string $text): string
    {
        // Remove HTML tags
        $text = strip_tags($text);
        
        // Remove markdown formatting
        $text = preg_replace('/\*\*(.*?)\*\*/', '$1', $text); // Bold **text**
        $text = preg_replace('/\*(.*?)\*/', '$1', $text); // Italic *text*
        $text = preg_replace('/#{1,6}\s*/', '', $text); // Headers # ## ###
        $text = preg_replace('/`(.*?)`/', '$1', $text); // Code `text`
        
        // Normalize line breaks
        $text = preg_replace('/\r\n|\r/', "\n", $text);
        
        // Remove excessive whitespace but keep paragraph breaks
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        
        return trim($text);
    }
}
