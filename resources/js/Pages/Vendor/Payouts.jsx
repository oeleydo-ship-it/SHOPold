import { Head, Link, useForm } from '@inertiajs/react';
import { CheckCircle2, Clock, DollarSign, Wallet } from 'lucide-react';
import VendorLayout from '../../Layouts/VendorLayout';
import StatCard from '../../Components/StatCard';
import { Badge, Button, Card, CardHeader, Field, Input, Pagination } from '../../Components/ui';
import { DataTable } from '../../Components/crud';

const TONE = { pending: 'warning', processing: 'info', completed: 'success', failed: 'danger' };

export default function Payouts({ summary, requestable, requestableLabel, minimum, minimumLabel, method, payouts }) {
    const { data, setData, post, processing, errors, reset } = useForm({ amount: '' });
    const submit = (e) => { e.preventDefault(); post('/vendor/payouts/request', { preserveScroll: true, onSuccess: () => reset() }); };
    const canRequest = method && requestable >= minimum;

    const columns = [
        { key: 'id', label: '#', render: (p) => <span className="text-muted">#{p.id}</span> },
        { key: 'amount', label: 'Amount', render: (p) => <span className="font-semibold">{p.amount}</span> },
        { key: 'method', label: 'Method' },
        { key: 'status', label: 'Status', render: (p) => <Badge tone={TONE[p.status] ?? 'muted'}>{p.status[0].toUpperCase() + p.status.slice(1)}</Badge> },
        { key: 'reference', label: 'Reference', render: (p) => <span className="text-muted">{p.reference ?? '—'}</span> },
        { key: 'date', label: 'Requested', render: (p) => <span className="whitespace-nowrap text-muted">{p.date}{p.processed && <span className="block text-xs">Paid {p.processed}</span>}</span> },
    ];

    return (
        <VendorLayout title="Payouts">
            <Head title="Payouts" />

            <div className="mb-5 grid grid-cols-2 gap-3 xl:grid-cols-4">
                <StatCard label="Total earned" value={summary.earnings} icon={DollarSign} tone="primary" />
                <StatCard label="Balance" value={summary.balance} icon={Wallet} tone="success" />
                <StatCard label="Requested, not paid yet" value={summary.pending} icon={Clock} tone="warning" />
                <StatCard label="Paid out" value={summary.paid} icon={CheckCircle2} tone="info" />
            </div>

            <div className="grid gap-5 xl:grid-cols-3">
                <Card className="xl:order-2">
                    <CardHeader title="Request a payout" />
                    <div className="p-4 md:px-6">
                        {!method ? (
                            <p className="text-sm">Add your payout details first. <Link href="/vendor/payment-settings" className="text-primary hover:underline">Set up payout details</Link></p>
                        ) : (
                            <form onSubmit={submit} className="space-y-3">
                                <p className="text-sm text-muted">Available to request: <span className="font-semibold text-ink">{requestableLabel}</span>. Paid by {method}. Minimum {minimumLabel}.</p>
                                <Field label="Amount" error={errors.amount}>
                                    <Input type="number" step="0.01" min={minimum} max={requestable} required disabled={!canRequest} value={data.amount} onChange={(e) => setData('amount', e.target.value)} />
                                </Field>
                                <Button type="submit" disabled={processing || !canRequest}>Request payout</Button>
                                {!canRequest && <p className="text-xs text-muted">You can request a payout once at least {minimumLabel} is available.</p>}
                            </form>
                        )}
                    </div>
                </Card>

                <Card className="overflow-hidden xl:col-span-2">
                    <CardHeader title="Payout history" />
                    <DataTable columns={columns} rows={payouts.data} empty="No payouts yet." />
                    <Pagination links={payouts.links} from={payouts.from} to={payouts.to} total={payouts.total} />
                </Card>
            </div>
        </VendorLayout>
    );
}
