import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { Button, Card, Field, Input } from '../../../Components/ui';

export default function Form({ tag }) {
    const edit = !!tag;
    const form = useForm({ name: tag?.name ?? '', slug: tag?.slug ?? '', color: tag?.color ?? '#3b82f6' });
    const { data, setData, errors, processing } = form;
    const submit = (e) => {
        e.preventDefault();
        if (edit) form.put(`/admin/tags/${tag.id}`);
        else form.post('/admin/tags');
    };
    const title = edit ? 'Edit Tag' : 'Add Tag';
    return (
        <AdminLayout title={title}>
            <Head title={title} />
            <Link href="/admin/tags" className="mb-5 inline-flex items-center gap-2 text-sm text-muted hover:text-ink"><ArrowLeft size={16} />Back to Tags</Link>
            <form onSubmit={submit} className="max-w-xl">
                <Card className="space-y-4 p-4 md:p-6">
                    <Field label="Tag Name *" error={errors.name}><Input value={data.name} onChange={(e) => setData('name', e.target.value)} required /></Field>
                    <Field label="Slug" hint="Leave empty to generate from the name" error={errors.slug}>
                        <Input value={data.slug ?? ''} onChange={(e) => setData('slug', e.target.value)} placeholder="Auto-generated from name" />
                    </Field>
                    <Field label="Color" error={errors.color}>
                        <div className="flex gap-3">
                            <input
                                type="color"
                                value={/^#[0-9a-f]{6}$/i.test(data.color) ? data.color : '#3b82f6'}
                                onChange={(e) => setData('color', e.target.value)}
                                className="h-10 w-14 cursor-pointer rounded-lg border border-line bg-surface p-1"
                                aria-label="Pick color"
                            />
                            <Input value={data.color} maxLength={7} onChange={(e) => setData('color', e.target.value)} placeholder="#3b82f6" aria-label="Color hex" />
                        </div>
                    </Field>
                    <Button type="submit" className="w-full py-3" disabled={processing}>{edit ? 'Update Tag' : 'Create Tag'}</Button>
                </Card>
            </form>
        </AdminLayout>
    );
}
