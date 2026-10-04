import { Head } from '@inertiajs/react';
import { ExternalLink, FileText, Plus } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { Badge, Card, LinkButton, Pagination } from '../../../Components/ui';
import { DataTable, ListToolbar, RowActions } from '../../../Components/crud';

export default function Index({ pages }) {
    const columns = [
        {
            key: 'title', label: 'Title',
            render: (p) => (
                <div className="flex items-center gap-3">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-surface-2 text-muted">{p.image ? <img src={p.image} alt="" className="h-full w-full object-cover" /> : <FileText size={18} />}</div>
                    <div className="min-w-0"><p className="font-medium">{p.title}</p>{p.parent && <p className="text-xs text-muted">Parent: {p.parent}</p>}</div>
                </div>
            ),
        },
        { key: 'slug', label: 'Slug', render: (p) => <span className="text-muted">/{p.slug}</span> },
        { key: 'template', label: 'Template', render: (p) => <Badge>{p.template}</Badge> },
        { key: 'status', label: 'Status', render: (p) => <Badge tone={p.is_active ? 'success' : 'warning'}>{p.is_active ? 'Active' : 'Draft'}</Badge> },
        { key: 'menu', label: 'Menu', render: (p) => (p.show_in_menu ? <Badge tone="info">In menu</Badge> : <span className="text-muted">—</span>) },
        {
            key: 'actions', label: '', className: 'text-right',
            render: (p) => (
                <RowActions editHref={`/admin/pages/${p.id}/edit`} deleteHref={`/admin/pages/${p.id}`} deleteMessage={`Delete page "${p.title}"?`}>
                    <a href={p.url} target="_blank" rel="noreferrer" title="View page" aria-label={`View ${p.title}`} className="text-muted hover:text-ink"><ExternalLink size={18} /></a>
                </RowActions>
            ),
        },
    ];
    return (
        <AdminLayout title="Pages">
            <Head title="Pages" />
            <ListToolbar><LinkButton href="/admin/pages/create"><Plus size={16} />Add Page</LinkButton></ListToolbar>
            <Card className="overflow-hidden">
                <DataTable columns={columns} rows={pages.data} empty="No pages found. Create your first page!" />
                <Pagination links={pages.links} from={pages.from} to={pages.to} total={pages.total} />
            </Card>
        </AdminLayout>
    );
}
