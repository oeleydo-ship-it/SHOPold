import { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Download, FileText, ImagePlus, Link2, Package, Pencil, Plus, Trash2, Upload, X } from 'lucide-react';
import VendorLayout from '../../../Layouts/VendorLayout';
import RichEditor from '../../../Components/RichEditor';
import { Badge, Button, Card, CardHeader, Field, Input, Select, Textarea } from '../../../Components/ui';
import { Toggle, confirmDelete } from '../../../Components/crud';

const s = (v) => (v === null || v === undefined ? '' : String(v));
const TYPES = [
    ['simple', 'Simple', 'One item with a single price and stock'],
    ['variable', 'Variable', 'Options like size or colour, each with its own price and stock'],
    ['digital', 'Digital', 'A downloadable file, no shipping or stock'],
];

function Section({ title, children, action }) {
    return <Card><CardHeader title={title}>{action}</CardHeader><div className="space-y-4 p-4 md:p-6">{children}</div></Card>;
}

/** Variations of an existing variable product: list, quick add, edit link, delete. */
function Variations({ product }) {
    const add = useForm({ sku: '', price: '', sale_price: '', quantity: '' });
    const submit = (e) => {
        e.preventDefault();
        add.post(`/vendor/products/${product.id}/variations`, { preserveScroll: true, onSuccess: () => add.reset() });
    };

    return (
        <Section title={`Variations (${product.variations.length})`}>
            {product.variations.length === 0 ? <p className="text-sm text-muted">No variations yet. Add one below, then set its options (size, colour…) on its edit page.</p> : (
                <ul className="divide-y divide-line rounded-lg border border-line">
                    {product.variations.map((v) => (
                        <li key={v.id} className="flex flex-wrap items-center justify-between gap-3 p-3">
                            <div className="min-w-0">
                                <p className="truncate text-sm font-medium">{v.name || 'Options not set'}</p>
                                <p className="text-xs text-muted">SKU {v.sku} · {v.price ?? 'product price'} · stock {v.quantity}{!v.is_active && ' · hidden'}</p>
                            </div>
                            <div className="flex gap-3">
                                <Link href={`/vendor/products/${product.id}/variations/${v.id}/edit`} aria-label={`Edit variation ${v.sku}`} className="text-primary"><Pencil size={16} /></Link>
                                <button type="button" aria-label={`Delete variation ${v.sku}`} onClick={() => confirmDelete(`/vendor/products/${product.id}/variations/${v.id}`, `Delete variation ${v.sku}?`)} className="text-danger"><Trash2 size={16} /></button>
                            </div>
                        </li>
                    ))}
                </ul>
            )}
            <form onSubmit={submit} className="grid gap-3 rounded-lg bg-surface-2 p-3 sm:grid-cols-5">
                <Field label="SKU *" error={add.errors.sku}><Input required value={add.data.sku} onChange={(e) => add.setData('sku', e.target.value)} /></Field>
                <Field label="Price" error={add.errors.price}><Input type="number" step="0.01" min="0" value={add.data.price} onChange={(e) => add.setData('price', e.target.value)} /></Field>
                <Field label="Sale price" error={add.errors.sale_price}><Input type="number" step="0.01" min="0" value={add.data.sale_price} onChange={(e) => add.setData('sale_price', e.target.value)} /></Field>
                <Field label="Stock" error={add.errors.quantity}><Input type="number" min="0" value={add.data.quantity} onChange={(e) => add.setData('quantity', e.target.value)} /></Field>
                <div className="flex items-end"><Button type="submit" className="w-full" disabled={add.processing}><Plus size={16} />Add</Button></div>
            </form>
        </Section>
    );
}

export default function Form({ product, categories, brands, attributes, approvalRequired, maxUploadMb }) {
    const edit = !!product;
    const form = useForm({
        product_type: product?.product_type ?? 'simple',
        name: s(product?.name), description: s(product?.description), short_description: s(product?.short_description),
        category_id: s(product?.category_id), brand_id: s(product?.brand_id),
        price: s(product?.price), sale_price: s(product?.sale_price), sale_start_date: s(product?.sale_start_date), sale_end_date: s(product?.sale_end_date),
        cost_price: s(product?.cost_price), sku: s(product?.sku), barcode: s(product?.barcode),
        quantity: s(product?.quantity ?? 0), low_stock_threshold: s(product?.low_stock_threshold ?? 5), manage_stock: product?.manage_stock ?? true,
        weight: s(product?.weight), dimensions: s(product?.dimensions),
        meta_title: s(product?.meta_title), meta_description: s(product?.meta_description), meta_keywords: s(product?.meta_keywords),
        features: product?.features?.length ? product.features : [],
        attribute_values: product?.attribute_values ?? {},
        images: [], delete_images: [],
        new_digital_files: [], delete_digital_files: [],
    });
    const { data, setData, errors, processing } = form;
    const f = (k) => ({ value: data[k], onChange: (e) => setData(k, e.target.value) });
    const [previews, setPreviews] = useState([]);
    const digital = data.product_type === 'digital';
    const variable = data.product_type === 'variable';

    const pickImages = (files) => {
        const list = [...files];
        setData('images', [...data.images, ...list]);
        setPreviews((p) => [...p, ...list.map((file) => ({ name: file.name, url: URL.createObjectURL(file) }))]);
    };
    const dropNewImage = (i) => { setData('images', data.images.filter((_, j) => j !== i)); setPreviews((p) => p.filter((_, j) => j !== i)); };
    const toggleIn = (key, id) => setData(key, data[key].includes(id) ? data[key].filter((x) => x !== id) : [...data[key], id]);
    const toggleValue = (attrId, valueId) => {
        const current = data.attribute_values[attrId] ?? [];
        setData('attribute_values', { ...data.attribute_values, [attrId]: current.includes(valueId) ? current.filter((v) => v !== valueId) : [...current, valueId] });
    };
    const setFile = (i, k, v) => setData('new_digital_files', data.new_digital_files.map((x, j) => (j === i ? { ...x, [k]: v } : x)));

    const submit = (e) => {
        e.preventDefault();
        const opts = { forceFormData: true, preserveScroll: true };
        if (edit) form.transform((d) => ({ ...d, _method: 'put' })).post(`/vendor/products/${product.id}`, opts);
        else form.post('/vendor/products', opts);
    };

    const title = edit ? `Edit: ${product.name}` : 'Add product';
    return (
        <VendorLayout title={edit ? 'Edit product' : 'Add product'}>
            <Head title={title} />
            <Link href="/vendor/products" className="mb-5 inline-flex items-center gap-2 text-sm text-muted hover:text-ink"><ArrowLeft size={16} />Back to products</Link>
            {approvalRequired && !edit && <p className="mb-4 rounded-lg bg-info/10 px-4 py-2 text-sm">New products are reviewed by the store before they go live.</p>}

            <form onSubmit={submit} className="grid gap-5 lg:grid-cols-3">
                <div className="space-y-5 lg:col-span-2">
                    <Section title="Product type">
                        <div className="grid gap-3 sm:grid-cols-3" role="radiogroup" aria-label="Product type">
                            {TYPES.map(([value, label, hint]) => (
                                <label key={value} className={`cursor-pointer rounded-lg border p-3 ${data.product_type === value ? 'border-primary bg-primary/5 ring-2 ring-primary/20' : 'border-line'}`}>
                                    <input type="radio" name="product_type" value={value} checked={data.product_type === value} onChange={() => setData('product_type', value)} className="sr-only" />
                                    <span className="block font-medium">{label}</span><span className="block text-xs text-muted">{hint}</span>
                                </label>
                            ))}
                        </div>
                        {errors.product_type && <p className="text-xs text-danger">{errors.product_type}</p>}
                    </Section>

                    <Section title="Basic information">
                        <Field label="Name *" error={errors.name}><Input required maxLength={255} {...f('name')} /></Field>
                        <Field label="Short description" error={errors.short_description}><Textarea rows={2} maxLength={500} {...f('short_description')} /></Field>
                        <Field label="Description *" error={errors.description}><RichEditor value={data.description} onChange={(v) => setData('description', v)} placeholder="Describe your product…" height={260} /></Field>
                    </Section>

                    <Section title="Pricing">
                        <div className="grid gap-4 sm:grid-cols-3">
                            <Field label="Price *" error={errors.price}><Input type="number" step="0.01" min="0" required {...f('price')} /></Field>
                            <Field label="Sale price" error={errors.sale_price}><Input type="number" step="0.01" min="0" {...f('sale_price')} /></Field>
                            <Field label="Cost price" hint="Only you see this" error={errors.cost_price}><Input type="number" step="0.01" min="0" {...f('cost_price')} /></Field>
                            <Field label="Sale starts" error={errors.sale_start_date}><Input type="date" {...f('sale_start_date')} /></Field>
                            <Field label="Sale ends" error={errors.sale_end_date}><Input type="date" {...f('sale_end_date')} /></Field>
                        </div>
                    </Section>

                    <Section title="Inventory">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field label={edit ? 'SKU *' : 'SKU'} hint={edit ? undefined : 'Leave empty to generate one'} error={errors.sku}><Input required={edit} {...f('sku')} /></Field>
                            <Field label="Barcode" error={errors.barcode}><Input {...f('barcode')} /></Field>
                            {!digital && <>
                                <Field label="Stock quantity *" error={errors.quantity}><Input type="number" min="0" required {...f('quantity')} /></Field>
                                <Field label="Low-stock alert at" error={errors.low_stock_threshold}><Input type="number" min="0" {...f('low_stock_threshold')} /></Field>
                                <Field label="Weight (kg)" error={errors.weight}><Input type="number" step="0.01" min="0" {...f('weight')} /></Field>
                                <Field label="Dimensions" hint="e.g. 20 × 10 × 5 cm" error={errors.dimensions}><Input maxLength={100} {...f('dimensions')} /></Field>
                            </>}
                        </div>
                        {!digital && <Toggle label="Track stock for this product" checked={data.manage_stock} onChange={(v) => setData('manage_stock', v)} />}
                        {digital && <p className="text-sm text-muted">Digital products don't track stock. They're available whenever they're active.</p>}
                    </Section>

                    {variable && (
                        <Section title="Options for variations">
                            {attributes.length === 0 ? <p className="text-sm text-muted">No options are set up yet. Ask the store administrator to add attributes such as Size or Colour.</p> : attributes.map((a) => (
                                <fieldset key={a.id}>
                                    <legend className="mb-2 text-sm font-medium">{a.name}</legend>
                                    <div className="flex flex-wrap gap-2">
                                        {a.values.map((v) => {
                                            const on = (data.attribute_values[a.id] ?? []).includes(v.id);
                                            return (
                                                <label key={v.id} className={`inline-flex cursor-pointer items-center gap-2 rounded-full border px-3 py-1.5 text-sm ${on ? 'border-primary bg-primary/10' : 'border-line'}`}>
                                                    <input type="checkbox" className="sr-only" checked={on} onChange={() => toggleValue(a.id, v.id)} />
                                                    {v.color && <span className="h-3 w-3 rounded-full border border-line" style={{ background: v.color }} />}{v.value}
                                                </label>
                                            );
                                        })}
                                    </div>
                                </fieldset>
                            ))}
                        </Section>
                    )}
                    {variable && edit && <Variations product={product} />}
                    {variable && !edit && <p className="rounded-lg bg-surface-2 px-4 py-3 text-sm text-muted">Save the product first, then add its variations.</p>}

                    <Section title="Images" action={<span className="text-xs text-muted">JPG, PNG or WebP, up to 2 MB each</span>}>
                        <div className="grid grid-cols-3 gap-3 sm:grid-cols-5">
                            {(product?.images ?? []).map((img) => {
                                const removing = data.delete_images.includes(img.id);
                                return (
                                    <div key={img.id} className={`relative aspect-square overflow-hidden rounded-lg border ${removing ? 'border-danger opacity-40' : 'border-line'}`}>
                                        <img src={img.url} alt="" className="h-full w-full object-cover" />
                                        {img.primary && <span className="absolute left-1 top-1"><Badge tone="primary">Main</Badge></span>}
                                        <button type="button" onClick={() => toggleIn('delete_images', img.id)} aria-label={removing ? 'Keep image' : 'Remove image'}
                                            className="absolute right-1 top-1 rounded-full bg-white/90 p-1 text-danger shadow">{removing ? <Plus size={14} /> : <X size={14} />}</button>
                                    </div>
                                );
                            })}
                            {previews.map((p, i) => (
                                <div key={p.url} className="relative aspect-square overflow-hidden rounded-lg border border-primary">
                                    <img src={p.url} alt={p.name} className="h-full w-full object-cover" />
                                    <span className="absolute left-1 top-1"><Badge tone="info">New</Badge></span>
                                    <button type="button" onClick={() => dropNewImage(i)} aria-label={`Remove ${p.name}`} className="absolute right-1 top-1 rounded-full bg-white/90 p-1 text-danger shadow"><X size={14} /></button>
                                </div>
                            ))}
                            <label className="flex aspect-square cursor-pointer flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed border-line text-muted hover:border-primary hover:text-primary">
                                <ImagePlus size={22} /><span className="text-xs">Add images</span>
                                <input type="file" multiple accept="image/jpeg,image/png,image/webp,image/gif" className="sr-only" onChange={(e) => { pickImages(e.target.files); e.target.value = ''; }} />
                            </label>
                        </div>
                        {Object.entries(errors).filter(([k]) => k.startsWith('images')).map(([k, m]) => <p key={k} className="text-xs text-danger">{m}</p>)}
                    </Section>

                    <Section title="Features" action={<Button tone="neutral" onClick={() => setData('features', [...data.features, { name: '', value: '' }])}><Plus size={14} />Add</Button>}>
                        {data.features.length === 0 && <p className="text-sm text-muted">Key facts shown in the product's Features tab, e.g. Material: Cotton.</p>}
                        {data.features.map((ft, i) => (
                            <div key={i} className="flex gap-2">
                                <Input aria-label="Feature name" placeholder="Name" value={ft.name} onChange={(e) => setData('features', data.features.map((x, j) => (j === i ? { ...x, name: e.target.value } : x)))} />
                                <Input aria-label="Feature value" placeholder="Value" value={ft.value} onChange={(e) => setData('features', data.features.map((x, j) => (j === i ? { ...x, value: e.target.value } : x)))} />
                                <button type="button" aria-label="Remove feature" onClick={() => setData('features', data.features.filter((_, j) => j !== i))} className="text-danger"><Trash2 size={16} /></button>
                            </div>
                        ))}
                    </Section>

                    {(digital || (product?.digital_files?.length ?? 0) > 0) && (
                        <Section title="Downloadable files" action={<Button tone="neutral" onClick={() => setData('new_digital_files', [...data.new_digital_files, { name: '', file_type: 'upload', file: null, file_url: '', download_limit: '', download_expiry_days: '' }])}><Plus size={14} />Add file</Button>}>
                            {(product?.digital_files ?? []).map((df) => {
                                const removing = data.delete_digital_files.includes(df.id);
                                return (
                                    <div key={df.id} className={`flex flex-wrap items-center justify-between gap-3 rounded-lg border p-3 ${removing ? 'border-danger opacity-50' : 'border-line'}`}>
                                        <div className="flex min-w-0 items-center gap-3">
                                            {df.file_type === 'url' ? <Link2 size={18} className="text-muted" /> : <FileText size={18} className="text-muted" />}
                                            <div className="min-w-0"><p className="truncate text-sm font-medium">{df.name}</p><p className="truncate text-xs text-muted">{df.file_name ?? df.file_url}{df.size && ` · ${df.size}`} · {df.download_limit ? `${df.download_limit} downloads` : 'unlimited downloads'}{df.download_expiry_days ? ` · expires after ${df.download_expiry_days} days` : ''}</p></div>
                                        </div>
                                        <button type="button" onClick={() => toggleIn('delete_digital_files', df.id)} className="text-sm text-danger hover:underline">{removing ? 'Keep' : 'Remove'}</button>
                                    </div>
                                );
                            })}
                            {data.new_digital_files.map((nf, i) => (
                                <div key={i} className="space-y-3 rounded-lg bg-surface-2 p-3">
                                    <div className="flex items-center justify-between"><Badge tone="info">New file</Badge><button type="button" aria-label="Remove new file" onClick={() => setData('new_digital_files', data.new_digital_files.filter((_, j) => j !== i))} className="text-danger"><X size={16} /></button></div>
                                    <div className="grid gap-3 sm:grid-cols-2">
                                        <Field label="Name *" error={errors[`new_digital_files.${i}.name`]}><Input value={nf.name} onChange={(e) => setFile(i, 'name', e.target.value)} /></Field>
                                        <Field label="Source"><Select value={nf.file_type} onChange={(e) => setFile(i, 'file_type', e.target.value)}><option value="upload">Upload a file</option><option value="url">Link to a file</option></Select></Field>
                                        {nf.file_type === 'upload'
                                            ? <Field label={`File (max ${maxUploadMb} MB)`} error={errors[`new_digital_files.${i}.file`]}><input type="file" onChange={(e) => setFile(i, 'file', e.target.files[0] ?? null)} className="block w-full text-sm" /></Field>
                                            : <Field label="File URL" error={errors[`new_digital_files.${i}.file_url`]}><Input type="url" placeholder="https://…" value={nf.file_url} onChange={(e) => setFile(i, 'file_url', e.target.value)} /></Field>}
                                        <div className="grid grid-cols-2 gap-2">
                                            <Field label="Download limit"><Input type="number" min="0" placeholder="Unlimited" value={nf.download_limit} onChange={(e) => setFile(i, 'download_limit', e.target.value)} /></Field>
                                            <Field label="Expires (days)"><Input type="number" min="0" placeholder="Never" value={nf.download_expiry_days} onChange={(e) => setFile(i, 'download_expiry_days', e.target.value)} /></Field>
                                        </div>
                                    </div>
                                </div>
                            ))}
                            {digital && (product?.digital_files?.length ?? 0) === 0 && data.new_digital_files.length === 0 && <p className="flex items-center gap-2 text-sm text-muted"><Upload size={16} />Add the file customers receive after buying.</p>}
                        </Section>
                    )}

                    <Section title="Search engine listing">
                        <Field label="Meta title" hint="Leave empty to use the product name" error={errors.meta_title}><Input maxLength={255} {...f('meta_title')} /></Field>
                        <Field label="Meta description" error={errors.meta_description}><Textarea rows={2} maxLength={500} {...f('meta_description')} /></Field>
                        <Field label="Meta keywords" error={errors.meta_keywords}><Input maxLength={255} {...f('meta_keywords')} /></Field>
                    </Section>
                </div>

                <div className="space-y-5">
                    <Card className="lg:sticky lg:top-20">
                        <div className="space-y-4 p-4 md:p-6">
                            {edit && (
                                <div className="flex items-center justify-between text-sm">
                                    <span className="text-muted">Status</span>
                                    <Badge tone={product.is_active ? 'success' : 'warning'}>{product.is_active ? 'Active' : (approvalRequired ? 'Awaiting approval' : 'Hidden')}</Badge>
                                </div>
                            )}
                            <Field label="Category *" error={errors.category_id}>
                                <Select required {...f('category_id')}>
                                    <option value="">Choose a category</option>
                                    {categories.map((c) => <option key={c.id} value={c.id}>{c.depth ? '— ' : ''}{c.name}</option>)}
                                </Select>
                            </Field>
                            <Field label="Brand" error={errors.brand_id}>
                                <Select {...f('brand_id')}><option value="">No brand</option>{brands.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}</Select>
                            </Field>
                            <Button type="submit" className="w-full" disabled={processing}>{processing ? 'Saving…' : edit ? 'Save changes' : 'Create product'}</Button>
                            {edit && product.url && <a href={product.url} target="_blank" rel="noreferrer" className="flex items-center justify-center gap-2 text-sm text-primary hover:underline"><Download size={14} className="rotate-[-90deg]" />View in store</a>}
                        </div>
                    </Card>
                    {edit && (
                        <Card className="border-danger/30">
                            <CardHeader title="Danger zone" />
                            <div className="p-4 md:p-6">
                                <Button tone="danger" className="w-full" onClick={() => confirmDelete(`/vendor/products/${product.id}`, `Delete "${product.name}"? This cannot be undone.`)}><Trash2 size={16} />Delete product</Button>
                            </div>
                        </Card>
                    )}
                </div>
            </form>
            {!edit && !categories.length && <p className="mt-4 text-sm text-danger"><Package size={14} className="mr-1 inline" />No categories exist yet; ask the store administrator to add some.</p>}
        </VendorLayout>
    );
}
