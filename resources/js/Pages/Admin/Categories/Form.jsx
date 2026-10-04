import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import MediaPicker from '../../../Components/MediaPicker';
import { Button, Card, Field, Input, Select, Textarea } from '../../../Components/ui';
import { Toggle } from '../../../Components/crud';

export default function Form({ category, parents }) {
    const edit = !!category;
    const form = useForm({
        name: category?.name ?? '',
        parent_id: category?.parent_id ?? '',
        description: category?.description ?? '',
        image: category?.image ?? '',
        sort_order: category?.sort_order ?? 0,
        is_active: category?.is_active ?? true,
    });
    const { data, setData, errors, processing } = form;

    const submit = (e) => {
        e.preventDefault();
        if (edit) form.put(`/admin/categories/${category.id}`);
        else form.post('/admin/categories');
    };

    const title = edit ? 'Edit Category' : 'Add Category';
    return (
        <AdminLayout title={title}>
            <Head title={title} />
            <Link href="/admin/categories" className="mb-5 inline-flex items-center gap-2 text-sm text-muted hover:text-ink"><ArrowLeft size={16} />Back to Categories</Link>
            <form onSubmit={submit} className="max-w-2xl">
                <Card className="space-y-4 p-4 md:p-6">
                    <Field label="Category Name *" error={errors.name}><Input value={data.name} onChange={(e) => setData('name', e.target.value)} required /></Field>
                    <Field label="Parent Category" error={errors.parent_id}>
                        <Select value={data.parent_id ?? ''} onChange={(e) => setData('parent_id', e.target.value)}>
                            <option value="">None (Top Level)</option>
                            {parents.map((p) => <option key={p.id} value={p.id}>{p.name}</option>)}
                        </Select>
                    </Field>
                    <Field label="Description" error={errors.description}><Textarea rows={3} value={data.description ?? ''} onChange={(e) => setData('description', e.target.value)} /></Field>
                    <div>
                        <p className="mb-2 text-sm font-medium">Category Image</p>
                        <div className="max-w-xs"><MediaPicker value={data.image} onChange={(v) => setData('image', v)} collection="categories" label="Select Image from Media Library" /></div>
                        {errors.image && <p className="mt-1 text-xs text-danger">{errors.image}</p>}
                    </div>
                    <Field label="Sort Order" error={errors.sort_order}><Input type="number" value={data.sort_order ?? 0} onChange={(e) => setData('sort_order', e.target.value)} /></Field>
                    <Toggle label="Active" checked={data.is_active} onChange={(v) => setData('is_active', v)} />
                    <Button type="submit" className="w-full py-3" disabled={processing}>{edit ? 'Update Category' : 'Create Category'}</Button>
                </Card>
            </form>
        </AdminLayout>
    );
}
