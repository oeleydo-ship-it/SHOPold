import { Head, router } from '@inertiajs/react';
import { Check, Eye, EyeOff, Palette, SlidersHorizontal } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { Badge, Button, Card, LinkButton } from '../../../Components/ui';

function Thumb({ theme }) {
    if (theme.preview_image) return <img src={theme.preview_image} alt={`${theme.name} preview`} className="h-40 w-full object-cover" />;
    const [a = '#2563eb', b = '#1e40af', c = '#f59e0b'] = theme.swatches;
    // no screenshot shipped with the theme: draw a small mock-up from its colours and fonts
    return (
        <div className="h-40 w-full overflow-hidden bg-surface-2 p-3" aria-hidden="true">
            <div className="h-full overflow-hidden rounded-md border border-line bg-white shadow-sm">
                <div className="flex items-center gap-1.5 px-2 py-1.5" style={{ background: b }}>
                    <span className="h-1.5 w-8 rounded-full bg-white/80" /><span className="h-1 w-4 rounded-full bg-white/40" /><span className="h-1 w-4 rounded-full bg-white/40" />
                </div>
                <div className="px-3 py-3" style={{ background: `linear-gradient(135deg, ${a}, ${b})` }}>
                    <p className="text-[11px] font-bold leading-tight text-white" style={{ fontFamily: `'${theme.fonts[0]}', sans-serif` }}>Discover Premium Products</p>
                    <span className="mt-1.5 inline-block rounded px-2 py-0.5 text-[8px] font-semibold text-white" style={{ background: c }}>Shop Now</span>
                </div>
                <div className="grid grid-cols-3 gap-1.5 p-2">{[0, 1, 2].map((i) => <div key={i} className="h-8 rounded bg-gray-100" />)}</div>
            </div>
        </div>
    );
}

export default function Index({ themes, previewing }) {
    const activate = (t) => { if (window.confirm(`Make "${t.name}" the storefront theme for all visitors?`)) router.post(`/admin/themes/${t.slug}/activate`, {}, { preserveScroll: true }); };

    return (
        <AdminLayout title="Themes">
            <Head title="Themes" />
            <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
                <p className="text-sm text-muted">Choose how your storefront looks. Each theme keeps its own customizer settings, so you can switch back and forth without losing changes.</p>
                {previewing && <Button tone="neutral" onClick={() => router.delete('/admin/themes/preview', { preserveScroll: true })}><EyeOff size={16} />Exit preview ({previewing})</Button>}
            </div>

            <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                {themes.map((t) => (
                    <Card key={t.slug} className={`overflow-hidden ${t.active ? 'ring-2 ring-primary' : ''}`}>
                        <Thumb theme={t} />
                        <div className="space-y-3 p-4">
                            <div className="flex items-start justify-between gap-2">
                                <div>
                                    <h3 className="font-semibold">{t.name}</h3>
                                    <p className="text-xs text-muted">v{t.version} · {t.author}</p>
                                </div>
                                <div className="flex flex-wrap justify-end gap-1">
                                    {t.active && <Badge tone="success"><span className="inline-flex items-center gap-1"><Check size={12} />Active</span></Badge>}
                                    {t.customized && <Badge tone="info">Customized</Badge>}
                                </div>
                            </div>
                            {t.description && <p className="text-sm text-muted">{t.description}</p>}
                            <div className="flex items-center gap-2 text-xs text-muted">
                                <span className="flex gap-1" aria-label="Theme colors">{t.swatches.map((c) => <span key={c} title={c} className="h-4 w-4 rounded-full border border-line" style={{ background: c }} />)}</span>
                                <span>{t.fonts[0] || t.fonts[1]}</span>
                                {t.overrides > 0 && <span>· {t.overrides} custom template{t.overrides === 1 ? '' : 's'}</span>}
                            </div>
                            <div className="flex flex-wrap gap-2 pt-1">
                                {!t.active && <Button onClick={() => activate(t)}><Palette size={16} />Activate</Button>}
                                <LinkButton tone="neutral" href={`/admin/themes/${t.slug}/customize`}><SlidersHorizontal size={16} />Customize</LinkButton>
                                {!t.active && <LinkButton external tone="neutral" href={`/admin/themes/${t.slug}/preview`} target="_blank" rel="noreferrer"><Eye size={16} />Preview</LinkButton>}
                            </div>
                        </div>
                    </Card>
                ))}
            </div>

            <Card className="mt-6 p-4 text-sm text-muted">
                <p className="mb-1 font-medium text-ink">Adding a theme</p>
                <p>Create a folder in <code>resources/views/themes/&lt;name&gt;</code> with a <code>theme.json</code> (name, description, <code>defaults</code> for the customizer). Any Blade file placed in the folder with the same path as a storefront view (for example <code>home.blade.php</code>) replaces that view while the theme is active.</p>
            </Card>
        </AdminLayout>
    );
}
