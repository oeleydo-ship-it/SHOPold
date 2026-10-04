import { useEffect, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, ExternalLink, ImagePlus } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import RichEditor from '../../../Components/RichEditor';
import { Button, Card, CardHeader, Field, Input, LinkButton, Select, Textarea } from '../../../Components/ui';
import { Toggle } from '../../../Components/crud';

const s = (v) => (v === null || v === undefined ? '' : String(v));

export default function Form({ page, templates, parents }) {
    const edit = !!page;
    const form = useForm({
        title: s(page?.title), slug: s(page?.slug), content: s(page?.content), excerpt: s(page?.excerpt),
        meta_title: s(page?.meta_title), meta_description: s(page?.meta_description), meta_keywords: s(page?.meta_keywords),
        template: page?.template ?? 'default', is_active: page?.is_active ?? true, show_in_menu: page?.show_in_menu ?? false,
        menu_order: s(page?.menu_order ?? 0), parent_id: s(page?.parent_id), featured_image: null,
    });
    const { data, setData, errors, processing } = form;
    const f = (k) => ({ value: data[k], onChange: (e) => setData(k, e.target.value) });
    const [preview, setPreview] = useState(page?.image ?? null);

    // release the object URL of a newly chosen file when it changes or the page unmounts
    useEffect(() => {
        if (!data.featured_image) return undefined;
        const url = URL.createObjectURL(data.featured_image);
        setPreview(url);
        return () => URL.revokeObjectURL(url);
    }, [data.featured_image]);

    const submit = (e) => {
        e.preventDefault();
        const opts = { forceFormData: true };
        if (edit) {
            // multipart PUT is not parsed by PHP, so it is spoofed through POST
            form.transform((d) => ({ ...d, _method: 'put' }));
            form.post(`/admin/pages/${page.id}`, opts);
        } else {
            form.transform((d) => d);
            form.post('/admin/pages', opts);
        }
    };

    const title = edit ? `Edit Page: ${page.title}` : 'Add Page';
    return (
        <AdminLayout title={edit ? 'Edit Page' : 'Add Page'}>
            <Head title={title} />
            <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
                <Link href="/admin/pages" className="inline-flex items-center gap-2 text-sm text-muted hover:text-ink"><ArrowLeft size={16} />Back to Pages</Link>
                {edit && <LinkButton external tone="neutral" href={page.url} target="_blank" rel="noreferrer"><ExternalLink size={16} />View Page</LinkButton>}
            </div>

            <form onSubmit={submit} className="grid gap-5 lg:grid-cols-3">
                <div className="space-y-5 lg:col-span-2">
                    <Card className="space-y-4 p-4 md:p-6">
                        <Field label="Page Title *" error={errors.title}><Input {...f('title')} required /></Field>
                        <Field label="Slug" hint="Leave empty to generate from the title" error={errors.slug}>
                            <div className="flex items-center gap-2"><span className="text-muted">/</span><Input {...f('slug')} placeholder="page-url" /></div>
                        </Field>
                        <Field label="Excerpt" error={errors.excerpt}><Textarea rows={2} maxLength={500} {...f('excerpt')} /></Field>
                        <Field label="Content" error={errors.content}>
                            <RichEditor value={data.content} onChange={(v) => setData('content', v)} placeholder="Write your page content…" height={360} />
                        </Field>
                    </Card>

                    <Card>
                        <CardHeader title="SEO Settings" />
                        <div className="space-y-4 p-4 md:p-6">
                            <Field label="Meta Title" error={errors.meta_title}><Input {...f('meta_title')} /></Field>
                            <Field label="Meta Description" error={errors.meta_description}><Textarea rows={2} maxLength={500} {...f('meta_description')} /></Field>
                            <Field label="Meta Keywords" error={errors.meta_keywords}><Input {...f('meta_keywords')} /></Field>
                        </div>
                    </Card>
                </div>

                <div className="space-y-5">
                    <Card>
                        <CardHeader title="Publish" />
                        <div className="space-y-4 p-4 md:p-6">
                            <Toggle label="Active" checked={data.is_active} onChange={(v) => setData('is_active', v)} />
                            <Toggle label="Show in Menu" checked={data.show_in_menu} onChange={(v) => setData('show_in_menu', v)} />
                            <Field label="Menu Order" error={errors.menu_order}><Input type="number" {...f('menu_order')} /></Field>
                            <Button type="submit" className="w-full" disabled={processing}>{edit ? 'Update Page' : 'Create Page'}</Button>
                        </div>
                    </Card>

                    <Card>
                        <CardHeader title="Template" />
                        <div className="p-4 md:p-6">
                            <Select {...f('template')} aria-label="Template">{Object.entries(templates).map(([k, l]) => <option key={k} value={k}>{l}</option>)}</Select>
                            {errors.template && <p className="mt-1 text-xs text-danger">{errors.template}</p>}
                        </div>
                    </Card>

                    <Card>
                        <CardHeader title="Parent Page" />
                        <div className="p-4 md:p-6">
                            <Select {...f('parent_id')} aria-label="Parent page">
                                <option value="">None (Top Level)</option>
                                {parents.map((p) => <option key={p.id} value={p.id}>{p.title}</option>)}
                            </Select>
                        </div>
                    </Card>

                    <Card>
                        <CardHeader title="Featured Image" />
                        <div className="space-y-3 p-4 md:p-6">
                            <div className="flex aspect-video items-center justify-center overflow-hidden rounded-lg border border-line bg-surface-2">
                                {preview ? <img src={preview} alt="" className="h-full w-full object-cover" /> : <ImagePlus size={28} className="text-muted" />}
                            </div>
                            <input id="page-image" type="file" accept="image/*" className="hidden" onChange={(e) => setData('featured_image', e.target.files[0] ?? null)} />
                            <label htmlFor="page-image" className="block cursor-pointer rounded-lg border border-line px-4 py-2 text-center text-sm hover:bg-surface-2">Choose image</label>
                            <p className="text-xs text-muted">Recommended: 1200×630px, max 2MB</p>
                            {errors.featured_image && <p className="text-xs text-danger">{errors.featured_image}</p>}
                        </div>
                    </Card>
                </div>
            </form>
        </AdminLayout>
    );
}
