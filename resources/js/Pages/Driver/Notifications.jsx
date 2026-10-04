import { Head, Link, router } from '@inertiajs/react';
import { Bell, CheckCheck } from 'lucide-react';
import DriverLayout from '../../Layouts/DriverLayout';
import { Button, Card, Pagination } from '../../Components/ui';

export default function Notifications({ notifications }) {
    const unread = notifications.data.some((n) => !n.read);
    // Mark as read first, then open the order: two visits started together would cancel each other.
    const open = (n, href) => (e) => {
        e?.preventDefault();
        if (n.read) { if (href) router.visit(href); return; }
        router.post(`/driver/notifications/${n.id}/read`, {}, { preserveScroll: true, preserveState: true, onSuccess: () => href && router.visit(href) });
    };

    return (
        <DriverLayout title="Notifications"
            actions={unread && <Button tone="neutral" onClick={() => router.post('/driver/notifications/read-all', {}, { preserveScroll: true })}><CheckCheck size={16} /><span className="hidden sm:inline">Mark all read</span></Button>}>
            <Head title="Notifications" />
            <Card className="overflow-hidden">
                {notifications.data.length === 0 ? (
                    <p className="p-10 text-center text-sm text-muted"><Bell size={28} className="mx-auto mb-2 opacity-50" />No notifications yet.</p>
                ) : (
                    <ul className="divide-y divide-line">
                        {notifications.data.map((n) => {
                            const body = (
                                <>
                                    <span className={`mt-1.5 h-2 w-2 shrink-0 rounded-full ${n.read ? 'bg-transparent' : 'bg-primary'}`} aria-hidden />
                                    <span className="min-w-0 flex-1">
                                        <span className={`block text-sm ${n.read ? '' : 'font-semibold'}`}>{n.title}</span>
                                        {n.message && <span className="block text-sm text-muted">{n.message}</span>}
                                        <span className="block text-xs text-muted">{n.order && `${n.order} · `}{n.date}</span>
                                    </span>
                                    {!n.read && <span className="sr-only">Unread</span>}
                                </>
                            );
                            const cls = 'flex w-full items-start gap-3 p-4 text-left hover:bg-surface-2';
                            return (
                                <li key={n.id}>
                                    {n.order_id
                                        ? <Link href={`/driver/orders/${n.order_id}`} onClick={open(n, `/driver/orders/${n.order_id}`)} className={cls}>{body}</Link>
                                        : <button type="button" onClick={open(n)} className={cls}>{body}</button>}
                                </li>
                            );
                        })}
                    </ul>
                )}
                <Pagination links={notifications.links} from={notifications.from} to={notifications.to} total={notifications.total} />
            </Card>
        </DriverLayout>
    );
}
