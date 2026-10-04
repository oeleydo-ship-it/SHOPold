import { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { Clock, MapPin, Pencil, Phone, Plus, Trash2 } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { Badge, Button, Card, CardHeader, Field, Input, Textarea } from '../../../Components/ui';
import { Toggle, confirmDelete } from '../../../Components/crud';

const DAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
const ALL_DAYS = [0, 1, 2, 3, 4, 5, 6];

function SlotForm({ slot, onDone }) {
    const edit = !!slot;
    const form = useForm({
        name: slot?.name ?? '', start_time: slot?.start_time ?? '', end_time: slot?.end_time ?? '', max_orders: slot?.max_orders ?? 10,
        extra_charge: slot?.extra_charge ?? 0, available_days: slot?.days ?? ALL_DAYS, is_active: slot?.is_active ?? true,
    });
    const { data, setData, errors, processing } = form;
    const toggleDay = (d) => setData('available_days', data.available_days.includes(d) ? data.available_days.filter((x) => x !== d) : [...data.available_days, d].sort());
    const submit = (e) => {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: () => { if (!edit) form.reset(); onDone?.(); } };
        if (edit) form.put(`/admin/delivery/slots/${slot.id}`, opts); else form.post('/admin/delivery/slots', opts);
    };

    return (
        <form onSubmit={submit} className="space-y-3">
            <Field label="Slot Name *" error={errors.name}><Input required placeholder="e.g., Morning, Afternoon" value={data.name} onChange={(e) => setData('name', e.target.value)} /></Field>
            <div className="grid grid-cols-2 gap-3">
                <Field label="Start Time *" error={errors.start_time}><Input type="time" required value={data.start_time} onChange={(e) => setData('start_time', e.target.value)} /></Field>
                <Field label="End Time *" error={errors.end_time}><Input type="time" required value={data.end_time} onChange={(e) => setData('end_time', e.target.value)} /></Field>
                <Field label="Max Orders/Day *" error={errors.max_orders}><Input type="number" min="1" required value={data.max_orders} onChange={(e) => setData('max_orders', e.target.value)} /></Field>
                <Field label="Extra Charge" error={errors.extra_charge}><Input type="number" min="0" step="0.01" value={data.extra_charge} onChange={(e) => setData('extra_charge', e.target.value)} /></Field>
            </div>
            <Field label="Available Days" error={errors.available_days}>
                <div className="flex flex-wrap gap-2">{DAYS.map((d, i) => <label key={d} className="flex cursor-pointer items-center gap-1 text-sm"><input type="checkbox" checked={data.available_days.includes(i)} onChange={() => toggleDay(i)} />{d}</label>)}</div>
            </Field>
            <Toggle label="Active" checked={data.is_active} onChange={(v) => setData('is_active', v)} />
            <div className="flex justify-end gap-2">
                {onDone && <Button tone="neutral" onClick={onDone}>Cancel</Button>}
                <Button type="submit" disabled={processing}>{edit ? 'Save Slot' : <><Plus size={14} />Add Slot</>}</Button>
            </div>
        </form>
    );
}

function LocationForm({ location, onDone }) {
    const edit = !!location;
    const form = useForm({
        name: location?.name ?? '', address: location?.address ?? '', city: location?.city ?? '', state: location?.state ?? '', zip: location?.zip ?? '',
        country: location?.country ?? 'United States', phone: location?.phone ?? '', email: location?.email ?? '', instructions: location?.instructions ?? '', is_active: location?.is_active ?? true,
    });
    const { data, setData, errors, processing } = form;
    const f = (k) => ({ value: data[k], onChange: (e) => setData(k, e.target.value) });
    const submit = (e) => {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: () => { if (!edit) form.reset(); onDone?.(); } };
        if (edit) form.put(`/admin/delivery/locations/${location.id}`, opts); else form.post('/admin/delivery/locations', opts);
    };

    return (
        <form onSubmit={submit} className="space-y-3">
            <Field label="Location Name *" error={errors.name}><Input required placeholder="e.g., Main Store" {...f('name')} /></Field>
            <Field label="Address *" error={errors.address}><Textarea rows={2} required {...f('address')} /></Field>
            <div className="grid grid-cols-2 gap-3">
                <Field label="City *" error={errors.city}><Input required {...f('city')} /></Field>
                <Field label="State" error={errors.state}><Input {...f('state')} /></Field>
                <Field label="ZIP Code" error={errors.zip}><Input {...f('zip')} /></Field>
                <Field label="Country *" error={errors.country}><Input required {...f('country')} /></Field>
                <Field label="Phone" error={errors.phone}><Input {...f('phone')} /></Field>
                <Field label="Email" error={errors.email}><Input type="email" {...f('email')} /></Field>
            </div>
            <Field label="Pickup Instructions" error={errors.instructions}><Textarea rows={2} placeholder="e.g., Enter through the back door…" {...f('instructions')} /></Field>
            <Toggle label="Active" checked={data.is_active} onChange={(v) => setData('is_active', v)} />
            <div className="flex justify-end gap-2">
                {onDone && <Button tone="neutral" onClick={onDone}>Cancel</Button>}
                <Button type="submit" disabled={processing}>{edit ? 'Save Location' : <><Plus size={14} />Add Location</>}</Button>
            </div>
        </form>
    );
}

const Actions = ({ label, active, toggleUrl, deleteUrl, deleteMessage, onEdit }) => (
    <div className="flex shrink-0 items-center gap-3">
        <button type="button" onClick={() => router.patch(toggleUrl, {}, { preserveScroll: true })} aria-label={`${active ? 'Deactivate' : 'Activate'} ${label}`}><Badge tone={active ? 'success' : 'muted'}>{active ? 'Active' : 'Inactive'}</Badge></button>
        <button type="button" onClick={onEdit} aria-label={`Edit ${label}`} className="text-primary"><Pencil size={16} /></button>
        <button type="button" onClick={() => confirmDelete(deleteUrl, deleteMessage)} aria-label={`Delete ${label}`} className="text-danger"><Trash2 size={16} /></button>
    </div>
);

export default function Index({ slots, locations }) {
    const [editSlot, setEditSlot] = useState(null);
    const [editLoc, setEditLoc] = useState(null);

    return (
        <AdminLayout title="Delivery">
            <Head title="Delivery Management" />
            <div className="grid gap-5 lg:grid-cols-2">
                <div className="space-y-5">
                    <Card>
                        <CardHeader title="Delivery Time Slots" />
                        {slots.length === 0 ? <p className="p-6 text-sm text-muted">No delivery slots yet.</p> : (
                            <ul className="divide-y divide-line">
                                {slots.map((s) => (
                                    <li key={s.id} className="p-4 md:px-6">
                                        {editSlot === s.id ? <div className="rounded-lg bg-surface-2 p-4"><SlotForm slot={s} onDone={() => setEditSlot(null)} /></div> : (
                                            <div className="flex items-center justify-between gap-3">
                                                <div className="flex items-center gap-3"><Clock size={18} className="text-muted" /><div>
                                                    <p className="font-medium">{s.name}</p><p className="text-sm text-muted">{s.time_range}</p>
                                                    <p className="text-xs text-muted">Max {s.max_orders} orders/day{Number(s.extra_charge) > 0 ? ` · +${Number(s.extra_charge).toFixed(2)}` : ''} · {s.days.length === 7 ? 'Every day' : s.days.map((d) => DAYS[d]).join(', ')}</p>
                                                </div></div>
                                                <Actions label={s.name} active={s.is_active} toggleUrl={`/admin/delivery/slots/${s.id}/toggle`} deleteUrl={`/admin/delivery/slots/${s.id}`} deleteMessage="Delete this slot?" onEdit={() => setEditSlot(s.id)} />
                                            </div>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Card>
                    <Card><CardHeader title="Add Delivery Slot" /><div className="p-4 md:p-6"><SlotForm slot={null} /></div></Card>
                </div>

                <div className="space-y-5">
                    <Card>
                        <CardHeader title="Pickup Locations" />
                        {locations.length === 0 ? <p className="p-6 text-sm text-muted">No pickup locations yet.</p> : (
                            <ul className="divide-y divide-line">
                                {locations.map((l) => (
                                    <li key={l.id} className="p-4 md:px-6">
                                        {editLoc === l.id ? <div className="rounded-lg bg-surface-2 p-4"><LocationForm location={l} onDone={() => setEditLoc(null)} /></div> : (
                                            <div className="flex items-start justify-between gap-3">
                                                <div className="flex gap-3"><MapPin size={18} className="mt-0.5 shrink-0 text-muted" /><div>
                                                    <p className="font-medium">{l.name}</p><p className="text-sm text-muted">{l.address}</p><p className="text-sm text-muted">{l.city}, {l.country}</p>
                                                    {l.phone && <p className="flex items-center gap-1 text-xs text-muted"><Phone size={12} />{l.phone}</p>}
                                                </div></div>
                                                <Actions label={l.name} active={l.is_active} toggleUrl={`/admin/delivery/locations/${l.id}/toggle`} deleteUrl={`/admin/delivery/locations/${l.id}`} deleteMessage="Delete this location?" onEdit={() => setEditLoc(l.id)} />
                                            </div>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Card>
                    <Card><CardHeader title="Add Pickup Location" /><div className="p-4 md:p-6"><LocationForm location={null} /></div></Card>
                </div>
            </div>
        </AdminLayout>
    );
}
