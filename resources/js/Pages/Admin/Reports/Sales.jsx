import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Banknote, Percent, Receipt, Truck } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import StatCard from '../../../Components/StatCard';
import { Button, Card, Field, Input, OrderStatusBadge, PaymentStatusBadge, Pagination } from '../../../Components/ui';
import { DataTable } from '../../../Components/crud';

export default function Sales({ orders, summary, startDate, endDate }) {
    const [range, setRange] = useState({ start_date: startDate, end_date: endDate });

    const columns = [
        { key: 'number', label: 'Order', render: (o) => <Link href={`/admin/orders/${o.id}`} className="font-medium text-primary hover:underline">{o.number}</Link> },
        { key: 'customer', label: 'Customer' },
        { key: 'status', label: 'Status', render: (o) => <OrderStatusBadge status={o.status} /> },
        { key: 'payment', label: 'Payment', render: (o) => <PaymentStatusBadge status={o.payment_status} /> },
        { key: 'subtotal', label: 'Subtotal' },
        { key: 'tax', label: 'Tax' },
        { key: 'total', label: 'Total', className: 'font-medium' },
        { key: 'date', label: 'Date', render: (o) => <span className="text-muted">{o.date}</span> },
    ];

    return (
        <AdminLayout title="Sales Report">
            <Head title="Sales Report" />
            <Link href="/admin/reports" className="mb-5 inline-flex items-center gap-2 text-sm text-muted hover:text-ink"><ArrowLeft size={16} />Back to Reports</Link>

            <Card className="mb-5 p-4">
                <form onSubmit={(e) => { e.preventDefault(); router.get('/admin/reports/sales', range, { preserveState: true }); }} className="flex flex-wrap items-end gap-3">
                    <Field label="Start Date"><Input type="date" value={range.start_date} onChange={(e) => setRange({ ...range, start_date: e.target.value })} /></Field>
                    <Field label="End Date"><Input type="date" value={range.end_date} onChange={(e) => setRange({ ...range, end_date: e.target.value })} /></Field>
                    <Button type="submit">Apply</Button>
                </form>
            </Card>

            <div className="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
                <StatCard label="Total Orders" value={summary.orders} icon={Receipt} tone="info" />
                <StatCard label="Revenue (Paid)" value={summary.revenue} icon={Banknote} tone="success" />
                <StatCard label="Tax Collected" value={summary.tax} icon={Percent} tone="warning" />
                <StatCard label="Shipping Collected" value={summary.shipping} icon={Truck} tone="primary" />
            </div>

            <Card className="overflow-hidden">
                <DataTable columns={columns} rows={orders.data} empty="No orders in this date range." />
                <Pagination links={orders.links} from={orders.from} to={orders.to} total={orders.total} />
            </Card>
        </AdminLayout>
    );
}
