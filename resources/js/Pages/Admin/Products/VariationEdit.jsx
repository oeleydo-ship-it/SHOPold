import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import MediaPicker from '../../../Components/MediaPicker';
import { Button, Card, CardHeader, Field, Input, Select } from '../../../Components/ui';
import { Toggle } from '../../../Components/crud';

const s = (v) => (v === null || v === undefined ? '' : String(v));

export default function VariationEdit({ product, variation, attributes }) {
    const { data, setData, put, processing, errors } = useForm({
        sku: s(variation.sku),
        price: s(variation.price),
        sale_price: s(variation.sale_price),
        quantity: s(variation.quantity),
        weight: s(variation.weight),
        is_active: variation.is_active,
        image: variation.image ?? '',
        remove_image: false,
        values: Object.fromEntries(Object.entries(variation.values ?? {}).map(([k, v]) => [k, s(v)])),
    });

    const submit = (e) => {
        e.preventDefault();
        put(`/admin/products/${product.id}/variations/${variation.id}`, {
            preserveScroll: true,
            // The controller expects attribute_values as attribute id => [value id]
            transform: (d) => {
                const { values, ...rest } = d;
                return { ...rest, attribute_values: Object.fromEntries(Object.entries(values).filter(([, v]) => v).map(([k, v]) => [k, [v]])) };
            },
        });
    };

    const back = `/admin/products/${product.id}/edit`;
    const pickImage = (path) => { setData((d) => ({ ...d, image: path ?? '', remove_image: !path })); };

    return (
        <AdminLayout title="Edit Variation">
            <Head title={`Variation ${variation.name ?? variation.sku}`} />
            <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <Link href={back} className="inline-flex items-center gap-1 text-sm text-muted hover:text-ink"><ArrowLeft size={16} />Back to {product.name}</Link>
                    {variation.name && <p className="mt-1 text-lg font-semibold">{variation.name}</p>}
                </div>
            </div>

            <form onSubmit={submit} className="grid max-w-5xl gap-5 lg:grid-cols-3">
                <div className="space-y-5 lg:col-span-2">
                    <Card>
                        <CardHeader title="Options" />
                        <div className="grid gap-4 p-4 sm:grid-cols-2 md:p-6">
                            {attributes.length === 0 && <p className="text-sm text-muted">No attributes yet. Add them under Catalog &gt; Attributes.</p>}
                            {attributes.map((a) => (
                                <Field key={a.id} label={a.name}>
                                    <Select value={data.values[a.id] ?? ''} onChange={(e) => setData('values', { ...data.values, [a.id]: e.target.value })}>
                                        <option value="">Not used</option>
                                        {a.values.map((v) => <option key={v.id} value={v.id}>{v.value}</option>)}
                                    </Select>
                                </Field>
                            ))}
                        </div>
                    </Card>

                    <Card>
                        <CardHeader title="Price and stock" />
                        <div className="grid gap-4 p-4 sm:grid-cols-2 md:p-6">
                            <Field label="SKU *" error={errors.sku}><Input required value={data.sku} onChange={(e) => setData('sku', e.target.value)} /></Field>
                            <Field label="Stock" error={errors.quantity}><Input type="number" min="0" value={data.quantity} onChange={(e) => setData('quantity', e.target.value)} /></Field>
                            <Field label="Price" hint="Empty = product price" error={errors.price}><Input type="number" step="0.01" min="0" value={data.price} onChange={(e) => setData('price', e.target.value)} /></Field>
                            <Field label="Sale price" error={errors.sale_price}><Input type="number" step="0.01" min="0" value={data.sale_price} onChange={(e) => setData('sale_price', e.target.value)} /></Field>
                            <Field label="Weight (kg)" error={errors.weight}><Input type="number" step="0.01" min="0" value={data.weight} onChange={(e) => setData('weight', e.target.value)} /></Field>
                            <div className="flex items-end pb-2"><Toggle label="Available for sale" checked={data.is_active} onChange={(v) => setData('is_active', v)} /></div>
                        </div>
                    </Card>
                </div>

                <div className="space-y-5">
                    <Card>
                        <CardHeader title="Image" />
                        <div className="space-y-3 p-4 md:p-6">
                            {!data.image && (
                                <div className="flex items-center gap-3 text-xs text-muted">
                                    {product.image && <img src={product.image} alt="" className="h-12 w-12 rounded-lg object-cover opacity-60" />}
                                    No own image: the product image is shown.
                                </div>
                            )}
                            <MediaPicker value={data.image || null} onChange={pickImage} label={data.image ? 'Change image' : 'Select image'} />
                            {errors.image && <p className="text-xs text-danger">{errors.image}</p>}
                        </div>
                    </Card>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={processing}>{processing ? 'Saving…' : 'Save variation'}</Button>
                        <Link href={back} className="px-4 py-2 text-sm text-muted hover:text-ink">Cancel</Link>
                    </div>
                </div>
            </form>
        </AdminLayout>
    );
}
