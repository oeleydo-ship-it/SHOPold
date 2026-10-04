import { Head } from '@inertiajs/react';
import { Folder, Plus } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { Badge, Card, LinkButton, Pagination } from '../../../Components/ui';
import { DataTable, ListToolbar, RowActions } from '../../../Components/crud';

export default function Index({ categories }) {
    const columns = [
        {
            key: 'name', label: 'Category',
            render: (c) => (
                <div className="flex items-center gap-3">
                    {c.image
                        ? <img src={c.image} alt="" className="h-10 w-10 rounded-lg object-cover" />
                        : <span className="flex h-10 w-10 items-center justify-center rounded-lg bg-surface-2 text-muted"><Folder size={18} /></span>}
                    <div><p className="font-medium">{c.name}</p><p className="text-xs text-muted">{c.slug}</p></div>
                </div>
            ),
        },
        { key: 'parent', label: 'Parent', render: (c) => <span className="text-muted">{c.parent ?? '-'}</span> },
        { key: 'products_count', label: 'Products' },
        { key: 'is_active', label: 'Status', render: (c) => <Badge tone={c.is_active ? 'success' : 'danger'}>{c.is_active ? 'Active' : 'Inactive'}</Badge> },
        {
            key: 'actions', label: '', className: 'text-right',
            render: (c) => <RowActions editHref={`/admin/categories/${c.id}/edit`} deleteHref={`/admin/categories/${c.id}`} deleteMessage={`Delete "${c.name}"?`} />,
        },
    ];
    return (
        <AdminLayout title="Categories">
            <Head title="Categories" />
            <ListToolbar><LinkButton href="/admin/categories/create"><Plus size={16} />Add Category</LinkButton></ListToolbar>
            <Card className="overflow-hidden">
                <DataTable columns={columns} rows={categories.data} empty="No categories yet." />
                <Pagination links={categories.links} from={categories.from} to={categories.to} total={categories.total} />
            </Card>
        </AdminLayout>
    );
}
