<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function index()
    {
        $driver = Auth::user();
        return view('driver.profile.index', compact('driver'));
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
            'password' => 'required|min:8|confirmed',
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
            'schedule.*.start' => 'nullable|string',
            'schedule.*.end' => 'nullable|string',
        ]);

        Auth::user()->update([
            'driver_availability_schedule' => $request->schedule
        ]);

        return back()->with('success', 'Availability schedule updated!');
    }
}
