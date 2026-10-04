<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorPayout extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'amount',
        'payment_method',
        'payment_details',
        'status',
        'transaction_id',
        'notes',
        'processed_at',
    ];

    /** Payout details are never serialised (toArray/JSON); read the attribute explicitly where needed. */
    protected $hidden = ['payment_details'];

    protected $casts = [
        'payment_details' => \App\Casts\EncryptedJson::class, // snapshot of the vendor's payout details, encrypted at rest
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    /** Human-readable "send the money here" lines for the admin payout screen (super admins only). */
    public function payToLines(): array
    {
        $d = $this->payment_details ?? [];
        $lines = [];
        if (!empty($d['bank'])) {
            $b = $d['bank'];
            $lines = array_filter([
                ($b['name'] ?? null) ? 'Bank: ' . $b['name'] : null,
                ($b['account_name'] ?? null) ? 'Account holder: ' . $b['account_name'] : null,
                ($b['account_number'] ?? null) ? 'Account number: ' . $b['account_number'] : null,
                ($b['routing_number'] ?? null) ? 'Routing number: ' . $b['routing_number'] : null,
                ($b['swift_code'] ?? null) ? 'SWIFT/BIC: ' . $b['swift_code'] : null,
            ]);
        }
        if (!empty($d['paypal']['email'])) {
            $lines[] = 'PayPal: ' . $d['paypal']['email'];
        }
        if (!empty($d['stripe']['account_id'])) {
            $lines[] = 'Stripe account: ' . $d['stripe']['account_id'];
        }

        return array_values($lines);
    }
}
