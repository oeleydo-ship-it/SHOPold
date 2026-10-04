import { useEffect, useMemo, useRef, useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, ChevronDown, Monitor, RotateCcw, Smartphone, Undo2 } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import MediaPicker from '../../../Components/MediaPicker';
import { ColorField } from '../../../Components/schemaForm';
import { Badge, Button, Card, Field, Input, Select, Textarea } from '../../../Components/ui';
import { Toggle } from '../../../Components/crud';
import { jsonRequest } from '../../../lib/http';

function FieldInput({ f, value, onChange, error, isDefault, onReset }) {
    const label = (
        <span className="flex items-center justify-between gap-2">
            <span>{f.label}</span>
            {!isDefault && <button type="button" onClick={onReset} title="Back to default" aria-label={`Reset ${f.label}`} className="text-muted hover:text-ink"><Undo2 size={13} /></button>}
        </span>
    );
    // Toggle and image fields have their own labels, so they are not wrapped in a <label>
    if (f.type === 'toggle') return <div><Toggle checked={!!value} onChange={onChange} label={<span>{f.label}</span>} />{error && <p className="mt-1 text-xs text-danger">{error}</p>}</div>;
    if (f.type === 'image') return <div><p className="mb-1 text-sm font-medium">{f.label}</p><MediaPicker value={value ?? ''} onChange={onChange} collection="banners" label="Select image" />{error && <p className="mt-1 text-xs text-danger">{error}</p>}</div>;

    return (
        <Field label={label} error={error} hint={f.hint}>
            {f.type === 'color' && <ColorField value={value} onChange={onChange} label={f.label} />}
            {f.type === 'select' && <Select value={value ?? ''} onChange={(e) => onChange(e.target.value)}>{f.options.map(([v, l]) => <option key={v} value={v}>{l}</option>)}</Select>}
            {f.type === 'number' && <Input type="number" min={f.min} max={f.max} value={value ?? ''} onChange={(e) => onChange(e.target.value === '' ? '' : Number(e.target.value))} />}
            {f.type === 'text' && <Input maxLength={f.max} value={value ?? ''} onChange={(e) => onChange(e.target.value)} />}
            {f.type === 'url' && <Input type="text" inputMode="url" placeholder="https://…" maxLength={f.max} value={value ?? ''} onChange={(e) => onChange(e.target.value)} />}
            {f.type === 'textarea' && <Textarea rows={3} value={value ?? ''} onChange={(e) => onChange(e.target.value)} />}
            {f.type === 'code' && <Textarea rows={8} spellCheck={false} className="font-mono text-xs" value={value ?? ''} onChange={(e) => onChange(e.target.value)} />}
        </Field>
    );
}

export default function Customize({ theme, sections, values: initial, defaults, saved }) {
    const { errors } = usePage().props;
    const [values, setValues] = useState(initial);
    const [open, setOpen] = useState(sections[0]?.id);
    const [device, setDevice] = useState('desktop');
    const [frameKey, setFrameKey] = useState(0);
    const [busy, setBusy] = useState(false);
    const timer = useRef(null);
    const first = useRef(true);

    const dirty = useMemo(() => JSON.stringify(values) !== JSON.stringify(initial), [values, initial]);
    const set = (key, v) => setValues((s) => ({ ...s, [key]: v }));
    // fields that only matter for a particular choice (e.g. slider options) are hidden otherwise
    const visible = (f) => !f.show_if || f.show_if.in.includes(values[f.show_if.key]);

    // Live preview: send the unsaved values to the server (session only), then reload the iframe
    useEffect(() => {
        if (first.current) { first.current = false; return undefined; }
        clearTimeout(timer.current);
        timer.current = setTimeout(async () => {
            try { await jsonRequest(`/admin/themes/${theme.slug}/draft`, { method: 'POST', body: { values } }); setFrameKey((k) => k + 1); } catch { /* preview only */ }
        }, 700);
        return () => clearTimeout(timer.current);
    }, [values]);

    // Start the preview session for this theme, and end it when leaving the customizer
    useEffect(() => {
        jsonRequest(`/admin/themes/${theme.slug}/draft`, { method: 'POST', body: { values: initial } }).then(() => setFrameKey((k) => k + 1)).catch(() => {});
        return () => { jsonRequest('/admin/themes/preview', { method: 'DELETE' }).catch(() => {}); };
    }, []);

    const save = () => router.put(`/admin/themes/${theme.slug}/customize`, { values }, { preserveScroll: true, onStart: () => setBusy(true), onFinish: () => setBusy(false) });
    const reset = () => { if (window.confirm(`Reset every ${theme.name} setting to its defaults?`)) router.delete(`/admin/themes/${theme.slug}/customize`, { preserveScroll: true, onSuccess: () => setValues(defaults) }); };

    return (
        <AdminLayout title={`Customize ${theme.name}`}>
            <Head title={`Customize ${theme.name}`} />
            <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div className="flex items-center gap-3">
                    <Link href="/admin/themes" className="inline-flex items-center gap-2 text-sm text-muted hover:text-ink"><ArrowLeft size={16} />Themes</Link>
                    {theme.active ? <Badge tone="success">Active theme</Badge> : <Badge tone="warning">Not active</Badge>}
                    {saved.length > 0 && <Badge tone="info">{saved.length} customized</Badge>}
                </div>
                <div className="flex gap-2">
                    <Button tone="neutral" onClick={reset} disabled={busy || saved.length === 0}><RotateCcw size={16} />Reset</Button>
                    <Button onClick={save} disabled={busy || !dirty}>{dirty ? 'Save changes' : 'Saved'}</Button>
                </div>
            </div>
            {!theme.active && <p className="mb-4 rounded-lg bg-warning/10 px-4 py-2 text-sm">These settings only affect the storefront once this theme is activated. The preview shows it now.</p>}

            <div className="grid gap-5 xl:grid-cols-[24rem_1fr]">
                <div className="space-y-3">
                    {sections.map((s) => (
                        <Card key={s.id} className="overflow-hidden">
                            <button type="button" onClick={() => setOpen(open === s.id ? null : s.id)} aria-expanded={open === s.id} className="flex w-full items-center justify-between gap-2 p-4 text-left font-semibold">
                                <span>{s.title}</span>
                                <span className="flex items-center gap-2 text-xs font-normal text-muted">
                                    {s.fields.filter((f) => saved.includes(f.key)).length > 0 && <span>{s.fields.filter((f) => saved.includes(f.key)).length} changed</span>}
                                    <ChevronDown size={16} className={`transition-transform ${open === s.id ? 'rotate-180' : ''}`} />
                                </span>
                            </button>
                            {open === s.id && (
                                <div className="space-y-4 border-t border-line p-4">
                                    {s.fields.filter(visible).map((f) => (
                                        <FieldInput key={f.key} f={f} value={values[f.key]} onChange={(v) => set(f.key, v)} error={errors?.[f.key]} isDefault={values[f.key] === defaults[f.key]} onReset={() => set(f.key, defaults[f.key])} />
                                    ))}
                                </div>
                            )}
                        </Card>
                    ))}
                </div>

                <Card className="self-start overflow-hidden xl:sticky xl:top-4">
                    <div className="flex items-center justify-between border-b border-line px-4 py-2">
                        <span className="text-sm font-medium">Live preview</span>
                        <div className="flex gap-1">
                            <button type="button" onClick={() => setDevice('desktop')} aria-pressed={device === 'desktop'} aria-label="Desktop preview" className={`rounded p-1.5 ${device === 'desktop' ? 'bg-primary text-on-primary' : 'text-muted hover:bg-surface-2'}`}><Monitor size={16} /></button>
                            <button type="button" onClick={() => setDevice('mobile')} aria-pressed={device === 'mobile'} aria-label="Mobile preview" className={`rounded p-1.5 ${device === 'mobile' ? 'bg-primary text-on-primary' : 'text-muted hover:bg-surface-2'}`}><Smartphone size={16} /></button>
                        </div>
                    </div>
                    <div className="flex justify-center bg-surface-2 p-3">
                        <iframe key={frameKey} title="Storefront preview" src="/" className="h-[70vh] rounded border border-line bg-white transition-all" style={{ width: device === 'mobile' ? 390 : '100%' }} />
                    </div>
                </Card>
            </div>
        </AdminLayout>
    );
}
