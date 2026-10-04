import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Plus, Search } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { Badge, Button, Card, Input, LinkButton, Pagination, Select } from '../../../Components/ui';
import { DataTable, RowActions } from '../../../Components/crud';

export default function Index({ attributes, filters }) {
    const [form, setForm] = useState({ search: filters.search ?? '', type: filters.type ?? '' });
    const hasFilters = !!(filters.search || filters.type);
    const go = (e) => {
        e.preventDefault();
        router.get('/admin/attributes', Object.fromEntries(Object.entries(form).filter(([, v]) => v)), { preserveState: true });
    };

    const columns = [
        { key: 'name', label: 'Attribute', render: (a) => <div><p className="font-medium">{a.name}</p><p className="text-xs text-muted">{a.slug}</p></div> },
        { key: 'type', label: 'Type', render: (a) => a.type_label },
        { key: 'values_count', label: 'Values', render: (a) => <Badge tone="info">{a.values_count} values</Badge> },
        {
            key: 'settings', label: 'Settings',
            render: (a) => (
                <div className="flex flex-wrap gap-1">
                    {a.is_visible && <Badge tone="success">Visible</Badge>}
                    {a.is_variation && <Badge tone="primary">Variation</Badge>}
                    {a.is_global && <Badge>Global</Badge>}
                </div>
            ),
        },
        {
            key: 'actions', label: '', className: 'text-right',
            render: (a) => <RowActions editHref={`/admin/attributes/${a.id}/edit`} deleteHref={`/admin/attributes/${a.id}`} deleteMessage={`Delete "${a.name}" and all its values?`} />,
        },
    ];

    return (
        <AdminLayout title="Attributes">
            <Head title="Attributes" />
            <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                <form onSubmit={go} className="flex flex-1 flex-wrap gap-2">
                    <div className="relative min-w-48 flex-1 sm:max-w-sm">
                        <Search size={16} className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
                        <Input value={form.search} onChange={(e) => setForm({ ...form, search: e.target.value })} placeholder="Search attributes…" className="pl-9" aria-label="Search attributes" />
                    </div>
                    <Select value={form.type} onChange={(e) => setForm({ ...form, type: e.target.value })} className="!w-auto" aria-label="Type">
                        <option value="">All Types</option>
                        <option value="select">Dropdown</option>
                        <option value="color">Color Swatch</option>
                        <option value="button">Button</option>
                        <option value="radio">Radio</option>
                    </Select>
                    <Button type="submit">Filter</Button>
                    {hasFilters && <Button tone="neutral" onClick={() => { setForm({ search: '', type: '' }); router.get('/admin/attributes'); }}>Clear</Button>}
                </form>
                <LinkButton href="/admin/attributes/create"><Plus size={16} />Add Attribute</LinkButton>
            </div>
            <Card className="overflow-hidden">
                <DataTable columns={columns} rows={attributes.data} empty={hasFilters ? 'No attributes match your filters.' : 'No attributes yet.'} />
                <Pagination links={attributes.links} from={attributes.from} to={attributes.to} total={attributes.total} />
            </Card>
        </AdminLayout>
    );
}
