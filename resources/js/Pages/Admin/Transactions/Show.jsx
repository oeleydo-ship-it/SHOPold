import { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Banknote, Check, CheckCircle2, Copy, CreditCard, FileText, Info, ShoppingCart, Undo2, X } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { Badge, Button, Card, CardHeader, LinkButton, OrderStatusBadge, PaymentStatusBadge, Textarea } from '../../../Components/ui';

function Item({ label, children }) {
    return <div><p className="text-xs text-muted">{label}</p><div className="mt-0.5 text-sm font-medium">{children}</div></div>;
}

function Refund({ order }) {
    const form = useForm({ refund_reason: '' });
    const submit = (e) => {
        e.preventDefault();
        if (!window.confirm('Are you sure you want to process this refund? This action cannot be undone.')) return;
        form.post(`/admin/transactions/${order.id}/refund`, { preserveScroll: true });
    };
    return (
        <Card id="refund">
            <CardHeader title="Process Refund" />
            <div className="space-y-3 p-4 md:p-6">
                <p className="rounded-lg border border-warning/40 bg-warning/10 p-3 text-sm">
                    <strong className="text-warning">Warning:</strong> this refunds <strong>{order.total}</strong> to the customer's original payment method and cannot be undone.
                </p>
                <form onSubmit={submit} className="space-y-3">
                    <Textarea rows={3} value={form.data.refund_reason} onChange={(e) => form.setData('refund_reason', e.target.value)} placeholder="Reason for refund (optional but recommended)" aria-label="Refund reason" />
                    {form.errors.refund_reason && <p className="text-xs text-danger">{form.errors.refund_reason}</p>}
                    <Button type="submit" tone="danger" className="w-full" disabled={form.processing}><Undo2 size={16} />Process Refund ({order.total})</Button>
                </form>
            </div>
        </Card>
    );
}

function Notice({ icon: Icon, tone, title, children }) {
    return (
        <Card className="p-6 text-center">
            <span className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full" style={{ color: `var(--${tone})`, background: `color-mix(in srgb, var(--${tone}) 15%, transparent)` }}><Icon size={22} /></span>
            <h3 className="font-semibold">{title}</h3>
            <div className="mt-1 text-sm text-muted">{children}</div>
        </Card>
    );
}

export default function Show({ order }) {
    const [copied, setCopied] = useState(false);
    const p = order.payment;

    const copy = async () => {
        try { await navigator.clipboard.writeText(order.transaction_id); setCopied(true); setTimeout(() => setCopied(false), 1500); } catch { /* clipboard unavailable */ }
    };

    const timeline = [
        ['Order Created', order.created_full, Check, 'success'],
        (order.payment_status === 'paid' || order.payment_status === 'refunded') && ['Payment Received', `${order.total} via ${p.gateway}`, CheckCircle2, 'primary'],
        order.refund?.at_short && ['Refund Processed', order.refund.at_short, Undo2, 'danger'],
    ].filter(Boolean);

    return (
        <AdminLayout title="Transaction Details">
            <Head title={`Transaction ${order.number}`} />
            <div className="mb-5 flex items-center gap-3">
                <Link href="/admin/transactions" aria-label="Back to transactions" className="text-muted hover:text-ink"><ArrowLeft size={20} /></Link>
                <p className="text-sm text-muted">{order.number}</p>
            </div>

            <div className="grid gap-5 lg:grid-cols-3">
                <div className="space-y-5 lg:col-span-2">
                    <Card>
                        <CardHeader title="Transaction Overview" />
                        <div className="grid gap-5 p-4 sm:grid-cols-3 md:p-6">
                            <Item label="Order Number">{order.number}</Item>
                            <Item label="Date & Time">{order.created_at}<span className="block text-xs font-normal text-muted">{order.created_time}</span></Item>
                            <Item label="Customer">{order.customer}<span className="block text-xs font-normal text-muted">{order.email}</span></Item>
                            <Item label="Order Status"><OrderStatusBadge status={order.status} /></Item>
                            <Item label="Payment Status"><PaymentStatusBadge status={order.payment_status} /></Item>
                            <Item label="Amount"><span className="text-lg">{order.total}</span></Item>
                        </div>
                    </Card>

                    <Card>
                        <CardHeader title="Payment Details" />
                        <div className="grid gap-5 p-4 sm:grid-cols-2 md:p-6">
                            <Item label="Payment Type">
                                <Badge tone={p.is_card ? 'primary' : 'success'}><span className="inline-flex items-center gap-1">{p.is_card ? <CreditCard size={12} /> : <Banknote size={12} />}{p.is_card ? 'Card Payment' : 'Cash Payment'}</span></Badge>
                            </Item>
                            <Item label="Payment Gateway">{p.gateway}</Item>
                            <Item label="Transaction ID">
                                {order.transaction_id ? (
                                    <span className="flex items-center gap-2"><code className="break-all rounded bg-surface-2 px-2 py-1 text-xs">{order.transaction_id}</code>
                                        <button type="button" onClick={copy} title="Copy" aria-label="Copy transaction ID" className="text-muted hover:text-ink">{copied ? <Check size={14} className="text-success" /> : <Copy size={14} />}</button></span>
                                ) : <span className="font-normal text-muted">No transaction ID available</span>}
                            </Item>
                            <Item label="Refund Supported">
                                <Badge tone={p.supports_refund ? 'success' : 'muted'}><span className="inline-flex items-center gap-1">{p.supports_refund ? <Check size={12} /> : <X size={12} />}{p.supports_refund ? 'Yes' : 'No'}</span></Badge>
                            </Item>
                        </div>
                    </Card>

                    <Card>
                        <CardHeader title="Order Items" />
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-surface-2 text-left text-xs uppercase text-muted"><tr>{['Product', 'Price', 'Qty', 'Subtotal'].map((h, i) => <th key={h} className={`px-4 py-3 font-medium md:px-6 ${i > 0 ? 'text-right' : ''}`}>{h}</th>)}</tr></thead>
                                <tbody className="divide-y divide-line">
                                    {order.items.map((i) => (
                                        <tr key={i.id}>
                                            <td className="px-4 py-3 md:px-6">
                                                <div className="flex items-center gap-3">
                                                    <div className="h-10 w-10 shrink-0 overflow-hidden rounded bg-surface-2">{i.image && <img src={i.image} alt="" className="h-full w-full object-cover" />}</div>
                                                    <div><p className="font-medium">{i.name}</p>{i.variant && <p className="text-xs text-muted">{i.variant}</p>}</div>
                                                </div>
                                            </td>
                                            <td className="px-4 py-3 text-right md:px-6">{i.price}</td>
                                            <td className="px-4 py-3 text-right md:px-6">{i.quantity}</td>
                                            <td className="px-4 py-3 text-right md:px-6">{i.subtotal}</td>
                                        </tr>
                                    ))}
                                </tbody>
                                <tfoot className="border-t border-line text-sm">
                                    <tr><td colSpan={3} className="px-4 py-2 text-right text-muted md:px-6">Subtotal</td><td className="px-4 py-2 text-right md:px-6">{order.subtotal}</td></tr>
                                    {order.discount && <tr><td colSpan={3} className="px-4 py-2 text-right text-muted md:px-6">Discount</td><td className="px-4 py-2 text-right text-success md:px-6">-{order.discount}</td></tr>}
                                    <tr><td colSpan={3} className="px-4 py-2 text-right text-muted md:px-6">Shipping</td><td className="px-4 py-2 text-right md:px-6">{order.shipping_cost}</td></tr>
                                    <tr><td colSpan={3} className="px-4 py-2 text-right text-muted md:px-6">Tax</td><td className="px-4 py-2 text-right md:px-6">{order.tax}</td></tr>
                                    <tr className="font-bold"><td colSpan={3} className="px-4 py-3 text-right md:px-6">Total</td><td className="px-4 py-3 text-right md:px-6">{order.total}</td></tr>
                                </tfoot>
                            </table>
                        </div>
                    </Card>

                    {order.refund && (
                        <Card>
                            <CardHeader title="Refund Information" />
                            <div className="grid gap-5 p-4 sm:grid-cols-2 md:p-6">
                                <Item label="Refund ID"><code className="rounded bg-surface-2 px-2 py-1 text-xs">{order.refund.id ?? 'N/A'}</code></Item>
                                <Item label="Refund Amount"><span className="text-danger">{order.refund.amount}</span></Item>
                                <Item label="Refunded At">{order.refund.at ?? 'N/A'}</Item>
                                {order.refund.reason && <Item label="Reason">{order.refund.reason}</Item>}
                            </div>
                        </Card>
                    )}
                </div>

                <div className="space-y-5">
                    <Card>
                        <CardHeader title="Quick Actions" />
                        <div className="space-y-2 p-4 md:p-6">
                            <LinkButton tone="neutral" href={`/admin/orders/${order.id}`} className="w-full"><ShoppingCart size={16} />View Order</LinkButton>
                            {/* invoices are print documents served by Laravel, so this is a normal link */}
                            <LinkButton external tone="neutral" href={`/admin/invoices/${order.id}`} className="w-full"><FileText size={16} />View Invoice</LinkButton>
                        </div>
                    </Card>

                    {order.payment_status === 'paid' && p.supports_refund && <Refund order={order} />}
                    {order.payment_status === 'refunded' && <Notice icon={Undo2} tone="info" title="Already Refunded">This transaction has been refunded.<p className="mt-1 text-lg font-bold text-ink">{order.refund?.amount}</p></Notice>}
                    {order.payment_status === 'paid' && !p.supports_refund && <Notice icon={Info} tone="warning" title="Refund Not Available">Automatic refunds are not supported for {p.gateway}.<p className="mt-1">Manual refund may be required.</p></Notice>}
                    {!['paid', 'refunded'].includes(order.payment_status) && <Notice icon={Info} tone="muted" title={`Payment ${order.payment_status[0].toUpperCase() + order.payment_status.slice(1)}`}>Refunds can only be processed for paid orders.</Notice>}

                    <Card>
                        <CardHeader title="Timeline" />
                        <ol className="space-y-4 p-4 md:p-6">
                            {timeline.map(([t, d, Icon, tone]) => (
                                <li key={t} className="flex items-start gap-3">
                                    <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full" style={{ color: `var(--${tone})`, background: `color-mix(in srgb, var(--${tone}) 15%, transparent)` }}><Icon size={14} /></span>
                                    <div><p className="text-sm font-medium">{t}</p><p className="text-xs text-muted">{d}</p></div>
                                </li>
                            ))}
                        </ol>
                    </Card>
                </div>
            </div>
        </AdminLayout>
    );
}
