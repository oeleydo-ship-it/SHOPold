<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HelpdeskTicket;
use App\Models\HelpdeskTicketReply;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HelpdeskController extends Controller
{
    public function index(Request $request)
    {
        $query = HelpdeskTicket::with(['user', 'assignedTo']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $tickets = $query->latest()->paginate(15)->withQueryString();

        $stats = [
            'open' => HelpdeskTicket::where('status', 'open')->count(),
            'in_progress' => HelpdeskTicket::where('status', 'in_progress')->count(),
            'waiting' => HelpdeskTicket::where('status', 'waiting')->count(),
            'resolved' => HelpdeskTicket::where('status', 'resolved')->count(),
        ];

        return Inertia::render('Admin/Helpdesk/Index', [
            'tickets' => $tickets->through(fn ($t) => [
                'id' => $t->id,
                'number' => $t->ticket_number,
                'subject' => $t->subject,
                'by_ai' => (bool) $t->created_by_ai,
                'customer' => $t->user?->name ?? 'Unknown',
                'email' => $t->user?->email ?? '',
                'status' => $t->status,
                'priority' => $t->priority,
                'assigned' => $t->assignedTo?->name,
                'date' => $t->created_at->format('M d, Y'),
                'ago' => $t->created_at->diffForHumans(),
            ]),
            'stats' => $stats,
            'filters' => $request->only('search', 'status', 'priority', 'category'),
        ]);
    }

    public function show(HelpdeskTicket $ticket)
    {
        $ticket->load(['user', 'assignedTo', 'order', 'replies.user', 'aiConversation.messages']);
        
        $admins = User::where('is_admin', true)->get();

        $ai = $ticket->aiConversation;

        return Inertia::render('Admin/Helpdesk/Show', [
            'ticket' => [
                'id' => $ticket->id,
                'number' => $ticket->ticket_number,
                'subject' => $ticket->subject,
                'description' => $ticket->description,
                'status' => $ticket->status,
                'priority' => $ticket->priority,
                'category' => ucfirst(str_replace('_', ' ', $ticket->category ?? 'General')),
                'assigned_to' => $ticket->assigned_to,
                'by_ai' => (bool) $ticket->created_by_ai,
                'created_at' => $ticket->created_at->format('M d, Y h:i A'),
                'created_short' => $ticket->created_at->format('M d, Y'),
                'updated_ago' => $ticket->updated_at->diffForHumans(),
                'customer' => ['id' => $ticket->user?->id, 'name' => $ticket->user?->name ?? 'Unknown', 'email' => $ticket->user?->email ?? ''],
                'order' => $ticket->order ? [
                    'id' => $ticket->order->id,
                    'number' => $ticket->order->order_number,
                    'date' => $ticket->order->created_at->format('M d, Y'),
                    'status' => ucfirst($ticket->order->status),
                ] : null,
                'ai_conversation' => $ai ? [
                    'id' => $ai->id,
                    'messages' => $ai->messages->take(6)->map(fn ($m) => ['id' => $m->id, 'role' => $m->role, 'content' => \Illuminate\Support\Str::limit($m->content, 150)])->values(),
                    'total' => $ai->messages->count(),
                ] : null,
                'replies' => $ticket->replies->map(fn ($r) => [
                    'id' => $r->id,
                    'name' => $r->user?->name ?? 'Unknown',
                    'is_admin' => (bool) $r->is_admin_reply,
                    'internal' => (bool) $r->is_internal_note,
                    'message' => $r->message,
                    'at' => $r->created_at->format('M d, Y h:i A'),
                ])->values(),
            ],
            'admins' => $admins->map(fn ($a) => ['id' => $a->id, 'name' => $a->name])->values(),
        ]);
    }

    public function reply(Request $request, HelpdeskTicket $ticket)
    {
        $request->validate([
            'message' => 'required|string|max:5000',
            'is_internal_note' => 'boolean',
        ]);

        HelpdeskTicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'message' => $request->message,
            'is_admin_reply' => true,
            'is_internal_note' => $request->boolean('is_internal_note'),
        ]);

        if (!$request->boolean('is_internal_note') && $ticket->status === 'open') {
            $ticket->update(['status' => 'in_progress']);
        }

        return back()->with('success', 'Reply sent successfully!');
    }

    public function updateStatus(Request $request, HelpdeskTicket $ticket)
    {
        $request->validate([
            'status' => 'required|in:open,in_progress,waiting,resolved,closed',
        ]);

        $ticket->update(['status' => $request->status]);

        return back()->with('success', 'Ticket status updated!');
    }

    public function assign(Request $request, HelpdeskTicket $ticket)
    {
        $request->validate([
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $ticket->update(['assigned_to' => $request->assigned_to]);

        return back()->with('success', 'Ticket assigned successfully!');
    }

    public function updatePriority(Request $request, HelpdeskTicket $ticket)
    {
        $request->validate([
            'priority' => 'required|in:low,medium,high,urgent',
        ]);

        $ticket->update(['priority' => $request->priority]);

        return back()->with('success', 'Priority updated!');
    }

    public function destroy(HelpdeskTicket $ticket)
    {
        $ticket->delete();

        return redirect()->route('admin.helpdesk.index')
            ->with('success', 'Ticket deleted successfully.');
    }
}
