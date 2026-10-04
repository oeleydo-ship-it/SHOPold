import { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Pencil, Plus, X } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { Badge, Button, Card, CardHeader, Field, Input, Textarea } from '../../../Components/ui';
import { Toggle, confirmDelete } from '../../../Components/crud';

/** Add (category = null) or edit one blog category. */
function CategoryForm({ category, onDone }) {
    const edit = !!category;
    const form = useForm({ name: category?.name ?? '', slug: category?.slug ?? '', description: category?.description ?? '', is_active: category?.is_active ?? true });
    const { data, setData, errors, processing } = form;

    const submit = (e) => {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: () => { form.reset(); onDone?.(); } };
        if (edit) form.put(`/admin/blog/categories/${category.id}`, opts);
        else form.post('/admin/blog/categories', opts);
    };

    return (
        <form onSubmit={submit} className="space-y-3">
            <Field label="Name *" error={errors.name}><Input value={data.name} onChange={(e) => setData('name', e.target.value)} required /></Field>
            <Field label="Slug" hint="Leave empty to generate from the name" error={errors.slug}><Input value={data.slug ?? ''} onChange={(e) => setData('slug', e.target.value)} /></Field>
            <Field label="Description" error={errors.description}><Textarea rows={2} value={data.description ?? ''} onChange={(e) => setData('description', e.target.value)} /></Field>
            <Toggle label="Active" checked={data.is_active} onChange={(v) => setData('is_active', v)} />
            <div className="flex justify-end gap-2">
                {onDone && <Button tone="neutral" onClick={onDone}>Cancel</Button>}
                <Button type="submit" disabled={processing}>{edit ? 'Save Category' : <><Plus size={14} />Add Category</>}</Button>
            </div>
        </form>
    );
}

export default function Categories({ categories }) {
    const [editing, setEditing] = useState(null);

    return (
        <AdminLayout title="Blog Categories">
            <Head title="Blog Categories" />
            <Link href="/admin/blog" className="mb-5 inline-flex items-center gap-2 text-sm text-muted hover:text-ink"><ArrowLeft size={16} />Back to Posts</Link>

            <div className="grid gap-5 lg:grid-cols-3">
                <Card className="self-start">
                    <CardHeader title="Add New Category" />
                    <div className="p-4 md:p-6"><CategoryForm category={null} /></div>
                </Card>

                <Card className="lg:col-span-2">
                    <CardHeader title={`Categories (${categories.length})`} />
                    {categories.length === 0 ? <p className="p-8 text-center text-sm text-muted">No categories yet.</p> : (
                        <ul className="divide-y divide-line">
                            {categories.map((c) => (
                                <li key={c.id} className="p-4 md:px-6">
                                    {editing === c.id ? (
                                        <div className="rounded-lg bg-surface-2 p-4">
                                            <div className="mb-2 flex justify-end"><button type="button" onClick={() => setEditing(null)} aria-label="Close editor" className="text-muted"><X size={16} /></button></div>
                                            <CategoryForm category={c} onDone={() => setEditing(null)} />
                                        </div>
                                    ) : (
                                        <div className="flex items-center justify-between gap-3">
                                            <div className="min-w-0">
                                                <p className="flex items-center gap-2 font-medium">{c.name}<Badge tone={c.is_active ? 'success' : 'muted'}>{c.is_active ? 'Active' : 'Inactive'}</Badge></p>
                                                <p className="text-xs text-muted">/{c.slug} · {c.posts_count} post{c.posts_count === 1 ? '' : 's'}</p>
                                                {c.description && <p className="mt-1 text-sm text-muted">{c.description}</p>}
                                            </div>
                                            <div className="flex shrink-0 gap-3">
                                                <button type="button" onClick={() => setEditing(c.id)} aria-label={`Edit ${c.name}`} className="text-primary"><Pencil size={16} /></button>
                                                <button type="button" onClick={() => confirmDelete(`/admin/blog/categories/${c.id}`, `Delete category "${c.name}"?`)} aria-label={`Delete ${c.name}`} className="text-danger">✕</button>
                                            </div>
                                        </div>
                                    )}
                                </li>
                            ))}
                        </ul>
                    )}
                </Card>
            </div>
        </AdminLayout>
    );
}
