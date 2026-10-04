<?php

/*
 * Customer helpdesk: tickets are private to their owner and can only reference the customer's own orders.
 */

use App\Models\{HelpdeskTicket, Order};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function helpdeskOrder(int $userId): Order
{
    return Order::create([
        'order_number' => 'ORD-' . Str::upper(Str::random(8)), 'user_id' => $userId, 'subtotal' => 10, 'total' => 10,
        'status' => 'processing', 'payment_status' => 'paid', 'payment_method' => 'stripe',
        'shipping_name' => 'X', 'shipping_email' => 'x@example.test', 'shipping_address' => '1 St', 'shipping_city' => 'C', 'shipping_zip' => '1', 'shipping_country' => 'L',
    ]);
}

beforeEach(function () {
    $this->me = customer();
    $this->other = customer();
    $this->actingAs($this->me);
});

test('a ticket can reference only the customer\'s own order', function () {
    $mine = helpdeskOrder($this->me->id);
    $theirs = helpdeskOrder($this->other->id);
    $ticket = ['subject' => 'Zz help', 'description' => 'Please help', 'priority' => 'medium', 'category' => 'general'];

    $this->post('/helpdesk', $ticket + ['order_id' => $theirs->id])->assertSessionHasErrors('order_id');
    $this->post('/helpdesk', $ticket + ['order_id' => $mine->id])->assertSessionHasNoErrors();

    expect(HelpdeskTicket::where('user_id', $this->me->id)->value('order_id'))->toBe($mine->id);
});

test('another customer\'s ticket stays private', function () {
    $ticket = HelpdeskTicket::create(['user_id' => $this->other->id, 'subject' => 'Theirs', 'description' => 'x', 'priority' => 'low', 'status' => 'open', 'category' => 'general']);

    $this->get("/helpdesk/{$ticket->id}")->assertForbidden();
    $this->post("/helpdesk/{$ticket->id}/reply", ['message' => 'hi'])->assertForbidden();
    $this->patch("/helpdesk/{$ticket->id}/close")->assertForbidden();
    $this->get('/helpdesk')->assertOk()->assertDontSee('Theirs');
});
