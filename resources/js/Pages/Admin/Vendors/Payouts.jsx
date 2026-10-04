import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { Badge, Button, Card, CardHeader, Field, Input, Pagination, Select, Textarea, titleCase } from '../../../Components/ui';
import { DataTable } from '../../../Components/crud';

export const PAYOUT_TONE = { completed: 'success', pending: 'warning', processing: 'info', failed: 'danger' };
export const METHODS = [['bank_transfer', 'Bank Transfer'], ['paypal', 'PayPal'], ['stripe', 'Stripe'], ['check', 'Check']];
export const methodLabel = (m) => METHODS.find(([v]) => v === m)?.[1] ?? titleCase(m);

export default function Payouts({ vendor, balance, payouts }) {
    const form = useForm({ amount: '', payment_method: 'bank_transfer', notes: '' });
    const { data, setData, errors, processing } = form;
    const submit = (e) => { e.preventDefault(); form.post(`/admin/vendors/${vendor.id}/payouts`, { preserveScroll: true, onSuccess: () => form.reset() }); };

    const columns = [
        { key: 'date', label: 'Date' },
        { key: 'amount', label: 'Amount' },
        { key: 'method', label: 'Method', render: (p) => methodLabel(p.method) },
        { key: 'status', label: 'Status', render: (p) => <Badge tone={PAYOUT_TONE[p.status] ?? 'muted'}>{titleCase(p.status)}</Badge> },
    ];

    return (
        <AdminLayout title="Vendor Payouts">
            <Head title={`Payouts - ${vendor.store_name}`} />
            <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
                <div><h2 className="text-lg font-bold">Payouts - {vendor.store_name}</h2><p className="text-sm text-muted">Available balance: <span className="font-semibold text-success">{balance.label}</span></p></div>
                <Link href={`/admin/vendors/${vendor.id}`} className="inline-flex items-center gap-2 text-sm text-muted hover:text-ink"><ArrowLeft size={16} />Back to Vendor</Link>
            </div>

            <div className="grid gap-5 lg:grid-cols-3">
                <Card className="self-start">
                    <CardHeader title="Create Payout" />
                    <form onSubmit={submit} className="space-y-4 p-4 md:p-6">
                        <Field label="Amount *" hint={`Max: ${balance.label}`} error={errors.amount}>
                            <Input type="number" step="0.01" min="1" max={balance.raw} required value={data.amount} onChange={(e) => setData('amount', e.target.value)} />
                        </Field>
                        <Field label="Payment Method *" error={errors.payment_method}>
                            <Select value={data.payment_method} onChange={(e) => setData('payment_method', e.target.value)}>{METHODS.map(([v, l]) => <option key={v} value={v}>{l}</option>)}</Select>
                        </Field>
                        <Field label="Notes" error={errors.notes}><Textarea rows={3} value={data.notes} onChange={(e) => setData('notes', e.target.value)} /></Field>
                        <Button type="submit" className="w-full" disabled={processing || balance.raw < 1}>Create Payout</Button>
                    </form>
                </Card>

                <Card className="overflow-hidden lg:col-span-2">
                    <DataTable columns={columns} rows={payouts.data} empty="No payouts yet" />
                    <Pagination links={payouts.links} from={payouts.from} to={payouts.to} total={payouts.total} />
                </Card>
            </div>
        </AdminLayout>
    );
}
