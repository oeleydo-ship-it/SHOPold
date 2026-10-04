<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AiUsage extends Model
{
    use HasFactory;

    protected $table = 'ai_usage';

    protected $fillable = [
        'provider',
        'model',
        'feature',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'estimated_cost',
        'user_id',
        'conversation_id',
    ];

    protected $casts = [
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'total_tokens' => 'integer',
        'estimated_cost' => 'decimal:6',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function conversation()
    {
        return $this->belongsTo(AiConversation::class, 'conversation_id');
    }

    /**
     * Get usage stats for the current month
     */
    public static function getMonthlyStats(): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        
        $stats = self::where('created_at', '>=', $startOfMonth)
            ->selectRaw('SUM(total_tokens) as tokens_used, COUNT(*) as requests_count, SUM(estimated_cost) as total_cost')
            ->first();

        return [
            'tokens_used' => (int) ($stats->tokens_used ?? 0),
            'requests_count' => (int) ($stats->requests_count ?? 0),
            'estimated_cost' => (float) ($stats->total_cost ?? 0),
        ];
    }

    /**
     * Get usage stats by feature
     */
    public static function getStatsByFeature(?Carbon $from = null): array
    {
        $from = $from ?? Carbon::now()->startOfMonth();
        
        return self::where('created_at', '>=', $from)
            ->selectRaw('feature, SUM(total_tokens) as tokens, COUNT(*) as requests, SUM(estimated_cost) as cost')
            ->groupBy('feature')
            ->get()
            ->keyBy('feature')
            ->toArray();
    }

    /**
     * Calculate estimated cost based on provider and model
     */
    public static function calculateCost(string $provider, string $model, int $promptTokens, int $completionTokens): float
    {
        // Pricing per 1M tokens (as of late 2024)
        $pricing = [
            'openai' => [
                'gpt-4o' => ['input' => 2.50, 'output' => 10.00],
                'gpt-4o-mini' => ['input' => 0.15, 'output' => 0.60],
                'gpt-4-turbo' => ['input' => 10.00, 'output' => 30.00],
                'gpt-3.5-turbo' => ['input' => 0.50, 'output' => 1.50],
            ],
            'anthropic' => [
                'claude-3-opus' => ['input' => 15.00, 'output' => 75.00],
                'claude-3-sonnet' => ['input' => 3.00, 'output' => 15.00],
                'claude-3-haiku' => ['input' => 0.25, 'output' => 1.25],
            ],
            'google' => [
                'gemini-pro' => ['input' => 0.50, 'output' => 1.50],
                'gemini-pro-vision' => ['input' => 0.50, 'output' => 1.50],
            ],
        ];

        $rates = $pricing[$provider][$model] ?? ['input' => 0.50, 'output' => 1.50];
        
        $inputCost = ($promptTokens / 1_000_000) * $rates['input'];
        $outputCost = ($completionTokens / 1_000_000) * $rates['output'];
        
        return $inputCost + $outputCost;
    }

    /**
     * Log AI usage
     */
    public static function log(
        string $provider,
        string $model,
        string $feature,
        int $promptTokens,
        int $completionTokens,
        ?int $userId = null,
        ?int $conversationId = null
    ): self {
        $totalTokens = $promptTokens + $completionTokens;
        $cost = self::calculateCost($provider, $model, $promptTokens, $completionTokens);

        return self::create([
            'provider' => $provider,
            'model' => $model,
            'feature' => $feature,
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $totalTokens,
            'estimated_cost' => $cost,
            'user_id' => $userId,
            'conversation_id' => $conversationId,
        ]);
    }

    /**
     * Check if monthly limit is exceeded
     */
    public static function isMonthlyLimitExceeded(): bool
    {
        $limit = (int) setting('ai.monthly_limit', 0);
        if ($limit <= 0) {
            return false; // Unlimited
        }

        $stats = self::getMonthlyStats();
        return $stats['tokens_used'] >= $limit;
    }
}
