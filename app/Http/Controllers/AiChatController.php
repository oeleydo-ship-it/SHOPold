<?php

namespace App\Http\Controllers;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\HelpdeskTicket;
use App\Services\AiChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AiChatController extends Controller
{
    protected AiChatService $aiChatService;

    public function __construct(AiChatService $aiChatService)
    {
        $this->aiChatService = $aiChatService;
    }

    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'history' => ['sometimes', 'array'],
            'history.*.role' => ['required_with:history', 'string', 'in:user,assistant,system'],
            'history.*.content' => ['required_with:history', 'string', 'max:4000'],
            'order_number' => ['nullable', 'string', 'max:64'],
            'email' => ['nullable', 'email', 'max:255'],
            'product_context' => ['sometimes', 'array'],
            'product_context.slug' => ['sometimes', 'string', 'max:255'],
            'product_context.name' => ['sometimes', 'string', 'max:255'],
            'product_context.url' => ['sometimes', 'string', 'max:500'],
            'product_context.category' => ['sometimes', 'nullable', 'string', 'max:255'],
            'product_context.brand' => ['sometimes', 'nullable', 'string', 'max:255'],
            'product_context.price' => ['sometimes', 'nullable', 'string', 'max:100'],
            'product_context.sale_price' => ['sometimes', 'nullable', 'string', 'max:100'],
            'product_context.price_value' => ['sometimes', 'nullable', 'numeric'],
            'product_context.sale_price_value' => ['sometimes', 'nullable', 'numeric'],
            'product_context.short_description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'product_context.features' => ['sometimes', 'array'],
            'product_context.features.*' => ['string', 'max:255'],
        ]);

        $result = $this->aiChatService->chat(
            $validated['message'],
            $validated['history'] ?? [],
            $validated['order_number'] ?? null,
            $validated['email'] ?? null,
            auth()->id(),
            $validated['product_context'] ?? null
        );

        return response()->json($result);
    }

    public function searchProducts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'max:200'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:20'],
        ]);

        $products = $this->aiChatService->searchProducts(
            $validated['query'],
            (int)($validated['limit'] ?? 5)
        );

        return response()->json([
            'success' => true,
            'products' => $products,
        ]);
    }

    public function orderStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_number' => ['required', 'string', 'max:64'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $order = $this->aiChatService->getOrderInfo($validated['order_number'], $validated['email'] ?? null);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found or email does not match.',
            ]);
        }

        return response()->json([
            'success' => true,
            'order' => $order,
        ]);
    }

    public function clearCache(): JsonResponse
    {
        AiChatService::clearKnowledgeCache();

        return response()->json([
            'success' => true,
            'message' => 'AI chat cache cleared.',
        ]);
    }

    public function storeMessage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|string|max:100',
            'role' => 'required|in:user,assistant',
            'content' => 'required|string|max:10000',
        ]);

        $conversation = AiConversation::firstOrCreate(
            ['session_id' => $validated['session_id']],
            [
                'user_id' => auth()->id(),
                'status' => 'active',
            ]
        );

        AiMessage::create([
            'conversation_id' => $conversation->id,
            'role' => $validated['role'],
            'content' => $validated['content'],
        ]);

        $conversation->increment('message_count');
        $conversation->update(['last_message_at' => now()]);

        return response()->json([
            'success' => true,
            'conversation_id' => $conversation->id,
        ]);
    }

    public function createTicket(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|string|max:100',
            'subject' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'category' => 'nullable|string|max:100',
            'priority' => 'nullable|in:low,medium,high,urgent',
        ]);

        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please log in to create a support ticket.',
            ], 401);
        }

        $conversation = AiConversation::where('session_id', $validated['session_id'])->first();

        $ticket = HelpdeskTicket::create([
            'user_id' => auth()->id(),
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'category' => $validated['category'] ?? 'ai_generated',
            'priority' => $validated['priority'] ?? 'medium',
            'created_by_ai' => true,
        ]);

        if ($conversation) {
            $conversation->update([
                'status' => 'converted_to_ticket',
                'ticket_id' => $ticket->id,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Support ticket created successfully!',
            'ticket_number' => $ticket->ticket_number,
            'ticket_id' => $ticket->id,
        ]);
    }

    public function endConversation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|string|max:100',
        ]);

        $conversation = AiConversation::where('session_id', $validated['session_id'])->first();

        if ($conversation && $conversation->status === 'active') {
            $conversation->update(['status' => 'ended']);
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function loadHistory(Request $request): JsonResponse
    {
        if (!auth()->check()) {
            return response()->json([
                'success' => true,
                'messages' => [],
                'session_id' => null,
                'is_logged_in' => false,
            ]);
        }

        // Find the most recent active conversation for this user
        $conversation = AiConversation::where('user_id', auth()->id())
            ->where('status', 'active')
            ->orderBy('last_message_at', 'desc')
            ->first();

        if (!$conversation) {
            return response()->json([
                'success' => true,
                'messages' => [],
                'session_id' => null,
                'is_logged_in' => true,
                'user_name' => auth()->user()->name,
            ]);
        }

        $messages = $conversation->messages()
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) {
                return [
                    'role' => $message->role,
                    'content' => $message->content,
                    'created_at' => $message->created_at->format('h:i A'),
                ];
            });

        return response()->json([
            'success' => true,
            'messages' => $messages,
            'session_id' => $conversation->session_id,
            'conversation_id' => $conversation->id,
            'is_logged_in' => true,
            'user_name' => auth()->user()->name,
        ]);
    }

    public function startNewConversation(Request $request): JsonResponse
    {
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Not logged in',
            ]);
        }

        // End any active conversations for this user
        AiConversation::where('user_id', auth()->id())
            ->where('status', 'active')
            ->update(['status' => 'ended']);

        return response()->json([
            'success' => true,
        ]);
    }
}
