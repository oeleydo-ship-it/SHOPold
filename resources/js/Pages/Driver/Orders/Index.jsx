import { Head, Link, router } from '@inertiajs/react';
import { History } from 'lucide-react';
import DriverLayout from '../../../Layouts/DriverLayout';
import DriverOrderCard from '../../../Components/DriverOrderCard';
import { Card, Pagination } from '../../../Components/ui';

const TABS = [['', 'Active'], ['processing', 'To pick up'], ['shipped', 'On the way']];

export default function Index({ orders, filters }) {
    const current = filters.status ?? '';
    return (
        <DriverLayout title="Deliveries" actions={<Link href="/driver/orders/history" className="hidden items-center gap-1 text-sm text-muted hover:text-ink sm:inline-flex"><History size={16} />History</Link>}>
            <Head title="Deliveries" />
            <div className="mb-4 flex gap-2 overflow-x-auto" role="tablist" aria-label="Filter deliveries">
                {TABS.map(([v, l]) => (
                    <button key={v} type="button" role="tab" aria-selected={current === v}
                        onClick={() => router.get('/driver/orders', v ? { status: v } : {}, { preserveState: true })}
                        className={`whitespace-nowrap rounded-full px-4 py-2 text-sm ${current === v ? 'bg-primary text-on-primary' : 'border border-line bg-surface text-muted'}`}>{l}</button>
                ))}
                <Link href="/driver/orders/history" className="whitespace-nowrap rounded-full border border-line bg-surface px-4 py-2 text-sm text-muted sm:hidden">History</Link>
            </div>
            {orders.data.length === 0 ? (
                <Card className="p-8 text-center text-sm text-muted">No deliveries here.</Card>
            ) : (
                <div className="grid gap-3 lg:grid-cols-2">{orders.data.map((o) => <DriverOrderCard key={o.id} order={o} />)}</div>
            )}
            <div className="mt-4"><Pagination links={orders.links} from={orders.from} to={orders.to} total={orders.total} /></div>
        </DriverLayout>
    );
}
