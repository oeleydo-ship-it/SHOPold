import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Check, ExternalLink, Pencil, RotateCcw, Trash2, X } from 'lucide-react';
import DOMPurify from 'dompurify';
import AdminLayout from '../../../Layouts/AdminLayout';
import { Badge, Card, CardHeader, LinkButton } from '../../../Components/ui';
import { confirmDelete } from '../../../Components/crud';

const STATUS_TONE = { published: 'success', draft: 'muted', scheduled: 'info' };
const COMMENT_TONE = { approved: 'success', pending: 'warning', spam: 'danger' };
const moderate = (id, status) => router.patch(`/admin/blog/comments/${id}`, { status }, { preserveScroll: true });

const Row = ({ k, children }) => <div className="flex justify-between gap-3 text-sm"><dt className="text-muted">{k}</dt><dd className="text-right font-medium">{children}</dd></div>;

export default function Show({ post }) {
    return (
        <AdminLayout title="Blog Post">
            <Head title={post.title} />
            <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
                <Link href="/admin/blog" className="inline-flex items-center gap-2 text-sm text-muted hover:text-ink"><ArrowLeft size={16} />Back to Posts</Link>
                <div className="flex gap-2">
                    <LinkButton external tone="neutral" href={post.url} target="_blank" rel="noreferrer"><ExternalLink size={16} />View Live</LinkButton>
                    <LinkButton href={`/admin/blog/${post.id}/edit`}><Pencil size={16} />Edit Post</LinkButton>
                </div>
            </div>

            <div className="grid gap-5 lg:grid-cols-3">
                <div className="space-y-5 lg:col-span-2">
                    <Card className="overflow-hidden">
                        {post.image && <img src={post.image} alt="" className="max-h-72 w-full object-cover" />}
                        <div className="p-4 md:p-6">
                            <h2 className="text-xl font-bold">{post.title}</h2>
                            {post.excerpt && <p className="mt-2 text-muted">{post.excerpt}</p>}
                            {/* HTML written by admins in the editor; sanitised so one admin cannot run script in another's session */}
                            <div className="rich-content mt-4 text-sm" dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(post.content ?? '') }} />
                        </div>
                    </Card>

                    <Card>
                        <CardHeader title="SEO Settings" />
                        <dl className="space-y-2 p-4 md:p-6">
                            <Row k="Meta Title">{post.meta_title || '—'}</Row><Row k="Meta Description">{post.meta_description || '—'}</Row><Row k="Slug">/{post.slug}</Row>
                        </dl>
                    </Card>

                    {post.comments.length > 0 && (
                        <Card>
                            <div id="comments" />
                            <CardHeader title={`Comments (${post.comments.length})`}>
                                {post.comments.some((c) => c.status === 'pending') && <Badge tone="warning">{post.comments.filter((c) => c.status === 'pending').length} pending</Badge>}
                            </CardHeader>
                            <ul className="divide-y divide-line">
                                {post.comments.map((c) => (
                                    <li key={c.id} className="p-4 md:px-6">
                                        <div className="mb-1 flex flex-wrap items-center justify-between gap-2 text-sm">
                                            <span className="flex items-center gap-2 font-medium">{c.author}<Badge tone={COMMENT_TONE[c.status] ?? 'muted'}>{c.status[0].toUpperCase() + c.status.slice(1)}</Badge></span>
                                            <span className="text-xs text-muted">{c.ago}</span>
                                        </div>
                                        {c.email && <p className="mb-1 text-xs text-muted">{c.email}</p>}
                                        <p className="whitespace-pre-wrap text-sm">{c.content}</p>
                                        <div className="mt-2 flex flex-wrap gap-3 text-sm">
                                            {c.status !== 'approved' && <button type="button" onClick={() => moderate(c.id, 'approved')} className="inline-flex items-center gap-1 text-success hover:underline"><Check size={14} />Approve</button>}
                                            {c.status !== 'spam' && <button type="button" onClick={() => moderate(c.id, 'spam')} className="inline-flex items-center gap-1 text-warning hover:underline"><X size={14} />Mark as spam</button>}
                                            {c.status !== 'pending' && <button type="button" onClick={() => moderate(c.id, 'pending')} className="inline-flex items-center gap-1 text-muted hover:underline"><RotateCcw size={14} />Back to pending</button>}
                                            <button type="button" onClick={() => confirmDelete(`/admin/blog/comments/${c.id}`, 'Delete this comment?')} className="inline-flex items-center gap-1 text-danger hover:underline"><Trash2 size={14} />Delete</button>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        </Card>
                    )}
                </div>

                <div className="space-y-5">
                    <Card>
                        <CardHeader title="Post Details" />
                        <dl className="space-y-2 p-4 md:p-6">
                            <Row k="Status"><Badge tone={STATUS_TONE[post.status] ?? 'muted'}>{post.status[0].toUpperCase() + post.status.slice(1)}</Badge></Row>
                            <Row k="Views">{post.views}</Row><Row k="Comments">{post.allow_comments ? 'Enabled' : 'Disabled'}</Row>
                        </dl>
                    </Card>
                    <Card><CardHeader title="Author" /><p className="p-4 text-sm md:p-6">{post.author ?? '—'}</p></Card>
                    <Card><CardHeader title="Category" /><p className="p-4 text-sm md:p-6">{post.category ?? 'Uncategorized'}</p></Card>
                    <Card>
                        <CardHeader title="Dates" />
                        <dl className="space-y-2 p-4 md:p-6"><Row k="Created">{post.created_at}</Row><Row k="Last Updated">{post.updated_at}</Row><Row k="Published">{post.published_at ?? '—'}</Row></dl>
                    </Card>
                </div>
            </div>
        </AdminLayout>
    );
}
