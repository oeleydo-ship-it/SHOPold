import { useEffect, useRef, useState } from 'react';
import { Check, ImagePlus, Loader2, Search, Upload, X } from 'lucide-react';
import { Button, Input } from './ui';
import { jsonRequest, storageUrl } from '../lib/http';

const MAX_BYTES = 2 * 1024 * 1024; // same safety limit as the old picker (PHP upload limits)

/**
 * Pick one or many images from the media library (or upload new ones).
 *  - single:   value is a path string, onChange(path)
 *  - multiple: value is an array of path strings, onChange(paths)
 * Stored values are library paths (e.g. "media/products/x.png"), which is what the backend expects.
 */
export default function MediaPicker({ value, onChange, multiple = false, collection = 'products', label }) {
    const [open, setOpen] = useState(false);
    const [items, setItems] = useState([]);
    const [selected, setSelected] = useState([]); // media objects chosen inside the modal
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);
    const [hasMore, setHasMore] = useState(false);
    const [loading, setLoading] = useState(false);
    const [uploading, setUploading] = useState(false);
    const [error, setError] = useState('');
    const fileInput = useRef(null);

    const paths = multiple ? (value ?? []) : value ? [value] : [];

    const load = async (nextPage = 1, term = search) => {
        setLoading(true);
        try {
            const q = new URLSearchParams({ collection, search: term, page: String(nextPage) });
            const data = await jsonRequest(`/admin/media/picker?${q}`);
            setItems((cur) => (nextPage === 1 ? data.media : [...cur, ...data.media]));
            setPage(nextPage);
            setHasMore(data.pagination.current_page < data.pagination.last_page);
        } catch (e) {
            setError(e.message);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        if (!open) return;
        setSelected([]);
        setError('');
        load(1, '');
        setSearch('');
    }, [open]);

    const isSelected = (m) => selected.some((s) => s.id === m.id);
    const toggle = (m) => {
        if (!multiple) return setSelected([m]);
        setSelected((cur) => (isSelected(m) ? cur.filter((s) => s.id !== m.id) : [...cur, m]));
    };

    const confirm = () => {
        const chosen = selected.map((m) => m.path);
        if (multiple) onChange([...new Set([...(value ?? []), ...chosen])]);
        else if (chosen[0]) onChange(chosen[0]);
        setOpen(false);
    };

    const upload = async (files) => {
        setError('');
        setUploading(true);
        for (const file of files) {
            if (file.size > MAX_BYTES) {
                setError(`${file.name} is too large (${(file.size / 1048576).toFixed(2)}MB). Maximum size is 2MB.`);
                continue;
            }
            const fd = new FormData();
            fd.append('file', file);
            fd.append('collection', collection);
            try {
                const data = await jsonRequest('/admin/media', { method: 'POST', formData: fd });
                if (data.media) {
                    setItems((cur) => [data.media, ...cur]);
                    setSelected((cur) => (multiple ? [...cur, data.media] : [data.media]));
                }
            } catch (e) {
                setError(e.message);
            }
        }
        setUploading(false);
        if (fileInput.current) fileInput.current.value = '';
    };

    const remove = (p) => onChange(multiple ? paths.filter((x) => x !== p) : '');

    return (
        <div>
            {paths.length > 0 && (
                <div className={`mb-3 grid gap-2 ${multiple ? 'grid-cols-3' : 'grid-cols-1'}`}>
                    {paths.map((p) => (
                        <div key={p} className="group relative aspect-square overflow-hidden rounded-lg border border-line bg-surface-2">
                            <img src={storageUrl(p)} alt="" className="h-full w-full object-cover" />
                            <button
                                type="button"
                                onClick={() => remove(p)}
                                aria-label="Remove image"
                                className="absolute right-1 top-1 flex h-6 w-6 items-center justify-center rounded-full bg-black/60 text-white opacity-0 transition group-hover:opacity-100 focus:opacity-100"
                            >
                                <X size={14} />
                            </button>
                        </div>
                    ))}
                </div>
            )}
            <Button tone="neutral" className="w-full" onClick={() => setOpen(true)}>
                <ImagePlus size={16} />{label ?? (multiple ? 'Select Images' : paths.length ? 'Change Image' : 'Select Image')}
            </Button>

            {open && (
                <div className="fixed inset-0 z-[80] flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-label="Media library">
                    <div className="absolute inset-0 bg-black/60" onClick={() => setOpen(false)} />
                    <div className="relative flex max-h-[90vh] w-full max-w-4xl flex-col rounded-card bg-surface shadow-xl">
                        <div className="flex items-center justify-between border-b border-line p-4">
                            <h4 className="font-semibold">Media Library</h4>
                            <button type="button" onClick={() => setOpen(false)} aria-label="Close" className="text-muted hover:text-ink"><X size={18} /></button>
                        </div>

                        <div className="flex flex-wrap items-center gap-3 border-b border-line p-4">
                            <form className="relative min-w-48 flex-1" onSubmit={(e) => { e.preventDefault(); load(1, search); }}>
                                <Search size={16} className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
                                <Input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Search media…" className="pl-9" aria-label="Search media" />
                            </form>
                            <input ref={fileInput} type="file" accept="image/*" multiple className="hidden" onChange={(e) => upload([...e.target.files])} />
                            <Button tone="neutral" onClick={() => fileInput.current?.click()} disabled={uploading}>
                                {uploading ? <Loader2 size={16} className="animate-spin" /> : <Upload size={16} />}Upload
                            </Button>
                        </div>

                        {error && <p className="border-b border-line bg-danger/10 px-4 py-2 text-sm text-danger" role="alert">{error}</p>}

                        <div className="flex-1 overflow-y-auto p-4">
                            {!loading && items.length === 0 && <p className="py-12 text-center text-sm text-muted">No images yet. Upload one to get started.</p>}
                            <div className="grid grid-cols-3 gap-3 sm:grid-cols-4 md:grid-cols-5">
                                {items.map((m) => (
                                    <button
                                        key={m.id}
                                        type="button"
                                        onClick={() => toggle(m)}
                                        aria-pressed={isSelected(m)}
                                        className={`relative aspect-square overflow-hidden rounded-lg border-2 ${isSelected(m) ? 'border-primary' : 'border-line'}`}
                                    >
                                        <img src={m.url} alt={m.name ?? ''} className="h-full w-full object-cover" loading="lazy" />
                                        {isSelected(m) && (
                                            <span className="absolute right-1 top-1 flex h-5 w-5 items-center justify-center rounded-full bg-primary text-on-primary"><Check size={12} /></span>
                                        )}
                                    </button>
                                ))}
                            </div>
                            {loading && <p className="py-6 text-center text-sm text-muted">Loading…</p>}
                            {hasMore && !loading && (
                                <div className="mt-4 text-center"><Button tone="neutral" onClick={() => load(page + 1)}>Load more</Button></div>
                            )}
                        </div>

                        <div className="flex items-center justify-between border-t border-line p-4">
                            <span className="text-sm text-muted">{selected.length} selected</span>
                            <div className="flex gap-2">
                                <Button tone="neutral" onClick={() => setOpen(false)}>Cancel</Button>
                                <Button onClick={confirm} disabled={selected.length === 0}>Select</Button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
