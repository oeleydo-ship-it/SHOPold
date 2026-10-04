<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** A delivery was assigned to a driver. */
class DriverAssigned extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, \App\Mail\Concerns\RetriesDelivery;

    public function __construct(
        public Order $order,
        public User $driver,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'New delivery: order ' . $this->order->order_number);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.driver-assigned');
    }
}
