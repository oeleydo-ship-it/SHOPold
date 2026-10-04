<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    protected function vendor()
    {
        return Auth::user()->vendor;
    }

    public function index()
    {
        $vendor = $this->vendor();
        
        // Calculate real-time earnings
        $vendor->total_sales = $vendor->calculated_total_sales;
        $vendor->total_earnings = $vendor->calculated_total_earnings;
        $vendor->balance = $vendor->calculated_balance;
        
        return view('vendor.settings.index', compact('vendor'));
    }

    public function update(Request $request)
    {
        $vendor = $this->vendor();

        $request->validate([
            'store_name' => 'required|string|max:255|unique:vendors,store_name,' . $vendor->id,
            'description' => 'nullable|string',
            'phone' => 'required|string|max:20',
            'email' => 'required|email',
            'address' => 'required|string',
            'city' => 'required|string',
            'state' => 'nullable|string',
            'country' => 'required|string',
            'zip_code' => 'nullable|string',
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
            $data['social_links'] = array_filter($request->social_links);
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
        
        // Calculate real-time earnings
        $vendor->total_sales = $vendor->calculated_total_sales;
        $vendor->total_earnings = $vendor->calculated_total_earnings;
        $vendor->balance = $vendor->calculated_balance;
        
        return view('vendor.settings.payouts', compact('vendor', 'payouts'));
    }

    public function paymentSettings()
    {
        $vendor = $this->vendor();
        return view('vendor.settings.payment', compact('vendor'));
    }

    public function updatePaymentSettings(Request $request)
    {
        $vendor = $this->vendor();

        $request->validate([
            'preferred_method' => 'required|in:bank_transfer,paypal,stripe',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_routing_number' => 'nullable|string|max:50',
            'bank_swift_code' => 'nullable|string|max:20',
            'paypal_email' => 'nullable|email|max:255',
            'stripe_account_id' => 'nullable|string|max:255',
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
        $availableBalance = $vendor->calculated_balance;

        $request->validate([
            'amount' => 'required|numeric|min:50|max:' . $availableBalance,
        ]);

        // Check if vendor has payment info set up
        if (empty($vendor->payment_info) || empty($vendor->payment_info['preferred_method'])) {
            return redirect()->route('vendor.payment-settings')
                ->with('error', 'Please set up your payment information before requesting a payout.');
        }

        $paymentMethod = $vendor->payment_info['preferred_method'];
        $paymentDetails = '';

        switch ($paymentMethod) {
            case 'bank_transfer':
                $bank = $vendor->payment_info['bank'] ?? [];
                $paymentDetails = "Bank: {$bank['name']}\nAccount: {$bank['account_name']}\nNumber: {$bank['account_number']}";
                break;
            case 'paypal':
                $paymentDetails = "PayPal: " . ($vendor->payment_info['paypal']['email'] ?? '');
                break;
            case 'stripe':
                $paymentDetails = "Stripe: " . ($vendor->payment_info['stripe']['account_id'] ?? '');
                break;
        }

        \App\Models\VendorPayout::create([
            'vendor_id' => $vendor->id,
            'amount' => $request->amount,
            'payment_method' => $paymentMethod,
            'notes' => $paymentDetails,
            'status' => 'pending',
        ]);

        return redirect()->back()->with('success', 'Payout request submitted successfully! We will process it within 3-5 business days.');
    }
}



