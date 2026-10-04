<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Newsletter;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NewsletterController extends Controller
{
    public function index(Request $request)
    {
        $query = Newsletter::query();

        if ($request->filled('search')) {
            $query->where('email', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'unsubscribed') {
                $query->whereNotNull('unsubscribed_at');
            }
        }

        $subscribers = $query->latest()->paginate(20)->withQueryString()->through(fn ($n) => [
            'id' => $n->id,
            'email' => $n->email,
            'unsubscribed' => (bool) $n->unsubscribed_at,
            'subscribed_at' => $n->subscribed_at?->format('M d, Y H:i'),
        ]);

        return Inertia::render('Admin/Newsletters/Index', [
            'subscribers' => $subscribers,
            'filters' => $request->only('search', 'status'),
            'totalActive' => Newsletter::active()->count(),
            'totalUnsubscribed' => Newsletter::whereNotNull('unsubscribed_at')->count(),
        ]);
    }

    public function destroy(Newsletter $newsletter)
    {
        $newsletter->delete();
        return back()->with('success', 'Subscriber deleted successfully.');
    }

    public function export()
    {
        $subscribers = Newsletter::active()->pluck('email')->toArray();
        
        $filename = 'newsletter_subscribers_' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($subscribers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Email', 'Subscribed At']);
            
            foreach (Newsletter::active()->get() as $subscriber) {
                fputcsv($file, [
                    $subscriber->email,
                    $subscriber->subscribed_at?->format('Y-m-d H:i:s')
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
