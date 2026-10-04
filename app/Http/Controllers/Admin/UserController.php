<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /** Non-super admins may never view-to-modify, change or delete a super admin account. */
    private function guardTarget(User $target): void
    {
        abort_if(
            $target->isSuperAdmin() && !auth()->user()->isSuperAdmin(),
            403,
            'Only a super admin can modify a super admin account.'
        );
    }

    /**
     * The admin role to store for a requested value. Only super admins can grant super_admin;
     * everyone else can hand out manager/editor, and otherwise keeps the target's existing role.
     */
    private function resolveRole(?string $requested, ?string $existing = null): string
    {
        $valid = ['super_admin', 'manager', 'editor'];
        $requested = in_array($requested, $valid, true) ? $requested : null;

        if (auth()->user()->isSuperAdmin()) {
            return $requested ?? (in_array($existing, $valid, true) ? $existing : 'manager');
        }

        abort_if($requested === 'super_admin', 403, 'Only a super admin can grant the super admin role.');

        if (in_array($requested, ['manager', 'editor'], true)) {
            return $requested;
        }

        return in_array($existing, ['manager', 'editor'], true) ? $existing : 'manager';
    }

    public function index(Request $request)
    {
        $query = User::withCount('orders')
            ->withSum(['orders as paid_total' => fn ($q) => $q->where('payment_status', 'paid')], 'total');

        // Search filter
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%');
            });
        }

        // Role filter
        if ($request->role === 'super_admin') {
            $query->where('is_admin', true)->where('admin_role', 'super_admin');
        } elseif ($request->role === 'manager') {
            $query->where('is_admin', true)->where('admin_role', 'manager');
        } elseif ($request->role === 'editor') {
            $query->where('is_admin', true)->where('admin_role', 'editor');
        } elseif ($request->role === 'customer') {
            $query->where('is_admin', false);
        }

        // Status filter
        if ($request->status === 'active') {
            $query->where('is_active', true);
        } elseif ($request->status === 'inactive') {
            $query->where('is_active', false);
        }

        $users = $query->latest()->paginate(15)->withQueryString()->through(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'phone' => $u->phone,
            'avatar' => $u->avatar ? asset('storage/' . $u->avatar) : null,
            'location' => $u->city ? $u->city . ', ' . $u->country : null,
            'is_admin' => (bool) $u->is_admin,
            'admin_role' => $u->admin_role,
            'is_active' => (bool) $u->is_active,
            'orders_count' => $u->orders_count,
            'total_spent' => format_currency($u->paid_total ?? 0),
            'joined' => $u->created_at->format('M d, Y'),
            'last_login' => $u->last_login_at?->diffForHumans(),
            'is_self' => $u->id === auth()->id(),
        ]);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only('search', 'role', 'status'),
        ]);
    }

    /** Options shared by the create and edit forms. */
    private function formOptions(): array
    {
        return [
            'options' => [
                'driverPortal' => (bool) setting('features.driver_portal'),
                'canAssignSuper' => (bool) auth()->user()->isSuperAdmin(),
            ],
        ];
    }

    public function create()
    {
        return Inertia::render('Admin/Users/Form', $this->formOptions());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'admin_role' => 'nullable|in:super_admin,manager,editor',
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'company' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
            'company' => $request->company,
            'website' => $request->website,
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'zip_code' => $request->zip_code,
            'country' => $request->country,
            'notes' => $request->notes,
            'is_admin' => $request->boolean('is_admin', false),
            'admin_role' => $request->boolean('is_admin', false) ? $this->resolveRole($request->admin_role) : null,
            'is_active' => $request->boolean('is_active', true),
        ];

        // Handle driver fields (always process if is_driver is set)
        $userData['is_driver'] = $request->boolean('is_driver', false);
        if ($userData['is_driver']) {
            $userData['driver_phone'] = $request->driver_phone;
            $userData['driver_vehicle'] = $request->driver_vehicle;
            $userData['driver_license'] = $request->driver_license;
            $userData['driver_status'] = $request->driver_status ?? 'offline';
        }

        User::create($userData);

        return redirect()->route('admin.users.index')->with('success', 'User created successfully!');
    }

    public function show(User $user)
    {
        $user->load(['orders' => function ($query) {
            $query->latest();
        }, 'reviews', 'wishlist']);
        
        $fmt = fn ($d, $f = 'M d, Y') => $d?->format($f);

        return Inertia::render('Admin/Users/Show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar ? asset('storage/' . $user->avatar) : null,
                'is_admin' => (bool) $user->is_admin,
                'admin_role' => $user->admin_role,
                'is_active' => (bool) $user->is_active,
                'phone' => $user->phone,
                'date_of_birth' => $fmt($user->date_of_birth),
                'gender' => $user->gender,
                'company' => $user->company,
                'website' => $user->website,
                'address' => $user->address,
                'city' => $user->city,
                'state' => $user->state,
                'zip_code' => $user->zip_code,
                'country' => $user->country,
                'notes' => $user->notes,
                'joined' => $fmt($user->created_at),
                'last_login' => $fmt($user->last_login_at, 'M d, Y H:i'),
                'last_login_ip' => $user->last_login_ip,
                'orders_count' => $user->orders->count(),
                'total_spent' => format_currency($user->total_spent),
                'reviews_count' => $user->reviews->count(),
                'wishlist_count' => $user->wishlist->count(),
                'recent_orders' => $user->orders->take(5)->map(fn ($o) => [
                    'id' => $o->id,
                    'number' => $o->order_number,
                    'date' => $fmt($o->created_at),
                    'status' => $o->status,
                    'total' => format_currency($o->total),
                ])->values(),
            ],
        ]);
    }

    public function edit(User $user)
    {
        $this->guardTarget($user);
        $user->loadCount(['orders', 'reviews', 'wishlist']);
        $isDriver = (bool) $user->is_driver;

        return Inertia::render('Admin/Users/Form', $this->formOptions() + [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'date_of_birth' => $user->date_of_birth?->format('Y-m-d'),
                'gender' => $user->gender,
                'company' => $user->company,
                'website' => $user->website,
                'address' => $user->address,
                'city' => $user->city,
                'state' => $user->state,
                'zip_code' => $user->zip_code,
                'country' => $user->country,
                'notes' => $user->notes,
                'is_active' => (bool) $user->is_active,
                'is_admin' => (bool) $user->is_admin,
                'admin_role' => $user->admin_role,
                'is_driver' => $isDriver,
                'driver_phone' => $user->driver_phone,
                'driver_vehicle' => $user->driver_vehicle,
                'driver_license' => $user->driver_license,
                'driver_status' => $user->driver_status,
                'is_self' => $user->id === auth()->id(),
                'stats' => [
                    'orders' => $user->orders_count,
                    'spent' => format_currency($user->total_spent),
                    'since' => $user->created_at->format('M d, Y'),
                    'last_login' => $user->last_login_at?->diffForHumans(),
                    'assigned_deliveries' => $isDriver ? $user->driverOrders()->whereIn('status', ['processing', 'shipped'])->count() : null,
                    'completed_deliveries' => $isDriver ? $user->driverOrders()->where('status', 'delivered')->count() : null,
                ],
            ],
        ]);
    }

    public function update(Request $request, User $user)
    {
        $this->guardTarget($user);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'admin_role' => 'nullable|in:super_admin,manager,editor',
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'company' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
            'company' => $request->company,
            'website' => $request->website,
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'zip_code' => $request->zip_code,
            'country' => $request->country,
            'notes' => $request->notes,
            'is_active' => $request->boolean('is_active', true),
        ];

        // Only update is_admin if not the current user
        if ($user->id !== auth()->id()) {
            $data['is_admin'] = $request->boolean('is_admin', false);
            $data['admin_role'] = $data['is_admin'] ? $this->resolveRole($request->admin_role, $user->admin_role) : null;
        } elseif ($user->is_admin && auth()->user()->isSuperAdmin()) {
            // A super admin may change their own role, but cannot demote themselves
            if (!($user->isSuperAdmin() && $request->admin_role !== 'super_admin')) {
                $data['admin_role'] = $this->resolveRole($request->admin_role, $user->admin_role);
            }
        }
        // Anyone else keeps their own role: nobody can promote themselves.

        // Handle driver fields
        if (setting('features.driver_portal')) {
            $data['is_driver'] = $request->boolean('is_driver', false);
            $data['driver_phone'] = $request->driver_phone;
            $data['driver_vehicle'] = $request->driver_vehicle;
            $data['driver_license'] = $request->driver_license;
            $data['driver_status'] = $request->driver_status ?? 'offline';
        }

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully!');
    }

    public function destroy(User $user)
    {
        $this->guardTarget($user);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully!');
    }
}
