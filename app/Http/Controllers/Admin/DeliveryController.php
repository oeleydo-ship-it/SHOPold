<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliverySlot;
use App\Models\PickupLocation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DeliveryController extends Controller
{
    /**
     * Delivery settings overview
     */
    public function index()
    {
        $slots = DeliverySlot::orderBy('sort_order')->get();
        $locations = PickupLocation::all();
        
        return Inertia::render('Admin/Delivery/Index', [
            'slots' => $slots->map(fn ($s) => [
                'id' => $s->id, 'name' => $s->name, 'start_time' => substr((string) $s->start_time, 0, 5), 'end_time' => substr((string) $s->end_time, 0, 5),
                'time_range' => $s->time_range, 'max_orders' => (int) $s->max_orders, 'extra_charge' => (string) ($s->extra_charge ?? 0),
                'days' => array_map('intval', $s->available_days ?? [0, 1, 2, 3, 4, 5, 6]), 'is_active' => (bool) $s->is_active,
            ])->values(),
            'locations' => $locations->map(fn ($l) => [
                'id' => $l->id, 'name' => $l->name, 'address' => $l->address, 'city' => $l->city, 'state' => (string) $l->state, 'zip' => (string) $l->zip,
                'country' => $l->country, 'phone' => (string) $l->phone, 'email' => (string) $l->email, 'instructions' => (string) $l->instructions,
                'is_active' => (bool) $l->is_active,
            ])->values(),
        ]);
    }

    /**
     * Store a new delivery slot
     */
    public function storeSlot(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'max_orders' => 'required|integer|min:1',
            'extra_charge' => 'nullable|numeric|min:0',
            'available_days' => 'nullable|array',
            'available_days.*' => 'integer|between:0,6',
        ]);

        DeliverySlot::create([
            'name' => $request->name,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'max_orders' => $request->max_orders,
            'extra_charge' => $request->extra_charge ?? 0,
            'available_days' => $request->available_days ?? [0, 1, 2, 3, 4, 5, 6],
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => DeliverySlot::max('sort_order') + 1,
        ]);

        return back()->with('success', 'Delivery slot created successfully!');
    }

    /**
     * Update a delivery slot
     */
    public function updateSlot(Request $request, DeliverySlot $slot)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'max_orders' => 'required|integer|min:1',
            'extra_charge' => 'nullable|numeric|min:0',
            'available_days' => 'nullable|array',
        ]);

        $slot->update([
            'name' => $request->name,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'max_orders' => $request->max_orders,
            'extra_charge' => $request->extra_charge ?? 0,
            'available_days' => $request->available_days ?? [0, 1, 2, 3, 4, 5, 6],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Delivery slot updated successfully!');
    }

    /**
     * Delete a delivery slot
     */
    public function destroySlot(DeliverySlot $slot)
    {
        $slot->delete();
        return back()->with('success', 'Delivery slot deleted successfully!');
    }

    /**
     * Store a new pickup location
     */
    public function storeLocation(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'city' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'state' => 'nullable|string|max:255',
            'zip' => 'nullable|string|max:50',
            'instructions' => 'nullable|string',
        ]);

        PickupLocation::create($data + ['is_active' => $request->boolean('is_active', true)]);

        return back()->with('success', 'Pickup location created successfully!');
    }

    /**
     * Update a pickup location
     */
    public function updateLocation(Request $request, PickupLocation $location)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'city' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'state' => 'nullable|string|max:255',
            'zip' => 'nullable|string|max:50',
            'instructions' => 'nullable|string',
        ]);

        $location->update($data + ($request->has('is_active') ? ['is_active' => $request->boolean('is_active')] : []));

        return back()->with('success', 'Pickup location updated successfully!');
    }

    /**
     * Delete a pickup location
     */
    public function destroyLocation(PickupLocation $location)
    {
        $location->delete();
        return back()->with('success', 'Pickup location deleted successfully!');
    }

    /**
     * Toggle slot status
     */
    public function toggleSlot(DeliverySlot $slot)
    {
        $slot->update(['is_active' => !$slot->is_active]);
        return back()->with('success', 'Slot status updated!');
    }

    /**
     * Toggle location status
     */
    public function toggleLocation(PickupLocation $location)
    {
        $location->update(['is_active' => !$location->is_active]);
        return back()->with('success', 'Location status updated!');
    }
}
