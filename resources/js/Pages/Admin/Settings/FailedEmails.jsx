import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, MailWarning, RotateCcw, Trash2 } from 'lucide-react';
import SettingsLayout from '../../../Layouts/SettingsLayout';
import { Button, Card, Pagination } from '../../../Components/ui';
import QueueStatus from '../../../Components/QueueStatus';

export default function FailedEmails({ failed, queue }) {
    const act = (method, url, payload, confirmText) => {
        if (confirmText && !window.confirm(confirmText)) return;
        router[method](url, method === 'delete' ? { data: payload, preserveScroll: true } : payload, method === 'delete' ? undefined : { preserveScroll: true });
    };

    return (
        <SettingsLayout>
            <Head title="Failed emails" />
            <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                <Link href="/admin/settings/email" className="inline-flex items-center gap-1 text-sm text-muted hover:text-ink"><ArrowLeft size={16} />Email settings</Link>
                {failed.data.length > 0 && (
                    <div className="flex gap-2">
                        <Button onClick={() => act('post', '/admin/settings/email/failed/retry', { all: true })}><RotateCcw size={16} />Retry all</Button>
                        <Button tone="neutral" onClick={() => act('delete', '/admin/settings/email/failed', { all: true }, 'Remove every failed email? They will not be sent.')}><Trash2 size={16} />Remove all</Button>
                    </div>
                )}
            </div>

            <div className="mb-4"><QueueStatus queue={queue} /></div>

            <Card className="overflow-hidden">
                {failed.data.length === 0 ? (
                    <p className="p-10 text-center text-sm text-muted"><MailWarning size={28} className="mx-auto mb-2 opacity-50" />No failed emails.</p>
                ) : (
                    <ul className="divide-y divide-line">
                        {failed.data.map((f) => (
                            <li key={f.id} className="flex flex-wrap items-start gap-3 p-4 md:px-6">
                                <div className="min-w-0 flex-1">
                                    <p className="font-medium">{f.type}{f.to.length > 0 && <span className="font-normal text-muted"> → {f.to.join(', ')}</span>}</p>
                                    <p className="mt-1 break-words text-xs text-danger">{f.error}</p>
                                    <p className="mt-1 text-xs text-muted">Failed {f.failed_at}</p>
                                </div>
                                <div className="flex gap-2">
                                    <Button tone="neutral" onClick={() => act('post', '/admin/settings/email/failed/retry', { ids: [f.id] })} aria-label={`Retry ${f.type}`}><RotateCcw size={14} />Retry</Button>
                                    <Button tone="neutral" onClick={() => act('delete', '/admin/settings/email/failed', { ids: [f.id] })} aria-label={`Remove ${f.type}`}><Trash2 size={14} /></Button>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
                <Pagination links={failed.links} from={failed.from} to={failed.to} total={failed.total} />
            </Card>
        </SettingsLayout>
    );
}
