import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Plus, Search, Star, Tag } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { Badge, Button, Card, Input, LinkButton, Pagination, Select } from '../../../Components/ui';
import { DataTable, RowActions } from '../../../Components/crud';

export default function Index({ brands, filters }) {
    const [form, setForm] = useState({ search: filters.search ?? '', status: filters.status ?? '' });
    const hasFilters = !!(filters.search || filters.status);

    const go = (e) => {
        e.preventDefault();
        router.get('/admin/brands', Object.fromEntries(Object.entries(form).filter(([, v]) => v)), { preserveState: true });
    };

    const toggle = (b) => router.patch(`/admin/brands/${b.id}/toggle-status`, {}, { preserveScroll: true });

    const columns = [
        {
            key: 'name', label: 'Brand',
            render: (b) => (
                <div className="flex items-center gap-3">
                    {b.logo
                        ? <img src={b.logo} alt="" className="h-12 w-12 rounded-lg border border-line object-contain" />
                        : <span className="flex h-12 w-12 items-center justify-center rounded-lg bg-surface-2 text-muted"><Tag size={18} /></span>}
                    <div><p className="font-medium">{b.name}</p><p className="text-xs text-muted">{b.slug}</p></div>
                </div>
            ),
        },
        { key: 'products_count', label: 'Products', render: (b) => <Badge>{b.products_count} products</Badge> },
        {
            key: 'is_active', label: 'Status',
            render: (b) => (
                <button type="button" onClick={() => toggle(b)} title="Click to toggle" aria-label={`Toggle ${b.name} status`}>
                    <Badge tone={b.is_active ? 'success' : 'danger'}>{b.is_active ? 'Active' : 'Inactive'}</Badge>
                </button>
            ),
        },
        { key: 'is_featured', label: 'Featured', render: (b) => (b.is_featured ? <Star size={16} className="text-warning" fill="currentColor" aria-label="Featured" /> : <span className="text-muted">-</span>) },
        {
            key: 'actions', label: '', className: 'text-right',
            render: (b) => <RowActions editHref={`/admin/brands/${b.id}/edit`} deleteHref={`/admin/brands/${b.id}`} deleteMessage={`Delete brand "${b.name}"?`} />,
        },
    ];

    return (
        <AdminLayout title="Brands">
            <Head title="Brands" />
            <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                <form onSubmit={go} className="flex flex-1 flex-wrap gap-2">
                    <div className="relative min-w-48 flex-1 sm:max-w-sm">
                        <Search size={16} className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
                        <Input value={form.search} onChange={(e) => setForm({ ...form, search: e.target.value })} placeholder="Search brands…" className="pl-9" aria-label="Search brands" />
                    </div>
                    <Select value={form.status} onChange={(e) => setForm({ ...form, status: e.target.value })} className="!w-auto" aria-label="Status">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </Select>
                    <Button type="submit">Filter</Button>
                    {hasFilters && <Button tone="neutral" onClick={() => { setForm({ search: '', status: '' }); router.get('/admin/brands'); }}>Clear</Button>}
                </form>
                <LinkButton href="/admin/brands/create"><Plus size={16} />Add Brand</LinkButton>
            </div>
            <Card className="overflow-hidden">
                <DataTable columns={columns} rows={brands.data} empty={hasFilters ? 'No brands match your filters.' : 'No brands yet.'} />
                <Pagination links={brands.links} from={brands.from} to={brands.to} total={brands.total} />
            </Card>
        </AdminLayout>
    );
}
