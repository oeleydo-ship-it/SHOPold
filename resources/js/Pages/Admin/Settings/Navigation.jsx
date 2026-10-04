import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { ArrowDown, ArrowUp, Info, Plus, Trash2 } from 'lucide-react';
import SettingsLayout from '../../../Layouts/SettingsLayout';
import { Button, Field, Input } from '../../../Components/ui';
import { SaveButton, SettingsCard } from '../../../Components/settingsUi';

/** Editable, reorderable list of { label, url[, route] } links. */
function LinkList({ title, description, items, setItems, withRoute = false, minItems = 0, addLabel }) {
    const update = (i, patch) => setItems(items.map((it, n) => (n === i ? { ...it, ...patch } : it)));
    const move = (i, dir) => {
        const j = i + dir;
        if (j < 0 || j >= items.length) return;
        const next = [...items];
        [next[i], next[j]] = [next[j], next[i]];
        setItems(next);
    };
    const remove = (i) => {
        if (items.length <= minItems) return window.alert(`You must have at least ${minItems} menu item${minItems > 1 ? 's' : ''}.`);
        if (window.confirm('Remove this item?')) setItems(items.filter((_, n) => n !== i));
    };

    return (
        <SettingsCard title={title} description={description}>
            <div className="space-y-3">
                {items.map((it, i) => (
                    <div key={i} className="flex items-start gap-3 rounded-lg border border-line bg-surface-2 p-3">
                        <div className="flex flex-col pt-6">
                            <button type="button" onClick={() => move(i, -1)} disabled={i === 0} aria-label="Move up" className="text-muted hover:text-ink disabled:opacity-30"><ArrowUp size={16} /></button>
                            <button type="button" onClick={() => move(i, 1)} disabled={i === items.length - 1} aria-label="Move down" className="text-muted hover:text-ink disabled:opacity-30"><ArrowDown size={16} /></button>
                        </div>
                        <div className={`grid flex-1 gap-3 ${withRoute ? 'md:grid-cols-3' : 'md:grid-cols-2'}`}>
                            <Field label="Label"><Input value={it.label ?? ''} onChange={(e) => update(i, { label: e.target.value })} placeholder="Menu Label" /></Field>
                            <Field label="URL"><Input value={it.url ?? ''} onChange={(e) => update(i, { url: e.target.value })} placeholder="/custom-page or https://…" /></Field>
                            {withRoute && <Field label="Route (optional)"><Input value={it.route ?? ''} onChange={(e) => update(i, { route: e.target.value })} placeholder="route.name" /></Field>}
                        </div>
                        <button type="button" onClick={() => remove(i)} aria-label="Remove item" className="mt-7 text-danger"><Trash2 size={16} /></button>
                    </div>
                ))}
                {items.length === 0 && <p className="rounded-lg border-2 border-dashed border-line p-6 text-center text-sm text-muted">No links yet.</p>}
            </div>
            <div className="mt-4">
                <Button tone="neutral" onClick={() => setItems([...items, withRoute ? { label: '', url: '', route: '' } : { label: '', url: '' }])}><Plus size={14} />{addLabel}</Button>
            </div>
        </SettingsCard>
    );
}

export default function Navigation({ header: h0, quick: q0, service: s0, pages }) {
    const [header, setHeader] = useState(h0);
    const [quick, setQuick] = useState(q0);
    const [service, setService] = useState(s0);
    const [processing, setProcessing] = useState(false);

    const submit = (e) => {
        e.preventDefault();
        // Header items need both a label and a URL; blank footer links are simply dropped (same rules as before).
        if (header.some((it) => !it.label?.trim() || !it.url?.trim())) {
            return window.alert('Please fill in both Label and URL for all header menu items.');
        }
        const clean = (list) => list.filter((it) => it.label?.trim() && it.url?.trim());
        const nextQuick = clean(quick);
        const nextService = clean(service);
        setQuick(nextQuick);
        setService(nextService);

        router.post('/admin/settings', {
            _group: 'navigation',
            _boolean_fields: '',
            navigation_header_menu: JSON.stringify(header),
            navigation_footer_quick: JSON.stringify(nextQuick),
            navigation_footer_service: JSON.stringify(nextService),
        }, {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
        });
    };

    const known = [['Home', '/'], ['Shop', '/shop'], ['About', '/about'], ['Contact', '/contact'], ...pages.map((p) => [p.title, p.url])];

    return (
        <SettingsLayout>
            <Head title="Navigation Settings" />
            <form onSubmit={submit} className="space-y-5">
                <LinkList
                    title="Header Navigation" description="Use the arrows to reorder. These links appear in the main navigation."
                    items={header} setItems={setHeader} withRoute minItems={1} addLabel="Add Item"
                />
                <p className="flex items-center gap-2 text-xs text-muted"><Info size={14} />Use full URLs (https://…) for external links or relative paths (/page/…) for internal pages.</p>
                <LinkList title="Footer — Quick Links" description='Links displayed in the "Quick Links" section of the footer.' items={quick} setItems={setQuick} addLabel="Add Link" />
                <LinkList title="Footer — Customer Service" description='Links displayed in the "Customer Service" section of the footer.' items={service} setItems={setService} addLabel="Add Link" />

                <SettingsCard title="Available Pages" description="You can link to these pages in your navigation:">
                    <div className="grid gap-2 text-sm sm:grid-cols-2">
                        {known.map(([label, url]) => (
                            <div key={url} className="rounded-lg bg-surface-2 px-3 py-2"><strong>{label}:</strong> <code className="text-xs text-primary">{url}</code></div>
                        ))}
                    </div>
                </SettingsCard>

                <SaveButton processing={processing}>Save Navigation</SaveButton>
            </form>
        </SettingsLayout>
    );
}
