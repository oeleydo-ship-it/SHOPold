import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import VendorLayout from '../../../Layouts/VendorLayout';
import { Badge, Button, Card, Input, OrderStatusBadge, Pagination, PaymentStatusBadge, Select } from '../../../Components/ui';
import { DataTable } from '../../../Components/crud';

const ORDER_STATUSES = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

export default function Index({ orders, filters, fulfillmentStatuses }) {
    const [form, setForm] = useState({ search: filters.search ?? '', status: filters.status ?? '', fulfillment: filters.fulfillment ?? '' });
    const set = (k) => (e) => setForm((f) => ({ ...f, [k]: e.target.value }));
    const go = (e) => { e?.preventDefault(); router.get('/vendor/orders', Object.fromEntries(Object.entries(form).filter(([, v]) => v)), { preserveState: true }); };
    const hasFilters = Object.values(filters).some(Boolean);

    const columns = [
        { key: 'number', label: 'Order', render: (o) => <Link href={`/vendor/orders/${o.id}`} className="font-medium text-primary hover:underline">{o.number}</Link> },
        { key: 'customer', label: 'Customer' },
        { key: 'items', label: 'Your items', render: (o) => <span className="inline-flex items-center gap-2">{o.items}{o.pending_items > 0 && <Badge tone="warning">{o.pending_items} to fulfil</Badge>}</span> },
        { key: 'total', label: 'Your total', render: (o) => <span className="font-medium">{o.total}</span> },
        { key: 'status', label: 'Order', render: (o) => <OrderStatusBadge status={o.status} /> },
        { key: 'payment', label: 'Payment', render: (o) => <PaymentStatusBadge status={o.payment_status} /> },
        { key: 'date', label: 'Date', render: (o) => <span className="whitespace-nowrap text-muted">{o.date}</span> },
    ];

    return (
        <VendorLayout title="Orders">
            <Head title="Orders" />
            <Card className="mb-4 p-4">
                <form onSubmit={go} className="flex flex-wrap gap-2">
                    <div className="relative min-w-48 flex-1">
                        <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
                        <Input className="pl-9" placeholder="Order number…" aria-label="Search orders" value={form.search} onChange={set('search')} />
                    </div>
                    <Select className="w-auto" aria-label="Order status" value={form.status} onChange={set('status')}>
                        <option value="">All orders</option>
                        {ORDER_STATUSES.map((s) => <option key={s} value={s}>{s[0].toUpperCase() + s.slice(1)}</option>)}
                    </Select>
                    <Select className="w-auto" aria-label="Fulfilment" value={form.fulfillment} onChange={set('fulfillment')}>
                        <option value="">Any fulfilment</option>
                        {Object.entries(fulfillmentStatuses).map(([v, l]) => <option key={v} value={v}>{l}</option>)}
                    </Select>
                    <Button type="submit">Filter</Button>
                    {hasFilters && <Button tone="neutral" onClick={() => router.get('/vendor/orders')}>Clear</Button>}
                </form>
            </Card>

            <Card className="overflow-hidden">
                <DataTable columns={columns} rows={orders.data} empty="No orders yet." />
                <Pagination links={orders.links} from={orders.from} to={orders.to} total={orders.total} />
            </Card>
        </VendorLayout>
    );
}
