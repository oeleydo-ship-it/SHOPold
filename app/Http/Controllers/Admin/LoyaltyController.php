<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserPoints;
use App\Models\PointsTransaction;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LoyaltyController extends Controller
{
    public function index(Request $request)
    {
        $query = UserPoints::with('user');

        if ($request->filled('search')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        $userPoints = $query->orderByDesc('balance')->paginate(15);

        $stats = [
            'total_points' => UserPoints::sum('balance'),
            'total_earned' => UserPoints::sum('lifetime_points'),
            'total_redeemed' => UserPoints::sum('redeemed_points'),
            'active_users' => UserPoints::where('balance', '>', 0)->count(),
        ];

        return Inertia::render('Admin/Loyalty/Index', [
            'points' => [
                'data' => $userPoints->getCollection()->map(fn ($p) => [
                    'id' => $p->id, 'user_id' => $p->user_id, 'name' => $p->user?->name, 'email' => $p->user?->email,
                    'balance' => (int) $p->balance, 'lifetime' => (int) $p->lifetime_points, 'redeemed' => (int) $p->redeemed_points,
                ])->values(),
                'links' => $userPoints->linkCollection()->toArray(), 'from' => $userPoints->firstItem(), 'to' => $userPoints->lastItem(), 'total' => $userPoints->total(),
            ],
            'stats' => array_map('intval', $stats),
            'users' => User::orderBy('name')->get(['id', 'name', 'email'])->map(fn ($u) => ['id' => $u->id, 'label' => "{$u->name} ({$u->email})"])->values(),
            'filters' => ['search' => (string) $request->search],
        ]);
    }

    public function transactions(Request $request)
    {
        $query = PointsTransaction::with('user');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $transactions = $query->latest()->paginate(20);

        return Inertia::render('Admin/Loyalty/Transactions', [
            'transactions' => [
                'data' => $transactions->getCollection()->map(fn ($t) => [
                    'id' => $t->id, 'date' => $t->created_at?->format('M d, Y H:i'), 'user' => $t->user?->name, 'type' => $t->type,
                    'points' => (int) $t->points, 'balance_after' => (int) $t->balance_after, 'description' => $t->description,
                ])->values(),
                'links' => $transactions->linkCollection()->toArray(), 'from' => $transactions->firstItem(), 'to' => $transactions->lastItem(), 'total' => $transactions->total(),
            ],
            'filters' => ['type' => (string) $request->type, 'user_id' => (string) $request->user_id],
        ]);
    }

    public function adjustPoints(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'points' => 'required|integer',
            'description' => 'required|string|max:255'
        ]);

        $userPoints = UserPoints::firstOrCreate(
            ['user_id' => $request->user_id],
            ['balance' => 0, 'lifetime_points' => 0, 'redeemed_points' => 0]
        );

        $newBalance = $userPoints->balance + $request->points;
        
        if ($newBalance < 0) {
            return redirect()->back()->with('error', 'Cannot reduce points below zero!');
        }

        PointsTransaction::create([
            'user_id' => $request->user_id,
            'type' => 'adjusted',
            'points' => $request->points,
            'balance_after' => $newBalance,
            'description' => $request->description,
            'reference_type' => 'admin',
        ]);

        $userPoints->update([
            'balance' => $newBalance,
            'lifetime_points' => $request->points > 0 ? $userPoints->lifetime_points + $request->points : $userPoints->lifetime_points
        ]);

        return redirect()->back()->with('success', 'Points adjusted successfully!');
    }
}
