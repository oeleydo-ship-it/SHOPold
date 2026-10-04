import { router, usePage } from '@inertiajs/react';
import { Bell, Clock3, History, LayoutDashboard, Truck, User, Wallet } from 'lucide-react';
import PortalLayout from './PortalLayout';

export const DRIVER_STATUS = {
    available: ['Available', 'var(--success)'],
    busy: ['Busy', 'var(--warning)'],
    offline: ['Offline', 'var(--muted)'],
};

/** Availability picker for the header; posts to /driver/status. */
export function StatusPicker({ status, compact = false }) {
    const [label, color] = DRIVER_STATUS[status] ?? DRIVER_STATUS.offline;
    return (
        <label className="relative inline-flex items-center gap-2 rounded-full border border-line px-3 py-1.5 text-sm">
            <span className="h-2.5 w-2.5 rounded-full" style={{ background: color }} aria-hidden />
            {!compact && <span className="sr-only sm:not-sr-only">{label}</span>}
            <select value={status} aria-label="Your availability" className="absolute inset-0 cursor-pointer opacity-0"
                onChange={(e) => router.post('/driver/status', { status: e.target.value }, { preserveScroll: true })}>
                {Object.entries(DRIVER_STATUS).map(([v, [l]]) => <option key={v} value={v}>{l}</option>)}
            </select>
        </label>
    );
}

/** Driver portal frame: mobile-first, with a bottom tab bar on phones. */
export default function DriverLayout({ title, children, actions }) {
    const { driver } = usePage().props;
    const nav = [
        { label: 'Dashboard', short: 'Home', href: '/driver', icon: LayoutDashboard, exact: true, tab: true },
        { label: 'Deliveries', href: '/driver/orders', icon: Truck, count: driver?.active_orders, tab: true },
        { label: 'Delivery history', href: '/driver/orders/history', icon: History },
        { label: 'Earnings', href: '/driver/earnings', icon: Wallet, tab: true },
        { label: 'Notifications', short: 'Alerts', href: '/driver/notifications', icon: Bell, count: driver?.unread, tab: true },
        { label: 'Profile', href: '/driver/profile', icon: User, tab: true },
    ];

    return (
        <PortalLayout tabs title={title} nav={nav} home="/driver" badge="Driver"
            subtitle={<span className="inline-flex items-center gap-1"><Clock3 size={12} />{(DRIVER_STATUS[driver?.status] ?? DRIVER_STATUS.offline)[0]}</span>}
            actions={<>{actions}{driver && <StatusPicker status={driver.status} />}</>}>
            {children}
        </PortalLayout>
    );
}
