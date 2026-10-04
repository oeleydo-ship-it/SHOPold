<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** A vendor's share of a new order: only their own items, with a link to fulfil it in the vendor portal. */
class VendorNewOrder extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, \App\Mail\Concerns\RetriesDelivery;

    public function __construct(
        public Order $order,
        public Vendor $vendor,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'New order ' . $this->order->order_number . ' for ' . $this->vendor->store_name);
    }

    public function content(): Content
    {
        $items = $this->order->items()->whereHas('product', fn ($q) => $q->where('vendor_id', $this->vendor->id))->get();

        return new Content(view: 'emails.vendor-new-order', with: [
            'items' => $items,
            'itemsTotal' => $items->sum('total'),
        ]);
    }
}
