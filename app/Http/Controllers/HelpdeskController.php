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

        return view('helpdesk.index', compact('tickets'));
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

        return view('helpdesk.create', compact('orders', 'categories'));
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

        return view('helpdesk.show', compact('ticket'));
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
