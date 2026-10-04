<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $query = Review::with(['user', 'product']);

        if ($request->has('approved') && $request->approved !== '') {
            $query->where('is_approved', $request->approved);
        }

        $reviews = $query->latest()->paginate(15)->withQueryString()->through(fn ($r) => [
            'id' => $r->id,
            'product' => $r->product?->name,
            'customer' => $r->user?->name,
            'rating' => (int) $r->rating,
            'comment' => $r->comment,
            'date' => $r->created_at->format('M d, Y'),
            'is_approved' => (bool) $r->is_approved,
        ]);

        return Inertia::render('Admin/Reviews/Index', [
            'reviews' => $reviews,
            'filters' => $request->only('approved'),
        ]);
    }

    public function approve(Review $review)
    {
        $review->update(['is_approved' => true]);
        return back()->with('success', 'Review approved successfully!');
    }

    public function reject(Review $review)
    {
        $review->update(['is_approved' => false]);
        return back()->with('success', 'Review rejected.');
    }

    public function destroy(Review $review)
    {
        $review->delete();
        return back()->with('success', 'Review deleted successfully!');
    }
}
