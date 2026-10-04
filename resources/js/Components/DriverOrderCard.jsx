import { Link } from '@inertiajs/react';
import { Banknote, ChevronRight, MapPin, Package } from 'lucide-react';
import { Badge } from './ui';

export function deliveryStage(o) {
    if (o.status === 'delivered') return ['Delivered', 'success'];
    if (o.status === 'cancelled') return ['Cancelled', 'danger'];
    if (o.status === 'shipped' || o.picked_up) return ['On the way', 'primary'];
    return ['To pick up', 'warning'];
}

/** Tappable delivery card used on the driver dashboard and order lists. */
export default function DriverOrderCard({ order }) {
    const [label, tone] = deliveryStage(order);
    return (
        <Link href={`/driver/orders/${order.id}`} className="flex items-center gap-3 rounded-card border border-line bg-surface p-4 active:bg-surface-2 hover:border-primary">
            <div className="min-w-0 flex-1 space-y-1">
                <div className="flex flex-wrap items-center gap-2">
                    <span className="font-semibold">{order.number}</span>
                    <Badge tone={tone}>{label}</Badge>
                    {order.cod && <Badge tone="warning"><Banknote size={12} className="mr-1 inline" />Collect {order.total}</Badge>}
                </div>
                <p className="truncate text-sm">{order.customer}</p>
                <p className="flex items-start gap-1 text-sm text-muted"><MapPin size={14} className="mt-0.5 shrink-0" /><span className="line-clamp-2">{order.address}</span></p>
                <p className="flex items-center gap-1 text-xs text-muted"><Package size={12} />{order.items} item{order.items === 1 ? '' : 's'} · {order.delivered_at ?? order.date}{order.earnings && ` · earned ${order.earnings}`}</p>
            </div>
            <ChevronRight size={20} className="shrink-0 text-muted" />
        </Link>
    );
}
