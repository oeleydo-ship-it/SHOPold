import { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { CheckCircle2, Clock, Pencil, RefreshCw, X } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import StatCard from '../../../Components/StatCard';
import FilterBar from '../../../Components/FilterBar';
import { Badge, Button, Card, CardHeader, Field, Input, Pagination, Select, Textarea, titleCase } from '../../../Components/ui';
import { DataTable } from '../../../Components/crud';
import { VendorLogo } from './Index';
import { PAYOUT_TONE, methodLabel } from './Payouts';

const STATUSES = [['pending', 'Pending'], ['processing', 'Processing'], ['completed', 'Completed'], ['failed', 'Failed']];

function StatusModal({ payout, onClose }) {
    const form = useForm({ status: payout.status, transaction_id: payout.transaction_id ?? '', notes: payout.notes ?? '' });
    const { data, setData, errors, processing } = form;
    const submit = (e) => { e.preventDefault(); form.patch(`/admin/payouts/${payout.id}/status`, { preserveScroll: true, onSuccess: onClose }); };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" role="dialog" aria-modal="true" aria-label="Update payout">
            <Card className="w-full max-w-md">
                <CardHeader title={`Update payout #${payout.id}`}><button type="button" onClick={onClose} aria-label="Close" className="text-muted hover:text-ink"><X size={18} /></button></CardHeader>
                <form onSubmit={submit} className="space-y-4 p-4 md:p-6">
                    <p className="text-sm text-muted">{payout.vendor} · {payout.amount} · {methodLabel(payout.method)}</p>
                    {payout.pay_to?.length > 0 && (
                        <div className="rounded-lg bg-surface-2 p-3 text-sm">
                            <p className="mb-1 text-xs font-semibold uppercase text-muted">Send to</p>
                            {payout.pay_to.map((l) => <p key={l} className="select-all">{l}</p>)}
                        </div>
                    )}
                    <Field label="Status *" error={errors.status}><Select value={data.status} onChange={(e) => setData('status', e.target.value)}>{STATUSES.map(([v, l]) => <option key={v} value={v}>{l}</option>)}</Select></Field>
                    <Field label="Transaction ID" error={errors.transaction_id}><Input value={data.transaction_id} onChange={(e) => setData('transaction_id', e.target.value)} /></Field>
                    <Field label="Notes" error={errors.notes}><Textarea rows={3} value={data.notes} onChange={(e) => setData('notes', e.target.value)} /></Field>
                    <div className="flex justify-end gap-2"><Button tone="neutral" onClick={onClose}>Cancel</Button><Button type="submit" disabled={processing}>Update</Button></div>
                </form>
            </Card>
        </div>
    );
}

export default function PayoutRequests({ payouts, totals, filters }) {
    const [editing, setEditing] = useState(null);

    const columns = [
        { key: 'id', label: 'ID', render: (p) => `#${p.id}` },
        { key: 'vendor', label: 'Vendor', render: (p) => <div className="flex items-center gap-3"><VendorLogo src={p.vendor_logo} /><div className="min-w-0"><Link href={`/admin/vendors/${p.vendor_id}`} className="font-medium hover:underline">{p.vendor}</Link><p className="text-xs text-muted">{p.vendor_email}</p></div></div> },
        { key: 'amount', label: 'Amount' },
        { key: 'method', label: 'Method', render: (p) => methodLabel(p.method) },
        { key: 'status', label: 'Status', render: (p) => <Badge tone={PAYOUT_TONE[p.status] ?? 'muted'}>{titleCase(p.status)}</Badge> },
        { key: 'date', label: 'Requested' },
        { key: 'actions', label: '', className: 'text-right', render: (p) => <button type="button" title="Update status" aria-label={`Update payout ${p.id}`} onClick={() => setEditing(p)} className="text-primary"><Pencil size={18} /></button> },
    ];

    return (
        <AdminLayout title="Payout Requests">
            <Head title="Payout Requests" />
            <div className="mb-4 grid gap-3 sm:grid-cols-3">
                <StatCard label="Pending Payouts" value={totals.pending} icon={Clock} tone="warning" />
                <StatCard label="Processing" value={totals.processing} icon={RefreshCw} tone="info" />
                <StatCard label="Total Paid Out" value={totals.completed} icon={CheckCircle2} tone="success" />
            </div>
            <FilterBar path="/admin/payouts" filters={filters} fields={[{ name: 'search', placeholder: 'Search vendor…' }, { name: 'status', placeholder: 'All status', options: STATUSES }]} />
            <Card className="overflow-hidden">
                <DataTable columns={columns} rows={payouts.data} empty="No payout requests yet" />
                <Pagination links={payouts.links} from={payouts.from} to={payouts.to} total={payouts.total} />
            </Card>
            {editing && <StatusModal key={editing.id} payout={editing} onClose={() => setEditing(null)} />}
        </AdminLayout>
    );
}
