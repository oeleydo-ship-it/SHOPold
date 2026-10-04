<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SettingController extends Controller
{
    /** Smallest payout a vendor can request. */
    protected const MIN_PAYOUT = 50;

    protected function vendor()
    {
        return Auth::user()->vendor;
    }

    /** Earnings minus completed/processing payouts minus requests still waiting, so a balance can't be requested twice. */
    protected function requestable($vendor): float
    {
        return max(0, round($vendor->calculated_balance - $vendor->payouts()->where('status', 'pending')->sum('amount'), 2));
    }

    public function index()
    {
        $vendor = $this->vendor();

        return Inertia::render('Vendor/Settings/Store', [
            'store' => [
                'store_name' => $vendor->store_name,
                'slug' => $vendor->slug,
                'url' => route('shop.vendor', $vendor->slug),
                'description' => $vendor->description,
                'phone' => $vendor->phone,
                'email' => $vendor->email,
                'address' => $vendor->address,
                'city' => $vendor->city,
                'state' => $vendor->state,
                'country' => $vendor->country,
                'zip_code' => $vendor->zip_code,
                'logo' => $vendor->logo ? $vendor->logo_url : null,
                'banner' => $vendor->banner ? asset('storage/' . $vendor->banner) : null,
                'social_links' => [
                    'facebook' => $vendor->social_links['facebook'] ?? '',
                    'twitter' => $vendor->social_links['twitter'] ?? '',
                    'instagram' => $vendor->social_links['instagram'] ?? '',
                ],
                'status' => $vendor->status,
                'commission_rate' => (float) $vendor->commission_rate,
                'joined' => $vendor->created_at?->format('M d, Y'),
            ],
            'summary' => [
                'sales' => format_currency($vendor->calculated_total_sales),
                'earnings' => format_currency($vendor->calculated_total_earnings),
                'balance' => format_currency($vendor->calculated_balance),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $vendor = $this->vendor();

        $request->validate([
            'store_name' => 'required|string|max:255|unique:vendors,store_name,' . $vendor->id,
            'description' => 'nullable|string|max:5000',
            'phone' => 'required|string|max:20',
            'email' => 'required|email',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'required|string|max:255',
            'zip_code' => 'nullable|string|max:20',
            'logo' => 'nullable|image|max:2048',
            'banner' => 'nullable|image|max:4096',
            'social_links.facebook' => 'nullable|url',
            'social_links.twitter' => 'nullable|url',
            'social_links.instagram' => 'nullable|url',
        ]);

        $data = $request->only([
            'store_name', 'description', 'phone', 'email',
            'address', 'city', 'state', 'country', 'zip_code'
        ]);

        if ($request->has('social_links')) {
            $data['social_links'] = array_filter(array_intersect_key((array) $request->social_links, array_flip(['facebook', 'twitter', 'instagram'])));
        }

        if ($request->hasFile('logo')) {
            if ($vendor->logo) {
                Storage::disk('public')->delete($vendor->logo);
            }
            $data['logo'] = $request->file('logo')->store('vendors/logos', 'public');
        }

        if ($request->hasFile('banner')) {
            if ($vendor->banner) {
                Storage::disk('public')->delete($vendor->banner);
            }
            $data['banner'] = $request->file('banner')->store('vendors/banners', 'public');
        }

        $vendor->update($data);

        return redirect()->back()->with('success', 'Store settings updated successfully!');
    }

    public function payouts()
    {
        $vendor = $this->vendor();
        $payouts = $vendor->payouts()->latest()->paginate(15);
        $method = $vendor->payment_info['preferred_method'] ?? null;

        return Inertia::render('Vendor/Payouts', [
            'summary' => [
                'earnings' => format_currency($vendor->calculated_total_earnings),
                'balance' => format_currency($vendor->calculated_balance),
                'pending' => format_currency($vendor->pending_payout),
                'paid' => format_currency($vendor->total_paid_out),
            ],
            'requestable' => $this->requestable($vendor),
            'requestableLabel' => format_currency($this->requestable($vendor)),
            'minimum' => self::MIN_PAYOUT,
            'minimumLabel' => format_currency(self::MIN_PAYOUT),
            'method' => $method ? ucwords(str_replace('_', ' ', $method)) : null,
            'payouts' => $payouts->through(fn ($p) => [
                'id' => $p->id,
                'amount' => format_currency($p->amount),
                'method' => ucwords(str_replace('_', ' ', $p->payment_method)),
                'status' => $p->status,
                'reference' => $p->transaction_id,
                'date' => $p->created_at->format('M d, Y'),
                'processed' => $p->processed_at ? \Illuminate\Support\Carbon::parse($p->processed_at)->format('M d, Y') : null,
            ]),
        ]);
    }

    public function paymentSettings()
    {
        $info = $this->vendor()->payment_info ?? [];

        return Inertia::render('Vendor/PaymentSettings', [
            'info' => [
                'preferred_method' => $info['preferred_method'] ?? '',
                'bank_name' => $info['bank']['name'] ?? '',
                'bank_account_name' => $info['bank']['account_name'] ?? '',
                'bank_account_number' => $info['bank']['account_number'] ?? '',
                'bank_routing_number' => $info['bank']['routing_number'] ?? '',
                'bank_swift_code' => $info['bank']['swift_code'] ?? '',
                'paypal_email' => $info['paypal']['email'] ?? '',
                'stripe_account_id' => $info['stripe']['account_id'] ?? '',
            ],
        ]);
    }

    public function updatePaymentSettings(Request $request)
    {
        $vendor = $this->vendor();

        $request->validate([
            'preferred_method' => 'required|in:bank_transfer,paypal,stripe',
            'bank_name' => 'nullable|required_if:preferred_method,bank_transfer|string|max:255',
            'bank_account_name' => 'nullable|required_if:preferred_method,bank_transfer|string|max:255',
            'bank_account_number' => 'nullable|required_if:preferred_method,bank_transfer|string|max:50',
            'bank_routing_number' => 'nullable|string|max:50',
            'bank_swift_code' => 'nullable|string|max:20',
            'paypal_email' => 'nullable|required_if:preferred_method,paypal|email|max:255',
            'stripe_account_id' => 'nullable|required_if:preferred_method,stripe|string|max:255',
        ]);

        $paymentInfo = [
            'preferred_method' => $request->preferred_method,
            'bank' => [
                'name' => $request->bank_name,
                'account_name' => $request->bank_account_name,
                'account_number' => $request->bank_account_number,
                'routing_number' => $request->bank_routing_number,
                'swift_code' => $request->bank_swift_code,
            ],
            'paypal' => [
                'email' => $request->paypal_email,
            ],
            'stripe' => [
                'account_id' => $request->stripe_account_id,
            ],
        ];

        $vendor->update(['payment_info' => $paymentInfo]);

        return redirect()->back()->with('success', 'Payment settings updated successfully!');
    }

    public function requestPayout(Request $request)
    {
        $vendor = $this->vendor();

        // Check if vendor has payment info set up
        if (empty($vendor->payment_info) || empty($vendor->payment_info['preferred_method'])) {
            return redirect()->route('vendor.payment-settings')
                ->with('error', 'Please set up your payment information before requesting a payout.');
        }

        $request->validate([
            'amount' => 'required|numeric|min:' . self::MIN_PAYOUT . '|max:' . $this->requestable($vendor),
        ], [
            'amount.max' => 'You can request at most ' . format_currency($this->requestable($vendor)) . ' (your balance minus requests still pending).',
        ]);

        $paymentMethod = $vendor->payment_info['preferred_method'];
        $key = ['bank_transfer' => 'bank', 'paypal' => 'paypal', 'stripe' => 'stripe'][$paymentMethod] ?? null;

        \App\Models\VendorPayout::create([
            'vendor_id' => $vendor->id,
            'amount' => $request->amount,
            'payment_method' => $paymentMethod,
            // where to send the money, as it was when requested; encrypted at rest (EncryptedJson cast)
            'payment_details' => $key ? [$key => $vendor->payment_info[$key] ?? []] : null,
            'status' => 'pending',
        ]);

        return redirect()->back()->with('success', 'Payout request submitted successfully! We will process it within 3-5 business days.');
    }
}
