import { useEffect, useMemo, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import {
    AlertTriangle, Boxes, Check, CheckCircle2, CornerDownRight, Download, History, ImageIcon, Loader2, PackageOpen,
    Pencil, Search, X, XCircle,
} from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { Badge, Button, Card, Input, LinkButton, Pagination, Select } from '../../../Components/ui';
import { jsonRequest } from '../../../lib/http';

const STATUS = {
    in_stock: ['In Stock', 'success'],
    low_stock: ['Low Stock', 'warning'],
    out_of_stock: ['Out of Stock', 'danger'],
};

function Stat({ label, value, icon: Icon, tone }) {
    return (
        <Card className="flex items-center gap-4 p-4">
            <span className="flex h-12 w-12 items-center justify-center rounded-lg" style={{ color: tone, background: `color-mix(in srgb, ${tone} 15%, transparent)` }}><Icon size={22} /></span>
            <div><p className="text-sm text-muted">{label}</p><p className="text-2xl font-bold" style={{ color: tone === 'var(--primary)' ? undefined : tone }}>{value}</p></div>
        </Card>
    );
}

/** Click-to-edit quantity; saves with Enter or the check button, cancels with Esc. */
function QuantityCell({ item }) {
    const [editing, setEditing] = useState(false);
    const [qty, setQty] = useState(String(item.quantity));
    const [saving, setSaving] = useState(false);

    useEffect(() => setQty(String(item.quantity)), [item.quantity]);

    const save = () => {
        if (qty === '' || Number(qty) < 0) return;
        setSaving(true);
        router.post('/admin/stock/update', { item_id: item.id, item_type: item.type, quantity: Number(qty) }, {
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
            <Input
                type="number" min="0" autoFocus value={qty} className="!w-20 !px-2 !py-1"
                onChange={(e) => setQty(e.target.value)}
                onKeyDown={(e) => { if (e.key === 'Enter') save(); if (e.key === 'Escape') cancel(); }}
                aria-label="New quantity"
            />
            <button type="button" onClick={save} disabled={saving} aria-label="Save quantity" className="text-success">{saving ? <Loader2 size={16} className="animate-spin" /> : <Check size={16} />}</button>
            <button type="button" onClick={cancel} aria-label="Cancel" className="text-muted"><X size={16} /></button>
        </div>
    );
}

function HistoryModal({ target, onClose }) {
    const [state, setState] = useState({ loading: true, logs: [], error: '' });

    useEffect(() => {
        let alive = true;
        jsonRequest(`/admin/stock/history/${target.product_id}`)
            .then((d) => alive && setState({ loading: false, logs: d.logs.data, error: '' }))
            .catch((e) => alive && setState({ loading: false, logs: [], error: e.message }));
        return () => { alive = false; };
    }, [target.product_id]);

    const typeTone = (t) => (t === 'add' ? 'success' : t === 'remove' || t === 'sale' ? 'danger' : 'info');
    const date = (d) => new Date(d).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });

    return (
        <div className="fixed inset-0 z-[80] flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-label="Inventory history">
            <div className="absolute inset-0 bg-black/60" onClick={onClose} />
            <div className="relative w-full max-w-3xl rounded-card bg-surface p-4 shadow-xl md:p-6">
                <div className="mb-4 flex items-center justify-between">
                    <h3 className="flex items-center gap-2 text-lg font-semibold"><History size={18} />Inventory History: {target.name}</h3>
                    <button type="button" onClick={onClose} aria-label="Close" className="text-muted hover:text-ink"><X size={18} /></button>
                </div>
                {state.loading && <p className="py-10 text-center text-muted"><Loader2 className="mx-auto mb-2 animate-spin" />Loading history…</p>}
                {state.error && <p role="alert" className="py-10 text-center text-danger">{state.error}</p>}
                {!state.loading && !state.error && state.logs.length === 0 && <p className="py-10 text-center text-muted">No history records found.</p>}
                {state.logs.length > 0 && (
                    <div className="max-h-96 overflow-auto">
                        <table className="w-full text-sm">
                            <thead className="sticky top-0 bg-surface-2 text-left text-xs uppercase text-muted">
                                <tr>{['Date', 'User', 'Type', 'Before', 'Change', 'After', 'Note'].map((h) => <th key={h} className="px-3 py-2 font-medium">{h}</th>)}</tr>
                            </thead>
                            <tbody className="divide-y divide-line">
                                {state.logs.map((l) => (
                                    <tr key={l.id}>
                                        <td className="whitespace-nowrap px-3 py-2 text-muted">{date(l.created_at)}</td>
                                        <td className="px-3 py-2">{l.user?.name ?? 'System'}</td>
                                        <td className="px-3 py-2"><Badge tone={typeTone(l.type)}>{l.type}</Badge></td>
                                        <td className="px-3 py-2">{l.quantity_before}</td>
                                        <td className={`px-3 py-2 ${l.quantity_change >= 0 ? 'text-success' : 'text-danger'}`}>{l.quantity_change >= 0 ? '+' : ''}{l.quantity_change}</td>
                                        <td className="px-3 py-2 font-medium">{l.quantity_after}</td>
                                        <td className="max-w-[160px] truncate px-3 py-2 text-muted" title={l.note ?? ''}>{l.note || '-'}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
                <div className="mt-4 flex justify-between">
                    <a href={`/admin/stock/history/${target.product_id}`} className="text-sm text-primary hover:underline">Open full history</a>
                    <Button tone="neutral" onClick={onClose}>Close</Button>
                </div>
            </div>
        </div>
    );
}

export default function Index({ items, statistics, categories, vendors, filters }) {
    const [form, setForm] = useState({
        search: filters.search ?? '', status: filters.status ?? '', vendor_id: filters.vendor_id ?? '', category_id: filters.category_id ?? '',
    });
    const hasFilters = Object.values(filters).some(Boolean);
    const [selected, setSelected] = useState({}); // key -> { id, type }
    const [bulk, setBulk] = useState({ action: '', quantity: '', note: '' });
    const [applying, setApplying] = useState(false);
    const [history, setHistory] = useState(null);

    const rows = items.data;
    const selectedList = useMemo(() => Object.values(selected), [selected]);
    const allOnPage = rows.length > 0 && rows.every((r) => selected[r.key]);

    const toggle = (r) => setSelected((cur) => {
        const next = { ...cur };
        if (next[r.key]) delete next[r.key]; else next[r.key] = { id: r.id, type: r.type };
        return next;
    });
    const toggleAll = (on) => setSelected((cur) => {
        const next = { ...cur };
        rows.forEach((r) => { if (on) next[r.key] = { id: r.id, type: r.type }; else delete next[r.key]; });
        return next;
    });

    const go = (e) => {
        e.preventDefault();
        router.get('/admin/stock', Object.fromEntries(Object.entries(form).filter(([, v]) => v)), { preserveState: true });
    };

    const applyBulk = () => {
        setApplying(true);
        router.post('/admin/stock/bulk-update', {
            action: bulk.action,
            quantity: Number(bulk.quantity),
            note: bulk.note || null,
            items: selectedList,
        }, {
            preserveScroll: true,
            onSuccess: () => { setSelected({}); setBulk({ action: '', quantity: '', note: '' }); },
            onFinish: () => setApplying(false),
        });
    };

    const exportQuery = new URLSearchParams(Object.fromEntries(Object.entries(filters).filter(([, v]) => v))).toString();

    return (
        <AdminLayout title="Stock Management">
            <Head title="Stock Management" />

            <div className="mb-4 flex justify-end">
                <LinkButton external tone="success" href={`/admin/stock/export${exportQuery ? `?${exportQuery}` : ''}`}><Download size={16} />Export CSV</LinkButton>
            </div>

            <div className="mb-5 grid grid-cols-2 gap-3 md:grid-cols-4">
                <Stat label="Total Products" value={statistics.total} icon={Boxes} tone="var(--primary)" />
                <Stat label="In Stock" value={statistics.in_stock} icon={CheckCircle2} tone="var(--success)" />
                <Stat label="Low Stock" value={statistics.low_stock} icon={AlertTriangle} tone="var(--warning)" />
                <Stat label="Out of Stock" value={statistics.out_of_stock} icon={XCircle} tone="var(--danger)" />
            </div>

            <Card className="mb-4 p-3 md:p-4">
                <form onSubmit={go} className="flex flex-col gap-3 lg:flex-row">
                    <div className="relative flex-1">
                        <Search size={16} className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
                        <Input value={form.search} onChange={(e) => setForm({ ...form, search: e.target.value })} placeholder="Search by name or SKU…" className="pl-9" aria-label="Search stock" />
                    </div>
                    <div className="grid grid-cols-1 gap-2 sm:grid-cols-3 lg:flex">
                        <Select value={form.status} onChange={(e) => setForm({ ...form, status: e.target.value })} aria-label="Stock status">
                            <option value="">All Status</option>
                            <option value="in_stock">In Stock</option>
                            <option value="low_stock">Low Stock</option>
                            <option value="out_of_stock">Out of Stock</option>
                        </Select>
                        <Select value={form.vendor_id} onChange={(e) => setForm({ ...form, vendor_id: e.target.value })} aria-label="Vendor">
                            <option value="">All Vendors</option>
                            <option value="admin">Admin Products</option>
                            {vendors.map((v) => <option key={v.id} value={v.id}>{v.store_name}</option>)}
                        </Select>
                        <Select value={form.category_id} onChange={(e) => setForm({ ...form, category_id: e.target.value })} aria-label="Category">
                            <option value="">All Categories</option>
                            {categories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                        </Select>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" className="flex-1">Filter</Button>
                        {hasFilters && <Button tone="neutral" onClick={() => { setForm({ search: '', status: '', vendor_id: '', category_id: '' }); router.get('/admin/stock'); }}>Clear</Button>}
                    </div>
                </form>
            </Card>

            {selectedList.length > 0 && (
                <div className="mb-4 flex flex-wrap items-center gap-3 rounded-card border border-primary/40 bg-primary-soft/30 p-4" role="region" aria-label="Bulk actions">
                    <span className="font-medium text-primary">{selectedList.length} item(s) selected</span>
                    <Select value={bulk.action} onChange={(e) => setBulk({ ...bulk, action: e.target.value })} className="!w-auto" aria-label="Bulk action">
                        <option value="">Select Action</option>
                        <option value="set">Set Stock To</option>
                        <option value="add">Add Stock</option>
                        <option value="reduce">Reduce Stock</option>
                    </Select>
                    <Input type="number" min="0" value={bulk.quantity} onChange={(e) => setBulk({ ...bulk, quantity: e.target.value })} placeholder="Qty" className="!w-24" aria-label="Quantity" />
                    <Input value={bulk.note} onChange={(e) => setBulk({ ...bulk, note: e.target.value })} placeholder="Note (optional)" className="!w-48" aria-label="Note" />
                    <Button onClick={applyBulk} disabled={!bulk.action || bulk.quantity === '' || applying}>{applying && <Loader2 size={14} className="animate-spin" />}Apply</Button>
                    <button type="button" onClick={() => setSelected({})} className="text-sm text-muted hover:text-ink">Clear selection</button>
                </div>
            )}

            <Card className="overflow-hidden">
                {rows.length === 0 ? (
                    <p className="p-10 text-center text-sm text-muted"><PackageOpen size={32} className="mx-auto mb-2 opacity-50" />No products found</p>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-surface-2 text-left text-xs uppercase text-muted">
                                <tr>
                                    <th className="px-4 py-3"><input type="checkbox" checked={allOnPage} onChange={(e) => toggleAll(e.target.checked)} aria-label="Select all on this page" className="h-4 w-4 accent-[var(--primary)]" /></th>
                                    {['Product', 'SKU', 'Quantity', 'Status', 'Vendor', 'Category', ''].map((h) => <th key={h} className="px-4 py-3 font-medium">{h}</th>)}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-line">
                                {rows.map((r) => (
                                    <tr key={r.key} className={r.is_variation ? 'bg-surface-2/60' : ''}>
                                        <td className="px-4 py-3"><input type="checkbox" checked={!!selected[r.key]} onChange={() => toggle(r)} aria-label={`Select ${r.name}`} className="h-4 w-4 accent-[var(--primary)]" /></td>
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
                                        <td className="px-4 py-3">{r.vendor ? <span className="text-primary">{r.vendor}</span> : <span className="italic text-muted">Admin</span>}</td>
                                        <td className="px-4 py-3 text-muted">{r.category || '-'}</td>
                                        <td className="px-4 py-3">
                                            <button type="button" onClick={() => setHistory(r)} title="View history" aria-label={`History for ${r.name}`} className="text-muted hover:text-ink"><History size={16} /></button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
                <Pagination links={items.links} from={items.from} to={items.to} total={items.total} />
            </Card>

            {history && <HistoryModal target={history} onClose={() => setHistory(null)} />}
        </AdminLayout>
    );
}
