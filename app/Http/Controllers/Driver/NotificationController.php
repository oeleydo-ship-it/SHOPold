<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\DriverNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = DriverNotification::where('driver_id', Auth::id())
            ->with('order')
            ->latest()
            ->paginate(30);

        return view('driver.notifications.index', compact('notifications'));
    }

    public function markAsRead(DriverNotification $notification)
    {
        if ($notification->driver_id !== Auth::id()) {
            abort(403);
        }

        $notification->update(['is_read' => true]);

        return back();
    }

    public function markAllAsRead()
    {
        DriverNotification::where('driver_id', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return back()->with('success', 'All notifications marked as read');
    }

    public function getUnreadCount()
    {
        $count = DriverNotification::where('driver_id', Auth::id())
            ->where('is_read', false)
            ->count();

        return response()->json(['count' => $count]);
    }

    public function getRecent()
    {
        $notifications = DriverNotification::where('driver_id', Auth::id())
            ->latest()
            ->take(10)
            ->get();

        return response()->json($notifications);
    }
}
