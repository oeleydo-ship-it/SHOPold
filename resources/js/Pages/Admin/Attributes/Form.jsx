import { useRef, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowDown, ArrowLeft, ArrowUp, ImagePlus, Pencil, Plus, Trash2, X } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { Button, Card, CardHeader, Field, Input, Select } from '../../../Components/ui';
import { Toggle, confirmDelete } from '../../../Components/crud';
import { jsonRequest } from '../../../lib/http';

function Swatch({ type, value }) {
    if (type === 'color' && value.color_code) {
        return <span className="h-6 w-6 shrink-0 rounded-full border border-line" style={{ background: value.color_code }} />;
    }
    if (value.image) return <img src={value.image} alt="" className="h-8 w-8 shrink-0 rounded object-cover" />;
    return null;
}

/** One form for add and edit of a value; `value` null = add. */
function ValueForm({ attribute, value, onDone }) {
    const edit = !!value;
    const fileRef = useRef(null);
    const form = useForm({
        value: value?.value ?? '',
        color_code: value?.color_code ?? '#3b82f6',
        sort_order: value?.sort_order ?? attribute.values.length,
        image: null,
    });
    const { data, setData, errors, processing } = form;

    const submit = (e) => {
        e.preventDefault();
        const opts = {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => { form.reset(); if (fileRef.current) fileRef.current.value = ''; onDone?.(); },
        };
        const withColor = (d) => (attribute.type === 'color' ? d : { ...d, color_code: null });
        if (edit) {
            form.transform((d) => ({ ...withColor(d), _method: 'put' }));
            form.post(`/admin/attributes/values/${value.id}`, opts);
        } else {
            form.transform(withColor);
            form.post(`/admin/attributes/${attribute.id}/values`, opts);
        }
    };

    return (
        <form onSubmit={submit} className="space-y-3">
            <div className="grid gap-3 sm:grid-cols-2">
                <Field label="Value *" error={errors.value}>
                    <Input value={data.value} onChange={(e) => setData('value', e.target.value)} required placeholder="e.g. Red, Large" />
                </Field>
                <Field label="Sort Order" error={errors.sort_order}>
                    <Input type="number" min="0" value={data.sort_order} onChange={(e) => setData('sort_order', e.target.value)} />
                </Field>
                {attribute.type === 'color' && (
                    <Field label="Color" error={errors.color_code}>
                        <div className="flex gap-2">
                            <input type="color" value={/^#[0-9a-f]{6}$/i.test(data.color_code ?? '') ? data.color_code : '#3b82f6'} onChange={(e) => setData('color_code', e.target.value)} className="h-10 w-14 cursor-pointer rounded-lg border border-line bg-surface p-1" aria-label="Pick color" />
                            <Input value={data.color_code ?? ''} maxLength={7} onChange={(e) => setData('color_code', e.target.value)} aria-label="Color hex" />
                        </div>
                    </Field>
                )}
                <Field label="Image (optional)" hint="Max 1MB" error={errors.image}>
                    <input ref={fileRef} type="file" accept="image/*" onChange={(e) => setData('image', e.target.files[0] ?? null)} className="w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm" />
                </Field>
            </div>
            <div className="flex justify-end gap-2">
                {onDone && <Button tone="neutral" onClick={onDone}>Cancel</Button>}
                <Button type="submit" disabled={processing}>{edit ? 'Save Value' : <><Plus size={14} />Add Value</>}</Button>
            </div>
        </form>
    );
}

function Values({ attribute }) {
    const [editing, setEditing] = useState(null);
    const values = attribute.values;

    const move = async (index, dir) => {
        const next = [...values];
        const j = index + dir;
        if (j < 0 || j >= next.length) return;
        [next[index], next[j]] = [next[j], next[index]];
        try {
            await jsonRequest(`/admin/attributes/${attribute.id}/reorder-values`, { method: 'POST', body: { order: next.map((v) => v.id) } });
            router.reload({ only: ['attribute'], preserveScroll: true });
        } catch (e) {
            window.alert(e.message);
        }
    };

    return (
        <Card>
            <CardHeader title={`Values (${values.length})`} />
            <div className="p-4 md:p-6">
                <div className="mb-5 rounded-lg border-2 border-dashed border-line bg-surface-2 p-4">
                    <h4 className="mb-3 text-sm font-medium">Add New Value</h4>
                    <ValueForm attribute={attribute} value={null} />
                </div>

                {values.length === 0 ? (
                    <p className="py-6 text-center text-sm text-muted">No values yet. Add the first one above.</p>
                ) : (
                    <ul className="divide-y divide-line rounded-lg border border-line">
                        {values.map((v, i) => (
                            <li key={v.id} className="p-3">
                                {editing === v.id ? (
                                    <div className="rounded-lg bg-surface-2 p-3"><ValueForm attribute={attribute} value={v} onDone={() => setEditing(null)} /></div>
                                ) : (
                                    <div className="flex items-center gap-3">
                                        <div className="flex flex-col">
                                            <button type="button" onClick={() => move(i, -1)} disabled={i === 0} aria-label={`Move ${v.value} up`} className="text-muted hover:text-ink disabled:opacity-30"><ArrowUp size={14} /></button>
                                            <button type="button" onClick={() => move(i, 1)} disabled={i === values.length - 1} aria-label={`Move ${v.value} down`} className="text-muted hover:text-ink disabled:opacity-30"><ArrowDown size={14} /></button>
                                        </div>
                                        <Swatch type={attribute.type} value={v} />
                                        <div className="min-w-0 flex-1"><p className="truncate font-medium">{v.value}</p><p className="text-xs text-muted">{v.slug}</p></div>
                                        <button type="button" onClick={() => setEditing(v.id)} aria-label={`Edit ${v.value}`} className="text-primary"><Pencil size={16} /></button>
                                        <button type="button" onClick={() => confirmDelete(`/admin/attributes/values/${v.id}`, `Delete value "${v.value}"?`)} aria-label={`Delete ${v.value}`} className="text-danger"><Trash2 size={16} /></button>
                                    </div>
                                )}
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </Card>
    );
}

export default function Form({ attribute }) {
    const edit = !!attribute;
    const form = useForm({
        name: attribute?.name ?? '',
        slug: attribute?.slug ?? '',
        type: attribute?.type ?? 'select',
        is_visible: attribute?.is_visible ?? true,
        is_variation: attribute?.is_variation ?? true,
        is_global: attribute?.is_global ?? true,
        sort_order: attribute?.sort_order ?? 0,
    });
    const { data, setData, errors, processing } = form;

    const submit = (e) => {
        e.preventDefault();
        if (edit) form.put(`/admin/attributes/${attribute.id}`);
        else form.post('/admin/attributes');
    };

    const title = edit ? `Edit Attribute: ${attribute.name}` : 'Add Attribute';
    return (
        <AdminLayout title={edit ? 'Edit Attribute' : 'Add Attribute'}>
            <Head title={title} />
            <Link href="/admin/attributes" className="mb-5 inline-flex items-center gap-2 text-sm text-muted hover:text-ink"><ArrowLeft size={16} />Back to Attributes</Link>

            <div className={`grid gap-5 ${edit ? 'lg:grid-cols-2' : 'max-w-2xl'}`}>
                <div className="space-y-5">
                    <form onSubmit={submit}>
                        <Card className="space-y-4 p-4 md:p-6">
                            <Field label="Attribute Name *" error={errors.name}><Input value={data.name} onChange={(e) => setData('name', e.target.value)} required /></Field>
                            <Field label="Slug" hint="Leave empty to generate from the name" error={errors.slug}><Input value={data.slug ?? ''} onChange={(e) => setData('slug', e.target.value)} /></Field>
                            <Field label="Display Type" error={errors.type}>
                                <Select value={data.type} onChange={(e) => setData('type', e.target.value)}>
                                    <option value="select">Dropdown Select</option>
                                    <option value="color">Color Swatch</option>
                                    <option value="button">Button</option>
                                    <option value="radio">Radio Buttons</option>
                                </Select>
                            </Field>
                            <div className="space-y-3">
                                <Toggle label="Visible on Product Page" checked={data.is_visible} onChange={(v) => setData('is_visible', v)} />
                                <div><Toggle label="Used for Variations" checked={data.is_variation} onChange={(v) => setData('is_variation', v)} /></div>
                                <div><Toggle label="Global Attribute" checked={data.is_global} onChange={(v) => setData('is_global', v)} /></div>
                            </div>
                            <Field label="Sort Order" error={errors.sort_order}><Input type="number" min="0" value={data.sort_order ?? 0} onChange={(e) => setData('sort_order', e.target.value)} /></Field>
                            <Button type="submit" className="w-full py-3" disabled={processing}>{edit ? 'Update Attribute' : 'Create Attribute'}</Button>
                        </Card>
                    </form>

                    {edit && (
                        <Card className="p-4 md:p-6">
                            <h3 className="mb-1 text-sm font-semibold text-danger">Danger Zone</h3>
                            <p className="mb-4 text-xs text-muted">Deleting an attribute also deletes all of its values.</p>
                            <Button tone="neutral" className="w-full !border-danger !text-danger" onClick={() => confirmDelete(`/admin/attributes/${attribute.id}`, 'Delete this attribute and all its values?')}>
                                <Trash2 size={16} />Delete Attribute
                            </Button>
                        </Card>
                    )}
                </div>

                {edit && <Values attribute={attribute} />}
            </div>
        </AdminLayout>
    );
}
