import { useEffect, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, ImagePlus } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { Button, Card, Field, Input, Textarea } from '../../../Components/ui';
import { Toggle } from '../../../Components/crud';

export default function Form({ brand }) {
    const edit = !!brand;
    const form = useForm({
        name: brand?.name ?? '',
        slug: brand?.slug ?? '',
        description: brand?.description ?? '',
        website: brand?.website ?? '',
        logo: null,
        sort_order: brand?.sort_order ?? 0,
        is_active: brand?.is_active ?? true,
        is_featured: brand?.is_featured ?? false,
    });
    const { data, setData, errors, processing } = form;
    const [preview, setPreview] = useState(brand?.logo_url ?? null);

    // Release the object URL for a newly chosen file when it changes or the page unmounts.
    useEffect(() => {
        if (!data.logo) return undefined;
        const url = URL.createObjectURL(data.logo);
        setPreview(url);
        return () => URL.revokeObjectURL(url);
    }, [data.logo]);

    const submit = (e) => {
        e.preventDefault();
        const opts = { forceFormData: true };
        if (edit) {
            // Multipart PUT is not parsed by PHP, so spoof it through POST.
            form.transform((d) => ({ ...d, _method: 'put' }));
            form.post(`/admin/brands/${brand.id}`, opts);
        } else {
            form.transform((d) => d);
            form.post('/admin/brands', opts);
        }
    };

    const title = edit ? 'Edit Brand' : 'Add Brand';
    return (
        <AdminLayout title={title}>
            <Head title={title} />
            <Link href="/admin/brands" className="mb-5 inline-flex items-center gap-2 text-sm text-muted hover:text-ink"><ArrowLeft size={16} />Back to Brands</Link>
            <form onSubmit={submit} className="max-w-2xl">
                <Card className="space-y-4 p-4 md:p-6">
                    <Field label="Brand Name *" error={errors.name}><Input value={data.name} onChange={(e) => setData('name', e.target.value)} required /></Field>
                    <Field label="Slug" hint="Leave empty to generate from the name" error={errors.slug}><Input value={data.slug ?? ''} onChange={(e) => setData('slug', e.target.value)} /></Field>
                    <Field label="Description" error={errors.description}><Textarea rows={4} value={data.description ?? ''} onChange={(e) => setData('description', e.target.value)} /></Field>
                    <Field label="Website" error={errors.website}><Input type="url" value={data.website ?? ''} onChange={(e) => setData('website', e.target.value)} placeholder="https://example.com" /></Field>

                    <div>
                        <p className="mb-2 text-sm font-medium">Logo</p>
                        <div className="flex items-center gap-4">
                            <div className="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-line bg-surface-2">
                                {preview ? <img src={preview} alt="" className="h-full w-full object-contain" /> : <ImagePlus size={24} className="text-muted" />}
                            </div>
                            <div>
                                <input
                                    id="brand-logo"
                                    type="file"
                                    accept="image/*"
                                    className="hidden"
                                    onChange={(e) => setData('logo', e.target.files[0] ?? null)}
                                />
                                <label htmlFor="brand-logo" className="inline-block cursor-pointer rounded-lg border border-line px-4 py-2 text-sm hover:bg-surface-2">Choose file</label>
                                <p className="mt-1 text-xs text-muted">JPG, PNG, GIF, SVG or WebP. Max 2MB.</p>
                            </div>
                        </div>
                        {errors.logo && <p className="mt-1 text-xs text-danger">{errors.logo}</p>}
                    </div>

                    <Field label="Sort Order" error={errors.sort_order}><Input type="number" min="0" value={data.sort_order ?? 0} onChange={(e) => setData('sort_order', e.target.value)} /></Field>
                    <div className="flex flex-wrap gap-6">
                        <Toggle label="Active" checked={data.is_active} onChange={(v) => setData('is_active', v)} />
                        <Toggle label="Featured" checked={data.is_featured} onChange={(v) => setData('is_featured', v)} />
                    </div>
                    <Button type="submit" className="w-full py-3" disabled={processing}>{edit ? 'Update Brand' : 'Create Brand'}</Button>
                </Card>
            </form>
        </AdminLayout>
    );
}
