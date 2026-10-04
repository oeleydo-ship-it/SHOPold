<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\DriverNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = DriverNotification::where('driver_id', Auth::id())
            ->with('order')
            ->latest()
            ->paginate(30);

        return Inertia::render('Driver/Notifications', [
            'notifications' => $notifications->through(fn ($n) => [
                'id' => $n->id,
                'type' => $n->type,
                'title' => $n->title,
                'message' => $n->message,
                'read' => (bool) $n->is_read,
                'order_id' => $n->order_id,
                'order' => $n->order?->order_number,
                'date' => $n->created_at->diffForHumans(),
            ]),
        ]);
    }

    public function markAsRead(DriverNotification $notification)
    {
        if ((int) $notification->driver_id !== (int) Auth::id()) {
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
