import { useMemo, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Pencil, Plus, Trash2 } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { Badge, Button, Card, CardHeader, Field, Input, Select, titleCase } from '../../../Components/ui';
import { Toggle, confirmDelete } from '../../../Components/crud';
import { methodCost } from './Index';

const TYPES = [['flat_rate', 'Flat Rate'], ['free', 'Free Shipping'], ['weight_based', 'Weight Based'], ['per_item', 'Per Item']];
const blankMethod = { name: '', type: 'flat_rate', cost: '', min_order_amount: '', per_kg_rate: '', per_item_rate: '', delivery_time: '', sort_order: 0, is_active: true };

/** Add (method = null) or edit one shipping method of a zone. */
function MethodForm({ zoneId, method, onDone }) {
    const edit = !!method;
    const form = useForm(method ? { ...blankMethod, ...method } : blankMethod);
    const { data, setData, errors, processing } = form;
    const types = TYPES.some(([v]) => v === data.type) ? TYPES : [...TYPES, [data.type, titleCase(data.type)]];
    const num = (k, label, ph) => <Field label={label} error={errors[k]}><Input type="number" step="0.01" min="0" placeholder={ph} value={data[k] ?? ''} onChange={(e) => setData(k, e.target.value)} /></Field>;

    const submit = (e) => {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: () => { if (!edit) form.reset(); onDone?.(); } };
        if (edit) form.put(`/admin/shipping/methods/${method.id}`, opts);
        else form.post(`/admin/shipping/${zoneId}/methods`, opts);
    };

    return (
        <form onSubmit={submit} className="space-y-3">
            <div className="grid gap-3 sm:grid-cols-2">
                <Field label="Name *" error={errors.name}><Input required placeholder="e.g., Standard Shipping" value={data.name} onChange={(e) => setData('name', e.target.value)} /></Field>
                <Field label="Type *" error={errors.type}><Select value={data.type} onChange={(e) => setData('type', e.target.value)}>{types.map(([v, l]) => <option key={v} value={v}>{l}</option>)}</Select></Field>
                {data.type !== 'free' && num('cost', 'Cost', '0.00')}
                {data.type === 'free' && num('min_order_amount', 'Minimum order amount', 'Leave empty for always free')}
                {data.type === 'weight_based' && num('per_kg_rate', 'Rate per kg', '0.00')}
                {data.type === 'per_item' && num('per_item_rate', 'Rate per item', '0.00')}
                <Field label="Delivery time" error={errors.delivery_time}><Input placeholder="e.g., 3-5 business days" value={data.delivery_time ?? ''} onChange={(e) => setData('delivery_time', e.target.value)} /></Field>
            </div>
            <Toggle label="Active" checked={data.is_active} onChange={(v) => setData('is_active', v)} />
            <div className="flex justify-end gap-2">
                {onDone && <Button tone="neutral" onClick={onDone}>Cancel</Button>}
                <Button type="submit" disabled={processing}>{edit ? 'Save Method' : <><Plus size={14} />Add Method</>}</Button>
            </div>
        </form>
    );
}

export default function Form({ zone, methods, countries, allowedCountries, defaultCountry }) {
    const edit = !!zone;
    const form = useForm({ name: zone?.name ?? '', sort_order: zone?.sort_order ?? 0, is_active: zone?.is_active ?? true, countries: zone?.countries ?? [] });
    const { data, setData, errors, processing } = form;
    const [query, setQuery] = useState('');
    const [editing, setEditing] = useState(null);
    const byCode = useMemo(() => Object.fromEntries(countries.map((c) => [c.code, c.name])), [countries]);
    const all = data.countries.includes('*');
    const shown = countries.filter((c) => c.code !== '*' && c.name.toLowerCase().includes(query.trim().toLowerCase()));
    const quick = allowedCountries.filter((c) => byCode[c]);

    const toggle = (code) => setData('countries', data.countries.includes(code) ? data.countries.filter((c) => c !== code) : [...data.countries, code]);
    const submit = (e) => {
        e.preventDefault();
        if (edit) form.put(`/admin/shipping/${zone.id}`, { preserveScroll: true });
        else form.post('/admin/shipping');
    };
    const Check = ({ code, label }) => <label className="flex cursor-pointer items-center gap-2 rounded px-2 py-1 text-sm hover:bg-surface-2"><input type="checkbox" checked={data.countries.includes(code)} onChange={() => toggle(code)} />{label}</label>;

    return (
        <AdminLayout title={edit ? 'Edit Shipping Zone' : 'Create Shipping Zone'}>
            <Head title={edit ? `Edit Zone: ${zone.name}` : 'Create Shipping Zone'} />
            <Link href="/admin/shipping" className="mb-5 inline-flex items-center gap-2 text-sm text-muted hover:text-ink"><ArrowLeft size={16} />Back to Zones</Link>

            <form onSubmit={submit} className="space-y-5">
                <Card>
                    <CardHeader title="Zone Details" />
                    <div className="grid gap-4 p-4 sm:grid-cols-2 md:p-6">
                        <Field label="Zone Name *" error={errors.name}><Input required placeholder="e.g., Domestic, International, Europe" value={data.name} onChange={(e) => setData('name', e.target.value)} /></Field>
                        <Field label="Sort Order" error={errors.sort_order}><Input type="number" min="0" value={data.sort_order} onChange={(e) => setData('sort_order', e.target.value)} /></Field>
                        <Toggle label="Active" checked={data.is_active} onChange={(v) => setData('is_active', v)} />
                    </div>
                </Card>

                <Card>
                    <CardHeader title="Countries"><span className="text-xs text-muted">{all ? 'All countries' : `${data.countries.length} selected`}</span></CardHeader>
                    <div className="space-y-4 p-4 md:p-6">
                        <p className="text-sm text-muted">Select which countries this shipping zone applies to.</p>
                        {errors.countries && <p className="text-xs text-danger">{errors.countries}</p>}
                        <Check code="*" label={<strong>All Countries</strong>} />
                        {quick.length > 0 && (
                            <div>
                                <p className="mb-1 text-xs font-medium uppercase text-muted">Your allowed countries{defaultCountry && byCode[defaultCountry] ? ` (default: ${byCode[defaultCountry]})` : ''}</p>
                                <div className="grid gap-x-3 sm:grid-cols-2 lg:grid-cols-3">{quick.map((c) => <Check key={c} code={c} label={byCode[c]} />)}</div>
                            </div>
                        )}
                        <div>
                            <Input placeholder="Search countries…" aria-label="Search countries" value={query} onChange={(e) => setQuery(e.target.value)} />
                            <div className="mt-2 grid max-h-64 gap-x-3 overflow-y-auto rounded-lg border border-line p-2 sm:grid-cols-2 lg:grid-cols-3">
                                {shown.map((c) => <Check key={c.code} code={c.code} label={c.name} />)}
                                {shown.length === 0 && <p className="p-2 text-sm text-muted">No countries match.</p>}
                            </div>
                        </div>
                    </div>
                </Card>

                <div className="flex justify-end gap-2">
                    <Link href="/admin/shipping" className="rounded-lg border border-line px-4 py-2 text-sm hover:bg-surface-2">Cancel</Link>
                    <Button type="submit" disabled={processing}>{edit ? 'Update Zone' : 'Create Zone'}</Button>
                </div>
            </form>

            {edit && (
                <div className="mt-8 space-y-5">
                    <Card>
                        <CardHeader title="Shipping Methods" />
                        {methods.length === 0 ? <p className="p-6 text-sm text-muted">No shipping methods yet.</p> : (
                            <ul className="divide-y divide-line">
                                {methods.map((m) => (
                                    <li key={m.id} className="p-4 md:px-6">
                                        {editing === m.id ? (
                                            <div className="rounded-lg bg-surface-2 p-4"><MethodForm zoneId={zone.id} method={m} onDone={() => setEditing(null)} /></div>
                                        ) : (
                                            <div className="flex flex-wrap items-center justify-between gap-3">
                                                <div>
                                                    <p className="flex items-center gap-2 font-medium">{m.name}<Badge>{titleCase(m.type)}</Badge>{!m.is_active && <Badge tone="muted">Inactive</Badge>}</p>
                                                    <p className="text-sm text-muted">{methodCost(m)}{m.delivery_time ? ` · ${m.delivery_time}` : ''}</p>
                                                </div>
                                                <div className="flex gap-3">
                                                    <button type="button" aria-label={`Edit ${m.name}`} onClick={() => setEditing(m.id)} className="text-primary"><Pencil size={16} /></button>
                                                    <button type="button" aria-label={`Delete ${m.name}`} onClick={() => confirmDelete(`/admin/shipping/methods/${m.id}`, `Delete method "${m.name}"?`)} className="text-danger"><Trash2 size={16} /></button>
                                                </div>
                                            </div>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Card>
                    <Card>
                        <CardHeader title="Add Shipping Method" />
                        <div className="p-4 md:p-6"><MethodForm zoneId={zone.id} method={null} /></div>
                    </Card>
                </div>
            )}
        </AdminLayout>
    );
}
