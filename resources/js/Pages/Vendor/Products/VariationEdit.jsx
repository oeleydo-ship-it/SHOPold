import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import VendorLayout from '../../../Layouts/VendorLayout';
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
        values: Object.fromEntries(Object.entries(variation.values ?? {}).map(([k, v]) => [k, s(v)])),
    });

    const submit = (e) => {
        e.preventDefault();
        put(`/vendor/products/${product.id}/variations/${variation.id}`, {
            preserveScroll: true,
            // The controller expects attribute_values as attribute id => [value id]
            transform: (d) => {
                const { values, ...rest } = d;
                return { ...rest, attribute_values: Object.fromEntries(Object.entries(values).filter(([, v]) => v).map(([k, v]) => [k, [v]])) };
            },
        });
    };

    const back = `/vendor/products/${product.id}/edit`;

    return (
        <VendorLayout title="Edit variation" actions={<Link href={back} className="inline-flex items-center gap-1 text-sm text-muted hover:text-ink"><ArrowLeft size={16} />Back to product</Link>}>
            <Head title={`Variation ${variation.name ?? variation.sku}`} />
            <p className="mb-4 text-sm text-muted">{product.name}{variation.name ? ` · ${variation.name}` : ''}</p>

            <form onSubmit={submit} className="grid max-w-4xl gap-5 lg:grid-cols-2">
                <Card>
                    <CardHeader title="Options" />
                    <div className="space-y-4 p-4">
                        {attributes.length === 0 && <p className="text-sm text-muted">No attributes are set up in the store.</p>}
                        {attributes.map((a) => (
                            <Field key={a.id} label={a.name} error={errors[`attribute_values.${a.id}`]}>
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
                    <div className="space-y-4 p-4">
                        <Field label="SKU *" error={errors.sku}><Input required value={data.sku} onChange={(e) => setData('sku', e.target.value)} /></Field>
                        <div className="grid grid-cols-2 gap-3">
                            <Field label="Price" error={errors.price}><Input type="number" step="0.01" min="0" placeholder="Product price" value={data.price} onChange={(e) => setData('price', e.target.value)} /></Field>
                            <Field label="Sale price" error={errors.sale_price}><Input type="number" step="0.01" min="0" value={data.sale_price} onChange={(e) => setData('sale_price', e.target.value)} /></Field>
                            <Field label="Stock" error={errors.quantity}><Input type="number" min="0" value={data.quantity} onChange={(e) => setData('quantity', e.target.value)} /></Field>
                            <Field label="Weight (kg)" error={errors.weight}><Input type="number" step="0.01" min="0" value={data.weight} onChange={(e) => setData('weight', e.target.value)} /></Field>
                        </div>
                        <Toggle label="Available for sale" checked={data.is_active} onChange={(v) => setData('is_active', v)} />
                    </div>
                </Card>

                <div className="flex gap-2 lg:col-span-2">
                    <Button type="submit" disabled={processing}>{processing ? 'Saving…' : 'Save variation'}</Button>
                    <Link href={back} className="px-4 py-2 text-sm text-muted hover:text-ink">Cancel</Link>
                </div>
            </form>
        </VendorLayout>
    );
}
