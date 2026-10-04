import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, ArrowDownRight, ArrowUpRight, Clock, DollarSign, Package, Plus, ShoppingCart, Wallet } from 'lucide-react';
import VendorLayout from '../../Layouts/VendorLayout';
import StatCard from '../../Components/StatCard';
import { Badge, Card, CardHeader, LinkButton, OrderStatusBadge } from '../../Components/ui';

function SalesChart({ data }) {
    const max = Math.max(1, ...data.map((d) => d.value));
    return (
        <div className="flex h-48 items-end gap-2 px-4 pb-4 pt-6" role="img" aria-label="Sales over the last 7 days">
            {data.map((d) => (
                <div key={d.date} className="flex flex-1 flex-col items-center gap-1">
                    <span className="text-[10px] text-muted">{d.label}</span>
                    <div className="w-full rounded-t-md bg-primary/80 transition-all" style={{ height: `${Math.max(4, (d.value / max) * 140)}px` }} title={`${d.date}: ${d.label}`} />
                    <span className="text-xs text-muted">{d.date}</span>
                </div>
            ))}
        </div>
    );
}

export default function Dashboard({ vendor, stats, chart, recentOrders, topProducts }) {
    const approved = vendor.status === 'approved';
    const up = stats.growth >= 0;

    return (
        <VendorLayout title="Dashboard" actions={approved && <LinkButton href="/vendor/products/create" className="hidden sm:inline-flex"><Plus size={16} />Add product</LinkButton>}>
            <Head title="Vendor dashboard" />

            {!approved && (
                <div className="mb-5 flex items-start gap-3 rounded-xl border border-warning/40 bg-warning/10 p-4 text-sm">
                    <AlertTriangle size={20} className="mt-0.5 shrink-0 text-warning" />
                    <div>
                        <p className="font-semibold">Your store is {vendor.status === 'pending' ? 'awaiting approval' : vendor.status}.</p>
                        <p className="text-muted">{vendor.status === 'pending'
                            ? 'An administrator will review your application. You can add products once your store is approved.'
                            : 'Contact the store administrator for details.'}</p>
                    </div>
                </div>
            )}

            <div className="mb-5 grid grid-cols-2 gap-3 xl:grid-cols-4">
                <StatCard label="Sales this month" value={stats.month_sales} icon={DollarSign} tone="primary"
                    sub={<p className={`mt-0.5 inline-flex items-center gap-0.5 text-xs ${up ? 'text-success' : 'text-danger'}`}>{up ? <ArrowUpRight size={12} /> : <ArrowDownRight size={12} />}{Math.abs(stats.growth).toFixed(1)}% vs last month</p>} />
                <StatCard label="Available balance" value={stats.balance} icon={Wallet} tone="success" sub={<p className="mt-0.5 text-xs text-muted">Earned {stats.earnings}</p>} />
                <StatCard label="Products" value={`${stats.active_products}/${stats.total_products}`} icon={Package} tone="info" sub={<p className="mt-0.5 text-xs text-muted">active / total</p>} />
                <StatCard label="Pending orders" value={stats.pending_orders} icon={Clock} tone="warning" />
            </div>

            <div className="grid gap-5 xl:grid-cols-3">
                <Card className="xl:col-span-2">
                    <CardHeader title="Sales, last 7 days" />
                    <SalesChart data={chart} />
                </Card>

                <Card>
                    <CardHeader title="Top products" />
                    {topProducts.length === 0 ? <p className="p-6 text-sm text-muted">No sales yet.</p> : (
                        <ul className="divide-y divide-line">
                            {topProducts.map((p) => (
                                <li key={p.id} className="flex items-center gap-3 px-4 py-3">
                                    <div className="h-10 w-10 shrink-0 overflow-hidden rounded-lg bg-surface-2">{p.image && <img src={p.image} alt="" className="h-full w-full object-cover" />}</div>
                                    <div className="min-w-0 flex-1">
                                        <Link href={`/vendor/products/${p.id}/edit`} className="block truncate text-sm font-medium hover:text-primary">{p.name}</Link>
                                        <p className="text-xs text-muted">{p.sold} sold</p>
                                    </div>
                                    <Badge tone={p.is_active ? 'success' : 'muted'}>{p.is_active ? 'Active' : 'Hidden'}</Badge>
                                </li>
                            ))}
                        </ul>
                    )}
                </Card>
            </div>

            <Card className="mt-5">
                <CardHeader title="Recent orders"><Link href="/vendor/orders" className="text-sm text-primary hover:underline">View all</Link></CardHeader>
                {recentOrders.length === 0 ? <p className="p-6 text-sm text-muted">No orders yet.</p> : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-surface-2 text-left text-xs uppercase text-muted">
                                <tr><th className="px-4 py-3">Order</th><th className="px-4 py-3">Customer</th><th className="px-4 py-3">Your items</th><th className="px-4 py-3">Your total</th><th className="px-4 py-3">Status</th><th className="px-4 py-3">Date</th></tr>
                            </thead>
                            <tbody className="divide-y divide-line">
                                {recentOrders.map((o) => (
                                    <tr key={o.id}>
                                        <td className="px-4 py-3"><Link href={`/vendor/orders/${o.id}`} className="font-medium text-primary hover:underline">{o.number}</Link></td>
                                        <td className="px-4 py-3">{o.customer}</td>
                                        <td className="px-4 py-3 text-muted">{o.items}</td>
                                        <td className="px-4 py-3 font-medium">{o.total}</td>
                                        <td className="px-4 py-3"><OrderStatusBadge status={o.status} /></td>
                                        <td className="px-4 py-3 text-muted">{o.date}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </Card>
        </VendorLayout>
    );
}
