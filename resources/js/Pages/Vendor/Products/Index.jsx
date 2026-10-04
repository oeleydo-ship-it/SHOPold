import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Eye, EyeOff, Package, Pencil, Plus, Search, Trash2 } from 'lucide-react';
import VendorLayout from '../../../Layouts/VendorLayout';
import { Badge, Button, Card, Input, LinkButton, Pagination, Select, titleCase } from '../../../Components/ui';
import { DataTable, confirmDelete } from '../../../Components/crud';

export default function Index({ products, categories, filters, approvalRequired }) {
    const [form, setForm] = useState({ search: filters.search ?? '', status: filters.status ?? '', type: filters.type ?? '', category: filters.category ?? '' });
    const set = (k) => (e) => setForm((f) => ({ ...f, [k]: e.target.value }));
    const go = (e) => { e?.preventDefault(); router.get('/vendor/products', Object.fromEntries(Object.entries(form).filter(([, v]) => v)), { preserveState: true }); };
    const hasFilters = Object.values(filters).some(Boolean);

    const columns = [
        {
            key: 'name', label: 'Product',
            render: (p) => (
                <div className="flex items-center gap-3">
                    <div className="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-surface-2 text-muted">{p.image ? <img src={p.image} alt="" className="h-full w-full object-cover" /> : <Package size={18} />}</div>
                    <div className="min-w-0">
                        <Link href={`/vendor/products/${p.id}/edit`} className="block truncate font-medium hover:text-primary">{p.name}</Link>
                        <p className="text-xs text-muted">SKU {p.sku}</p>
                    </div>
                </div>
            ),
        },
        { key: 'type', label: 'Type', render: (p) => <Badge>{titleCase(p.type)}{p.variations > 0 ? ` · ${p.variations}` : ''}</Badge> },
        { key: 'category', label: 'Category', render: (p) => <span className="text-muted">{p.category ?? '—'}</span> },
        { key: 'price', label: 'Price', render: (p) => <div>{p.sale_price ? <><span className="font-medium text-primary">{p.sale_price}</span> <span className="text-xs text-muted line-through">{p.price}</span></> : <span className="font-medium">{p.price}</span>}</div> },
        { key: 'stock', label: 'Stock', render: (p) => (p.type === 'digital' ? <span className="text-muted">Digital</span> : <span className={p.stock <= p.low_stock ? 'font-semibold text-danger' : ''}>{p.stock}</span>) },
        { key: 'status', label: 'Status', render: (p) => <Badge tone={p.is_active ? 'success' : 'warning'}>{p.is_active ? 'Active' : (approvalRequired && p.awaiting_approval ? 'Awaiting approval' : 'Hidden')}</Badge> },
        {
            key: 'actions', label: '', className: 'text-right',
            render: (p) => (
                <div className="flex items-center justify-end gap-3">
                    {(p.is_active || p.can_activate) && (
                        <button type="button" title={p.is_active ? 'Hide from the store' : 'Show in the store'} aria-label={`${p.is_active ? 'Hide' : 'Show'} ${p.name}`}
                            onClick={() => router.patch(`/vendor/products/${p.id}/toggle`, {}, { preserveScroll: true })} className="text-muted hover:text-ink">
                            {p.is_active ? <EyeOff size={18} /> : <Eye size={18} />}
                        </button>
                    )}
                    <Link href={`/vendor/products/${p.id}/edit`} aria-label={`Edit ${p.name}`} className="text-primary"><Pencil size={18} /></Link>
                    <button type="button" aria-label={`Delete ${p.name}`} onClick={() => confirmDelete(`/vendor/products/${p.id}`, `Delete "${p.name}"? This cannot be undone.`)} className="text-danger"><Trash2 size={18} /></button>
                </div>
            ),
        },
    ];

    return (
        <VendorLayout title="Products" actions={<LinkButton href="/vendor/products/create"><Plus size={16} /><span className="hidden sm:inline">Add product</span></LinkButton>}>
            <Head title="My products" />
            {approvalRequired && <p className="mb-4 rounded-lg bg-info/10 px-4 py-2 text-sm">New products are reviewed by the store before they go live.</p>}

            <Card className="mb-4 p-4">
                <form onSubmit={go} className="flex flex-wrap gap-2">
                    <div className="relative min-w-48 flex-1">
                        <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
                        <Input className="pl-9" placeholder="Search products…" aria-label="Search products" value={form.search} onChange={set('search')} />
                    </div>
                    <Select className="w-auto" aria-label="Status" value={form.status} onChange={set('status')}><option value="">All statuses</option><option value="active">Active</option><option value="inactive">Hidden</option></Select>
                    <Select className="w-auto" aria-label="Type" value={form.type} onChange={set('type')}><option value="">All types</option><option value="simple">Simple</option><option value="variable">Variable</option><option value="digital">Digital</option></Select>
                    <Select className="w-auto" aria-label="Category" value={form.category} onChange={set('category')}><option value="">All categories</option>{categories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}</Select>
                    <Button type="submit">Filter</Button>
                    {hasFilters && <Button tone="neutral" onClick={() => router.get('/vendor/products')}>Clear</Button>}
                </form>
            </Card>

            <Card className="overflow-hidden">
                <DataTable columns={columns} rows={products.data} empty="No products yet. Add your first product." />
                <Pagination links={products.links} from={products.from} to={products.to} total={products.total} />
            </Card>
        </VendorLayout>
    );
}
