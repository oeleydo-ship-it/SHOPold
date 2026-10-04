import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Eye, Plus, Search } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { Badge, Button, Card, Input, LinkButton, Pagination, Select } from '../../../Components/ui';
import { DataTable, RowActions } from '../../../Components/crud';

export const ROLE_LABEL = { super_admin: 'Super Admin', manager: 'Manager', editor: 'Editor' };
const ROLE_TONE = { super_admin: 'danger', manager: 'primary', editor: 'info' };

export function RoleBadge({ user }) {
    return user.is_admin
        ? <Badge tone={ROLE_TONE[user.admin_role] ?? 'muted'}>{ROLE_LABEL[user.admin_role] ?? 'Admin'}</Badge>
        : <Badge>Customer</Badge>;
}

export function Avatar({ user, size = 'h-10 w-10' }) {
    return user.avatar
        ? <img src={user.avatar} alt="" className={`${size} shrink-0 rounded-full object-cover`} />
        : <span className={`${size} flex shrink-0 items-center justify-center rounded-full bg-surface-2 font-medium text-muted`}>{user.name?.[0]?.toUpperCase()}</span>;
}

export default function Index({ users, filters }) {
    const [form, setForm] = useState({ search: filters.search ?? '', role: filters.role ?? '', status: filters.status ?? '' });
    const hasFilters = !!(filters.search || filters.role || filters.status);
    const set = (k) => (e) => setForm((f) => ({ ...f, [k]: e.target.value }));
    const go = (e) => {
        e.preventDefault();
        router.get('/admin/users', Object.fromEntries(Object.entries(form).filter(([, v]) => v)), { preserveState: true });
    };

    const columns = [
        {
            key: 'name', label: 'User',
            render: (u) => (
                <div className="flex items-center gap-3">
                    <Avatar user={u} />
                    <div className="min-w-0">
                        <Link href={`/admin/users/${u.id}`} className="font-medium hover:text-primary">{u.name}</Link>
                        <p className="truncate text-xs text-muted">{u.email}</p>
                    </div>
                </div>
            ),
        },
        { key: 'contact', label: 'Contact', render: (u) => <div><p>{u.phone || '-'}</p><p className="text-xs text-muted">{u.location ?? '-'}</p></div> },
        { key: 'role', label: 'Role', render: (u) => <RoleBadge user={u} /> },
        { key: 'status', label: 'Status', render: (u) => <Badge tone={u.is_active ? 'success' : 'danger'}>{u.is_active ? 'Active' : 'Inactive'}</Badge> },
        { key: 'orders_count', label: 'Orders', className: 'font-medium' },
        { key: 'total_spent', label: 'Total Spent', render: (u) => <span className="font-medium text-success">{u.total_spent}</span> },
        { key: 'joined', label: 'Joined', render: (u) => <div className="text-muted">{u.joined}{u.last_login && <p className="text-xs">Last login: {u.last_login}</p>}</div> },
        {
            key: 'actions', label: '', className: 'text-right',
            render: (u) => (
                <RowActions
                    editHref={`/admin/users/${u.id}/edit`}
                    deleteHref={u.is_self ? null : `/admin/users/${u.id}`}
                    deleteMessage={`Delete ${u.name}? This cannot be undone.`}
                >
                    <Link href={`/admin/users/${u.id}`} aria-label={`View ${u.name}`} title="View" className="text-muted hover:text-ink"><Eye size={18} /></Link>
                </RowActions>
            ),
        },
    ];

    return (
        <AdminLayout title="Users">
            <Head title="Users" />
            <div className="mb-4 flex justify-end"><LinkButton href="/admin/users/create"><Plus size={16} />Add User</LinkButton></div>

            <Card className="mb-4 p-3 md:p-4">
                <form onSubmit={go} className="flex flex-col gap-3 md:flex-row">
                    <div className="relative flex-1">
                        <Search size={16} className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
                        <Input value={form.search} onChange={set('search')} placeholder="Search by name, email or phone…" className="pl-9" aria-label="Search users" />
                    </div>
                    <div className="grid grid-cols-2 gap-2 md:flex">
                        <Select value={form.role} onChange={set('role')} aria-label="Role">
                            <option value="">All Roles</option>
                            <option value="super_admin">Super Admin</option>
                            <option value="manager">Manager</option>
                            <option value="editor">Editor</option>
                            <option value="customer">Customers</option>
                        </Select>
                        <Select value={form.status} onChange={set('status')} aria-label="Status">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </Select>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" className="flex-1">Filter</Button>
                        {hasFilters && <Button tone="neutral" onClick={() => { setForm({ search: '', role: '', status: '' }); router.get('/admin/users'); }}>Clear</Button>}
                    </div>
                </form>
            </Card>

            <Card className="overflow-hidden">
                <DataTable columns={columns} rows={users.data} empty={hasFilters ? 'No users match your filters.' : 'No users yet.'} />
                <Pagination links={users.links} from={users.from} to={users.to} total={users.total} />
            </Card>
        </AdminLayout>
    );
}
