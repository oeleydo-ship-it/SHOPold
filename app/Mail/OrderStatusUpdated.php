<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdated extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, \App\Mail\Concerns\RetriesDelivery;

    public function __construct(
        public Order $order,
        public string $previousStatus
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Order ' . $this->order->order_number . ' - Status Updated',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-status-updated',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
