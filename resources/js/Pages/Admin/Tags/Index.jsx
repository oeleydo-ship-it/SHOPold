import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Plus, Search } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { Badge, Button, Card, Input, LinkButton, Pagination } from '../../../Components/ui';
import { DataTable, RowActions } from '../../../Components/crud';

export default function Index({ tags, filters }) {
    const [search, setSearch] = useState(filters.search ?? '');
    const go = (e) => {
        e.preventDefault();
        router.get('/admin/tags', search ? { search } : {}, { preserveState: true });
    };
    const columns = [
        {
            key: 'name', label: 'Tag',
            render: (t) => <span className="flex items-center gap-3 font-medium"><span className="h-4 w-4 rounded-full" style={{ background: t.color ?? '#3b82f6' }} />{t.name}</span>,
        },
        { key: 'slug', label: 'Slug', render: (t) => <span className="text-muted">{t.slug}</span> },
        { key: 'products_count', label: 'Products', render: (t) => <Badge>{t.products_count} products</Badge> },
        {
            key: 'actions', label: '', className: 'text-right',
            render: (t) => <RowActions editHref={`/admin/tags/${t.id}/edit`} deleteHref={`/admin/tags/${t.id}`} deleteMessage={`Delete tag "${t.name}"?`} />,
        },
    ];
    return (
        <AdminLayout title="Tags">
            <Head title="Tags" />
            <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                <form onSubmit={go} className="flex flex-1 gap-2 sm:max-w-md">
                    <div className="relative flex-1">
                        <Search size={16} className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
                        <Input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Search tags…" className="pl-9" aria-label="Search tags" />
                    </div>
                    <Button type="submit">Search</Button>
                    {filters.search && <Button tone="neutral" onClick={() => { setSearch(''); router.get('/admin/tags'); }}>Clear</Button>}
                </form>
                <LinkButton href="/admin/tags/create"><Plus size={16} />Add Tag</LinkButton>
            </div>
            <Card className="overflow-hidden">
                <DataTable columns={columns} rows={tags.data} empty="No tags found." />
                <Pagination links={tags.links} from={tags.from} to={tags.to} total={tags.total} />
            </Card>
        </AdminLayout>
    );
}
