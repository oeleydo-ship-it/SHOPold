import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import RichEditor from '../../../Components/RichEditor';
import MediaPicker from '../../../Components/MediaPicker';
import { Button, Card, CardHeader, Field, Input, Select, Textarea } from '../../../Components/ui';
import { Toggle } from '../../../Components/crud';

const s = (v) => (v === null || v === undefined ? '' : String(v));

export default function Form({ post, categories }) {
    const edit = !!post;
    const form = useForm({
        title: s(post?.title), slug: s(post?.slug), excerpt: s(post?.excerpt), content: s(post?.content),
        category_id: s(post?.category_id), featured_image: s(post?.featured_image), status: post?.status ?? 'draft',
        published_at: s(post?.published_at), meta_title: s(post?.meta_title), meta_description: s(post?.meta_description),
        allow_comments: post?.allow_comments ?? true,
    });
    const { data, setData, errors, processing } = form;
    const f = (k) => ({ value: data[k], onChange: (e) => setData(k, e.target.value) });

    const submit = (e) => {
        e.preventDefault();
        if (edit) form.put(`/admin/blog/${post.id}`);
        else form.post('/admin/blog');
    };

    const title = edit ? `Edit Post: ${post.title}` : 'New Post';
    return (
        <AdminLayout title={edit ? 'Edit Post' : 'New Post'}>
            <Head title={title} />
            <Link href="/admin/blog" className="mb-5 inline-flex items-center gap-2 text-sm text-muted hover:text-ink"><ArrowLeft size={16} />Back to Posts</Link>

            <form onSubmit={submit} className="grid gap-5 lg:grid-cols-3">
                <div className="space-y-5 lg:col-span-2">
                    <Card>
                        <CardHeader title="Post Content" />
                        <div className="space-y-4 p-4 md:p-6">
                            <Field label="Title *" error={errors.title}><Input {...f('title')} required /></Field>
                            <Field label="Slug" hint="Leave empty to generate from the title" error={errors.slug}><Input {...f('slug')} /></Field>
                            <Field label="Excerpt" error={errors.excerpt}><Textarea rows={2} maxLength={500} {...f('excerpt')} /></Field>
                            <Field label="Content *" error={errors.content}>
                                <RichEditor value={data.content} onChange={(v) => setData('content', v)} placeholder="Write your post…" height={420} />
                            </Field>
                        </div>
                    </Card>
                    <Card>
                        <CardHeader title="SEO Settings" />
                        <div className="space-y-4 p-4 md:p-6">
                            <Field label="Meta Title" error={errors.meta_title}><Input {...f('meta_title')} /></Field>
                            <Field label="Meta Description" error={errors.meta_description}><Textarea rows={2} maxLength={500} {...f('meta_description')} /></Field>
                        </div>
                    </Card>
                </div>

                <div className="space-y-5">
                    <Card>
                        <CardHeader title="Publish" />
                        <div className="space-y-4 p-4 md:p-6">
                            <Field label="Status" error={errors.status}>
                                <Select {...f('status')}><option value="draft">Draft</option><option value="published">Published</option><option value="scheduled">Scheduled</option></Select>
                            </Field>
                            <Field label="Publish Date" hint={data.status === 'published' ? 'Leave empty to publish now' : undefined} error={errors.published_at}><Input type="datetime-local" {...f('published_at')} /></Field>
                            <Toggle label="Allow comments" checked={data.allow_comments} onChange={(v) => setData('allow_comments', v)} />
                            <Button type="submit" className="w-full" disabled={processing}>{edit ? 'Update Post' : 'Create Post'}</Button>
                        </div>
                    </Card>
                    <Card>
                        <CardHeader title="Category" />
                        <div className="p-4 md:p-6">
                            <Select {...f('category_id')} aria-label="Category">
                                <option value="">Uncategorized</option>{categories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                            </Select>
                            {errors.category_id && <p className="mt-1 text-xs text-danger">{errors.category_id}</p>}
                        </div>
                    </Card>
                    <Card>
                        <CardHeader title="Featured Image" />
                        <div className="p-4 md:p-6">
                            <MediaPicker value={data.featured_image} onChange={(v) => setData('featured_image', v)} collection="blog" label="Select Featured Image" />
                            {errors.featured_image && <p className="mt-1 text-xs text-danger">{errors.featured_image}</p>}
                        </div>
                    </Card>
                </div>
            </form>
        </AdminLayout>
    );
}
