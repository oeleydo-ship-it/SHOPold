<?php

namespace App\Http\Controllers;

use App\Models\HelpdeskTicket;
use App\Models\HelpdeskTicketReply;
use App\Models\Order;
use Illuminate\Http\Request;

class HelpdeskController extends Controller
{
    public function index()
    {
        $tickets = HelpdeskTicket::where('user_id', auth()->id())
            ->with(['replies' => function($q) {
                $q->latest()->limit(1);
            }])
            ->latest()
            ->paginate(10);

        return \Inertia\Inertia::render('Storefront/Helpdesk/Index', [
            'tickets' => $tickets->through(fn ($t) => $this->ticketRow($t)),
            'seo' => \App\Support\AccountPage::seo('Support Tickets'),
        ]);
    }

    public function create()
    {
        $orders = Order::where('user_id', auth()->id())
            ->latest()
            ->limit(20)
            ->get();

        $categories = [
            'order' => 'Order Issue',
            'product' => 'Product Question',
            'shipping' => 'Shipping & Delivery',
            'payment' => 'Payment Issue',
            'refund' => 'Refund Request',
            'account' => 'Account Help',
            'other' => 'Other',
        ];

        return \Inertia\Inertia::render('Storefront/Helpdesk/Create', [
            'categories' => collect($categories)->map(fn ($label, $key) => ['value' => $key, 'label' => $label])->values(),
            'orders' => $orders->map(fn ($o) => [
                'value' => $o->id,
                'label' => $o->order_number . ' - ' . $o->created_at->format('M d, Y') . ' (' . format_currency($o->total) . ')',
            ])->values(),
            'orderId' => (int) request('order') ?: null,
            'seo' => \App\Support\AccountPage::seo('New Support Ticket'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'category' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
            // only the customer's own orders (otherwise the ticket would show someone else's order number and total)
            'order_id' => ['nullable', \Illuminate\Validation\Rule::exists('orders', 'id')->where('user_id', auth()->id())],
        ]);

        $validated['user_id'] = auth()->id();

        $ticket = HelpdeskTicket::create($validated);

        return redirect()->route('helpdesk.show', $ticket)
            ->with('success', 'Ticket created successfully! Ticket #' . $ticket->ticket_number);
    }

    public function show(HelpdeskTicket $ticket)
    {
        if ($ticket->user_id !== auth()->id()) {
            abort(403);
        }

        $ticket->load(['replies' => function($q) {
            $q->where('is_internal_note', false)->with('user')->oldest();
        }, 'order', 'aiConversation.messages']);

        return \Inertia\Inertia::render('Storefront/Helpdesk/Show', [
            'ticket' => $this->ticketRow($ticket) + [
                'author' => $ticket->user?->name,
                'created' => $ticket->created_at?->format('M d, Y h:i A'),
                'updated' => $ticket->updated_at?->diffForHumans(),
                'category' => ucfirst(str_replace('_', ' ', $ticket->category ?? 'General')),
                'canReply' => !in_array($ticket->status, ['closed', 'resolved'], true),
                'canClose' => $ticket->status !== 'closed',
                'aiMessages' => ($ticket->aiConversation?->messages ?? collect())->map(fn ($m) => ['id' => $m->id, 'assistant' => $m->role === 'assistant', 'content' => (string) $m->content])->values(),
                'replies' => $ticket->replies->map(fn ($r) => [
                    'id' => $r->id,
                    'staff' => (bool) $r->is_admin_reply,
                    'author' => $r->user->name ?? 'Support Team',
                    'date' => $r->created_at?->format('M d, Y h:i A'),
                    'message' => (string) $r->message,
                ])->values(),
                'order' => $ticket->order ? [
                    'number' => $ticket->order->order_number,
                    'date' => $ticket->order->created_at?->format('M d, Y'),
                    'total' => format_currency($ticket->order->total),
                    'url' => route('account.orders.show', $ticket->order),
                ] : null,
            ],
            'seo' => \App\Support\AccountPage::seo($ticket->subject),
        ]);
    }

    /** A ticket as the React helpdesk pages show it in lists and headers. */
    protected function ticketRow(HelpdeskTicket $t): array
    {
        $status = ['open' => 'blue', 'in_progress' => 'amber', 'waiting' => 'purple', 'resolved' => 'green', 'closed' => 'gray'];
        $priority = ['low' => 'gray', 'medium' => 'blue', 'high' => 'orange', 'urgent' => 'red'];

        return [
            'id' => $t->id,
            'number' => $t->ticket_number,
            'subject' => $t->subject,
            'excerpt' => \Illuminate\Support\Str::limit((string) $t->description, 100),
            'description' => (string) $t->description,
            'ai' => (bool) $t->created_by_ai,
            'status' => ['label' => ucfirst(str_replace('_', ' ', (string) $t->status)), 'tone' => $status[$t->status] ?? 'gray'],
            'priority' => ['label' => ucfirst((string) $t->priority), 'tone' => $priority[$t->priority] ?? 'gray'],
            'ago' => $t->created_at?->diffForHumans(),
            'url' => route('helpdesk.show', $t),
        ];
    }

    public function reply(Request $request, HelpdeskTicket $ticket)
    {
        if ($ticket->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        HelpdeskTicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'message' => $request->message,
            'is_admin_reply' => false,
        ]);

        if ($ticket->status === 'waiting') {
            $ticket->update(['status' => 'open']);
        }

        return back()->with('success', 'Reply sent successfully!');
    }

    public function close(HelpdeskTicket $ticket)
    {
        if ($ticket->user_id !== auth()->id()) {
            abort(403);
        }

        $ticket->update(['status' => 'closed']);

        return back()->with('success', 'Ticket closed successfully.');
    }
}
