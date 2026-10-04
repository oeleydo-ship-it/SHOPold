import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { BarChart3, Check, Languages, Pencil, Search, Sparkles } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { Badge, Button, Card, Input, LinkButton, Pagination, Select } from '../../../Components/ui';
import { jsonRequest } from '../../../lib/http';

const TYPES = [['products', 'Products'], ['categories', 'Categories'], ['brands', 'Brands'], ['pages', 'Pages']];

export default function Index({ type, locale, search, locales, defaultName, localeName, items }) {
    const [q, setQ] = useState(search ?? '');
    const [selected, setSelected] = useState([]);
    const [busy, setBusy] = useState([]);
    const [msg, setMsg] = useState(null);
    const notify = (text, error = false) => { setMsg({ text, error }); setTimeout(() => setMsg(null), 6000); };
    const ids = items.data.map((i) => i.id);
    const allOn = ids.length > 0 && ids.every((id) => selected.includes(id));

    if (locales.length === 0) {
        return (
            <AdminLayout title="Translations">
                <Head title="Translations" />
                <Card className="p-10 text-center">
                    <Languages className="mx-auto mb-3 text-muted" size={36} />
                    <h3 className="font-semibold">No additional languages configured</h3>
                    <p className="mb-4 text-sm text-muted">Currently only {defaultName} is enabled. Add languages in the store settings first.</p>
                    <LinkButton href="/admin/settings">Open Settings</LinkButton>
                </Card>
            </AdminLayout>
        );
    }

    const go = (params) => router.get('/admin/translations', { type, locale, search: q, ...params }, { preserveState: true, onSuccess: () => setSelected([]) });
    const run = async (ids2, label) => {
        setBusy((b) => [...b, ...ids2]);
        try {
            const res = ids2.length === 1 && label === 'one'
                ? await jsonRequest(`/admin/translations/${type}/${ids2[0]}/auto-translate`, { method: 'POST', body: { locale } })
                : await jsonRequest('/admin/translations/bulk-auto-translate', { method: 'POST', body: { type, locale, ids: ids2 } });
            notify(res.translated !== undefined ? `${res.translated} item(s) translated` : 'Translated');
            setSelected([]);
            router.reload();
        } catch (e) { notify(e.message, true); } finally { setBusy((b) => b.filter((x) => !ids2.includes(x))); }
    };

    return (
        <AdminLayout title="Translations">
            <Head title="Translations" />
            <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                <p className="text-sm text-muted">Manage translations for your store content</p>
                <LinkButton href="/admin/translations/stats" tone="neutral"><BarChart3 size={16} />Statistics</LinkButton>
            </div>
            {msg && <div role="status" className={`mb-4 rounded-lg px-4 py-2 text-sm ${msg.error ? 'bg-danger/10 text-danger' : 'bg-success/10 text-success'}`}>{msg.text}</div>}

            <Card className="mb-4 p-4">
                <div className="flex flex-wrap items-center gap-2">
                    <Select aria-label="Content type" className="w-auto" value={type} onChange={(e) => go({ type: e.target.value, search: '' })}>{TYPES.map(([v, l]) => <option key={v} value={v}>{l}</option>)}</Select>
                    <Select aria-label="Target language" className="w-auto" value={locale} onChange={(e) => go({ locale: e.target.value })}>{locales.map((l) => <option key={l.code} value={l.code}>{l.name}</option>)}</Select>
                    <form onSubmit={(e) => { e.preventDefault(); go({ search: q }); }} className="relative min-w-52 flex-1 sm:max-w-xs">
                        <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
                        <Input className="pl-9" placeholder="Search by name…" aria-label="Search" value={q} onChange={(e) => setQ(e.target.value)} />
                    </form>
                    {selected.length > 0 && <Button className="ml-auto" disabled={busy.length > 0} onClick={() => run(selected, 'bulk')}><Sparkles size={16} />Auto-translate {selected.length} selected</Button>}
                </div>
            </Card>

            <Card className="overflow-hidden">
                {items.data.length === 0 ? <p className="p-10 text-center text-sm text-muted">No items found</p> : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-surface-2 text-left text-xs uppercase text-muted">
                                <tr>
                                    <th className="w-10 px-4 py-3"><input type="checkbox" aria-label="Select all" checked={allOn} onChange={() => setSelected(allOn ? [] : ids)} /></th>
                                    <th className="px-3 py-3">Original ({defaultName})</th><th className="px-3 py-3">Translation ({localeName})</th><th className="px-3 py-3">Status</th><th className="px-3 py-3" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-line">
                                {items.data.map((it) => (
                                    <tr key={it.id}>
                                        <td className="px-4 py-3"><input type="checkbox" aria-label={`Select ${it.name}`} checked={selected.includes(it.id)} onChange={() => setSelected((s) => (s.includes(it.id) ? s.filter((x) => x !== it.id) : [...s, it.id]))} /></td>
                                        <td className="px-3 py-3">
                                            <div className="flex items-center gap-3">
                                                {it.image && <img src={it.image} alt="" className="h-10 w-10 rounded object-cover" />}
                                                <div className="min-w-0"><p className="font-medium">{it.name}</p>{it.excerpt && <p className="text-xs text-muted">{it.excerpt}</p>}</div>
                                            </div>
                                        </td>
                                        <td className="px-3 py-3">{it.translated ? <div><p className="font-medium">{it.translation}</p>{it.translation_excerpt && <p className="text-xs text-muted">{it.translation_excerpt}</p>}</div> : <span className="text-muted">Not translated</span>}</td>
                                        <td className="px-3 py-3">{it.translated ? <Badge tone="success"><span className="inline-flex items-center gap-1"><Check size={12} />Done</span></Badge> : <Badge tone="warning">Pending</Badge>}</td>
                                        <td className="px-3 py-3">
                                            <div className="flex items-center justify-end gap-3">
                                                <button type="button" title="Auto-translate" aria-label={`Auto-translate ${it.name}`} disabled={busy.includes(it.id)} onClick={() => run([it.id], 'one')} className="text-primary disabled:opacity-40"><Sparkles size={18} /></button>
                                                <Link href={`/admin/translations/${type}/${it.id}/edit?locale=${locale}`} title="Edit" aria-label={`Edit translation of ${it.name}`} className="text-muted hover:text-ink"><Pencil size={18} /></Link>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
                <Pagination links={items.links} from={items.from} to={items.to} total={items.total} />
            </Card>
        </AdminLayout>
    );
}
