import { useRef, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Check, Copy, Download, Search, Trash2, Upload, X } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { Button, Card, CardHeader, EmptyState, Field, Input, Pagination, Select } from '../../../Components/ui';
import { jsonRequest } from '../../../lib/http';

const MAX_BYTES = 10 * 1024 * 1024;
const TYPES = /^image\/(jpeg|png|gif|webp|svg\+xml)$/;

const CloseButton = ({ onClick, label }) => <button type="button" onClick={onClick} aria-label={label} className="text-muted hover:text-ink"><X size={18} /></button>;

function UploadModal({ collections, defaultCollection, onClose, onDone, notify }) {
    const [collection, setCollection] = useState(defaultCollection || 'general');
    const [busy, setBusy] = useState(false);
    const [over, setOver] = useState(false);
    const input = useRef(null);

    const upload = async (list) => {
        const files = [...list];
        const bad = files.filter((f) => f.size > MAX_BYTES || !TYPES.test(f.type));
        if (bad.length) notify(`Not uploaded (images only, max 10MB): ${bad.map((f) => f.name).join(', ')}`, true);
        const ok = files.filter((f) => !bad.includes(f));
        if (!ok.length) return;
        const fd = new FormData();
        ok.forEach((f) => fd.append('files[]', f));
        fd.append('collection', collection);
        setBusy(true);
        try {
            const res = await jsonRequest('/admin/media/bulk-upload', { method: 'POST', formData: fd });
            notify(res?.message ?? `${ok.length} file(s) uploaded`);
            onDone();
        } catch (e) { notify(e.message, true); } finally { setBusy(false); }
    };

    const options = [...new Set(['general', 'products', 'categories', 'banners', 'blog', ...collections])];
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" role="dialog" aria-modal="true" aria-label="Upload files">
            <Card className="w-full max-w-lg">
                <CardHeader title="Upload Files"><CloseButton onClick={onClose} label="Close" /></CardHeader>
                <div className="space-y-4 p-4 md:p-6">
                    <Field label="Collection"><Select value={collection} onChange={(e) => setCollection(e.target.value)}>{options.map((c) => <option key={c} value={c}>{c}</option>)}</Select></Field>
                    <div
                        onDragOver={(e) => { e.preventDefault(); setOver(true); }} onDragLeave={() => setOver(false)}
                        onDrop={(e) => { e.preventDefault(); setOver(false); upload(e.dataTransfer.files); }}
                        className={`rounded-lg border-2 border-dashed p-8 text-center ${over ? 'border-primary bg-primary/5' : 'border-line'}`}
                    >
                        <Upload className="mx-auto mb-2 text-muted" />
                        <p className="text-sm">{busy ? 'Uploading…' : 'Drag & drop images here, or'}</p>
                        <Button className="mt-2" disabled={busy} onClick={() => input.current?.click()}>Browse Files</Button>
                        <input ref={input} type="file" multiple accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml" className="hidden" onChange={(e) => { upload(e.target.files); e.target.value = ''; }} />
                        <p className="mt-3 text-xs text-muted">Max file size: 10MB</p>
                    </div>
                </div>
            </Card>
        </div>
    );
}

const Row = ({ k, children }) => <div className="flex justify-between gap-3 text-sm"><dt className="text-muted">{k}</dt><dd className="break-all text-right">{children}</dd></div>;

function Detail({ item, collections, onClose, onChanged, notify }) {
    const [name, setName] = useState(item.name ?? '');
    const [collection, setCollection] = useState(item.collection ?? 'general');
    const [copied, setCopied] = useState(false);
    const fullUrl = new URL(item.url, window.location.origin).href;
    const options = [...new Set([collection, ...collections])];

    const save = async () => {
        try { await jsonRequest(`/admin/media/${item.id}`, { method: 'PUT', body: { name, collection } }); notify('Media updated'); onChanged(); }
        catch (e) { notify(e.message, true); }
    };
    const remove = async () => {
        if (!window.confirm(`Delete "${item.name}"? This cannot be undone.`)) return;
        try { await jsonRequest(`/admin/media/${item.id}`, { method: 'DELETE' }); notify('Media deleted'); onClose(); onChanged(); }
        catch (e) { notify(e.message, true); }
    };
    const copy = async () => { try { await navigator.clipboard.writeText(fullUrl); setCopied(true); setTimeout(() => setCopied(false), 1500); } catch { notify('Copy failed', true); } };

    return (
        <Card className="self-start lg:sticky lg:top-4">
            <CardHeader title="Details"><CloseButton onClick={onClose} label="Close details" /></CardHeader>
            <div className="space-y-4 p-4">
                <div className="flex justify-center rounded-lg bg-surface-2 p-2"><img src={item.url} alt={item.name} className="max-h-56 object-contain" /></div>
                <Field label="Name"><Input value={name} onChange={(e) => setName(e.target.value)} /></Field>
                <Field label="Collection"><Select value={collection} onChange={(e) => setCollection(e.target.value)}>{options.map((c) => <option key={c} value={c}>{c}</option>)}</Select></Field>
                <dl className="space-y-1.5">
                    <Row k="File Name">{item.file_name}</Row><Row k="Size">{item.size}</Row><Row k="Type">{item.mime_type}</Row><Row k="Uploaded">{item.created_at}</Row>
                </dl>
                <div className="flex gap-2">
                    <Input readOnly value={fullUrl} aria-label="File URL" onFocus={(e) => e.target.select()} />
                    <Button tone="neutral" onClick={copy} aria-label="Copy URL">{copied ? <Check size={16} /> : <Copy size={16} />}</Button>
                </div>
                <div className="flex flex-wrap gap-2">
                    <Button onClick={save}>Save</Button>
                    <a href={item.url} download={item.file_name} className="inline-flex items-center gap-2 rounded-lg border border-line px-3 py-2 text-sm hover:bg-surface-2"><Download size={16} />Download</a>
                    <Button tone="danger" onClick={remove}><Trash2 size={16} />Delete</Button>
                </div>
            </div>
        </Card>
    );
}

export default function Index({ media, collections, filters }) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [selected, setSelected] = useState([]);
    const [viewing, setViewing] = useState(null);
    const [uploading, setUploading] = useState(false);
    const [msg, setMsg] = useState(null);

    const notify = (text, error = false) => { setMsg({ text, error }); setTimeout(() => setMsg(null), 5000); };
    const visit = (params) => router.get('/admin/media', { ...filters, ...params }, { preserveState: true, replace: true, onSuccess: () => setSelected([]) });
    const refresh = () => router.reload({ onSuccess: () => setSelected([]) });
    const toggle = (id) => setSelected((s) => (s.includes(id) ? s.filter((x) => x !== id) : [...s, id]));
    const bulkDelete = async () => {
        if (!window.confirm(`Delete ${selected.length} selected file(s)? This cannot be undone.`)) return;
        try {
            await jsonRequest('/admin/media/bulk-delete', { method: 'POST', body: { ids: selected } });
            notify(`${selected.length} file(s) deleted`);
            if (selected.includes(viewing?.id)) setViewing(null);
            refresh();
        } catch (e) { notify(e.message, true); }
    };

    // Always show the latest server copy of the item being viewed (after a rename/collection change)
    const current = viewing && (media.data.find((m) => m.id === viewing.id) ?? viewing);

    return (
        <AdminLayout title="Media Library">
            <Head title="Media Library" />
            <p className="mb-4 text-sm text-muted">Upload and manage the images used across your store.</p>
            {msg && <div role="status" className={`mb-4 rounded-lg px-4 py-2 text-sm ${msg.error ? 'bg-danger/10 text-danger' : 'bg-success/10 text-success'}`}>{msg.text}</div>}

            <div className="mb-4 flex flex-wrap items-center gap-2">
                <form onSubmit={(e) => { e.preventDefault(); visit({ search }); }} className="relative min-w-52 flex-1 sm:max-w-xs">
                    <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
                    <Input className="pl-9" placeholder="Search files…" aria-label="Search files" value={search} onChange={(e) => setSearch(e.target.value)} />
                </form>
                <Select aria-label="Collection" className="w-auto" value={filters.collection ?? ''} onChange={(e) => visit({ collection: e.target.value })}>
                    <option value="">All collections</option>{collections.map((c) => <option key={c} value={c}>{c}</option>)}
                </Select>
                <div className="ml-auto flex gap-2">
                    {selected.length > 0 && <Button tone="danger" onClick={bulkDelete}><Trash2 size={16} />Delete ({selected.length})</Button>}
                    <Button onClick={() => setUploading(true)}><Upload size={16} />Upload Files</Button>
                </div>
            </div>

            <div className={`grid gap-5 ${current ? 'lg:grid-cols-[1fr_20rem]' : ''}`}>
                <Card className="overflow-hidden">
                    {media.data.length === 0 ? <EmptyState>No media files. Upload your first image to get started.</EmptyState> : (
                        <div className="grid grid-cols-2 gap-3 p-4 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6">
                            {media.data.map((m) => (
                                <div key={m.id} className={`relative overflow-hidden rounded-lg border ${current?.id === m.id ? 'border-primary ring-2 ring-primary/30' : 'border-line'}`}>
                                    <input type="checkbox" aria-label={`Select ${m.name}`} checked={selected.includes(m.id)} onChange={() => toggle(m.id)} className="absolute left-2 top-2 z-10 h-4 w-4" />
                                    <button type="button" onClick={() => setViewing(m)} className="block w-full text-left">
                                        <div className="aspect-square bg-surface-2"><img src={m.thumb || m.url} alt={m.name} loading="lazy" className="h-full w-full object-cover" /></div>
                                        <div className="p-2"><p className="truncate text-xs font-medium">{m.name}</p><p className="truncate text-xs text-muted">{m.collection} · {m.size}</p></div>
                                    </button>
                                </div>
                            ))}
                        </div>
                    )}
                    <Pagination links={media.links} from={media.from} to={media.to} total={media.total} />
                </Card>
                {current && <Detail key={`${current.id}-${current.name}-${current.collection}`} item={current} collections={collections} onClose={() => setViewing(null)} onChanged={refresh} notify={notify} />}
            </div>

            <Card className="mt-5 p-4 text-sm">
                <p className="mb-1 font-medium">Recommended image sizes</p>
                <ul className="list-inside list-disc text-muted">
                    <li><strong>Product Images:</strong> 800x800px (1:1 ratio), max 2MB</li>
                    <li><strong>Category Images:</strong> 400x400px (1:1 ratio)</li>
                    <li><strong>Hero/Banner Images:</strong> 1920x800px</li>
                </ul>
            </Card>

            {uploading && <UploadModal collections={collections} defaultCollection={filters.collection} onClose={() => setUploading(false)} onDone={() => { setUploading(false); refresh(); }} notify={notify} />}
        </AdminLayout>
    );
}
