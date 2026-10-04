<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiConversation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AiConversationController extends Controller
{
    public function index(Request $request)
    {
        $query = AiConversation::with(['user', 'ticket']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('has_ticket')) {
            if ($request->has_ticket === 'yes') {
                $query->whereNotNull('ticket_id');
            } else {
                $query->whereNull('ticket_id');
            }
        }

        $conversations = $query->latest()->paginate(15)->withQueryString();

        $stats = [
            'total' => AiConversation::count(),
            'active' => AiConversation::where('status', 'active')->count(),
            'converted' => AiConversation::where('status', 'converted_to_ticket')->count(),
        ];

        return Inertia::render('Admin/AiConversations/Index', [
            'conversations' => $conversations->through(fn ($c) => [
                'id' => $c->id,
                'customer' => $c->user ? ['name' => $c->user->name, 'email' => $c->user->email] : null,
                'session' => \Illuminate\Support\Str::limit($c->session_id, 20),
                'messages' => $c->message_count,
                'status' => $c->status,
                'ticket' => $c->ticket ? ['id' => $c->ticket->id, 'number' => $c->ticket->ticket_number] : null,
                'last' => ($c->last_message_at ?? $c->created_at)->diffForHumans(),
            ]),
            'stats' => $stats,
            'filters' => $request->only('search', 'status', 'has_ticket'),
        ]);
    }

    public function show(AiConversation $conversation)
    {
        $conversation->load(['user', 'messages', 'ticket']);

        return Inertia::render('Admin/AiConversations/Show', [
            'conversation' => [
                'id' => $conversation->id,
                'session' => \Illuminate\Support\Str::limit($conversation->session_id, 30),
                'status' => $conversation->status,
                'message_count' => $conversation->message_count,
                'created_at' => $conversation->created_at->format('M d, Y h:i A'),
                'last' => $conversation->last_message_at ? $conversation->last_message_at->diffForHumans() : '-',
                'customer' => $conversation->user ? ['id' => $conversation->user->id, 'name' => $conversation->user->name, 'email' => $conversation->user->email] : null,
                'ticket' => $conversation->ticket ? [
                    'id' => $conversation->ticket->id,
                    'number' => $conversation->ticket->ticket_number,
                    'subject' => \Illuminate\Support\Str::limit($conversation->ticket->subject, 30),
                    'status' => ucfirst($conversation->ticket->status),
                ] : null,
                'messages' => $conversation->messages->map(fn ($m) => [
                    'id' => $m->id,
                    'role' => $m->role,
                    'content' => $m->content,
                    'time' => $m->created_at->format('h:i A'),
                ])->values(),
            ],
        ]);
    }

    public function destroy(AiConversation $conversation)
    {
        $conversation->delete();

        return redirect()->route('admin.ai-conversations.index')
            ->with('success', 'Conversation deleted successfully.');
    }
}
