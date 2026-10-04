<?php

namespace App\Mail;

use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Tells a vendor their store was approved, rejected or suspended. */
class VendorStatusChanged extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, \App\Mail\Concerns\RetriesDelivery;

    public function __construct(
        public Vendor $vendor,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: match ($this->vendor->status) {
            'approved' => 'Your store ' . $this->vendor->store_name . ' is approved',
            'rejected' => 'Your store application was not approved',
            'suspended' => 'Your store ' . $this->vendor->store_name . ' has been suspended',
            default => 'Your store status changed',
        });
    }

    public function content(): Content
    {
        return new Content(view: 'emails.vendor-status');
    }
}
