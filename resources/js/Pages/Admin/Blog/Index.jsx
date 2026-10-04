import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Eye, ExternalLink, Folder, Plus, Search } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { Badge, Button, Card, Input, LinkButton, Pagination, Select } from '../../../Components/ui';
import { DataTable, RowActions } from '../../../Components/crud';

const TONE = { published: 'success', draft: 'muted', scheduled: 'info' };

export default function Index({ posts, categories, filters }) {
    const [form, setForm] = useState({ search: filters.search ?? '', status: filters.status ?? '', category: filters.category ?? '' });
    const hasFilters = Object.values(filters).some(Boolean);
    const set = (k) => (e) => setForm((f) => ({ ...f, [k]: e.target.value }));
    const go = (e) => { e.preventDefault(); router.get('/admin/blog', Object.fromEntries(Object.entries(form).filter(([, v]) => v)), { preserveState: true }); };

    const columns = [
        { key: 'title', label: 'Post', render: (p) => <div className="min-w-0"><Link href={`/admin/blog/${p.id}`} className="font-medium hover:text-primary">{p.title}</Link>{p.excerpt && <p className="truncate text-xs text-muted">{p.excerpt}</p>}</div> },
        { key: 'category', label: 'Category', render: (p) => <span className="text-muted">{p.category ?? 'Uncategorized'}</span> },
        { key: 'author', label: 'Author', render: (p) => <span className="text-muted">{p.author ?? '—'}</span> },
        { key: 'status', label: 'Status', render: (p) => <Badge tone={TONE[p.status] ?? 'muted'}>{p.status[0].toUpperCase() + p.status.slice(1)}</Badge> },
        { key: 'views', label: 'Views' },
        { key: 'comments', label: 'Comments', render: (p) => (p.pending_comments > 0 ? <Link href={`/admin/blog/${p.id}#comments`}><Badge tone="warning">{p.pending_comments} pending</Badge></Link> : <span className="text-muted">—</span>) },
        { key: 'date', label: 'Date', render: (p) => <span className="text-muted">{p.date}</span> },
        {
            key: 'actions', label: '', className: 'text-right',
            render: (p) => (
                <RowActions editHref={`/admin/blog/${p.id}/edit`} deleteHref={`/admin/blog/${p.id}`} deleteMessage={`Delete post "${p.title}"?`}>
                    <Link href={`/admin/blog/${p.id}`} title="Details" aria-label={`Details for ${p.title}`} className="text-muted hover:text-ink"><Eye size={18} /></Link>
                    <a href={p.url} target="_blank" rel="noreferrer" title="View live" aria-label={`View ${p.title} live`} className="text-muted hover:text-ink"><ExternalLink size={16} /></a>
                </RowActions>
            ),
        },
    ];

    return (
        <AdminLayout title="Blog Posts">
            <Head title="Blog Posts" />
            <div className="mb-4 flex flex-wrap justify-end gap-2">
                <LinkButton tone="neutral" href="/admin/blog/categories"><Folder size={16} />Categories</LinkButton>
                <LinkButton href="/admin/blog/create"><Plus size={16} />New Post</LinkButton>
            </div>

            <Card className="mb-4 p-3 md:p-4">
                <form onSubmit={go} className="flex flex-col gap-3 md:flex-row">
                    <div className="relative flex-1">
                        <Search size={16} className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
                        <Input value={form.search} onChange={set('search')} placeholder="Search posts…" className="pl-9" aria-label="Search posts" />
                    </div>
                    <Select value={form.status} onChange={set('status')} className="md:!w-auto" aria-label="Status">
                        <option value="">All Status</option><option value="published">Published</option><option value="draft">Draft</option><option value="scheduled">Scheduled</option>
                    </Select>
                    <Select value={form.category} onChange={set('category')} className="md:!w-auto" aria-label="Category">
                        <option value="">All Categories</option>{categories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                    </Select>
                    <div className="flex gap-2">
                        <Button type="submit" className="flex-1">Filter</Button>
                        {hasFilters && <Button tone="neutral" onClick={() => { setForm({ search: '', status: '', category: '' }); router.get('/admin/blog'); }}>Clear</Button>}
                    </div>
                </form>
            </Card>

            <Card className="overflow-hidden">
                <DataTable columns={columns} rows={posts.data} empty="No blog posts yet. Create your first post!" />
                <Pagination links={posts.links} from={posts.from} to={posts.to} total={posts.total} />
            </Card>
        </AdminLayout>
    );
}
