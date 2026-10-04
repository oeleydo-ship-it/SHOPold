import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import DriverLayout from '../../../Layouts/DriverLayout';
import { Badge, Button, Card, Field, Input, Pagination, Select, titleCase } from '../../../Components/ui';

const TYPE_TONE = { delivery: 'primary', tip: 'success', bonus: 'info', deduction: 'danger' };

export function EarningRow({ t }) {
    return (
        <li className="flex items-center gap-3 p-3 md:px-4">
            <div className="min-w-0 flex-1">
                <p className="flex items-center gap-2 text-sm"><Badge tone={TYPE_TONE[t.type] ?? 'muted'}>{titleCase(t.type)}</Badge>{t.status !== 'completed' && <Badge tone="warning">{titleCase(t.status)}</Badge>}</p>
                <p className="mt-0.5 truncate text-xs text-muted">{t.order ? <Link href={`/driver/orders/${t.order.id}`} className="hover:underline">{t.order.number}</Link> : t.description} · {t.date}</p>
            </div>
            <span className="font-semibold">{t.amount}</span>
        </li>
    );
}

export default function History({ transactions, totals, filters }) {
    const [form, setForm] = useState({ type: filters.type ?? '', date_from: filters.date_from ?? '', date_to: filters.date_to ?? '' });
    const go = (e) => { e.preventDefault(); router.get('/driver/earnings/history', Object.fromEntries(Object.entries(form).filter(([, v]) => v)), { preserveState: true }); };

    return (
        <DriverLayout title="Earnings history">
            <Head title="Earnings history" />
            <div className="mb-4 grid grid-cols-3 gap-2 text-center">
                {Object.entries(totals).map(([k, v]) => (
                    <Card key={k} className="p-3"><p className="text-xs text-muted">{titleCase(k)}</p><p className="font-semibold">{v}</p></Card>
                ))}
            </div>
            <Card className="mb-4 p-3">
                <form onSubmit={go} className="grid grid-cols-2 items-end gap-2 sm:flex">
                    <Field label="Type" className="col-span-2 sm:col-span-1">
                        <Select value={form.type} onChange={(e) => setForm({ ...form, type: e.target.value })}>
                            <option value="">All</option><option value="delivery">Delivery</option><option value="tip">Tip</option><option value="bonus">Bonus</option>
                        </Select>
                    </Field>
                    <Field label="From"><Input type="date" value={form.date_from} onChange={(e) => setForm({ ...form, date_from: e.target.value })} /></Field>
                    <Field label="To"><Input type="date" value={form.date_to} onChange={(e) => setForm({ ...form, date_to: e.target.value })} /></Field>
                    <Button type="submit" className="col-span-2 sm:col-span-1">Filter</Button>
                </form>
            </Card>
            <Card className="overflow-hidden">
                {transactions.data.length === 0 ? <p className="p-8 text-center text-sm text-muted">No earnings found.</p> : (
                    <ul className="divide-y divide-line">{transactions.data.map((t) => <EarningRow key={t.id} t={t} />)}</ul>
                )}
                <Pagination links={transactions.links} from={transactions.from} to={transactions.to} total={transactions.total} />
            </Card>
        </DriverLayout>
    );
}
