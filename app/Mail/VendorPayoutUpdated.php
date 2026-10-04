<?php

namespace App\Mail;

use App\Models\VendorPayout;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** A payout request was paid (completed) or could not be paid (failed). */
class VendorPayoutUpdated extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, \App\Mail\Concerns\RetriesDelivery;

    public function __construct(
        public VendorPayout $payout,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->payout->status === 'completed'
            ? 'Payout of ' . format_currency($this->payout->amount) . ' sent'
            : 'Payout of ' . format_currency($this->payout->amount) . ' could not be sent');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.vendor-payout');
    }
}
