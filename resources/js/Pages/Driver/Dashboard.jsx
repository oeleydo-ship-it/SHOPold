import { Head, Link } from '@inertiajs/react';
import { CalendarCheck, CheckCircle2, PackageSearch, Star, Wallet } from 'lucide-react';
import DriverLayout from '../../Layouts/DriverLayout';
import DriverOrderCard from '../../Components/DriverOrderCard';
import StatCard from '../../Components/StatCard';
import { Card } from '../../Components/ui';

export default function Dashboard({ name, stats, orders }) {
    return (
        <DriverLayout title="Dashboard">
            <Head title="Driver dashboard" />
            <p className="mb-4 text-lg font-semibold">Hi {name.split(' ')[0]} 👋</p>

            <div className="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
                <StatCard label="Delivered today" value={stats.today_deliveries} icon={CheckCircle2} tone="success" />
                <StatCard label="To pick up" value={stats.pending_pickups} icon={PackageSearch} tone="warning" />
                <StatCard label="Earned today" value={stats.today_earnings} icon={Wallet} tone="primary" sub={<p className="mt-0.5 text-xs text-muted">This week {stats.week_earnings}</p>} />
                <StatCard label="This month" value={stats.month_deliveries} icon={CalendarCheck} tone="info"
                    sub={<p className="mt-0.5 inline-flex items-center gap-1 text-xs text-muted"><Star size={12} />{stats.rating ?? '–'} · {stats.total_deliveries} total</p>} />
            </div>

            <div className="mb-3 flex items-center justify-between">
                <h2 className="font-semibold">Current deliveries</h2>
                <Link href="/driver/orders" className="text-sm text-primary hover:underline">View all</Link>
            </div>
            {orders.length === 0 ? (
                <Card className="p-8 text-center text-sm text-muted">No deliveries assigned right now. Set yourself to Available to receive new ones.</Card>
            ) : (
                <div className="grid gap-3 lg:grid-cols-2">{orders.map((o) => <DriverOrderCard key={o.id} order={o} />)}</div>
            )}
        </DriverLayout>
    );
}
