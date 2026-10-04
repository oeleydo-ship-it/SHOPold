import { useRef, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import {
    CheckCircle2, CloudDownload, ExternalLink, FileArchive, Loader2, RefreshCw, RotateCcw, ShieldCheck,
    Trash2, TriangleAlert, Upload, Wrench,
} from 'lucide-react';
import SettingsLayout from '../../../Layouts/SettingsLayout';
import { Badge, Button, Card } from '../../../Components/ui';
import { SettingsCard } from '../../../Components/settingsUi';
import { jsonRequest } from '../../../lib/http';

const UPDATES = '/admin/settings/updates';

function Bullets({ title, items, tone }) {
    if (!items.length) return null;
    return (
        <div className="rounded-lg border p-4" style={{ borderColor: `color-mix(in srgb, var(--${tone}) 40%, transparent)`, background: `color-mix(in srgb, var(--${tone}) 8%, transparent)` }}>
            <h4 className="mb-2 text-sm font-semibold" style={{ color: `var(--${tone})` }}>{title}</h4>
            <ul className="list-inside list-disc space-y-1 text-sm">{items.map((c, i) => <li key={i}>{c}</li>)}</ul>
        </div>
    );
}

export default function Updates({ currentVersion, author, authorUrl, updateInfo: info, backups, system }) {
    const [busy, setBusy] = useState(''); // name of the operation in progress; locks every other action
    const [checkError, setCheckError] = useState('');
    const [ack, setAck] = useState(false); // user confirmed they read the breaking changes
    const [file, setFile] = useState(null);
    const [progress, setProgress] = useState(null);
    const fileRef = useRef(null);
    const locked = busy !== '';

    const run = (name, method, url, data = {}, opts = {}) => {
        setBusy(name);
        const options = {
            preserveScroll: true,
            ...opts,
            onFinish: () => { setBusy(''); setProgress(null); opts.onFinish?.(); },
        };
        // router.delete takes (url, options); the others take (url, data, options)
        if (method === 'delete') router.delete(url, { ...options, data });
        else router[method](url, data, options);
    };

    const check = async () => {
        setBusy('check');
        setCheckError('');
        try {
            await jsonRequest(`${UPDATES}/check`);
            router.reload({ only: ['updateInfo'], preserveScroll: true });
        } catch (e) {
            setCheckError(e.message || 'Failed to check for updates');
        } finally {
            setBusy('');
        }
    };

    const install = () => {
        if (!window.confirm('Are you sure you want to install this update? A backup will be created automatically. Do not close this page until it finishes.')) return;
        run('install', 'post', `${UPDATES}/install`, info.breaking_changes.length ? { confirm_breaking: 1 } : {});
    };

    const upload = (e) => {
        e.preventDefault();
        if (!file) return;
        if (!window.confirm(`Install the update package "${file.name}"? Current files will be overwritten. A backup is created first.`)) return;
        run('upload', 'post', `${UPDATES}/upload`, { update_file: file }, {
            forceFormData: true,
            onProgress: (p) => setProgress(p?.percentage ?? null),
            onSuccess: () => { setFile(null); if (fileRef.current) fileRef.current.value = ''; },
        });
    };

    const needsAck = info.available && info.breaking_changes.length > 0;
    const spin = (name) => busy === name && <Loader2 size={14} className="animate-spin" />;

    return (
        <SettingsLayout>
            <Head title="System Updates" />
            <p className="text-sm text-muted">Check for updates and manage system versions</p>

            <div className="grid gap-5 xl:grid-cols-3">
                <div className="space-y-5 xl:col-span-2">
                    <SettingsCard>
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <h3 className="font-semibold">Current Version</h3>
                            <Badge tone="primary">v{currentVersion}</Badge>
                        </div>
                        <p className="mt-2 text-sm text-muted">PHP {system.php} · Laravel {system.laravel}</p>
                        {author && (
                            <p className="mt-3 flex items-center gap-2 border-t border-line pt-3 text-sm">
                                Developed by <strong>{author}</strong>
                                {authorUrl && <a href={authorUrl} target="_blank" rel="noreferrer" aria-label="Author website" className="text-primary"><ExternalLink size={14} /></a>}
                            </p>
                        )}
                    </SettingsCard>

                    <SettingsCard>
                        <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                            <h3 className="font-semibold">Update Status</h3>
                            <Button tone="neutral" onClick={check} disabled={locked}>{busy === 'check' ? <Loader2 size={14} className="animate-spin" /> : <RefreshCw size={14} />}{busy === 'check' ? 'Checking…' : 'Check for Updates'}</Button>
                        </div>

                        {(info.error || checkError) && (
                            <p role="alert" className="mb-4 flex items-start gap-2 rounded-lg border border-danger/40 bg-danger/10 p-3 text-sm text-danger">
                                <TriangleAlert size={16} className="mt-0.5 shrink-0" />
                                {checkError || `Could not connect to update server: ${info.error}`}
                            </p>
                        )}

                        {info.available ? (
                            <div className="space-y-4">
                                <div className="flex items-start gap-4 rounded-lg border border-success/40 bg-success/10 p-4">
                                    <span className="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-success text-white"><CloudDownload size={22} /></span>
                                    <div>
                                        <h4 className="font-semibold">New Update Available!</h4>
                                        <p className="text-sm text-muted">Version {info.latest_version} is available</p>
                                        <p className="mt-1 flex flex-wrap gap-3 text-xs text-muted">
                                            {info.release_date && <span>{info.release_date}</span>}
                                            {info.file_size ? <span>{(info.file_size / 1024 / 1024).toFixed(1)} MB</span> : null}
                                            {info.min_php_version && <span>PHP {info.min_php_version}+</span>}
                                        </p>
                                    </div>
                                </div>

                                <Bullets title="What's New" items={info.changelog} tone="info" />
                                <Bullets title="Breaking Changes" items={info.breaking_changes} tone="danger" />
                                {info.upgrade_notes && (
                                    <div className="rounded-lg border border-warning/40 bg-warning/10 p-4 text-sm"><h4 className="mb-1 font-semibold text-warning">Upgrade Notes</h4><p>{info.upgrade_notes}</p></div>
                                )}

                                <p className="flex items-start gap-2 rounded-lg border border-warning/40 bg-warning/10 p-3 text-sm">
                                    <TriangleAlert size={16} className="mt-0.5 shrink-0 text-warning" />
                                    <span><strong>Important:</strong> Always back up your database and files before updating. A file backup is created automatically, but a manual backup is recommended.</span>
                                </p>
                                {info.has_hash && <p className="flex items-center gap-2 text-xs text-muted"><ShieldCheck size={14} />File integrity is verified using a SHA-256 hash</p>}

                                {needsAck && (
                                    <label className="flex items-start gap-2 rounded-lg border border-danger/40 p-3 text-sm">
                                        <input type="checkbox" checked={ack} onChange={(e) => setAck(e.target.checked)} className="mt-0.5 h-4 w-4 accent-[var(--danger)]" />
                                        <span>I have read the breaking changes above and understand they may require changes to my store.</span>
                                    </label>
                                )}
                                <Button onClick={install} disabled={locked || (needsAck && !ack)} className="w-full py-3">
                                    {spin('install') || <CloudDownload size={16} />}{busy === 'install' ? 'Installing — please do not close this page…' : `Install Update v${info.latest_version}`}
                                </Button>
                            </div>
                        ) : (
                            <div className="flex items-center gap-4 rounded-lg border border-success/40 bg-success/10 p-4">
                                <span className="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-success text-white"><CheckCircle2 size={22} /></span>
                                <div><h4 className="font-semibold">You're up to date!</h4><p className="text-sm text-muted">Last checked: {info.checked_at ?? 'Never'}</p></div>
                            </div>
                        )}
                    </SettingsCard>

                    <SettingsCard title="Manual Update" description="If automatic updates don't work, you can upload an update package manually.">
                        <form onSubmit={upload}>
                            <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                                <input
                                    ref={fileRef} type="file" accept=".zip" required aria-label="Update package (.zip)"
                                    onChange={(e) => setFile(e.target.files[0] ?? null)}
                                    className="w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm"
                                />
                                <Button type="submit" disabled={locked || !file}>{spin('upload') || <Upload size={14} />}Upload &amp; Install</Button>
                            </div>
                            {progress !== null && (
                                <div className="mt-3 h-2 overflow-hidden rounded-full bg-line" role="progressbar" aria-valuenow={progress} aria-valuemin={0} aria-valuemax={100}>
                                    <div className="h-2 rounded-full bg-primary transition-all" style={{ width: `${progress}%` }} />
                                </div>
                            )}
                            <p className="mt-2 text-xs text-muted">Accepted format: .zip (max 100MB). Only upload packages from a source you trust — they replace application files.</p>
                        </form>
                    </SettingsCard>
                </div>

                <div className="space-y-5">
                    <SettingsCard title="System Information">
                        <dl className="space-y-2 text-sm">
                            {[['App Version', currentVersion], ['PHP Version', system.php], ['Laravel Version', system.laravel], ['Server', system.server], ['Database', system.database]].map(([k, v]) => (
                                <div key={k} className="flex justify-between gap-3"><dt className="text-muted">{k}</dt><dd className="font-medium">{v}</dd></div>
                            ))}
                        </dl>
                        <div className="mt-5 border-t border-line pt-4">
                            <h4 className="mb-3 text-sm font-semibold">Maintenance</h4>
                            <div className="grid grid-cols-2 gap-2">
                                <Button tone="neutral" disabled={locked} onClick={() => run('perms', 'post', `${UPDATES}/fix-permissions`)}>{spin('perms') || <Wrench size={14} />}Fix Permissions</Button>
                                <Button tone="neutral" disabled={locked} onClick={() => run('cache', 'post', '/admin/settings/clear-cache')}>{spin('cache') || <RefreshCw size={14} />}Clear Cache</Button>
                            </div>
                            <p className="mt-2 text-xs text-muted">Use "Fix Permissions" if you see permission errors after updating.</p>
                        </div>
                    </SettingsCard>

                    <SettingsCard title="Update Backups">
                        {backups.length === 0 ? (
                            <p className="text-sm text-muted">No backups available. Backups are created automatically before each update.</p>
                        ) : (
                            <ul className="space-y-2">
                                {backups.map((b) => (
                                    <li key={b.name} className="flex items-center justify-between gap-3 rounded-lg border border-line p-3">
                                        <div className="min-w-0">
                                            <p className="flex items-center gap-2 truncate text-sm font-medium"><FileArchive size={14} className="shrink-0 text-muted" />{b.name}</p>
                                            <p className="text-xs text-muted">{b.date} · {b.size.toFixed(2)} MB</p>
                                        </div>
                                        <div className="flex shrink-0 gap-3">
                                            <button
                                                type="button" disabled={locked} title="Restore" aria-label={`Restore ${b.name}`}
                                                onClick={() => window.confirm('Restore this backup? Current files will be overwritten.') && run('restore', 'post', `${UPDATES}/restore`, { backup: b.name })}
                                                className="text-primary disabled:opacity-40"
                                            ><RotateCcw size={16} /></button>
                                            <button
                                                type="button" disabled={locked} title="Delete" aria-label={`Delete ${b.name}`}
                                                onClick={() => window.confirm('Delete this backup permanently?') && run('delete', 'delete', `${UPDATES}/backup`, { backup: b.name })}
                                                className="text-danger disabled:opacity-40"
                                            ><Trash2 size={16} /></button>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </SettingsCard>
                </div>
            </div>
        </SettingsLayout>
    );
}
