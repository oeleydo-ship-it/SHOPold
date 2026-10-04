import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Banknote, CheckCircle2, Clock, CreditCard, Eye, Receipt, RotateCcw, Search, Undo2, Wallet } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import StatCard from '../../../Components/StatCard';
import { Badge, Button, Card, Input, PaymentStatusBadge, Pagination, Select } from '../../../Components/ui';
import { DataTable } from '../../../Components/crud';

const TABS = [['all', 'All Transactions', null], ['card', 'Card Payments', 'card'], ['cash', 'Cash Payments', 'cash']];

export default function Index({ transactions, stats, type, paymentMethods, filters }) {
    const [form, setForm] = useState({ search: filters.search ?? '', status: filters.status ?? '', method: filters.method ?? '', from: filters.from ?? '', to: filters.to ?? '' });
    const set = (k) => (e) => setForm((f) => ({ ...f, [k]: e.target.value }));
    const hasFilters = Object.values(filters).some(Boolean);
    const query = (extra = {}) => ({ type, ...Object.fromEntries(Object.entries(form).filter(([, v]) => v)), ...extra });

    const go = (e) => { e.preventDefault(); router.get('/admin/transactions', query(), { preserveState: true }); };
    const tab = (t) => router.get('/admin/transactions', { ...query({ type: t }) }, { preserveState: true });

    const columns = [
        { key: 'number', label: 'Order', render: (t) => <Link href={`/admin/orders/${t.id}`} className="font-medium text-primary hover:underline">{t.number}</Link> },
        { key: 'customer', label: 'Customer', render: (t) => <div><p className="font-medium">{t.customer}</p>{t.email && <p className="text-xs text-muted">{t.email}</p>}</div> },
        { key: 'type', label: 'Type', render: (t) => <Badge tone={t.is_card ? 'primary' : 'success'}><span className="inline-flex items-center gap-1">{t.is_card ? <CreditCard size={12} /> : <Banknote size={12} />}{t.is_card ? 'Card' : 'Cash'}</span></Badge> },
        { key: 'method', label: 'Method' },
        { key: 'transaction_id', label: 'Transaction ID', render: (t) => (t.transaction_id ? <code className="rounded bg-surface-2 px-2 py-1 text-xs">{t.transaction_id}</code> : <span className="text-muted">—</span>) },
        { key: 'total', label: 'Amount', render: (t) => <div><span className="font-medium">{t.total}</span>{t.refund_amount && <p className="text-xs text-danger">-{t.refund_amount}</p>}</div> },
        { key: 'status', label: 'Status', render: (t) => <PaymentStatusBadge status={t.payment_status} /> },
        { key: 'date', label: 'Date', render: (t) => <div className="text-muted">{t.date}<p className="text-xs">{t.time}</p></div> },
        {
            key: 'actions', label: '', className: 'text-right',
            render: (t) => (
                <div className="flex items-center justify-end gap-3">
                    <Link href={`/admin/transactions/${t.id}`} title="View details" aria-label={`View ${t.number}`} className="text-muted hover:text-ink"><Eye size={18} /></Link>
                    {t.can_refund && <Link href={`/admin/transactions/${t.id}#refund`} title="Process refund" aria-label={`Refund ${t.number}`} className="text-danger"><RotateCcw size={18} /></Link>}
                </div>
            ),
        },
    ];

    return (
        <AdminLayout title="Transactions">
            <Head title="Transactions" />
            <p className="mb-4 text-sm text-muted">View and manage all payment transactions</p>

            <div className="mb-4 grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
                <StatCard label="Total" value={stats.total} icon={Receipt} tone="primary" />
                <StatCard label="Card" value={stats.card} icon={CreditCard} tone="info" />
                <StatCard label="Cash" value={stats.cash} icon={Banknote} tone="success" />
                <StatCard label="Paid" value={stats.paid} icon={CheckCircle2} tone="success" />
                <StatCard label="Pending" value={stats.pending} icon={Clock} tone="warning" />
                <StatCard label="Refunded" value={stats.refunded} icon={Undo2} tone="danger" />
            </div>
            <div className="mb-5 grid gap-3 sm:grid-cols-2">
                <StatCard label="Total Revenue (Paid)" value={stats.total_revenue} icon={Wallet} tone="success" />
                <StatCard label="Total Refunded" value={stats.total_refunded} icon={Undo2} tone="danger" />
            </div>

            <Card className="overflow-hidden">
                <div className="flex gap-1 overflow-x-auto border-b border-line px-4" role="tablist">
                    {TABS.map(([id, label, key]) => (
                        <button key={id} type="button" role="tab" aria-selected={type === id} onClick={() => tab(id)}
                            className={`flex items-center gap-2 whitespace-nowrap border-b-2 px-4 py-3 text-sm font-medium ${type === id ? 'border-primary text-primary' : 'border-transparent text-muted hover:text-ink'}`}>
                            {label}{key && <Badge>{stats[key]}</Badge>}
                        </button>
                    ))}
                </div>

                <form onSubmit={go} className="flex flex-col gap-3 border-b border-line p-4 lg:flex-row lg:flex-wrap">
                    <div className="relative min-w-56 flex-1">
                        <Search size={16} className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
                        <Input value={form.search} onChange={set('search')} placeholder="Search order # or transaction ID…" className="pl-9" aria-label="Search transactions" />
                    </div>
                    <Select value={form.status} onChange={set('status')} className="lg:!w-auto" aria-label="Status">
                        <option value="">All Statuses</option>
                        {['pending', 'paid', 'failed', 'refunded'].map((s) => <option key={s} value={s}>{s[0].toUpperCase() + s.slice(1)}</option>)}
                    </Select>
                    <Select value={form.method} onChange={set('method')} className="lg:!w-auto" aria-label="Method">
                        <option value="">All Methods</option>
                        {paymentMethods.map((m) => <option key={m.value} value={m.value}>{m.label}</option>)}
                    </Select>
                    <Input type="date" value={form.from} onChange={set('from')} className="lg:!w-auto" aria-label="From date" />
                    <Input type="date" value={form.to} onChange={set('to')} className="lg:!w-auto" aria-label="To date" />
                    <div className="flex gap-2">
                        <Button type="submit">Filter</Button>
                        {hasFilters && <Button tone="neutral" onClick={() => { setForm({ search: '', status: '', method: '', from: '', to: '' }); router.get('/admin/transactions', { type }); }}>Clear</Button>}
                    </div>
                </form>

                <DataTable columns={columns} rows={transactions.data} empty="No transactions found" />
                <Pagination links={transactions.links} from={transactions.from} to={transactions.to} total={transactions.total} />
            </Card>
        </AdminLayout>
    );
}
