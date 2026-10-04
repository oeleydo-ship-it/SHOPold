<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\VendorPayout;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VendorController extends Controller
{
    /**
     * Check if multi-vendor feature is enabled
     */
    private function checkFeatureEnabled()
    {
        if (!setting('features.multi_vendor')) {
            abort(404, 'Multi-vendor feature is not enabled.');
        }
    }

    public function index(Request $request)
    {
        $this->checkFeatureEnabled();
        $query = Vendor::with('user')->withCount('products');

        if ($request->filled('search')) {
            $query->where('store_name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $vendors = $query->latest()->paginate(15);

        return Inertia::render('Admin/Vendors/Index', [
            'vendors' => [
                'data' => $vendors->getCollection()->map(fn ($v) => [
                    'id' => $v->id, 'store_name' => $v->store_name, 'email' => $v->email, 'logo' => $v->logo_url,
                    'owner' => $v->user?->name, 'products_count' => $v->products_count ?? 0, 'status' => $v->status,
                    // real-time figures, same as the old list
                    'sales' => format_currency($v->calculated_total_sales), 'balance' => format_currency($v->calculated_balance),
                ])->values(),
                'links' => $vendors->linkCollection()->toArray(), 'from' => $vendors->firstItem(), 'to' => $vendors->lastItem(), 'total' => $vendors->total(),
            ],
            'filters' => ['search' => (string) $request->search, 'status' => (string) $request->status],
        ]);
    }

    public function show(Vendor $vendor)
    {
        $this->checkFeatureEnabled();
        $vendor->load(['user', 'products']);
        $rate = (float) ($vendor->commission_rate ?? 0);
        $sales = $vendor->calculated_total_sales;

        return Inertia::render('Admin/Vendors/Show', [
            'vendor' => [
                'id' => $vendor->id, 'store_name' => $vendor->store_name, 'email' => $vendor->email, 'phone' => $vendor->phone,
                'logo' => $vendor->logo_url, 'owner' => $vendor->user?->name, 'registered' => $vendor->created_at?->format('M d, Y'),
                'address' => $vendor->address, 'description' => $vendor->description, 'status' => $vendor->status,
                'commission_rate' => $vendor->commission_rate ?? setting('features.vendor_commission', 10),
                'rating' => number_format((float) $vendor->average_rating, 1), 'rating_count' => (int) $vendor->rating_count,
                'products_count' => $vendor->products->count(),
                'products' => $vendor->products->take(5)->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'price' => format_currency($p->price), 'is_active' => (bool) $p->is_active])->values(),
            ],
            'earnings' => [
                'sales' => format_currency($sales), 'fee_rate' => $rate, 'fee' => format_currency($sales * ($rate / 100)),
                'earnings' => format_currency($vendor->calculated_total_earnings), 'paid_out' => format_currency($vendor->total_paid_out),
                'balance' => format_currency($vendor->calculated_balance),
            ],
        ]);
    }

    public function updateStatus(Request $request, Vendor $vendor)
    {
        $this->checkFeatureEnabled();
        $request->validate([
            'status' => 'required|in:pending,approved,suspended,rejected'
        ]);

        $vendor->update([
            'status' => $request->status,
            'approved_at' => $request->status === 'approved' ? now() : $vendor->approved_at
        ]);

        return redirect()->back()->with('success', 'Vendor status updated!');
    }

    public function payouts(Vendor $vendor)
    {
        $this->checkFeatureEnabled();
        $payouts = $vendor->payouts()->latest()->paginate(15);
        $balance = $vendor->calculated_balance;

        return Inertia::render('Admin/Vendors/Payouts', [
            'vendor' => ['id' => $vendor->id, 'store_name' => $vendor->store_name],
            'balance' => ['raw' => max(0, round((float) $balance, 2)), 'label' => format_currency($balance)],
            'payouts' => [
                'data' => $payouts->getCollection()->map(fn ($p) => [
                    'id' => $p->id, 'date' => $p->created_at?->format('M d, Y'), 'amount' => format_currency($p->amount),
                    'method' => $p->payment_method, 'status' => $p->status,
                ])->values(),
                'links' => $payouts->linkCollection()->toArray(), 'from' => $payouts->firstItem(), 'to' => $payouts->lastItem(), 'total' => $payouts->total(),
            ],
        ]);
    }

    public function createPayout(Request $request, Vendor $vendor)
    {
        $this->checkFeatureEnabled();
        $request->validate([
            'amount' => 'required|numeric|min:1|max:' . $vendor->calculated_balance,
            'payment_method' => 'required|string',
            'notes' => 'nullable|string'
        ]);

        VendorPayout::create([
            'vendor_id' => $vendor->id,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'notes' => $request->notes,
            'status' => 'pending'
        ]);

        return redirect()->back()->with('success', 'Payout created!');
    }

    /**
     * Show all payout requests
     */
    public function allPayouts(Request $request)
    {
        $this->checkFeatureEnabled();
        $query = VendorPayout::with('vendor');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->whereHas('vendor', function ($q) use ($request) {
                $q->where('store_name', 'like', '%' . $request->search . '%');
            });
        }

        $payouts = $query->latest()->paginate(20);

        // Calculate totals
        $pendingTotal = VendorPayout::where('status', 'pending')->sum('amount');
        $processingTotal = VendorPayout::where('status', 'processing')->sum('amount');
        $completedTotal = VendorPayout::where('status', 'completed')->sum('amount');

        return Inertia::render('Admin/Vendors/PayoutRequests', [
            'payouts' => [
                'data' => $payouts->getCollection()->map(fn ($p) => [
                    'id' => $p->id, 'vendor' => $p->vendor?->store_name, 'vendor_email' => $p->vendor?->email, 'vendor_logo' => $p->vendor?->logo_url,
                    'vendor_id' => $p->vendor_id, 'amount' => format_currency($p->amount), 'method' => $p->payment_method, 'status' => $p->status,
                    'transaction_id' => $p->transaction_id, 'notes' => $p->notes, 'date' => $p->created_at?->format('M d, Y'),
                ])->values(),
                'links' => $payouts->linkCollection()->toArray(), 'from' => $payouts->firstItem(), 'to' => $payouts->lastItem(), 'total' => $payouts->total(),
            ],
            'totals' => ['pending' => format_currency($pendingTotal), 'processing' => format_currency($processingTotal), 'completed' => format_currency($completedTotal)],
            'filters' => ['search' => (string) $request->search, 'status' => (string) $request->status],
        ]);
    }

    /**
     * Update payout status
     */
    public function updatePayoutStatus(Request $request, VendorPayout $payout)
    {
        $this->checkFeatureEnabled();
        $request->validate([
            'status' => 'required|in:pending,processing,completed,failed',
            'transaction_id' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $updateData = [
            'status' => $request->status,
        ];

        if ($request->filled('transaction_id')) {
            $updateData['transaction_id'] = $request->transaction_id;
        }

        if ($request->filled('notes')) {
            $updateData['notes'] = $request->notes;
        }

        if ($request->status === 'completed') {
            $updateData['processed_at'] = now();
        }

        $payout->update($updateData);

        return redirect()->back()->with('success', 'Payout status updated!');
    }
}
