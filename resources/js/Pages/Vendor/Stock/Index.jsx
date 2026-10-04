import { useEffect, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { AlertTriangle, Boxes, Check, CheckCircle2, CornerDownRight, History, ImageIcon, Loader2, PackageOpen, Pencil, Search, X, XCircle } from 'lucide-react';
import VendorLayout from '../../../Layouts/VendorLayout';
import StatCard from '../../../Components/StatCard';
import { Badge, Button, Card, Input, Pagination, Select } from '../../../Components/ui';

const STATUS = {
    in_stock: ['In stock', 'success'],
    low_stock: ['Low stock', 'warning'],
    out_of_stock: ['Out of stock', 'danger'],
};

/** Click-to-edit quantity; saves with Enter or the check button, cancels with Esc. */
function QuantityCell({ item }) {
    const [editing, setEditing] = useState(false);
    const [qty, setQty] = useState(String(item.quantity));
    const [saving, setSaving] = useState(false);

    useEffect(() => setQty(String(item.quantity)), [item.quantity]);

    const save = () => {
        if (qty === '' || Number(qty) < 0) return;
        setSaving(true);
        router.post('/vendor/stock/update', { item_id: item.id, item_type: item.type, quantity: Number(qty) }, {
            preserveScroll: true,
            onSuccess: () => setEditing(false),
            onFinish: () => setSaving(false),
        });
    };
    const cancel = () => { setEditing(false); setQty(String(item.quantity)); };

    if (!editing) {
        const tone = item.status === 'out_of_stock' ? 'text-danger' : item.status === 'low_stock' ? 'text-warning' : '';
        return (
            <button type="button" onClick={() => setEditing(true)} aria-label={`Edit quantity for ${item.name}`} className={`inline-flex items-center gap-1 rounded px-2 py-1 hover:bg-surface-2 ${tone} ${tone ? 'font-medium' : ''}`}>
                {item.quantity}<Pencil size={12} className="text-muted" />
            </button>
        );
    }
    return (
        <div className="flex items-center gap-1">
            <Input type="number" min="0" autoFocus value={qty} className="!w-20 !px-2 !py-1" aria-label="New quantity"
                onChange={(e) => setQty(e.target.value)}
                onKeyDown={(e) => { if (e.key === 'Enter') save(); if (e.key === 'Escape') cancel(); }} />
            <button type="button" onClick={save} disabled={saving} aria-label="Save quantity" className="text-success">{saving ? <Loader2 size={16} className="animate-spin" /> : <Check size={16} />}</button>
            <button type="button" onClick={cancel} aria-label="Cancel" className="text-muted"><X size={16} /></button>
        </div>
    );
}

export default function Index({ items, statistics, categories, filters, threshold }) {
    const [form, setForm] = useState({ search: filters.search ?? '', status: filters.status ?? '', category_id: filters.category_id ?? '' });
    const hasFilters = Object.values(filters).some(Boolean);
    const go = (e) => { e.preventDefault(); router.get('/vendor/stock', Object.fromEntries(Object.entries(form).filter(([, v]) => v)), { preserveState: true }); };
    const rows = items.data;

    return (
        <VendorLayout title="Stock">
            <Head title="Stock" />

            <div className="mb-5 grid grid-cols-2 gap-3 md:grid-cols-4">
                <StatCard label="Products" value={statistics.total} icon={Boxes} tone="primary" />
                <StatCard label="In stock" value={statistics.in_stock} icon={CheckCircle2} tone="success" />
                <StatCard label="Low stock" value={statistics.low_stock} icon={AlertTriangle} tone="warning" sub={<p className="mt-0.5 text-xs text-muted">{threshold} or fewer</p>} />
                <StatCard label="Out of stock" value={statistics.out_of_stock} icon={XCircle} tone="danger" />
            </div>

            <Card className="mb-4 p-3 md:p-4">
                <form onSubmit={go} className="flex flex-col gap-2 lg:flex-row">
                    <div className="relative flex-1">
                        <Search size={16} className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
                        <Input value={form.search} onChange={(e) => setForm({ ...form, search: e.target.value })} placeholder="Search by name or SKU…" className="pl-9" aria-label="Search stock" />
                    </div>
                    <Select className="lg:w-auto" value={form.status} onChange={(e) => setForm({ ...form, status: e.target.value })} aria-label="Stock status">
                        <option value="">All statuses</option>
                        {Object.entries(STATUS).map(([v, [l]]) => <option key={v} value={v}>{l}</option>)}
                    </Select>
                    <Select className="lg:w-auto" value={form.category_id} onChange={(e) => setForm({ ...form, category_id: e.target.value })} aria-label="Category">
                        <option value="">All categories</option>
                        {categories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                    </Select>
                    <div className="flex gap-2">
                        <Button type="submit" className="flex-1">Filter</Button>
                        {hasFilters && <Button tone="neutral" onClick={() => router.get('/vendor/stock')}>Clear</Button>}
                    </div>
                </form>
            </Card>

            <Card className="overflow-hidden">
                {rows.length === 0 ? (
                    <p className="p-10 text-center text-sm text-muted"><PackageOpen size={32} className="mx-auto mb-2 opacity-50" />No products found</p>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-surface-2 text-left text-xs uppercase text-muted">
                                <tr>{['Product', 'SKU', 'Quantity', 'Status', 'Category', ''].map((h) => <th key={h} className="px-4 py-3 font-medium">{h}</th>)}</tr>
                            </thead>
                            <tbody className="divide-y divide-line">
                                {rows.map((r) => (
                                    <tr key={r.key} className={r.is_variation ? 'bg-surface-2/60' : ''}>
                                        <td className="px-4 py-3">
                                            {r.is_variation ? (
                                                <p className="flex items-center gap-2 pl-8 text-muted"><CornerDownRight size={14} />{r.name}</p>
                                            ) : (
                                                <div className="flex items-center gap-3">
                                                    <div className="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-surface-2 text-muted">
                                                        {r.image ? <img src={r.image} alt="" className="h-full w-full object-cover" /> : <ImageIcon size={16} />}
                                                    </div>
                                                    <div><p className="font-medium">{r.name}</p>{r.product_type === 'variable' && <Badge tone="primary">Variable</Badge>}</div>
                                                </div>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-muted">{r.sku || '-'}</td>
                                        <td className="px-4 py-3"><QuantityCell item={r} /></td>
                                        <td className="px-4 py-3"><Badge tone={STATUS[r.status][1]}>{STATUS[r.status][0]}</Badge></td>
                                        <td className="px-4 py-3 text-muted">{r.category || '-'}</td>
                                        <td className="px-4 py-3">
                                            {!r.is_variation && <Link href={`/vendor/stock/history/${r.product_id}`} title="View history" aria-label={`History for ${r.name}`} className="text-muted hover:text-ink"><History size={16} /></Link>}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
                <Pagination links={items.links} from={items.from} to={items.to} total={items.total} />
            </Card>
        </VendorLayout>
    );
}
