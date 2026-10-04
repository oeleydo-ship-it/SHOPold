import { Head, router } from '@inertiajs/react';
import { Check, RefreshCw, Trash2, X } from 'lucide-react';
import SettingsLayout from '../../../Layouts/SettingsLayout';
import { Badge, Button, LinkButton } from '../../../Components/ui';
import { SettingsCard } from '../../../Components/settingsUi';

const show = (v) => (v === null || v === undefined ? 'NULL' : String(v));

function Stat({ label, children }) {
    return (
        <div className="rounded-lg bg-surface-2 p-4">
            <h3 className="mb-1 text-sm font-semibold text-muted">{label}</h3>
            <p className="font-mono text-lg">{children}</p>
        </div>
    );
}

export default function Debug({ cacheStatus, comparison, heroRaw, slides, headerSettings, server }) {
    const post = (url) => router.post(url, {}, { preserveScroll: true });

    return (
        <SettingsLayout>
            <Head title="Settings Debug" />

            <SettingsCard title="Cache Status">
                <div className="grid gap-3 md:grid-cols-2">
                    <Stat label="Cache Driver">{server.cacheDriver}</Stat>
                    <Stat label="Session Driver">{server.sessionDriver}</Stat>
                    <Stat label="Database Count">{cacheStatus.database_count ?? 'N/A'} settings</Stat>
                    <Stat label="Static Cache Loaded">{cacheStatus.static_cache_loaded ? 'Yes' : 'No'}</Stat>
                </div>
            </SettingsCard>

            <SettingsCard title="Settings Comparison (Cached vs Fresh)">
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="text-left text-xs uppercase text-muted"><tr>{['Key', 'Cached', 'Fresh', 'Match'].map((h) => <th key={h} className="px-3 py-2 font-medium">{h}</th>)}</tr></thead>
                        <tbody className="divide-y divide-line">
                            {comparison.map((c) => (
                                <tr key={c.key}>
                                    <td className="px-3 py-2 font-mono">{c.key}</td>
                                    <td className="max-w-[200px] truncate px-3 py-2 font-mono" title={show(c.cached)}>{show(c.cached)}</td>
                                    <td className="max-w-[200px] truncate px-3 py-2 font-mono" title={show(c.fresh)}>{show(c.fresh)}</td>
                                    <td className="px-3 py-2">{c.cached === c.fresh ? <Check size={16} className="text-success" aria-label="Match" /> : <X size={16} className="text-danger" aria-label="Mismatch" />}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </SettingsCard>

            <SettingsCard title="Hero Slides Debug">
                <h3 className="mb-2 text-sm font-semibold text-muted">Raw value from database</h3>
                <pre className="mb-4 overflow-x-auto rounded-lg bg-surface-2 p-4 text-xs">{show(heroRaw)}</pre>
                <h3 className="mb-2 text-sm font-semibold text-muted">Decoded slides ({slides.length})</h3>
                {slides.length === 0 ? <p className="text-sm text-muted">No slides found</p> : slides.map((s, i) => (
                    <div key={i} className="mb-2 rounded-lg bg-surface-2 p-3 text-xs">
                        <p className="font-semibold">Slide {i + 1}</p>
                        <p className="text-muted">Title: {s.title ?? 'N/A'}</p>
                        <p className="text-muted">Image: {s.image ?? 'N/A'}</p>
                    </div>
                ))}
            </SettingsCard>

            <SettingsCard title="Header Settings Debug">
                <div className="space-y-2">
                    {headerSettings.map((h) => (
                        <div key={h.key} className="flex items-center justify-between rounded bg-surface-2 p-2">
                            <span className="font-mono text-sm">{h.key}</span>
                            <span className="font-mono text-sm text-primary">{show(h.value)}</span>
                        </div>
                    ))}
                </div>
            </SettingsCard>

            <SettingsCard title="Server Info">
                <div className="grid gap-3 md:grid-cols-2">
                    <Stat label="PHP Version">{server.php}</Stat>
                    <Stat label="OPcache Enabled">{server.opcache ? 'Yes' : 'No'}</Stat>
                    <Stat label="Laravel Version">{server.laravel}</Stat>
                    <Stat label="Environment">{server.env}</Stat>
                </div>
            </SettingsCard>

            <SettingsCard title="Actions">
                <div className="flex flex-wrap gap-3">
                    <Button tone="neutral" onClick={() => post('/admin/settings/clear-cache')}><Trash2 size={14} />Clear Cache</Button>
                    <Button onClick={() => post('/admin/settings/force-refresh')}><RefreshCw size={14} />Force Refresh</Button>
                    <LinkButton external tone="success" href="/admin/settings/appearance">Go to Appearance</LinkButton>
                </div>
            </SettingsCard>
        </SettingsLayout>
    );
}
