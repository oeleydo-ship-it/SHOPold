import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import FilterBar from '../../../Components/FilterBar';
import { Badge, Card, Pagination, titleCase } from '../../../Components/ui';
import { DataTable } from '../../../Components/crud';

const TONE = { earned: 'success', redeemed: 'warning', adjusted: 'info', bonus: 'primary', expired: 'danger' };
const TYPES = ['earned', 'redeemed', 'adjusted', 'bonus', 'expired'].map((t) => [t, titleCase(t)]);
const fmt = (n) => Number(n).toLocaleString();

export default function Transactions({ transactions, filters }) {
    const columns = [
        { key: 'date', label: 'Date' },
        { key: 'user', label: 'User', render: (t) => t.user ?? 'Unknown' },
        { key: 'type', label: 'Type', render: (t) => <Badge tone={TONE[t.type] ?? 'muted'}>{titleCase(t.type)}</Badge> },
        { key: 'points', label: 'Points', render: (t) => <span className={`font-semibold ${t.points < 0 ? 'text-danger' : 'text-success'}`}>{t.points > 0 ? '+' : ''}{fmt(t.points)}</span> },
        { key: 'balance_after', label: 'Balance After', render: (t) => fmt(t.balance_after) },
        { key: 'description', label: 'Description' },
    ];

    return (
        <AdminLayout title="Points Transactions">
            <Head title="Points Transactions" />
            <Link href="/admin/loyalty" className="mb-5 inline-flex items-center gap-2 text-sm text-muted hover:text-ink"><ArrowLeft size={16} />Back to Loyalty</Link>
            {filters.user_id && <p className="mb-3 text-sm text-muted">Showing one user's history. <Link href="/admin/loyalty/transactions" className="text-primary hover:underline">Show everyone</Link></p>}
            <FilterBar path="/admin/loyalty/transactions" filters={filters} fields={[{ name: 'type', placeholder: 'All types', options: TYPES }]} />
            <Card className="overflow-hidden">
                <DataTable columns={columns} rows={transactions.data} empty="No transactions yet" />
                <Pagination links={transactions.links} from={transactions.from} to={transactions.to} total={transactions.total} />
            </Card>
        </AdminLayout>
    );
}
