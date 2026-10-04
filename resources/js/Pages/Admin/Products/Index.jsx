import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Eye, ImageIcon, Pencil, Plus, Search, Store, Trash2 } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { Badge, Button, Card, EmptyState, Input, LinkButton, Pagination, Select } from '../../../Components/ui';

function Thumb({ p, size }) {
    return (
        <div className={`${size} shrink-0 overflow-hidden rounded-lg bg-surface-2`}>
            {p.image
                ? <img src={p.image} alt={p.name} className="h-full w-full object-cover" />
                : <div className="flex h-full w-full items-center justify-center text-muted"><ImageIcon size={18} /></div>}
        </div>
    );
}

function Price({ p }) {
    return p.sale_price ? (
        <span className="inline-flex items-baseline gap-2">
            <span className="font-medium text-success">{p.sale_price}</span>
            <span className="text-xs text-muted line-through">{p.price}</span>
        </span>
    ) : <span className="font-medium">{p.price}</span>;
}

function Stock({ p }) {
    if (p.type === 'variable') return <span className="text-muted">{p.variations} variations</span>;
    return <span className={p.quantity <= 5 ? 'font-medium text-danger' : ''}>{p.quantity}</span>;
}

function Actions({ p }) {
    const remove = () => {
        if (window.confirm(`Delete "${p.name}"? This cannot be undone.`)) {
            router.delete(`/admin/products/${p.id}`, { preserveScroll: true });
        }
    };
    return (
        <div className="flex items-center gap-3">
            <a href={`/shop/${p.slug}`} target="_blank" rel="noreferrer" title="View" aria-label={`View ${p.name}`} className="text-muted hover:text-ink"><Eye size={18} /></a>
            <Link href={`/admin/products/${p.id}/edit`} title="Edit" aria-label={`Edit ${p.name}`} className="text-primary hover:text-primary-hover"><Pencil size={18} /></Link>
            <button type="button" onClick={remove} title="Delete" aria-label={`Delete ${p.name}`} className="text-danger hover:opacity-75"><Trash2 size={18} /></button>
        </div>
    );
}

export default function Index({ products, filters, categories, vendors }) {
    const [form, setForm] = useState({
        search: filters.search ?? '', category: filters.category ?? '', vendor: filters.vendor ?? '', status: filters.status ?? '',
    });
    const set = (k) => (e) => setForm((f) => ({ ...f, [k]: e.target.value }));

    const submit = (e) => {
        e.preventDefault();
        router.get('/admin/products', Object.fromEntries(Object.entries(form).filter(([, v]) => v !== '')), { preserveState: true });
    };
    const reset = () => {
        setForm({ search: '', category: '', vendor: '', status: '' });
        router.get('/admin/products');
    };

    return (
        <AdminLayout title="Products">
            <Head title="Products" />

            <div className="mb-4 flex justify-end">
                <LinkButton href="/admin/products/create"><Plus size={16} />Add Product</LinkButton>
            </div>

            <Card className="mb-4 p-3 md:p-4">
                <form onSubmit={submit} className="flex flex-col gap-3 lg:flex-row">
                    <div className="relative flex-1">
                        <Search size={16} className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
                        <Input value={form.search} onChange={set('search')} placeholder="Search by name or SKU…" className="pl-9" aria-label="Search products" />
                    </div>
                    <div className="grid grid-cols-1 gap-2 sm:grid-cols-3 lg:flex">
                        <Select value={form.category} onChange={set('category')} aria-label="Category">
                            <option value="">All Categories</option>
                            {categories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                        </Select>
                        <Select value={form.vendor} onChange={set('vendor')} aria-label="Source">
                            <option value="">All Sources</option>
                            <option value="admin">Admin Products</option>
                            {vendors.map((v) => <option key={v.id} value={v.id}>{v.store_name}</option>)}
                        </Select>
                        <Select value={form.status} onChange={set('status')} aria-label="Status">
                            <option value="">All Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </Select>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" className="flex-1">Filter</Button>
                        <Button tone="neutral" onClick={reset}>Reset</Button>
                    </div>
                </form>
            </Card>

            <Card className="overflow-hidden">
                {products.data.length === 0 ? (
                    <EmptyState>No products match your filters.</EmptyState>
                ) : (
                    <>
                        <div className="divide-y divide-line md:hidden">
                            {products.data.map((p) => (
                                <div key={p.id} className="p-4">
                                    <div className="mb-3 flex gap-3">
                                        <Thumb p={p} size="h-16 w-16" />
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate text-sm font-medium">{p.name}</p>
                                            <p className="text-xs text-muted">SKU: {p.sku}</p>
                                            <div className="mt-1 text-sm"><Price p={p} /></div>
                                        </div>
                                    </div>
                                    <div className="mb-3 flex flex-wrap items-center gap-2">
                                        <Badge tone={p.is_active ? 'success' : 'danger'}>{p.is_active ? 'Active' : 'Inactive'}</Badge>
                                        {p.type === 'variable' ? <Badge tone="primary">Variable</Badge> : <Badge>Stock: {p.quantity}</Badge>}
                                        {p.category && <span className="text-xs text-muted">{p.category}</span>}
                                    </div>
                                    <div className="flex items-center justify-between">
                                        {p.vendor ? <span className="text-xs text-primary">{p.vendor.name}</span> : <span className="text-xs italic text-muted">Admin Product</span>}
                                        <Actions p={p} />
                                    </div>
                                </div>
                            ))}
                        </div>

                        <div className="hidden overflow-x-auto md:block">
                            <table className="w-full text-sm">
                                <thead className="bg-surface-2 text-left text-xs uppercase text-muted">
                                    <tr>
                                        {['Product', 'Vendor', 'Category', 'Price', 'Stock', 'Status', 'Actions'].map((h) => <th key={h} className="px-6 py-3 font-medium">{h}</th>)}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-line">
                                    {products.data.map((p) => (
                                        <tr key={p.id} className="hover:bg-surface-2">
                                            <td className="px-6 py-4">
                                                <div className="flex items-center gap-3">
                                                    <Thumb p={p} size="h-12 w-12" />
                                                    <div className="min-w-0">
                                                        <p className="font-medium">{p.name}</p>
                                                        <p className="flex items-center gap-2 text-xs text-muted">
                                                            SKU: {p.sku}{p.type === 'variable' && <Badge tone="primary">Variable</Badge>}
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                {p.vendor ? (
                                                    <a href={`/admin/vendors/${p.vendor.id}`} className="flex items-center gap-2 text-primary hover:underline">
                                                        {p.vendor.logo
                                                            ? <img src={p.vendor.logo} alt="" className="h-6 w-6 rounded-full object-cover" />
                                                            : <span className="flex h-6 w-6 items-center justify-center rounded-full bg-surface-2 text-muted"><Store size={12} /></span>}
                                                        {p.vendor.name}
                                                    </a>
                                                ) : <span className="italic text-muted">Admin Product</span>}
                                            </td>
                                            <td className="px-6 py-4 text-muted">{p.category ?? '-'}</td>
                                            <td className="px-6 py-4"><Price p={p} /></td>
                                            <td className="px-6 py-4"><Stock p={p} /></td>
                                            <td className="px-6 py-4"><Badge tone={p.is_active ? 'success' : 'danger'}>{p.is_active ? 'Active' : 'Inactive'}</Badge></td>
                                            <td className="px-6 py-4"><Actions p={p} /></td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </>
                )}
                <Pagination links={products.links} from={products.from} to={products.to} total={products.total} />
            </Card>
        </AdminLayout>
    );
}
