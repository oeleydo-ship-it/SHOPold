<?php

namespace App\Services;

use App\Models\Setting;
use App\Support\Secrets;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TranslationService
{
    protected string $apiKey;
    protected string $provider;

    public function __construct()
    {
        $this->apiKey = Secrets::decrypt(setting('ai.api_key') ?: '');
        $this->provider = setting('ai.provider', 'openai');
    }

    /**
     * Translate text to target language using AI
     */
    public function translate(string $text, string $targetLanguage, string $sourceLanguage = 'en'): string
    {
        if (empty($text) || $targetLanguage === $sourceLanguage) {
            return $text;
        }

        if (empty($this->apiKey)) {
            return $text; // Return original if no API key
        }

        // Check cache first
        $cacheKey = 'translation_' . md5($text . $targetLanguage . $sourceLanguage);
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        try {
            $translated = $this->callAiTranslation($text, $targetLanguage, $sourceLanguage);
            
            // Cache for 24 hours
            Cache::put($cacheKey, $translated, 86400);
            
            return $translated;
        } catch (\Exception $e) {
            Log::error('Translation error: ' . $e->getMessage());
            return $text;
        }
    }

    /**
     * Translate multiple texts at once
     */
    public function translateBatch(array $texts, string $targetLanguage, string $sourceLanguage = 'en'): array
    {
        if (empty($this->apiKey) || $targetLanguage === $sourceLanguage) {
            return $texts;
        }

        $results = [];
        foreach ($texts as $key => $text) {
            $results[$key] = $this->translate($text, $targetLanguage, $sourceLanguage);
        }
        return $results;
    }

    /**
     * Call AI provider for translation
     */
    protected function callAiTranslation(string $text, string $targetLanguage, string $sourceLanguage): string
    {
        $languageNames = [
            'en' => 'English', 'es' => 'Spanish', 'fr' => 'French', 'de' => 'German',
            'it' => 'Italian', 'pt' => 'Portuguese', 'nl' => 'Dutch', 'ru' => 'Russian',
            'zh' => 'Chinese', 'ja' => 'Japanese', 'ko' => 'Korean', 'ar' => 'Arabic',
            'hi' => 'Hindi', 'tr' => 'Turkish', 'pl' => 'Polish', 'th' => 'Thai',
            'vi' => 'Vietnamese', 'id' => 'Indonesian', 'ms' => 'Malay', 'fil' => 'Filipino',
        ];

        $targetName = $languageNames[$targetLanguage] ?? $targetLanguage;
        $sourceName = $languageNames[$sourceLanguage] ?? $sourceLanguage;

        $prompt = "Translate the following text from {$sourceName} to {$targetName}. Return ONLY the translated text, nothing else. Preserve any HTML tags if present.\n\nText to translate:\n{$text}";

        @set_time_limit(120); // the AI provider can take longer than PHP's default 30 s limit

        $httpClient = Http::connectTimeout(10)->timeout(30);
        if (app()->environment('local', 'development')) {
            $httpClient = $httpClient->withOptions(['verify' => false]);
        }

        switch ($this->provider) {
            case 'anthropic':
                $response = $httpClient->withHeaders([
                    'x-api-key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                    'anthropic-version' => '2023-06-01'
                ])->post('https://api.anthropic.com/v1/messages', [
                    'model' => 'claude-3-haiku-20240307',
                    'max_tokens' => 2000,
                    'messages' => [['role' => 'user', 'content' => $prompt]]
                ]);
                
                if ($response->successful()) {
                    return trim($response->json('content.0.text') ?? $text);
                }
                break;

            case 'google':
                $response = $httpClient->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key={$this->apiKey}", [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['maxOutputTokens' => 2000]
                ]);
                
                if ($response->successful()) {
                    return trim($response->json('candidates.0.content.parts.0.text') ?? $text);
                }
                break;

            case 'openai':
            default:
                $response = $httpClient->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a professional translator. Translate text accurately while preserving meaning and tone.'],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'max_tokens' => 2000
                ]);
                
                if ($response->successful()) {
                    return trim($response->json('choices.0.message.content') ?? $text);
                }
                break;
        }

        return $text;
    }

    /**
     * Check if AI translation is available
     */
    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }
}
