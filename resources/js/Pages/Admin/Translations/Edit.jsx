import { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Check, Sparkles } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { Button, Card, CardHeader, Input, Textarea } from '../../../Components/ui';
import { jsonRequest } from '../../../lib/http';

export default function Edit({ type, locale, localeName, defaultCode, defaultName, item, locales, fields }) {
    const form = useForm({ locale, ...Object.fromEntries(fields.map((f) => [f.key, f.value])) });
    const { data, setData, errors, processing } = form;
    const [busy, setBusy] = useState([]);
    const [msg, setMsg] = useState(null);
    const notify = (text, error = false) => { setMsg({ text, error }); setTimeout(() => setMsg(null), 6000); };

    const submit = (e) => { e.preventDefault(); form.put(`/admin/translations/${type}/${item.id}`, { preserveScroll: true }); };

    const translateField = async (f) => {
        setBusy((b) => [...b, f.key]);
        try {
            const res = await jsonRequest('/api/translate', { method: 'POST', body: { text: f.original.slice(0, 5000), target: locale, source: defaultCode } });
            if (res.success === false) throw new Error(res.error || 'Translation failed');
            setData(f.key, res.translated);
        } catch (e) { notify(e.message, true); } finally { setBusy((b) => b.filter((k) => k !== f.key)); }
    };

    // Translates and saves every field on the server, then shows the saved values in the form
    const translateAll = async () => {
        setBusy(['*']);
        try {
            const res = await jsonRequest(`/admin/translations/${type}/${item.id}/auto-translate`, { method: 'POST', body: { locale } });
            form.setData((d) => ({ ...d, ...res.translations }));
            notify('All fields translated and saved');
        } catch (e) { notify(e.message, true); } finally { setBusy([]); }
    };

    return (
        <AdminLayout title="Edit Translation">
            <Head title={`Translate: ${item.name}`} />
            <Link href={`/admin/translations?type=${type}&locale=${locale}`} className="mb-5 inline-flex items-center gap-2 text-sm text-muted hover:text-ink"><ArrowLeft size={16} />Back to Translations</Link>
            <p className="mb-4 text-sm text-muted">Translating <strong className="text-ink">{item.name}</strong> to <strong className="text-ink">{localeName}</strong></p>
            {msg && <div role="status" className={`mb-4 rounded-lg px-4 py-2 text-sm ${msg.error ? 'bg-danger/10 text-danger' : 'bg-success/10 text-success'}`}>{msg.text}</div>}

            <Card className="mb-5">
                <div className="flex flex-wrap items-center gap-2 p-4">
                    <span className="text-sm text-muted">Language:</span>
                    {locales.map((l) => (
                        <Link key={l.code} href={`/admin/translations/${type}/${item.id}/edit?locale=${l.code}`} className={`inline-flex items-center gap-1 rounded-full px-3 py-1 text-sm ${l.code === locale ? 'bg-primary text-on-primary' : 'bg-surface-2 hover:bg-line'}`}>
                            {l.name}{l.done && <Check size={12} />}
                        </Link>
                    ))}
                </div>
            </Card>

            <form onSubmit={submit} className="grid gap-5 lg:grid-cols-2">
                <Card className="self-start">
                    <CardHeader title={`Original (${defaultName})`} />
                    <div className="space-y-4 p-4 md:p-6">
                        {fields.filter((f) => f.original).map((f) => (
                            <div key={f.key}><p className="mb-1 text-xs font-medium uppercase text-muted">{f.label}</p><p className="whitespace-pre-wrap break-words rounded-lg bg-surface-2 p-3 text-sm">{f.original}</p></div>
                        ))}
                    </div>
                </Card>

                <Card className="self-start">
                    <CardHeader title={`${localeName} translation`}>
                        <Button tone="neutral" disabled={busy.length > 0} onClick={translateAll}><Sparkles size={14} />Translate all &amp; save</Button>
                    </CardHeader>
                    <div className="space-y-4 p-4 md:p-6">
                        {fields.map((f) => (
                            <div key={f.key}>
                                <div className="mb-1 flex items-center justify-between gap-2">
                                    <label htmlFor={`field_${f.key}`} className="text-sm font-medium">{f.label}</label>
                                    {f.original && <button type="button" disabled={busy.length > 0} onClick={() => translateField(f)} className="inline-flex items-center gap-1 text-xs text-primary disabled:opacity-40"><Sparkles size={12} />{busy.includes(f.key) ? 'Translating…' : 'Auto-translate'}</button>}
                                </div>
                                {f.multiline
                                    ? <Textarea id={`field_${f.key}`} rows={f.key === 'description' || f.key === 'content' ? 8 : 3} placeholder={`Enter ${f.label.toLowerCase()} translation…`} value={data[f.key]} onChange={(e) => setData(f.key, e.target.value)} />
                                    : <Input id={`field_${f.key}`} placeholder={`Enter ${f.label.toLowerCase()} translation…`} value={data[f.key]} onChange={(e) => setData(f.key, e.target.value)} />}
                                {errors[f.key] && <p className="mt-1 text-xs text-danger">{errors[f.key]}</p>}
                            </div>
                        ))}
                        <div className="flex justify-end gap-2">
                            <Link href={`/admin/translations?type=${type}&locale=${locale}`} className="rounded-lg border border-line px-4 py-2 text-sm hover:bg-surface-2">Cancel</Link>
                            <Button type="submit" disabled={processing}>Save Translation</Button>
                        </div>
                    </div>
                </Card>
            </form>
        </AdminLayout>
    );
}
