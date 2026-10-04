import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Eye, Repeat, UserPlus, Users } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import StatCard from '../../../Components/StatCard';
import { Badge, Card, CardHeader, Pagination } from '../../../Components/ui';
import { DataTable } from '../../../Components/crud';

export default function Customers({ customers, total, newCustomers, returningCustomers }) {
    const columns = [
        {
            key: 'name', label: 'Customer',
            render: (c) => (
                <div className="flex items-center gap-3">
                    <span className="flex h-9 w-9 items-center justify-center rounded-full bg-surface-2 font-medium text-muted">{c.name?.[0]?.toUpperCase()}</span>
                    <span className="font-medium">{c.name}</span>
                </div>
            ),
        },
        { key: 'email', label: 'Email', render: (c) => <span className="text-muted">{c.email}</span> },
        { key: 'orders_count', label: 'Orders', render: (c) => <Badge tone="info">{c.orders_count}</Badge> },
        { key: 'total_spent', label: 'Total Spent', className: 'font-medium' },
        { key: 'joined', label: 'Joined', render: (c) => <span className="text-muted">{c.joined}</span> },
        { key: 'actions', label: '', className: 'text-right', render: (c) => <Link href={`/admin/users/${c.id}`} aria-label={`View ${c.name}`} className="text-muted hover:text-ink"><Eye size={18} /></Link> },
    ];

    return (
        <AdminLayout title="Customers Report">
            <Head title="Customers Report" />
            <Link href="/admin/reports" className="mb-5 inline-flex items-center gap-2 text-sm text-muted hover:text-ink"><ArrowLeft size={16} />Back to Reports</Link>

            <div className="mb-5 grid gap-3 sm:grid-cols-3">
                <StatCard label="Total Customers" value={total} icon={Users} tone="primary" />
                <StatCard label="New (last 30 days)" value={newCustomers} icon={UserPlus} tone="success" />
                <StatCard label="Returning Customers" value={returningCustomers} icon={Repeat} tone="info" />
            </div>

            <Card className="overflow-hidden">
                <CardHeader title="Top Customers by Spending" />
                <DataTable columns={columns} rows={customers.data} empty="No customers yet." />
                <Pagination links={customers.links} from={customers.from} to={customers.to} total={customers.total} />
            </Card>
        </AdminLayout>
    );
}
