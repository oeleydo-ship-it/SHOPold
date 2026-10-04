import { useEffect, useRef, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Banknote, Check, ClipboardCheck, Cog, CreditCard, Download, ExternalLink, FileText, PackageOpen, Printer, QrCode, StickyNote, Store, Truck, Undo2, UserCheck, X } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import {
    Badge, Button, Card, CardHeader, Field, Input, LinkButton, OrderStatusBadge, PaymentStatusBadge, Select,
    Textarea, titleCase,
} from '../../../Components/ui';

const FLOWS = {
    pickup: [
        ['pending', 'Pending', ClipboardCheck], ['processing', 'Processing', Cog],
        ['ready_for_pickup', 'Ready for Pickup', Store], ['delivered', 'Picked Up', Check],
    ],
    delivery: [
        ['pending', 'Pending', ClipboardCheck], ['processing', 'Processing', Cog],
        ['shipped', 'Shipped', Truck], ['delivered', 'Delivered', PackageOpen],
    ],
};

const FULFILL_TONE = { pending: 'warning', processing: 'info', shipped: 'primary', delivered: 'success', cancelled: 'danger' };

function Progress({ order }) {
    const steps = FLOWS[order.delivery_type === 'pickup' ? 'pickup' : 'delivery'];
    const index = steps.findIndex(([id]) => id === order.status);
    const pct = index > 0 ? (index / (steps.length - 1)) * 100 : 0;

    if (order.status === 'cancelled') {
        return (
            <Card className="flex items-center gap-4 border-danger p-5">
                <span className="flex h-12 w-12 items-center justify-center rounded-full bg-danger text-white"><X size={22} /></span>
                <div>
                    <p className="font-bold text-danger">Order Cancelled</p>
                    <p className="text-sm text-muted">This order has been cancelled and will not be processed.</p>
                </div>
            </Card>
        );
    }

    const dates = [
        ['Created', order.created_at],
        ['Processing', index >= 1 ? order.updated_at : null],
        ['Shipped', order.shipped_at],
        ['Delivered', order.delivered_at],
    ];

    return (
        <Card className="p-4 md:p-6">
            <div className="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <h3 className="text-sm font-semibold md:text-base">Order Progress</h3>
                <div className="flex gap-2"><OrderStatusBadge status={order.status} /><PaymentStatusBadge status={order.payment_status} /></div>
            </div>
            <div className="relative">
                <div className="absolute left-5 right-5 top-4 h-1 rounded bg-line md:top-5" />
                <div className="absolute left-5 top-4 h-1 rounded bg-success transition-all md:top-5" style={{ width: `calc((100% - 2.5rem) * ${pct / 100})` }} />
                <div className="relative flex justify-between">
                    {steps.map(([id, text, Icon], i) => {
                        const done = index >= i;
                        const current = index === i;
                        return (
                            <div key={id} className="flex flex-col items-center">
                                <span className={`flex h-8 w-8 items-center justify-center rounded-full md:h-10 md:w-10 ${done ? 'bg-success text-white' : 'bg-line text-muted'} ${current ? 'ring-4 ring-success/30' : ''}`}>
                                    {done && !current ? <Check size={16} /> : <Icon size={16} />}
                                </span>
                                <span className={`mt-2 text-[10px] font-medium md:text-xs ${done ? 'text-success' : 'text-muted'}`}>{text}</span>
                            </div>
                        );
                    })}
                </div>
            </div>
            <div className="mt-4 grid grid-cols-2 gap-2 border-t border-line pt-4 text-center text-xs sm:grid-cols-4">
                {dates.map(([k, v]) => (
                    <div key={k}><span className="text-muted">{k}</span><p className={`font-medium ${v ? '' : 'text-muted'}`}>{v ?? '--'}</p></div>
                ))}
            </div>
        </Card>
    );
}

function Items({ order }) {
    const total = order.items.length;
    const delivered = order.items.filter((i) => i.fulfillment_status === 'delivered').length;
    const shipped = order.items.filter((i) => i.fulfillment_status === 'shipped').length;
    return (
        <Card>
            <div className="border-b border-line p-4 md:px-6">
                <div className="flex items-center justify-between">
                    <h3 className="text-sm font-semibold md:text-base">Order Items</h3>
                    <span className="text-sm text-muted">{delivered}/{total} Delivered</span>
                </div>
                <div className="mt-3 h-2 w-full overflow-hidden rounded-full bg-line">
                    <div className="h-2 rounded-full bg-success transition-all" style={{ width: `${total ? (delivered / total) * 100 : 0}%` }} />
                </div>
                {shipped > 0 && (
                    <p className="mt-2 flex items-center gap-1 text-xs text-primary"><Truck size={12} />{shipped} item(s) in transit</p>
                )}
            </div>
            <div className="divide-y divide-line">
                {order.items.map((i) => (
                    <div key={i.id} className="flex gap-4 p-4 md:p-6">
                        <div className="h-16 w-16 shrink-0 overflow-hidden rounded-lg bg-surface-2">
                            {i.image && <img src={i.image} alt="" className="h-full w-full object-cover" />}
                        </div>
                        <div className="min-w-0 flex-1">
                            <p className="font-medium">{i.name}</p>
                            {i.options && (
                                <div className="mt-1 flex flex-wrap gap-1">
                                    {Object.entries(i.options).map(([k, v]) => <Badge key={k} tone="info">{k}: {v}</Badge>)}
                                </div>
                            )}
                            <p className="mt-1 text-sm text-muted">Qty: {i.quantity} × {i.price}</p>
                            {i.sku && <p className="text-xs text-muted">SKU: {i.sku}</p>}
                            {i.tracking_number && (
                                <p className="mt-1 flex items-center gap-1 text-xs text-muted"><Truck size={12} />{i.carrier ?? 'Tracking'}: {i.tracking_number}</p>
                            )}
                            {i.shipped_at && <p className="text-xs text-muted">Shipped: {i.shipped_at}</p>}
                            {i.delivered_at && <p className="text-xs text-muted">Delivered: {i.delivered_at}</p>}
                        </div>
                        <div className="shrink-0 text-right">
                            <p className="font-semibold">{i.total}</p>
                            <div className="mt-1"><Badge tone={FULFILL_TONE[i.fulfillment_status] ?? 'muted'}>{i.fulfillment_label}</Badge></div>
                        </div>
                    </div>
                ))}
            </div>
        </Card>
    );
}

function Customer({ order }) {
    const s = order.shipping;
    const p = order.pickup;
    return (
        <Card className="p-4 md:p-6">
            <h3 className="mb-4 text-sm font-semibold md:text-base">Customer Information</h3>
            <div className="grid gap-6 md:grid-cols-2">
                <div>
                    <p className="mb-1 text-sm text-muted">Customer</p>
                    <p className="font-medium">{order.customer_name}</p>
                    <p className="text-sm text-muted">{order.customer_email}</p>
                </div>
                <div className="text-sm">
                    {order.delivery_type === 'pickup' ? (
                        <>
                            <p className="mb-1 text-muted">Pickup Location</p>
                            <div className="mb-2"><Badge tone="primary">Store Pickup</Badge></div>
                            {p ? (
                                <>
                                    <p className="font-medium">{p.name}</p>
                                    <p className="text-muted">{p.address}</p>
                                    <p className="text-muted">{p.city}, {p.state} {p.zip}</p>
                                    <p className="text-muted">{p.country}</p>
                                    {p.phone && <p className="mt-1 text-muted">{p.phone}</p>}
                                </>
                            ) : <p className="text-muted">{s.address}</p>}
                        </>
                    ) : (
                        <>
                            <p className="mb-1 text-muted">Shipping Address</p>
                            <div className="mb-2"><Badge tone="info">Delivery</Badge></div>
                            <p className="font-medium">{s.name}</p>
                            <p className="text-muted">{s.address}</p>
                            <p className="text-muted">{s.city}, {s.state} {s.zip}</p>
                            <p className="text-muted">{s.country}</p>
                        </>
                    )}
                </div>
            </div>
        </Card>
    );
}

function Row({ label, children }) {
    return <div className="flex items-center justify-between gap-3"><span className="text-sm text-muted">{label}</span>{children}</div>;
}

function Payment({ order }) {
    const pay = order.payment;
    const [open, setOpen] = useState(false);
    const form = useForm({ refund_reason: '' });

    const refund = (e) => {
        e.preventDefault();
        if (!window.confirm('Are you sure you want to refund this order? This action cannot be undone.')) return;
        form.post(`/admin/orders/${order.id}/refund`, { preserveScroll: true, onSuccess: () => setOpen(false) });
    };

    return (
        <Card>
            <CardHeader title="Payment Information" />
            <div className="space-y-3 p-4 md:p-6">
                <Row label="Payment Method">
                    {pay.is_card
                        ? <Badge tone="primary"><span className="inline-flex items-center gap-1"><CreditCard size={12} />Card</span></Badge>
                        : <Badge tone="success"><span className="inline-flex items-center gap-1"><Banknote size={12} />Cash</span></Badge>}
                </Row>
                <Row label="Gateway"><span className="text-sm font-medium">{pay.gateway}</span></Row>
                {pay.transaction_id && (
                    <Row label="Transaction ID"><span className="rounded bg-surface-2 px-2 py-1 font-mono text-xs">{pay.transaction_id}</span></Row>
                )}
                <Row label="Payment Status"><PaymentStatusBadge status={order.payment_status} /></Row>

                {order.payment_status === 'refunded' && (
                    <div className="rounded-lg border border-danger/40 bg-danger/10 p-3 text-xs text-danger">
                        <p className="font-medium">Refunded</p>
                        {pay.refund_id && <p className="mt-1">Refund ID: {pay.refund_id}</p>}
                        {pay.refunded_at && <p>Date: {pay.refunded_at}</p>}
                        {pay.refund_amount && <p>Amount: {pay.refund_amount}</p>}
                        {pay.refund_reason && <p className="mt-1">Reason: {pay.refund_reason}</p>}
                    </div>
                )}

                {order.payment_status === 'paid' && pay.supports_refund && (
                    <div className="border-t border-line pt-4">
                        <Button tone="danger" className="w-full" onClick={() => setOpen((o) => !o)}><Undo2 size={16} />Process Refund</Button>
                        {open && (
                            <form onSubmit={refund} className="mt-3 space-y-2">
                                <Textarea rows={2} placeholder="Reason for refund (optional)" value={form.data.refund_reason} onChange={(e) => form.setData('refund_reason', e.target.value)} aria-label="Refund reason" />
                                {form.errors.refund_reason && <p className="text-xs text-danger">{form.errors.refund_reason}</p>}
                                <Button type="submit" tone="danger" className="w-full" disabled={form.processing}><Check size={16} />Confirm Refund</Button>
                            </form>
                        )}
                    </div>
                )}
                {order.payment_status === 'paid' && !pay.supports_refund && (
                    <p className="border-t border-line pt-4 text-center text-xs text-muted">Automatic refunds not available for this payment method</p>
                )}
            </div>
        </Card>
    );
}

function ScanModal({ onClose }) {
    const [code, setCode] = useState('');
    const scanner = useRef(null);

    const go = (value) => {
        const v = value.trim();
        if (v) window.location.href = `/admin/shipping-labels/resolve?code=${encodeURIComponent(v)}`;
    };

    useEffect(() => {
        let cancelled = false;
        import('html5-qrcode').then(({ Html5Qrcode, Html5QrcodeSupportedFormats }) => {
            if (cancelled) return;
            const qr = new Html5Qrcode('shippingLabelQrReader');
            scanner.current = qr;
            qr.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: { width: 240, height: 240 }, formatsToSupport: [Html5QrcodeSupportedFormats.CODE_128, Html5QrcodeSupportedFormats.QR_CODE] },
                (text) => go(text),
                () => {},
            ).catch(() => { /* no camera: manual entry still works */ });
        });
        return () => {
            cancelled = true;
            const qr = scanner.current;
            if (qr) qr.stop().then(() => qr.clear()).catch(() => {});
        };
    }, []);

    return (
        <div className="fixed inset-0 z-[80] flex items-center justify-center" role="dialog" aria-modal="true" aria-label="Scan shipping label">
            <div className="absolute inset-0 bg-black/60" onClick={onClose} />
            <div className="relative w-[92vw] max-w-lg rounded-card bg-surface p-4 shadow-xl">
                <div className="mb-3 flex items-center justify-between">
                    <h4 className="font-bold">Scan Shipping Label</h4>
                    <button type="button" onClick={onClose} aria-label="Close" className="text-muted hover:text-ink"><X size={18} /></button>
                </div>
                <p className="mb-3 text-sm text-muted">Scan the barcode/QR with the camera or a handheld scanner. You will be redirected to the label print page.</p>
                <form onSubmit={(e) => { e.preventDefault(); go(code); }} className="mb-3 flex gap-2">
                    <Input autoFocus value={code} onChange={(e) => setCode(e.target.value)} placeholder="Barcode or order number, then Enter" aria-label="Barcode or order number" />
                    <Button type="submit">Go</Button>
                </form>
                <div id="shippingLabelQrReader" className="w-full overflow-hidden rounded-lg border border-line" />
                <div className="mt-3 flex justify-end"><Button tone="neutral" onClick={onClose}>Close</Button></div>
            </div>
        </div>
    );
}

function Labels({ order }) {
    const [scan, setScan] = useState(false);
    return (
        <Card>
            <CardHeader title="Shipping Labels" />
            <div className="grid grid-cols-2 gap-3 p-4 md:p-6">
                <LinkButton external tone="neutral" href={`/admin/shipping-labels/${order.id}/print`} target="_blank" rel="noreferrer"><Printer size={16} />Print</LinkButton>
                <Button tone="neutral" onClick={() => setScan(true)}><QrCode size={16} />Scan QR</Button>
            </div>
            {scan && <ScanModal onClose={() => setScan(false)} />}
        </Card>
    );
}

function Status({ order }) {
    const status = useForm({ status: order.status });
    const payment = useForm({ payment_status: order.payment_status });
    const pickup = order.delivery_type === 'pickup';
    const opts = { preserveScroll: true };

    return (
        <Card>
            <CardHeader title="Update Status" />
            <div className="space-y-4 p-4 md:p-6">
                <form onSubmit={(e) => { e.preventDefault(); status.patch(`/admin/orders/${order.id}/status`, opts); }} className="space-y-2">
                    <Select value={status.data.status} onChange={(e) => status.setData('status', e.target.value)} aria-label="Order status">
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        {pickup ? <option value="ready_for_pickup">Ready for Pickup</option> : <option value="shipped">Shipped</option>}
                        <option value="delivered">{pickup ? 'Picked Up' : 'Delivered'}</option>
                        <option value="cancelled">Cancelled</option>
                    </Select>
                    {status.errors.status && <p className="text-xs text-danger">{status.errors.status}</p>}
                    <Button type="submit" className="w-full" disabled={status.processing}>Update Status</Button>
                </form>
                <form onSubmit={(e) => { e.preventDefault(); payment.patch(`/admin/orders/${order.id}/payment`, opts); }} className="space-y-2">
                    <Select value={payment.data.payment_status} onChange={(e) => payment.setData('payment_status', e.target.value)} aria-label="Payment status">
                        {['pending', 'paid', 'failed', 'refunded'].map((s) => <option key={s} value={s}>{titleCase(s)}</option>)}
                    </Select>
                    <Button type="submit" tone="neutral" className="w-full" disabled={payment.processing}>Update Payment</Button>
                </form>
            </div>
        </Card>
    );
}

function Driver({ order, drivers }) {
    const form = useForm({ driver_id: order.driver_id ?? '' });
    const d = order.driver;
    const label = (s) => (s === 'available' ? 'Available' : s === 'busy' ? 'Busy' : 'Offline');
    return (
        <Card>
            <CardHeader title="Driver Assignment" />
            <div className="p-4 md:p-6">
                {d && (
                    <div className="mb-4 rounded-lg border border-success/40 bg-success/10 p-3">
                        <div className="flex items-center justify-between gap-2">
                            <div><p className="font-medium">{d.name}</p><p className="text-sm text-muted">{d.phone}</p></div>
                            <Badge tone={d.status === 'available' ? 'success' : d.status === 'busy' ? 'warning' : 'muted'}>{label(d.status)}</Badge>
                        </div>
                        {d.assigned_at && <p className="mt-2 text-xs text-muted">Assigned: {d.assigned_at}</p>}
                    </div>
                )}
                <form onSubmit={(e) => { e.preventDefault(); form.patch(`/admin/orders/${order.id}/assign-driver`, { preserveScroll: true }); }} className="space-y-2">
                    <Select value={form.data.driver_id} onChange={(e) => form.setData('driver_id', e.target.value)} aria-label="Driver">
                        <option value="">-- Select Driver --</option>
                        {drivers.map((x) => <option key={x.id} value={x.id}>{x.name} ({label(x.driver_status)})</option>)}
                    </Select>
                    {form.errors.driver_id && <p className="text-xs text-danger">{form.errors.driver_id}</p>}
                    <Button type="submit" tone="success" className="w-full" disabled={form.processing}><UserCheck size={16} />{order.driver_id ? 'Reassign' : 'Assign'} Driver</Button>
                </form>
                {order.driver_notes && (
                    <div className="mt-4 rounded-lg bg-surface-2 p-3">
                        <p className="mb-1 text-xs text-muted">Driver Notes:</p>
                        <p className="whitespace-pre-wrap text-sm">{order.driver_notes}</p>
                    </div>
                )}
            </div>
        </Card>
    );
}

function Tracking({ order }) {
    const form = useForm({ tracking_number: order.tracking_number ?? '', tracking_url: order.tracking_url ?? '' });
    return (
        <Card>
            <CardHeader title="Tracking Information" />
            <div className="p-4 md:p-6">
                <form onSubmit={(e) => { e.preventDefault(); form.patch(`/admin/orders/${order.id}/tracking`, { preserveScroll: true }); }} className="space-y-3">
                    <Field label="Tracking Number" error={form.errors.tracking_number}>
                        <Input value={form.data.tracking_number} onChange={(e) => form.setData('tracking_number', e.target.value)} placeholder="Enter tracking number" />
                    </Field>
                    <Field label="Tracking URL" error={form.errors.tracking_url}>
                        <Input type="url" value={form.data.tracking_url} onChange={(e) => form.setData('tracking_url', e.target.value)} placeholder="https://…" />
                    </Field>
                    <Button type="submit" tone="success" className="w-full" disabled={form.processing}><Truck size={16} />Update Tracking</Button>
                </form>
                {order.tracking_number && (
                    <div className="mt-4 rounded-lg bg-primary-soft/40 p-3">
                        <p className="text-sm font-medium">{order.tracking_number}</p>
                        {order.tracking_url && (
                            <a href={order.tracking_url} target="_blank" rel="noreferrer" className="inline-flex items-center gap-1 text-sm text-primary hover:underline">
                                Track Package <ExternalLink size={12} />
                            </a>
                        )}
                    </div>
                )}
            </div>
        </Card>
    );
}

function Notes({ order }) {
    const form = useForm({ note: '' });
    return (
        <Card>
            <CardHeader title="Order Notes" />
            <div className="p-4 md:p-6">
                {order.notes && <div className="mb-4 whitespace-pre-wrap rounded-lg bg-surface-2 p-3 text-sm">{order.notes}</div>}
                <form
                    onSubmit={(e) => { e.preventDefault(); form.post(`/admin/orders/${order.id}/note`, { preserveScroll: true, onSuccess: () => form.reset() }); }}
                    className="space-y-2"
                >
                    <Textarea rows={2} required placeholder="Add a note…" value={form.data.note} onChange={(e) => form.setData('note', e.target.value)} aria-label="Note" />
                    {form.errors.note && <p className="text-xs text-danger">{form.errors.note}</p>}
                    <Button type="submit" tone="neutral" className="w-full" disabled={form.processing}><StickyNote size={16} />Add Note</Button>
                </form>
            </div>
        </Card>
    );
}

function Timeline({ order }) {
    const events = [
        ['Order Created', order.created_at, Check, 'success'],
        order.shipped_at && ['Order Shipped', order.shipped_at, Truck, 'primary'],
        order.delivered_at && ['Order Delivered', order.delivered_at, PackageOpen, 'success'],
    ].filter(Boolean);
    return (
        <Card>
            <CardHeader title="Timeline" />
            <ol className="space-y-4 p-4 md:p-6">
                {events.map(([t, d, Icon, tone]) => (
                    <li key={t} className="flex items-start gap-3">
                        <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full" style={{ color: `var(--${tone})`, background: `color-mix(in srgb, var(--${tone}) 15%, transparent)` }}><Icon size={14} /></span>
                        <div><p className="text-sm font-medium">{t}</p><p className="text-xs text-muted">{d}</p></div>
                    </li>
                ))}
            </ol>
        </Card>
    );
}

export default function Show({ order, drivers }) {
    return (
        <AdminLayout title={`Order ${order.number}`}>
            <Head title={`Order ${order.number}`} />

            <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
                <Link href="/admin/orders" className="inline-flex items-center gap-2 text-sm text-muted hover:text-ink"><ArrowLeft size={16} />Back to Orders</Link>
                <LinkButton external href={`/admin/invoices/${order.id}`}><FileText size={16} />Invoice</LinkButton>
                <LinkButton external tone="neutral" href={`/admin/invoices/${order.id}/download`}><Download size={16} />PDF</LinkButton>
            </div>

            <div className="grid gap-5 lg:grid-cols-3">
                <div className="space-y-5 lg:col-span-2">
                    <Progress order={order} />
                    <Items order={order} />
                    <Customer order={order} />
                </div>

                <div className="space-y-5">
                    <Card>
                        <CardHeader title="Order Summary" />
                        <div className="space-y-2 p-4 text-sm md:p-6">
                            <div className="flex justify-between"><span>Subtotal</span><span>{order.subtotal}</span></div>
                            {order.discount && <div className="flex justify-between text-success"><span>Discount</span><span>-{order.discount}</span></div>}
                            <div className="flex justify-between"><span>Shipping</span><span>{order.shipping_cost}</span></div>
                            <div className="flex justify-between"><span>Tax</span><span>{order.tax}</span></div>
                            <div className="flex justify-between border-t border-line pt-2 text-lg font-bold"><span>Total</span><span>{order.total}</span></div>
                        </div>
                    </Card>
                    <Payment order={order} />
                    <Labels order={order} />
                    <Status order={order} />
                    {drivers && <Driver order={order} drivers={drivers} />}
                    <Tracking order={order} />
                    <Notes order={order} />
                    <Timeline order={order} />
                </div>
            </div>
        </AdminLayout>
    );
}
