import { Head } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { Badge, Card, LinkButton, Pagination } from '../../../Components/ui';
import { DataTable, ListToolbar, RowActions } from '../../../Components/crud';

export default function Index({ coupons }) {
    const columns = [
        { key: 'code', label: 'Code', render: (c) => <span className="font-mono font-bold">{c.code}</span> },
        { key: 'value', label: 'Discount', render: (c) => <Badge tone={c.type === 'percent' ? 'info' : 'success'}>{c.value} off</Badge> },
        { key: 'usage', label: 'Usage', render: (c) => `${c.used_count} / ${c.max_uses ?? '∞'}` },
        { key: 'expires_at', label: 'Expires', render: (c) => <span className="text-muted">{c.expires_at ?? 'Never'}</span> },
        { key: 'is_active', label: 'Status', render: (c) => <Badge tone={c.is_active ? 'success' : 'danger'}>{c.is_active ? 'Active' : 'Inactive'}</Badge> },
        {
            key: 'actions', label: '', className: 'text-right',
            render: (c) => <RowActions editHref={`/admin/coupons/${c.id}/edit`} deleteHref={`/admin/coupons/${c.id}`} deleteMessage={`Delete coupon ${c.code}?`} />,
        },
    ];
    return (
        <AdminLayout title="Coupons">
            <Head title="Coupons" />
            <ListToolbar><LinkButton href="/admin/coupons/create"><Plus size={16} />Add Coupon</LinkButton></ListToolbar>
            <Card className="overflow-hidden">
                <DataTable columns={columns} rows={coupons.data} empty="No coupons yet." />
                <Pagination links={coupons.links} from={coupons.from} to={coupons.to} total={coupons.total} />
            </Card>
        </AdminLayout>
    );
}
