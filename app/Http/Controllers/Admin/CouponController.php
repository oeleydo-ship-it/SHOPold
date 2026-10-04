<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CouponController extends Controller
{
    public function index()
    {
        $coupons = Coupon::latest()->paginate(15)->through(fn ($c) => [
            'id' => $c->id,
            'code' => $c->code,
            'type' => $c->type,
            'value' => $c->type === 'percent' ? $c->value . '%' : format_currency($c->value),
            'used_count' => $c->used_count,
            'max_uses' => $c->max_uses,
            'expires_at' => $c->expires_at?->format('M d, Y'),
            'is_active' => (bool) $c->is_active,
        ]);

        return Inertia::render('Admin/Coupons/Index', ['coupons' => $coupons]);
    }

    public function create()
    {
        return Inertia::render('Admin/Coupons/Form');
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|unique:coupons|max:50',
            'type' => 'required|in:fixed,percent',
            'value' => 'required|numeric|min:0',
            'minimum_amount' => 'nullable|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'boolean',
        ]);

        Coupon::create([
            'code' => strtoupper($request->code),
            'type' => $request->type,
            'value' => $request->value,
            'minimum_amount' => $request->minimum_amount,
            'max_uses' => $request->max_uses,
            'starts_at' => $request->starts_at,
            'expires_at' => $request->expires_at,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon created successfully!');
    }

    public function edit(Coupon $coupon)
    {
        return Inertia::render('Admin/Coupons/Form', [
            'coupon' => $coupon->only(['id', 'code', 'type', 'value', 'minimum_amount', 'max_uses', 'is_active']) + [
                'starts_at' => $coupon->starts_at?->format('Y-m-d'),
                'expires_at' => $coupon->expires_at?->format('Y-m-d'),
            ],
        ]);
    }

    public function update(Request $request, Coupon $coupon)
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:coupons,code,' . $coupon->id,
            'type' => 'required|in:fixed,percent',
            'value' => 'required|numeric|min:0',
            'minimum_amount' => 'nullable|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'boolean',
        ]);

        $coupon->update([
            'code' => strtoupper($request->code),
            'type' => $request->type,
            'value' => $request->value,
            'minimum_amount' => $request->minimum_amount,
            'max_uses' => $request->max_uses,
            'starts_at' => $request->starts_at,
            'expires_at' => $request->expires_at,
            'is_active' => $request->boolean('is_active', false),
        ]);

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon updated successfully!');
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();
        return redirect()->route('admin.coupons.index')->with('success', 'Coupon deleted successfully!');
    }
}
