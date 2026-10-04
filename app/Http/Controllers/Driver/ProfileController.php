<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class ProfileController extends Controller
{
    protected const DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    public function index()
    {
        $driver = Auth::user();
        $schedule = (array) ($driver->driver_availability_schedule ?? []);

        return Inertia::render('Driver/Profile', [
            'profile' => [
                'name' => $driver->name,
                'email' => $driver->email,
                'driver_phone' => $driver->driver_phone,
                'driver_vehicle' => $driver->driver_vehicle,
                'driver_license' => $driver->driver_license,
                'avatar' => $driver->avatar ? asset('storage/' . $driver->avatar) : null,
            ],
            'stats' => [
                'deliveries' => (int) $driver->driver_total_deliveries,
                'rating' => $driver->driver_rating !== null ? round((float) $driver->driver_rating, 1) : null,
                'ratings' => (int) $driver->driver_total_ratings,
                'earnings' => format_currency($driver->driver_total_earnings ?? 0),
                'commission' => (float) ($driver->driver_commission_rate ?? 10),
            ],
            'schedule' => collect(self::DAYS)->mapWithKeys(fn ($d) => [$d => [
                'enabled' => (bool) ($schedule[$d]['enabled'] ?? false),
                'start' => $schedule[$d]['start'] ?? '09:00',
                'end' => $schedule[$d]['end'] ?? '18:00',
            ]]),
        ]);
    }

    public function update(Request $request)
    {
        $driver = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $driver->id,
            'driver_phone' => 'required|string|max:20',
            'driver_vehicle' => 'nullable|string|max:100',
            'driver_license' => 'nullable|string|max:50',
            'avatar' => 'nullable|image|max:2048',
        ]);

        $data = $request->only(['name', 'email', 'driver_phone', 'driver_vehicle', 'driver_license']);

        if ($request->hasFile('avatar')) {
            // Delete old avatar
            if ($driver->avatar && Storage::disk('public')->exists($driver->avatar)) {
                Storage::disk('public')->delete($driver->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $driver->update($data);

        return back()->with('success', 'Profile updated successfully!');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $driver = Auth::user();

        if (!Hash::check($request->current_password, $driver->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect']);
        }

        $driver->update(['password' => Hash::make($request->password)]);

        return back()->with('success', 'Password changed successfully!');
    }

    public function updateSchedule(Request $request)
    {
        $request->validate([
            'schedule' => 'required|array',
            'schedule.*.enabled' => 'boolean',
            'schedule.*.start' => 'nullable|date_format:H:i',
            'schedule.*.end' => 'nullable|date_format:H:i',
        ]);

        // Keep only the seven known days and the three known keys.
        $schedule = [];
        foreach (self::DAYS as $day) {
            $d = (array) $request->input("schedule.$day", []);
            $schedule[$day] = ['enabled' => (bool) ($d['enabled'] ?? false), 'start' => $d['start'] ?? null, 'end' => $d['end'] ?? null];
        }

        Auth::user()->update([
            'driver_availability_schedule' => $schedule
        ]);

        return back()->with('success', 'Availability schedule updated!');
    }
}
