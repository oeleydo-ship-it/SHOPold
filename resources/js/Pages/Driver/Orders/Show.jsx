import { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Banknote, Camera, CheckCircle2, MapPin, Navigation, Package, PackageCheck, Phone, StickyNote } from 'lucide-react';
import DriverLayout from '../../../Layouts/DriverLayout';
import SignaturePad from '../../../Components/SignaturePad';
import { deliveryStage } from '../../../Components/DriverOrderCard';
import { Badge, Button, Card, CardHeader, Field, Textarea, buttonClass, titleCase } from '../../../Components/ui';

function CompleteDelivery({ order }) {
    const { data, setData, post, processing, errors, progress } = useForm({ proof_photo: null, signature: '', driver_notes: '' });
    const submit = (e) => {
        e.preventDefault();
        post(`/driver/orders/${order.id}/complete`, { forceFormData: true, preserveScroll: true });
    };

    return (
        <Card>
            <CardHeader title="Complete delivery" />
            <form onSubmit={submit} className="space-y-4 p-4">
                {order.cod && <p className="flex items-center gap-2 rounded-lg bg-warning/10 p-3 text-sm font-medium"><Banknote size={18} className="text-warning" />Collect {order.total} in cash before handing over.</p>}
                <Field label="Photo of the delivered parcel" error={errors.proof_photo}>
                    <span className="flex cursor-pointer items-center gap-3 rounded-lg border border-dashed border-line p-3 text-sm text-muted hover:border-primary">
                        <Camera size={20} />{data.proof_photo ? data.proof_photo.name : 'Take or choose a photo (optional)'}
                        <input type="file" accept="image/*" capture="environment" className="sr-only" onChange={(e) => setData('proof_photo', e.target.files[0] ?? null)} />
                    </span>
                </Field>
                <div>
                    <SignaturePad onChange={(v) => setData('signature', v)} />
                    {errors.signature && <p className="mt-1 text-xs text-danger">{errors.signature}</p>}
                </div>
                <Field label="Note (optional)" error={errors.driver_notes}><Textarea rows={2} maxLength={1000} value={data.driver_notes} onChange={(e) => setData('driver_notes', e.target.value)} placeholder="Left with the neighbour at no. 5…" /></Field>
                {progress && <progress value={progress.percentage} max="100" className="w-full" />}
                <Button type="submit" tone="success" disabled={processing} className="w-full justify-center py-3 text-base"><CheckCircle2 size={20} />{processing ? 'Saving…' : 'Mark as delivered'}</Button>
            </form>
        </Card>
    );
}

function AddNote({ orderId }) {
    const { data, setData, post, processing, errors, reset } = useForm({ note: '' });
    const submit = (e) => { e.preventDefault(); post(`/driver/orders/${orderId}/note`, { preserveScroll: true, onSuccess: () => reset() }); };
    return (
        <form onSubmit={submit} className="flex gap-2 border-t border-line p-4">
            <Textarea rows={1} required maxLength={1000} aria-label="Add a note" placeholder="Add a note…" value={data.note} onChange={(e) => setData('note', e.target.value)} className="flex-1" />
            <Button type="submit" tone="neutral" disabled={processing || !data.note.trim()}>Add</Button>
            {errors.note && <p className="text-xs text-danger">{errors.note}</p>}
        </form>
    );
}

export default function Show({ order }) {
    const [label, tone] = deliveryStage(order);
    const [busy, setBusy] = useState(false);
    const toPickUp = order.active && order.status === 'processing' && !order.picked_up;
    const pickUp = () => {
        setBusy(true);
        router.patch(`/driver/orders/${order.id}/status`, { status: 'picked_up' }, { preserveScroll: true, onFinish: () => setBusy(false) });
    };

    return (
        <DriverLayout title={order.number} actions={<Link href="/driver/orders" aria-label="Back to deliveries" className="text-muted hover:text-ink"><ArrowLeft size={20} /></Link>}>
            <Head title={`Delivery ${order.number}`} />

            <div className="grid gap-4 lg:grid-cols-3">
                <div className="space-y-4 lg:col-span-2">
                    <Card className="p-4">
                        <div className="mb-3 flex items-center justify-between gap-2">
                            <Badge tone={tone}>{label}</Badge>
                            <span className="text-xs text-muted">Placed {order.date}</span>
                        </div>
                        <p className="text-lg font-semibold">{order.shipping.name}</p>
                        <p className="mt-1 flex items-start gap-1 text-sm text-muted"><MapPin size={16} className="mt-0.5 shrink-0" /><span>{order.shipping.address}<br />{order.shipping.city}<br />{order.shipping.country}</span></p>
                        <div className="mt-4 grid grid-cols-2 gap-2">
                            <a href={order.map} target="_blank" rel="noreferrer" className={buttonClass('primary', 'justify-center py-3')}><Navigation size={18} />Navigate</a>
                            {order.phone
                                ? <a href={`tel:${order.phone}`} className={buttonClass('neutral', 'justify-center py-3')}><Phone size={18} />Call</a>
                                : <span className={buttonClass('neutral', 'justify-center py-3 opacity-50')}><Phone size={18} />No phone</span>}
                        </div>
                    </Card>

                    {toPickUp && (
                        <Button onClick={pickUp} disabled={busy} className="w-full justify-center py-4 text-base"><PackageCheck size={20} />{busy ? 'Saving…' : 'I have picked up this order'}</Button>
                    )}
                    {order.active && !toPickUp && <CompleteDelivery order={order} />}

                    {(order.proof_photo || order.signature) && (
                        <Card>
                            <CardHeader title="Proof of delivery" />
                            <div className="grid gap-4 p-4 sm:grid-cols-2">
                                {order.proof_photo && <a href={order.proof_photo} target="_blank" rel="noreferrer"><img src={order.proof_photo} alt="Delivered parcel" className="max-h-56 w-full rounded-lg object-cover" /></a>}
                                {order.signature && <img src={order.signature} alt="Customer signature" className="h-32 w-full rounded-lg border border-line bg-white object-contain" />}
                            </div>
                        </Card>
                    )}
                </div>

                <div className="space-y-4">
                    <Card>
                        <CardHeader title={`Items (${order.items})`} />
                        <ul className="divide-y divide-line">
                            {order.lines.map((l) => (
                                <li key={l.id} className="flex items-center gap-3 p-3">
                                    <div className="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-surface-2 text-muted">{l.image ? <img src={l.image} alt="" className="h-full w-full object-cover" /> : <Package size={18} />}</div>
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-sm font-medium">{l.name}</p>
                                        {Object.keys(l.options).length > 0 && <p className="text-xs text-muted">{Object.entries(l.options).map(([k, v]) => `${k}: ${v}`).join(' · ')}</p>}
                                    </div>
                                    <span className="text-sm font-semibold">×{l.quantity}</span>
                                </li>
                            ))}
                        </ul>
                        <dl className="space-y-1 border-t border-line p-4 text-sm">
                            <div className="flex justify-between"><dt className="text-muted">Order total</dt><dd className="font-semibold">{order.total}</dd></div>
                            <div className="flex justify-between"><dt className="text-muted">Payment</dt><dd>{titleCase(order.payment_method ?? '')} · {titleCase(order.payment_status ?? '')}</dd></div>
                        </dl>
                    </Card>

                    <Card>
                        <CardHeader title="Notes" />
                        {order.customer_note && <p className="border-b border-line p-4 text-sm"><span className="block text-xs font-medium uppercase text-muted">From the customer</span>{order.customer_note}</p>}
                        {order.driver_notes
                            ? <p className="whitespace-pre-line p-4 text-sm text-muted"><StickyNote size={14} className="mr-1 inline" />{order.driver_notes}</p>
                            : !order.customer_note && <p className="p-4 text-sm text-muted">No notes yet.</p>}
                        <AddNote orderId={order.id} />
                    </Card>
                </div>
            </div>
        </DriverLayout>
    );
}
