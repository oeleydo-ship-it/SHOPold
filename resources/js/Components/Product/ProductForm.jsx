import { useEffect, useRef, useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import {
    AlertTriangle, Check, Clock, Cloud, Download, Infinity as InfinityIcon, Layers, Link2, Loader2,
    Pencil, Plus, Search, ShoppingBasket, Sparkles, Trash2, Wand2, X,
} from 'lucide-react';
import { Badge, Button, Card, CardHeader, Field, Input, Select, Textarea, titleCase } from '../ui';
import RichEditor from '../RichEditor';
import MediaPicker from '../MediaPicker';
import { jsonRequest } from '../../lib/http';

const blankFile = { name: '', file_type: 'upload', file: null, file_url: '', download_limit: '', download_expiry_days: '' };

function Section({ title, hint, action, children, tone }) {
    return (
        <Card className={tone === 'info' ? 'border-info/40 bg-info/5' : ''}>
            <div className="flex items-start justify-between gap-3 border-b border-line p-4 md:px-6">
                <div>
                    <h3 className="text-sm font-semibold md:text-base">{title}</h3>
                    {hint && <p className="mt-0.5 text-xs text-muted md:text-sm">{hint}</p>}
                </div>
                {action}
            </div>
            <div className="p-4 md:p-6">{children}</div>
        </Card>
    );
}

function Check2({ label, checked, onChange, hint }) {
    return (
        <label className="flex items-start gap-2 text-sm">
            <input type="checkbox" checked={!!checked} onChange={(e) => onChange(e.target.checked)} className="mt-0.5 h-4 w-4 accent-[var(--primary)]" />
            <span>{label}{hint && <span className="block text-xs text-muted">{hint}</span>}</span>
        </label>
    );
}

/* ---------------------------------------------------------------- product type */
function TypeSelector({ value, onChange, digital }) {
    const types = [
        ['simple', 'Simple', 'Single product'],
        ['variable', 'Variable', 'Has variations'],
        ...(digital ? [['digital', 'Digital', 'Downloadable']] : []),
    ];
    return (
        <Section title="Product Type">
            <div className="grid grid-cols-2 gap-3 md:grid-cols-4" role="radiogroup" aria-label="Product type">
                {types.map(([id, label, sub]) => (
                    <button
                        key={id}
                        type="button"
                        role="radio"
                        aria-checked={value === id}
                        onClick={() => onChange(id)}
                        className={`rounded-card border-2 p-4 text-center transition ${value === id ? 'border-primary bg-primary-soft/40' : 'border-line hover:bg-surface-2'}`}
                    >
                        <div className="font-medium">{label}</div>
                        <div className="text-xs text-muted">{sub}</div>
                    </button>
                ))}
            </div>
        </Section>
    );
}

/* --------------------------------------------------------------------- AI box */
function AiAssistant({ data, setData, categories, aiState }) {
    const [open, setOpen] = useState(false);
    const [prompt, setPrompt] = useState('');
    const [busy, setBusy] = useState('');
    const [error, setError] = useState('');

    const run = async (type) => {
        if (!prompt.trim()) return setError('Please describe your product first');
        setBusy(type);
        setError('');
        try {
            const res = await jsonRequest('/admin/ai/generate', {
                method: 'POST',
                body: { prompt, type, category: categories.find((c) => String(c.id) === String(data.category_id))?.name ?? '' },
            });
            if (!res.success) throw new Error(res.error || 'Failed to generate content');
            if (type === 'title' || type === 'all') setData('name', res.title || data.name);
            if (type === 'short' || type === 'all') setData('short_description', res.short_description || data.short_description);
            if (type === 'desc' || type === 'all') setData('description', res.description || data.description);
        } catch (e) {
            setError(e.message || 'Network error. Please try again.');
        } finally {
            setBusy('');
        }
    };

    if (!aiState) {
        return <a href="/admin/settings/ai" className="inline-flex items-center gap-2 rounded-lg bg-surface-2 px-3 py-1.5 text-sm text-muted hover:text-ink"><Sparkles size={14} />Setup AI</a>;
    }

    return (
        <>
            <Button tone="neutral" onClick={() => setOpen((o) => !o)} aria-expanded={open}><Sparkles size={14} />AI Assistant</Button>
            {open && (
                <div className="mt-4 w-full basis-full rounded-card border border-primary/30 bg-primary-soft/30 p-4">
                    <Field label="Describe your product">
                        <Textarea rows={3} value={prompt} onChange={(e) => setPrompt(e.target.value)} placeholder="e.g. Wireless Bluetooth headphones with noise cancellation and 30-hour battery…" />
                    </Field>
                    <div className="mt-3 flex flex-wrap gap-2">
                        {[['title', 'Generate Title'], ['short', 'Short Description'], ['desc', 'Full Description'], ['all', 'Generate All']].map(([t, l]) => (
                            <Button key={t} tone={t === 'all' ? 'primary' : 'neutral'} onClick={() => run(t)} disabled={!!busy}>
                                {busy === t ? <Loader2 size={14} className="animate-spin" /> : <Wand2 size={14} />}{l}
                            </Button>
                        ))}
                    </div>
                    {error && <p role="alert" className="mt-3 text-sm text-danger">{error}</p>}
                </div>
            )}
        </>
    );
}

/* ----------------------------------------------------------------- attributes */
function Attributes({ attributes, data, setData }) {
    const sel = data.attribute_values;
    const toggleAttr = (id, on) => {
        const next = { ...sel };
        if (on) next[id] = next[id] ?? [];
        else delete next[id];
        setData('attribute_values', next);
    };
    const toggleValue = (attrId, valueId) => {
        const cur = sel[attrId] ?? [];
        setData('attribute_values', { ...sel, [attrId]: cur.includes(valueId) ? cur.filter((v) => v !== valueId) : [...cur, valueId] });
    };

    return (
        <Section title="Product Attributes" hint="Select attributes and their values to use for variations (e.g. Size, Color)">
            {attributes.length === 0 ? (
                <p className="flex items-center gap-2 rounded-lg border border-warning/40 bg-warning/10 p-4 text-sm text-warning">
                    <AlertTriangle size={16} />No attributes available. <a href="/admin/attributes/create" className="underline">Create attributes first</a>
                </p>
            ) : (
                <div className="space-y-3">
                    {attributes.map((a) => {
                        const on = a.id in sel;
                        return (
                            <div key={a.id} className="rounded-lg border border-line p-4">
                                <label className="flex items-center gap-2 text-sm">
                                    <input type="checkbox" checked={on} onChange={(e) => toggleAttr(a.id, e.target.checked)} className="h-4 w-4 accent-[var(--primary)]" />
                                    <span className="font-medium">{a.name}</span>
                                    <Badge>{a.type_label}</Badge>
                                </label>
                                {on && (
                                    <div className="mt-3 flex flex-wrap gap-2 pl-6">
                                        {a.values.map((v) => (
                                            <label key={v.id} className="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-line px-3 py-1.5 text-sm hover:bg-surface-2">
                                                <input type="checkbox" checked={(sel[a.id] ?? []).includes(v.id)} onChange={() => toggleValue(a.id, v.id)} className="h-4 w-4 accent-[var(--primary)]" />
                                                {a.type === 'color' && v.color_code && <span className="h-4 w-4 rounded-full border border-line" style={{ background: v.color_code }} />}
                                                {v.value}
                                            </label>
                                        ))}
                                    </div>
                                )}
                            </div>
                        );
                    })}
                </div>
            )}
        </Section>
    );
}

/* ----------------------------------------------------------------- variations */
function Variations({ product }) {
    const [adding, setAdding] = useState(false);
    const [form, setForm] = useState({ sku: '', price: '', sale_price: '', quantity: '0' });
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState('');
    const reload = () => router.reload({ only: ['product'], preserveScroll: true });

    const start = () => {
        setForm({ sku: `${product.sku}-${product.variations.length + 1}`, price: '', sale_price: '', quantity: '0' });
        setError('');
        setAdding(true);
    };

    const save = async () => {
        if (!form.sku) return setError('SKU is required');
        setBusy(true);
        setError('');
        try {
            await jsonRequest(`/admin/products/${product.id}/variations`, { method: 'POST', body: form });
            setAdding(false);
            reload();
        } catch (e) {
            setError(e.message);
        } finally {
            setBusy(false);
        }
    };

    const remove = async (v) => {
        if (!window.confirm('Delete this variation?')) return;
        try {
            await jsonRequest(`/admin/products/${product.id}/variations/${v.id}`, { method: 'DELETE' });
            reload();
        } catch (e) {
            window.alert(e.message);
        }
    };

    return (
        <Section
            title="Product Variations"
            action={<Button tone="success" onClick={start}><Plus size={14} />Add Variation</Button>}
        >
            {product.variations.length === 0 && !adding && (
                <p className="rounded-lg border border-line bg-surface-2 p-4 text-center text-sm text-muted">No variations yet. Click "Add Variation" to create one.</p>
            )}
            <div className="space-y-3">
                {product.variations.map((v) => (
                    <div key={v.id} className="rounded-lg border border-line p-4">
                        <div className="flex items-center justify-between gap-3">
                            <p className="font-medium">{v.name}</p>
                            <div className="flex items-center gap-3">
                                <span className={`text-sm ${v.quantity > 0 ? 'text-success' : 'text-danger'}`}>Stock: {v.quantity}</span>
                                <a href={`/admin/products/${product.id}/variations/${v.id}/edit`} title="Edit variation" aria-label={`Edit ${v.name}`} className="text-primary"><Pencil size={16} /></a>
                                <button type="button" onClick={() => remove(v)} title="Delete variation" aria-label={`Delete ${v.name}`} className="text-danger"><Trash2 size={16} /></button>
                            </div>
                        </div>
                        <div className="mt-2 grid grid-cols-2 gap-2 text-sm md:grid-cols-4">
                            <span><span className="text-muted">SKU:</span> {v.sku}</span>
                            <span><span className="text-muted">Price:</span> {v.price}</span>
                            <span><span className="text-muted">Sale:</span> {v.sale_price ?? '-'}</span>
                            <span><Badge tone={v.is_active ? 'success' : 'danger'}>{v.is_active ? 'Active' : 'Inactive'}</Badge></span>
                        </div>
                        {v.attributes.length > 0 && (
                            <div className="mt-2 flex flex-wrap gap-1">{v.attributes.map((a) => <Badge key={a} tone="info">{a}</Badge>)}</div>
                        )}
                    </div>
                ))}
            </div>
            {adding && (
                <div className="mt-4 rounded-lg border-2 border-dashed border-primary/50 bg-primary-soft/20 p-4">
                    <h4 className="mb-3 text-sm font-medium">New Variation</h4>
                    <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
                        <Field label="SKU *"><Input value={form.sku} onChange={(e) => setForm({ ...form, sku: e.target.value })} /></Field>
                        <Field label="Price"><Input type="number" step="0.01" min="0" value={form.price} onChange={(e) => setForm({ ...form, price: e.target.value })} /></Field>
                        <Field label="Sale Price"><Input type="number" step="0.01" min="0" value={form.sale_price} onChange={(e) => setForm({ ...form, sale_price: e.target.value })} /></Field>
                        <Field label="Quantity"><Input type="number" min="0" value={form.quantity} onChange={(e) => setForm({ ...form, quantity: e.target.value })} /></Field>
                    </div>
                    {error && <p role="alert" className="mt-2 text-sm text-danger">{error}</p>}
                    <div className="mt-3 flex justify-end gap-2">
                        <Button tone="neutral" onClick={() => setAdding(false)}>Cancel</Button>
                        <Button onClick={save} disabled={busy}>{busy && <Loader2 size={14} className="animate-spin" />}Save Variation</Button>
                    </div>
                </div>
            )}
        </Section>
    );
}

/* ----------------------------------------------------------------------- tags */
function Tags({ tags, data, setData }) {
    const toggle = (id) => setData('tags', data.tags.includes(id) ? data.tags.filter((t) => t !== id) : [...data.tags, id]);
    return (
        <Section title="Tags">
            {tags.length === 0 ? (
                <p className="text-sm text-muted">No tags available. <a href="/admin/tags/create" className="text-primary hover:underline">Create one</a></p>
            ) : (
                <div className="flex flex-wrap gap-2">
                    {tags.map((t) => {
                        const on = data.tags.includes(t.id);
                        return (
                            <button
                                key={t.id}
                                type="button"
                                aria-pressed={on}
                                onClick={() => toggle(t.id)}
                                className="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-sm transition"
                                style={{ borderColor: `${t.color}55`, background: on ? `${t.color}22` : 'transparent' }}
                            >
                                <span className="h-3 w-3 rounded-full" style={{ background: t.color }} />
                                {t.name}
                                {on && <Check size={12} style={{ color: t.color }} />}
                            </button>
                        );
                    })}
                </div>
            )}
        </Section>
    );
}

/* ------------------------------------------------------------------- features */
function Features({ data, setData }) {
    const set = (i, patch) => setData('features', data.features.map((f, n) => (n === i ? { ...f, ...patch } : f)));
    return (
        <Section
            title="Product Features"
            hint="Key specifications that appear in a separate tab on the product page"
            action={<Button onClick={() => setData('features', [...data.features, { name: '', value: '' }])}><Plus size={14} />Add Feature</Button>}
        >
            {data.features.length === 0 ? (
                <div className="rounded-lg border-2 border-dashed border-line p-6 text-center text-sm text-muted">
                    No features added yet. Examples: Color — Phantom Blue, Storage — 256GB, Warranty — 2 years.
                </div>
            ) : (
                <div className="space-y-3">
                    {data.features.map((f, i) => (
                        <div key={i} className="flex items-end gap-3 rounded-lg border border-line bg-surface-2 p-3">
                            <div className="grid flex-1 gap-3 md:grid-cols-2">
                                <Field label="Feature Name"><Input value={f.name} onChange={(e) => set(i, { name: e.target.value })} placeholder="e.g. Color, Storage" /></Field>
                                <Field label="Feature Value"><Input value={f.value} onChange={(e) => set(i, { value: e.target.value })} placeholder="e.g. Phantom Blue, 256GB" /></Field>
                            </div>
                            <button type="button" onClick={() => setData('features', data.features.filter((_, n) => n !== i))} aria-label="Remove feature" className="mb-2 text-danger"><Trash2 size={16} /></button>
                        </div>
                    ))}
                </div>
            )}
        </Section>
    );
}

/* ---------------------------------------------------------------- cross-sell */
function CrossSell({ productId, data, setData, selected, setSelected }) {
    const [q, setQ] = useState('');
    const [results, setResults] = useState([]);
    const [busy, setBusy] = useState(false);
    const [openList, setOpenList] = useState(false);
    const timer = useRef(null);

    useEffect(() => {
        clearTimeout(timer.current);
        if (q.trim().length < 2) { setResults([]); return; }
        timer.current = setTimeout(async () => {
            setBusy(true);
            try {
                setResults(await jsonRequest(`/admin/products/search?q=${encodeURIComponent(q)}&exclude=${productId}`));
                setOpenList(true);
            } catch { setResults([]); }
            setBusy(false);
        }, 300);
        return () => clearTimeout(timer.current);
    }, [q]);

    const add = (p) => {
        if (!selected.some((s) => s.id === p.id)) {
            setSelected([...selected, p]);
            setData('cross_sell_products', [...data.cross_sell_products, p.id]);
        }
        setOpenList(false);
        setQ('');
    };
    const remove = (id) => {
        setSelected(selected.filter((s) => s.id !== id));
        setData('cross_sell_products', data.cross_sell_products.filter((x) => x !== id));
    };

    return (
        <Section title="Cross-Sell Products" hint="Complementary products recommended on this product's page">
            <div className="relative mb-4">
                <Search size={16} className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
                <Input value={q} onChange={(e) => setQ(e.target.value)} onFocus={() => setOpenList(true)} placeholder="Search products by name or SKU…" className="pl-9" aria-label="Search cross-sell products" />
                {busy && <Loader2 size={16} className="absolute right-3 top-1/2 -translate-y-1/2 animate-spin text-muted" />}
                {openList && q.trim().length >= 2 && !busy && (
                    <div className="absolute z-30 mt-1 max-h-64 w-full overflow-y-auto rounded-lg border border-line bg-surface shadow-lg">
                        {results.length === 0 && <p className="p-4 text-center text-sm text-muted">No products found</p>}
                        {results.map((r) => (
                            <button key={r.id} type="button" onClick={() => add(r)} className="flex w-full items-center gap-3 border-b border-line p-3 text-left last:border-0 hover:bg-surface-2">
                                <img src={r.image} alt="" className="h-10 w-10 rounded object-cover" />
                                <span className="min-w-0 flex-1"><span className="block truncate text-sm font-medium">{r.name}</span><span className="text-xs text-muted">{r.price}</span></span>
                                <Plus size={16} className="text-primary" />
                            </button>
                        ))}
                    </div>
                )}
            </div>
            {selected.length === 0 ? (
                <p className="py-4 text-center text-sm text-muted"><ShoppingBasket size={24} className="mx-auto mb-1 opacity-50" />No cross-sell products selected</p>
            ) : (
                <div className="space-y-2">
                    {selected.map((p) => (
                        <div key={p.id} className="flex items-center gap-3 rounded-lg bg-surface-2 p-3">
                            <img src={p.image} alt="" className="h-12 w-12 rounded object-cover" />
                            <span className="min-w-0 flex-1"><span className="block truncate text-sm font-medium">{p.name}</span><span className="text-xs text-muted">{p.price}</span></span>
                            <button type="button" onClick={() => remove(p.id)} aria-label={`Remove ${p.name}`} className="text-danger"><X size={16} /></button>
                        </div>
                    ))}
                </div>
            )}
        </Section>
    );
}

/* -------------------------------------------------------------- digital files */
function DigitalFiles({ data, setData, existing = [] }) {
    const [adding, setAdding] = useState(false);
    const [form, setForm] = useState(blankFile);
    const [error, setError] = useState('');
    const fileRef = useRef(null);
    const deleted = data.delete_digital_files;

    const confirmAdd = () => {
        if (!form.name) return setError('Please enter a display name');
        if (form.file_type === 'upload' && !form.file) return setError('Please select a file to upload');
        if (form.file_type === 'url' && !form.file_url) return setError('Please enter a file URL');
        setData('new_digital_files', [...data.new_digital_files, form]);
        setAdding(false);
        setForm(blankFile);
        setError('');
    };

    return (
        <Section
            title="Digital Files"
            hint="Upload files or add URLs for customers to download after purchase"
            action={<Button onClick={() => { setAdding(true); setForm(blankFile); setError(''); }}><Plus size={14} />Add File</Button>}
        >
            <div className="space-y-3">
                {existing.filter((f) => !deleted.includes(f.id)).map((f) => (
                    <div key={f.id} className="flex items-center justify-between gap-3 rounded-lg border border-line bg-surface-2 p-4">
                        <div className="flex min-w-0 items-center gap-3">
                            <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-soft/50 text-primary">{f.file_type === 'url' ? <Link2 size={18} /> : <Download size={18} />}</span>
                            <div className="min-w-0">
                                <p className="truncate font-medium">{f.name}</p>
                                <p className="truncate text-xs text-muted">{f.file_type === 'upload' ? `${f.file_name} · ${f.size}` : f.file_url}</p>
                            </div>
                        </div>
                        <div className="flex shrink-0 items-center gap-2">
                            <Badge tone={f.is_active ? 'success' : 'danger'}>{f.is_active ? 'Active' : 'Inactive'}</Badge>
                            <button type="button" onClick={() => setData('delete_digital_files', [...deleted, f.id])} aria-label={`Delete ${f.name}`} className="text-danger"><Trash2 size={16} /></button>
                        </div>
                    </div>
                ))}
                {deleted.length > 0 && (
                    <p className="text-xs text-warning">{deleted.length} file(s) will be deleted when you save.
                        {' '}<button type="button" onClick={() => setData('delete_digital_files', [])} className="underline">Undo</button></p>
                )}
                {data.new_digital_files.map((f, i) => (
                    <div key={i} className="flex items-center justify-between gap-3 rounded-lg border border-primary/40 bg-primary-soft/20 p-4">
                        <div className="min-w-0">
                            <p className="truncate font-medium">{f.name}</p>
                            <p className="flex items-center gap-1 truncate text-xs text-muted">
                                {f.file_type === 'upload' ? f.file?.name : f.file_url}
                                <Clock size={12} className="ml-2" />Will be saved with product
                            </p>
                        </div>
                        <button type="button" onClick={() => setData('new_digital_files', data.new_digital_files.filter((_, n) => n !== i))} aria-label={`Remove ${f.name}`} className="text-danger"><X size={16} /></button>
                    </div>
                ))}
                {!adding && existing.length === 0 && data.new_digital_files.length === 0 && (
                    <div className="rounded-lg border-2 border-dashed border-line p-8 text-center text-sm text-muted">
                        <Cloud size={32} className="mx-auto mb-2 opacity-50" />No digital files yet. Click "Add File" to upload.
                    </div>
                )}
            </div>

            {adding && (
                <div className="mt-4 space-y-4 rounded-lg border-2 border-dashed border-primary/50 bg-primary-soft/20 p-4">
                    <h4 className="text-sm font-medium">Add Digital File</h4>
                    <Field label="Display Name *"><Input value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} placeholder="e.g. Product Manual PDF" /></Field>
                    <div className="grid grid-cols-2 gap-3" role="radiogroup" aria-label="File source">
                        {[['upload', 'Upload File', 'From your computer'], ['url', 'External URL', 'Link to external file']].map(([id, l, s]) => (
                            <button key={id} type="button" role="radio" aria-checked={form.file_type === id} onClick={() => setForm({ ...form, file_type: id })}
                                className={`rounded-lg border-2 p-3 text-center ${form.file_type === id ? 'border-primary bg-primary-soft/40' : 'border-line hover:bg-surface-2'}`}>
                                <div className="text-sm font-medium">{l}</div><div className="text-xs text-muted">{s}</div>
                            </button>
                        ))}
                    </div>
                    {form.file_type === 'upload' ? (
                        <Field label="File *" hint="Max file size: 500MB. PDF, ZIP, RAR, MP3, MP4, etc.">
                            <input
                                ref={fileRef}
                                type="file"
                                onChange={(e) => {
                                    const file = e.target.files[0];
                                    if (file) setForm((f) => ({ ...f, file, name: f.name || file.name.replace(/\.[^/.]+$/, '') }));
                                }}
                                className="w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm"
                            />
                        </Field>
                    ) : (
                        <Field label="File URL *" hint="Direct link to the downloadable file (Dropbox, Google Drive, S3…)">
                            <Input type="url" value={form.file_url} onChange={(e) => setForm({ ...form, file_url: e.target.value })} placeholder="https://example.com/file.pdf" />
                        </Field>
                    )}
                    <div className="grid gap-3 md:grid-cols-2">
                        <Field label="Download Limit" hint="Leave empty for unlimited"><Input type="number" min="1" value={form.download_limit} onChange={(e) => setForm({ ...form, download_limit: e.target.value })} placeholder="Unlimited" /></Field>
                        <Field label="Expiry (Days)" hint="Days after purchase"><Input type="number" min="1" value={form.download_expiry_days} onChange={(e) => setForm({ ...form, download_expiry_days: e.target.value })} placeholder="Never expires" /></Field>
                    </div>
                    {error && <p role="alert" className="text-sm text-danger">{error}</p>}
                    <div className="flex justify-end gap-2">
                        <Button tone="neutral" onClick={() => setAdding(false)}>Cancel</Button>
                        <Button onClick={confirmAdd}><Plus size={14} />Add to List</Button>
                    </div>
                </div>
            )}
        </Section>
    );
}

/* --------------------------------------------------------------- translations */
function Translations({ data, setData, options, product }) {
    const langs = Object.entries(options.languages ?? {});
    const [tab, setTab] = useState(options.defaultLocale);
    const [busy, setBusy] = useState(false);
    if (langs.length <= 1) return null;

    const t = (code) => data.translations[code] ?? {};
    const set = (code, k, v) => setData('translations', { ...data.translations, [code]: { ...t(code), [k]: v } });

    const autoTranslate = async (code) => {
        const fields = [['name', data.name], ['short_description', data.short_description], ['description', data.description]].filter(([, v]) => v);
        if (fields.length === 0) return window.alert('Please fill in the default language content first.');
        setBusy(true);
        try {
            const next = { ...t(code) };
            await Promise.all(fields.map(async ([k, text]) => {
                const res = await jsonRequest('/api/translate', { method: 'POST', body: { text, target: code, source: options.defaultLocale } });
                if (res.success && res.translated) next[k] = res.translated;
            }));
            setData('translations', { ...data.translations, [code]: next });
        } catch {
            window.alert('Translation failed. Please try again.');
        }
        setBusy(false);
    };

    return (
        <Section title="Product Translations" action={<span className="text-sm text-muted">{langs.length} languages available</span>}>
            <div className="mb-4 flex flex-wrap gap-1 border-b border-line" role="tablist">
                {langs.map(([code, name]) => (
                    <button key={code} type="button" role="tab" aria-selected={tab === code} onClick={() => setTab(code)}
                        className={`rounded-t-lg px-4 py-2 text-sm font-medium ${tab === code ? 'border-b-2 border-primary bg-primary-soft/30 text-primary' : 'text-muted hover:bg-surface-2'}`}>
                        {name}
                        {code === options.defaultLocale ? <span className="ml-1 text-xs text-muted">(Default)</span>
                            : product?.translations?.[code] && <Check size={12} className="ml-1 inline text-success" />}
                    </button>
                ))}
            </div>
            {tab === options.defaultLocale ? (
                <p className="rounded-lg bg-surface-2 p-4 text-sm text-muted">This is the default language. Edit the main product fields for {options.languages[tab]} content.</p>
            ) : (
                <div className="space-y-4">
                    <p className="rounded-lg bg-primary-soft/30 p-3 text-sm text-primary">Enter {options.languages[tab]} translations below. Leave empty to use the default language.</p>
                    <Field label={`Product Name (${options.languages[tab]})`}><Input value={t(tab).name ?? ''} onChange={(e) => set(tab, 'name', e.target.value)} placeholder={data.name} /></Field>
                    <Field label="Short Description"><Textarea rows={2} value={t(tab).short_description ?? ''} onChange={(e) => set(tab, 'short_description', e.target.value)} /></Field>
                    <Field label="Full Description"><Textarea rows={5} value={t(tab).description ?? ''} onChange={(e) => set(tab, 'description', e.target.value)} /></Field>
                    <div className="grid gap-4 md:grid-cols-2">
                        <Field label="Meta Title"><Input value={t(tab).meta_title ?? ''} onChange={(e) => set(tab, 'meta_title', e.target.value)} /></Field>
                        <Field label="Meta Keywords"><Input value={t(tab).meta_keywords ?? ''} onChange={(e) => set(tab, 'meta_keywords', e.target.value)} /></Field>
                    </div>
                    <Field label="Meta Description"><Textarea rows={2} value={t(tab).meta_description ?? ''} onChange={(e) => set(tab, 'meta_description', e.target.value)} /></Field>
                    {options.hasAiKey && (
                        <Button tone="neutral" onClick={() => autoTranslate(tab)} disabled={busy}>
                            {busy ? <Loader2 size={14} className="animate-spin" /> : <Wand2 size={14} />}Auto-translate from {options.languages[options.defaultLocale] ?? 'English'}
                        </Button>
                    )}
                </div>
            )}
        </Section>
    );
}

/* ------------------------------------------------------------------ the form */
export default function ProductForm({ mode, product, categories, brands, tags, attributes, options }) {
    const edit = mode === 'edit';
    const p = product ?? {};
    const str = (v, d = '') => (v === null || v === undefined ? d : String(v));

    const form = useForm({
        product_type: p.product_type ?? 'simple',
        name: str(p.name), sku: str(p.sku), barcode: str(p.barcode),
        category_id: str(p.category_id), brand_id: str(p.brand_id),
        short_description: str(p.short_description), description: str(p.description),
        price: str(p.price), sale_price: str(p.sale_price), cost_price: str(p.cost_price),
        sale_start_date: str(p.sale_start_date), sale_end_date: str(p.sale_end_date),
        quantity: str(p.quantity, '0'), low_stock_threshold: str(p.low_stock_threshold, '5'),
        manage_stock: p.manage_stock ?? true,
        is_active: p.is_active ?? true, is_featured: p.is_featured ?? false,
        is_virtual: p.is_virtual ?? false, is_downloadable: p.is_downloadable ?? false,
        meta_title: str(p.meta_title), meta_description: str(p.meta_description), meta_keywords: str(p.meta_keywords),
        image: p.image ?? '', gallery: p.gallery ?? [], gallery_present: true,
        features: p.features ?? [],
        tags: p.tags ?? [],
        attribute_values: p.attribute_values ?? {},
        cross_sell_products: (p.cross_sell ?? []).map((c) => c.id),
        translations: p.translations ?? {},
        new_digital_files: [],
        delete_digital_files: [],
    });
    const { data, setData, errors, processing } = form;
    const [crossSell, setCrossSell] = useState(p.cross_sell ?? []);
    const [showSchedule, setShowSchedule] = useState(!!(p.sale_start_date || p.sale_end_date));

    const type = data.product_type;
    const f = (k) => ({ value: data[k], onChange: (e) => setData(k, e.target.value) });
    const showDigitalFiles = options.digitalProducts && (type === 'digital' || (edit && data.is_downloadable));

    const submit = (e) => {
        e.preventDefault();
        const opts = { forceFormData: true, queryStringArrayFormat: 'indices', preserveScroll: true };
        if (edit) {
            // Multipart PUT is not parsed by PHP, so spoof it through POST.
            form.transform((d) => ({ ...d, _method: 'put' }));
            form.post(`/admin/products/${p.id}`, opts);
        } else {
            form.transform((d) => d);
            form.post('/admin/products', opts);
        }
    };

    const destroy = () => {
        if (window.confirm('Are you sure? This cannot be undone.')) router.delete(`/admin/products/${p.id}`);
    };

    return (
        <form onSubmit={submit} className="space-y-5">
            <TypeSelector value={type} onChange={(v) => setData('product_type', v)} digital={options.digitalProducts} />

            {edit && <Translations data={data} setData={setData} options={options} product={p} />}

            <div className="grid gap-5 lg:grid-cols-3">
                <div className="space-y-5 lg:col-span-2">
                    <Card>
                        <div className="flex flex-wrap items-center justify-between gap-3 border-b border-line p-4 md:px-6">
                            <h3 className="text-sm font-semibold md:text-base">Product Information</h3>
                            <AiAssistant data={data} setData={setData} categories={categories} aiState={options.hasAiKey} />
                        </div>
                        <div className="space-y-4 p-4 md:p-6">
                            <Field label="Product Name *" error={errors.name}><Input {...f('name')} required /></Field>
                            <div className="grid gap-4 md:grid-cols-2">
                                <Field label="SKU *" error={errors.sku}><Input {...f('sku')} required /></Field>
                                <Field label="Barcode (UPC/EAN)" error={errors.barcode}><Input {...f('barcode')} /></Field>
                            </div>
                            <div className="grid gap-4 md:grid-cols-2">
                                <Field label="Category *" error={errors.category_id}>
                                    <Select {...f('category_id')} required>
                                        <option value="">Select Category</option>
                                        {categories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                                    </Select>
                                </Field>
                                <Field label="Brand" error={errors.brand_id}>
                                    <Select {...f('brand_id')}>
                                        <option value="">No Brand</option>
                                        {brands.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
                                    </Select>
                                </Field>
                            </div>
                            <Field label="Short Description" error={errors.short_description}><Textarea rows={2} {...f('short_description')} /></Field>
                            <Field label="Description *" error={errors.description}>
                                <RichEditor value={data.description} onChange={(v) => setData('description', v)} placeholder="Write your product description…" height={360} />
                            </Field>
                        </div>
                    </Card>

                    <Section title="Pricing">
                        <div className="grid gap-4 md:grid-cols-3">
                            <Field label="Price *" error={errors.price}><Input type="number" step="0.01" min="0" {...f('price')} required /></Field>
                            <Field label="Sale Price" error={errors.sale_price}><Input type="number" step="0.01" min="0" {...f('sale_price')} /></Field>
                            <Field label="Cost Price" hint="For profit calculation" error={errors.cost_price}><Input type="number" step="0.01" min="0" {...f('cost_price')} /></Field>
                        </div>
                        <div className="mt-4 rounded-lg border border-warning/40 bg-warning/10 p-4">
                            <div className="flex items-center justify-between gap-3">
                                <div>
                                    <h4 className="flex items-center gap-2 text-sm font-medium"><Clock size={14} />Sale Schedule</h4>
                                    <p className="text-xs text-muted">Set start and end dates for the sale price</p>
                                </div>
                                <button type="button" onClick={() => setShowSchedule((s) => !s)} className="text-sm text-primary" aria-expanded={showSchedule}>{showSchedule ? 'Hide' : 'Show'}</button>
                            </div>
                            {showSchedule && (
                                <div className="mt-4 grid gap-4 md:grid-cols-2">
                                    <Field label="Sale Start Date" error={errors.sale_start_date}><Input type="datetime-local" {...f('sale_start_date')} /></Field>
                                    <Field label="Sale End Date" error={errors.sale_end_date}><Input type="datetime-local" {...f('sale_end_date')} /></Field>
                                </div>
                            )}
                        </div>
                    </Section>

                    {type === 'simple' && (
                        <Section title="Inventory">
                            <div className="grid gap-4 md:grid-cols-3">
                                <Field label="Quantity *" error={errors.quantity}><Input type="number" min="0" {...f('quantity')} required /></Field>
                                <Field label="Low Stock Threshold" error={errors.low_stock_threshold}><Input type="number" min="0" {...f('low_stock_threshold')} /></Field>
                                <div className="flex items-end pb-2"><Check2 label="Track Inventory" checked={data.manage_stock} onChange={(v) => setData('manage_stock', v)} /></div>
                            </div>
                        </Section>
                    )}

                    {type === 'digital' && (
                        <Section title="Unlimited Availability" tone="info">
                            <p className="flex items-start gap-3 text-sm text-muted"><InfinityIcon size={20} className="shrink-0 text-info" />Digital products don't require inventory tracking. They are always available for purchase when active.</p>
                        </Section>
                    )}

                    {type === 'variable' && <Attributes attributes={attributes} data={data} setData={setData} />}
                    {type === 'variable' && edit && <Variations product={p} />}
                    {type === 'variable' && !edit && (
                        <p className="flex items-center gap-2 rounded-lg border border-info/40 bg-info/10 p-4 text-sm text-info"><Layers size={16} />After creating the product, you can manage variations from the edit page.</p>
                    )}

                    <Tags tags={tags} data={data} setData={setData} />
                    <Features data={data} setData={setData} />
                    {edit && <CrossSell productId={p.id} data={data} setData={setData} selected={crossSell} setSelected={setCrossSell} />}
                    {showDigitalFiles && <DigitalFiles data={data} setData={setData} existing={p.digital_files ?? []} />}

                    <Section title="SEO Settings">
                        <div className="space-y-4">
                            <Field label="Meta Title" hint="Leave empty to use product name. Max 60 characters."><Input maxLength={60} {...f('meta_title')} /></Field>
                            <Field label="Meta Description" hint="Leave empty to use short description. Max 160 characters."><Textarea rows={2} maxLength={160} {...f('meta_description')} /></Field>
                            <Field label="Meta Keywords"><Input placeholder="keyword1, keyword2, keyword3" {...f('meta_keywords')} /></Field>
                        </div>
                    </Section>
                </div>

                <div className="space-y-5">
                    <Section title="Product Images">
                        <div className="space-y-5">
                            <div>
                                <p className="mb-2 text-sm font-medium">Main Image{edit ? '' : ' *'}</p>
                                <MediaPicker value={data.image} onChange={(v) => setData('image', v)} collection="products" label={data.image ? 'Change Main Image' : 'Select Main Image'} />
                                {errors.image && <p className="mt-1 text-xs text-danger">{errors.image}</p>}
                            </div>
                            <div>
                                <p className="mb-2 text-sm font-medium">Gallery Images</p>
                                <MediaPicker multiple value={data.gallery} onChange={(v) => setData('gallery', v)} collection="products" label="Select Gallery Images" />
                            </div>
                        </div>
                    </Section>

                    <Section title="Status">
                        <div className="space-y-3">
                            <Check2 label="Active" checked={data.is_active} onChange={(v) => setData('is_active', v)} />
                            <Check2 label="Featured" checked={data.is_featured} onChange={(v) => setData('is_featured', v)} />
                            {edit && <Check2 label="Virtual (No shipping)" checked={data.is_virtual} onChange={(v) => setData('is_virtual', v)} />}
                            {edit && options.digitalProducts && <Check2 label="Downloadable (Digital Product)" checked={data.is_downloadable} onChange={(v) => setData('is_downloadable', v)} />}
                        </div>
                    </Section>

                    <Button type="submit" className="w-full py-3" disabled={processing}>
                        {processing && <Loader2 size={16} className="animate-spin" />}{edit ? 'Update Product' : 'Create Product'}
                    </Button>
                    {Object.keys(errors).length > 0 && (
                        <p role="alert" className="rounded-lg border border-danger/40 bg-danger/10 p-3 text-sm text-danger">
                            Please fix the highlighted fields: {Object.keys(errors).map((k) => titleCase(k.split('.')[0])).filter((v, i, a) => a.indexOf(v) === i).join(', ')}.
                        </p>
                    )}

                    {edit && (
                        <Card className="p-4 md:p-6">
                            <h3 className="mb-1 text-sm font-semibold text-danger">Danger Zone</h3>
                            <p className="mb-4 text-xs text-muted">Permanently delete this product and all its variations.</p>
                            <Button tone="neutral" className="w-full !border-danger !text-danger" onClick={destroy}><Trash2 size={16} />Delete Product</Button>
                        </Card>
                    )}
                </div>
            </div>
        </form>
    );
}
