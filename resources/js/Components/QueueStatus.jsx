import { Link } from '@inertiajs/react';
import { AlertTriangle, CheckCircle2, Clock } from 'lucide-react';

/** Background-email health: is sending queued, is the worker alive, how many are waiting / failed. */
export default function QueueStatus({ queue, showLink = false }) {
    if (!queue.ready) {
        return <p className="rounded-lg bg-warning/10 p-3 text-sm">The queue tables are missing. Run <code>php artisan migrate</code>.</p>;
    }
    const workerTone = queue.worker_ok ? 'text-success' : 'text-warning';

    return (
        <div className="grid gap-3 rounded-lg border border-line p-4 text-sm sm:grid-cols-3">
            <div>
                <p className="text-xs uppercase text-muted">Sending</p>
                <p className="font-medium">{queue.enabled ? 'In the background' : 'Immediately (while the customer waits)'}</p>
            </div>
            <div>
                <p className="text-xs uppercase text-muted">Worker</p>
                <p className={`inline-flex items-center gap-1 font-medium ${workerTone}`}>
                    {queue.worker_ok ? <CheckCircle2 size={14} /> : <AlertTriangle size={14} />}
                    {queue.last_run ? `Last run ${queue.last_run}` : 'Has not run yet'}
                </p>
            </div>
            <div>
                <p className="text-xs uppercase text-muted">Queue</p>
                <p className="inline-flex items-center gap-3 font-medium">
                    <span className="inline-flex items-center gap-1"><Clock size={14} />{queue.waiting} waiting</span>
                    {showLink
                        ? <Link href="/admin/settings/email/failed" className={queue.failed ? 'text-danger hover:underline' : 'text-muted hover:underline'}>{queue.failed} failed</Link>
                        : <span className={queue.failed ? 'text-danger' : 'text-muted'}>{queue.failed} failed</span>}
                </p>
            </div>
            {queue.enabled && !queue.worker_ok && (
                <p className="text-warning sm:col-span-3">
                    Emails are waiting for the worker. Schedule <code>scripts\run-scheduler.bat</code> to run every minute (see the README), or switch background sending off.
                </p>
            )}
        </div>
    );
}
