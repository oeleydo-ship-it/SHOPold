import { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { AlertTriangle, ArrowLeft, Package, Truck } from 'lucide-react';
import VendorLayout from '../../../Layouts/VendorLayout';
import { Badge, Button, Card, CardHeader, Input, OrderStatusBadge, PaymentStatusBadge, Select, Textarea, titleCase } from '../../../Components/ui';

const ITEM_TONE = { pending: 'warning', processing: 'info', shipped: 'primary', delivered: 'success', cancelled: 'danger' };

/** Fulfilment controls for one item: status, carrier, tracking number and a note for the store. */
function ItemFulfilment({ item, statuses }) {
    const [open, setOpen] = useState(false);
    const { data, setData, patch, processing, errors } = useForm({
        fulfillment_status: item.status, shipping_carrier: item.shipping_carrier ?? '', tracking_number: item.tracking_number ?? '', vendor_note: item.vendor_note ?? '',
    });
    const save = (e) => { e.preventDefault(); patch(`/vendor/orders/items/${item.id}/status`, { preserveScroll: true, onSuccess: () => setOpen(false) }); };

    if (!open) return <Button tone="neutral" onClick={() => setOpen(true)}>Update</Button>;
    return (
        <form onSubmit={save} className="mt-3 grid w-full gap-2 sm:grid-cols-2">
            <Select aria-label="Fulfilment status" value={data.fulfillment_status} onChange={(e) => setData('fulfillment_status', e.target.value)}>
                {Object.entries(statuses).map(([v, l]) => <option key={v} value={v}>{l}</option>)}
            </Select>
            <Input aria-label="Carrier" placeholder="Carrier" value={data.shipping_carrier} onChange={(e) => setData('shipping_carrier', e.target.value)} />
            <Input aria-label="Tracking number" placeholder="Tracking number" value={data.tracking_number} onChange={(e) => setData('tracking_number', e.target.value)} />
            <Textarea aria-label="Note" rows={1} maxLength={1000} placeholder="Note (optional)" value={data.vendor_note} onChange={(e) => setData('vendor_note', e.target.value)} />
            {Object.values(errors)[0] && <p className="text-xs text-danger sm:col-span-2">{Object.values(errors)[0]}</p>}
            <div className="flex gap-2 sm:col-span-2">
                <Button type="submit" disabled={processing}>Save</Button>
                <Button tone="neutral" onClick={() => setOpen(false)}>Cancel</Button>
            </div>
        </form>
    );
}

function BulkUpdate({ orderId }) {
    const { data, setData, post, processing, reset } = useForm({ fulfillment_status: '', tracking_number: '', shipping_carrier: '' });
    const submit = (e) => { e.preventDefault(); post(`/vendor/orders/${orderId}/bulk-status`, { preserveScroll: true, onSuccess: () => reset() }); };
    return (
        <form onSubmit={submit} className="flex flex-wrap items-center gap-2 border-b border-line bg-surface-2 p-4 md:px-6">
            <span className="text-sm font-medium">Update all your items:</span>
            <Select className="w-auto" required aria-label="Status for all items" value={data.fulfillment_status} onChange={(e) => setData('fulfillment_status', e.target.value)}>
                <option value="">Choose status</option>
                <option value="processing">Processing</option>
                <option value="shipped">Shipped</option>
                <option value="delivered">Delivered</option>
            </Select>
            <Input className="w-40" aria-label="Tracking number" placeholder="Tracking # (optional)" value={data.tracking_number} onChange={(e) => setData('tracking_number', e.target.value)} />
            <Input className="w-36" aria-label="Carrier" placeholder="Carrier (optional)" value={data.shipping_carrier} onChange={(e) => setData('shipping_carrier', e.target.value)} />
            <Button type="submit" disabled={processing || !data.fulfillment_status}>Update all</Button>
        </form>
    );
}

export default function Show({ order, totals, fulfillmentStatuses }) {
    const open = order.items.some((i) => i.status !== 'delivered' && i.status !== 'cancelled');

    return (
        <VendorLayout title={`Order ${order.number}`} actions={<Link href="/vendor/orders" className="inline-flex items-center gap-1 text-sm text-muted hover:text-ink"><ArrowLeft size={16} />All orders</Link>}>
            <Head title={`Order ${order.number}`} />

            <div className="grid gap-5 xl:grid-cols-3">
                <div className="space-y-5 xl:col-span-2">
                    <Card className="overflow-hidden">
                        <CardHeader title={`Placed ${order.date}`}><OrderStatusBadge status={order.status} /></CardHeader>
                        {!order.can_fulfill && order.status !== 'cancelled' && (
                            <p className="flex items-center gap-2 border-b border-line bg-warning/10 px-4 py-3 text-sm md:px-6"><AlertTriangle size={16} className="text-warning" />Waiting for payment. You can fulfil these items once the order is paid.</p>
                        )}
                        {order.can_fulfill && open && <BulkUpdate orderId={order.id} />}
                        <ul className="divide-y divide-line">
                            {order.items.map((item) => (
                                <li key={item.id} className="p-4 md:px-6">
                                    <div className="flex flex-wrap items-start gap-4">
                                        <div className="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-surface-2 text-muted">{item.image ? <img src={item.image} alt="" className="h-full w-full object-cover" /> : <Package size={20} />}</div>
                                        <div className="min-w-0 flex-1">
                                            <p className="font-medium">{item.name}</p>
                                            {Object.keys(item.options).length > 0 && <p className="text-xs text-muted">{Object.entries(item.options).map(([k, v]) => `${k}: ${v}`).join(' · ')}</p>}
                                            <p className="text-sm text-muted">{item.quantity} × {item.price} = <span className="font-medium text-ink">{item.total}</span></p>
                                            {(item.tracking_number || item.shipping_carrier) && (
                                                <p className="mt-1 flex items-center gap-1 text-xs text-muted"><Truck size={12} />{[item.shipping_carrier, item.tracking_number].filter(Boolean).join(' · ')}</p>
                                            )}
                                            {(item.shipped_at || item.delivered_at) && <p className="text-xs text-muted">{item.shipped_at && `Shipped ${item.shipped_at}`}{item.delivered_at && ` · Delivered ${item.delivered_at}`}</p>}
                                        </div>
                                        <div className="flex flex-col items-end gap-2">
                                            <Badge tone={ITEM_TONE[item.status] ?? 'muted'}>{item.status_label}</Badge>
                                            {order.can_fulfill && <ItemFulfilment item={item} statuses={fulfillmentStatuses} />}
                                        </div>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </Card>

                    {order.notes && (
                        <Card>
                            <CardHeader title="Customer note" />
                            <p className="whitespace-pre-line p-4 text-sm text-muted md:px-6">{order.notes}</p>
                        </Card>
                    )}
                </div>

                <div className="space-y-5">
                    <Card>
                        <CardHeader title="Your earnings" />
                        <dl className="space-y-2 p-4 text-sm md:px-6">
                            <div className="flex justify-between"><dt className="text-muted">Your items</dt><dd>{totals.sales}</dd></div>
                            <div className="flex justify-between"><dt className="text-muted">Store commission ({totals.rate}%)</dt><dd className="text-danger">−{totals.commission}</dd></div>
                            <div className="flex justify-between border-t border-line pt-2 font-semibold"><dt>You earn</dt><dd className="text-success">{totals.earnings}</dd></div>
                        </dl>
                    </Card>

                    <Card>
                        <CardHeader title="Payment" />
                        <dl className="space-y-2 p-4 text-sm md:px-6">
                            <div className="flex justify-between"><dt className="text-muted">Method</dt><dd>{titleCase(order.payment_method ?? '')}</dd></div>
                            <div className="flex justify-between"><dt className="text-muted">Status</dt><dd><PaymentStatusBadge status={order.payment_status} /></dd></div>
                        </dl>
                    </Card>

                    <Card>
                        <CardHeader title="Customer" />
                        <div className="space-y-1 p-4 text-sm md:px-6">
                            <p className="font-medium">{order.customer.name}</p>
                            {order.customer.email && <p className="text-muted">{order.customer.email}</p>}
                            {order.customer.phone && <p className="text-muted">{order.customer.phone}</p>}
                        </div>
                    </Card>

                    <Card>
                        <CardHeader title="Ship to" />
                        <address className="space-y-1 p-4 text-sm not-italic md:px-6">
                            <p className="font-medium">{order.shipping.name}</p>
                            <p className="text-muted">{order.shipping.address}</p>
                            <p className="text-muted">{order.shipping.city}</p>
                            <p className="text-muted">{order.shipping.country}</p>
                        </address>
                    </Card>
                </div>
            </div>
        </VendorLayout>
    );
}
