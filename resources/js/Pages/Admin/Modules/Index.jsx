import { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Bike, BookOpen, ChevronDown, Coins, ExternalLink, FileDown, GitCompare, Gift, Headset, Heart, Languages, Mail, Puzzle, ShoppingBag, Sparkles, Star, Store } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { Badge, Button, Card, Field, Input } from '../../../Components/ui';
import { Toggle } from '../../../Components/crud';

const ICONS = { Bike, BookOpen, Coins, FileDown, GitCompare, Gift, Headset, Heart, Languages, Mail, ShoppingBag, Sparkles, Star, Store };

function Options({ module }) {
    const form = useForm({ values: { ...module.values } });
    const set = (k, v) => form.setData('values', { ...form.data.values, [k]: v });
    const submit = (e) => { e.preventDefault(); form.put(`/admin/modules/${module.key}/options`, { preserveScroll: true }); };

    return (
        <form onSubmit={submit} className="space-y-3 border-t border-line bg-surface-2/50 p-4">
            <div className="grid gap-3 sm:grid-cols-2">
                {module.options.map((o) => (o.type === 'toggle'
                    ? <div key={o.key} className="sm:col-span-2"><Toggle checked={!!form.data.values[o.key]} onChange={(v) => set(o.key, v)} label={<span>{o.label}</span>} /></div>
                    : <Field key={o.key} label={o.label}><Input type={o.type === 'number' ? 'number' : 'text'} min={o.min} max={o.max} value={form.data.values[o.key] ?? ''} onChange={(e) => set(o.key, e.target.value)} /></Field>))}
            </div>
            <div className="flex justify-end"><Button type="submit" disabled={form.processing || !form.isDirty}>Save settings</Button></div>
        </form>
    );
}

function ModuleCard({ module }) {
    const [open, setOpen] = useState(false);
    const Icon = ICONS[module.icon] ?? Puzzle;
    const hasOptions = module.options.length > 0;

    return (
        <Card className="overflow-hidden">
            <div className="flex items-start gap-4 p-4">
                <span className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-lg ${module.enabled ? 'bg-primary/10 text-primary' : 'bg-surface-2 text-muted'}`}><Icon size={20} /></span>
                <div className="min-w-0 flex-1">
                    <div className="flex flex-wrap items-center gap-2"><h3 className="font-semibold">{module.name}</h3><Badge tone={module.enabled ? 'success' : 'muted'}>{module.enabled ? 'Enabled' : 'Disabled'}</Badge></div>
                    <p className="text-sm text-muted">{module.description}</p>
                    {module.enabled && module.link && <Link href={module.link} className="mt-1 inline-flex items-center gap-1 text-sm text-primary hover:underline">Open <ExternalLink size={12} /></Link>}
                </div>
                <div className="flex shrink-0 items-center gap-3">
                    {hasOptions && module.enabled && <button type="button" onClick={() => setOpen((o) => !o)} aria-expanded={open} aria-label={`${open ? 'Hide' : 'Show'} ${module.name} settings`} className="text-muted hover:text-ink"><ChevronDown size={18} className={`transition-transform ${open ? 'rotate-180' : ''}`} /></button>}
                    <Toggle checked={module.enabled} onChange={(v) => router.post(`/admin/modules/${module.key}/toggle`, { enabled: v }, { preserveScroll: true })} label={<span className="sr-only">{module.name}</span>} />
                </div>
            </div>
            {hasOptions && module.enabled && open && <Options module={module} />}
        </Card>
    );
}

export default function Index({ modules }) {
    const groups = [...new Set(modules.map((m) => m.group))];

    return (
        <AdminLayout title="Modules">
            <Head title="Modules" />
            <p className="mb-5 text-sm text-muted">Switch store features on or off. A disabled module disappears from the storefront and the admin menu, and its admin pages are closed. Your data is kept.</p>
            <div className="space-y-8">
                {groups.map((g) => (
                    <section key={g}>
                        <h2 className="mb-3 text-sm font-semibold uppercase tracking-wide text-muted">{g}</h2>
                        <div className="grid gap-3 lg:grid-cols-2">{modules.filter((m) => m.group === g).map((m) => <ModuleCard key={m.key} module={m} />)}</div>
                    </section>
                ))}
            </div>
        </AdminLayout>
    );
}
