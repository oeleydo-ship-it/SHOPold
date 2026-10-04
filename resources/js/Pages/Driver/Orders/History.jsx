import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import DriverLayout from '../../../Layouts/DriverLayout';
import DriverOrderCard from '../../../Components/DriverOrderCard';
import { Button, Card, Field, Input, Pagination } from '../../../Components/ui';

export default function History({ orders, filters }) {
    const [form, setForm] = useState({ date_from: filters.date_from ?? '', date_to: filters.date_to ?? '' });
    const go = (e) => { e.preventDefault(); router.get('/driver/orders/history', Object.fromEntries(Object.entries(form).filter(([, v]) => v)), { preserveState: true }); };

    return (
        <DriverLayout title="Delivery history">
            <Head title="Delivery history" />
            <Card className="mb-4 p-3">
                <form onSubmit={go} className="grid grid-cols-2 items-end gap-2 sm:flex">
                    <Field label="From"><Input type="date" value={form.date_from} onChange={(e) => setForm({ ...form, date_from: e.target.value })} /></Field>
                    <Field label="To"><Input type="date" value={form.date_to} onChange={(e) => setForm({ ...form, date_to: e.target.value })} /></Field>
                    <Button type="submit" className="col-span-2 sm:col-span-1">Filter</Button>
                    {(filters.date_from || filters.date_to) && <Button tone="neutral" className="col-span-2 sm:col-span-1" onClick={() => router.get('/driver/orders/history')}>Clear</Button>}
                </form>
            </Card>
            {orders.data.length === 0 ? (
                <Card className="p-8 text-center text-sm text-muted">No completed deliveries yet.</Card>
            ) : (
                <div className="grid gap-3 lg:grid-cols-2">{orders.data.map((o) => <DriverOrderCard key={o.id} order={o} />)}</div>
            )}
            <div className="mt-4"><Pagination links={orders.links} from={orders.from} to={orders.to} total={orders.total} /></div>
        </DriverLayout>
    );
}
