import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, Box, Clock, DollarSign, ShoppingCart, Star, Users } from 'lucide-react';
import {
    Area, AreaChart, CartesianGrid, Cell, Pie, PieChart, ResponsiveContainer, Tooltip, XAxis, YAxis,
} from 'recharts';
import AdminLayout from '../../Layouts/AdminLayout';

const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

const STATUS_TONE = {
    pending: 'var(--warning)',
    processing: 'var(--info)',
    ready_for_pickup: 'var(--primary)',
    shipped: 'var(--primary)',
    delivered: 'var(--success)',
    cancelled: 'var(--danger)',
};

const label = (s) => s.replace(/_/g, ' ').replace(/^\w/, (c) => c.toUpperCase());

function Card({ children, className = '' }) {
    return <section className={`rounded-card border border-line bg-surface ${className}`}>{children}</section>;
}

function Stat({ title, value, icon: Icon, tone }) {
    return (
        <Card className="p-4 md:p-5">
            <div className="flex items-center justify-between gap-3">
                <div className="min-w-0">
                    <p className="text-xs text-muted md:text-sm">{title}</p>
                    <p className="truncate text-xl font-bold md:text-2xl">{value}</p>
                </div>
                <span
                    className="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg"
                    style={{ color: tone, background: `color-mix(in srgb, ${tone} 15%, transparent)` }}
                >
                    <Icon size={20} />
                </span>
            </div>
        </Card>
    );
}

function StatusBadge({ status }) {
    const tone = STATUS_TONE[status] ?? 'var(--muted)';
    return (
        <span
            className="inline-block rounded-full px-2.5 py-0.5 text-xs font-medium"
            style={{ color: tone, background: `color-mix(in srgb, ${tone} 15%, transparent)` }}
        >
            {label(status)}
        </span>
    );
}

export default function Dashboard({ stats, revenueData, orderStatusData, recentOrders, topProducts, recentReviews }) {
    const revenue = revenueData.map((v, i) => ({ month: MONTHS[i], revenue: Number(v) }));
    const statuses = Object.entries(orderStatusData).map(([name, value]) => ({ name, value }));

    return (
        <AdminLayout title="Dashboard">
            <Head title="Dashboard" />

            <div className="mb-6 grid grid-cols-2 gap-3 md:gap-5 lg:grid-cols-4">
                <Stat title="Total Revenue" value={stats.revenue} icon={DollarSign} tone="var(--success)" />
                <Stat title="Total Orders" value={stats.orders} icon={ShoppingCart} tone="var(--info)" />
                <Stat title="Total Products" value={stats.products} icon={Box} tone="var(--primary)" />
                <Stat title="Total Customers" value={stats.customers} icon={Users} tone="var(--warning)" />
            </div>

            <div className="mb-6 grid grid-cols-2 gap-3 md:gap-5">
                <Stat title="Pending Orders" value={stats.pendingOrders} icon={Clock} tone="var(--warning)" />
                <Stat title="Low Stock" value={stats.lowStock} icon={AlertTriangle} tone="var(--danger)" />
            </div>

            <div className="mb-6 grid grid-cols-1 gap-5 lg:grid-cols-5">
                <Card className="p-4 md:p-6 lg:col-span-3">
                    <h3 className="mb-4 text-sm font-semibold md:text-base">Revenue Overview</h3>
                    <div className="h-64">
                        <ResponsiveContainer width="100%" height="100%">
                            <AreaChart data={revenue}>
                                <defs>
                                    <linearGradient id="rev" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stopColor="var(--primary)" stopOpacity={0.35} />
                                        <stop offset="100%" stopColor="var(--primary)" stopOpacity={0} />
                                    </linearGradient>
                                </defs>
                                <CartesianGrid stroke="var(--line)" vertical={false} />
                                <XAxis dataKey="month" stroke="var(--muted)" fontSize={12} tickLine={false} axisLine={false} />
                                <YAxis stroke="var(--muted)" fontSize={12} tickLine={false} axisLine={false} width={48} />
                                <Tooltip contentStyle={{ background: 'var(--surface)', border: '1px solid var(--line)', borderRadius: 8, color: 'var(--ink)' }} />
                                <Area type="monotone" dataKey="revenue" stroke="var(--primary)" strokeWidth={2} fill="url(#rev)" />
                            </AreaChart>
                        </ResponsiveContainer>
                    </div>
                </Card>

                <Card className="p-4 md:p-6 lg:col-span-2">
                    <h3 className="mb-4 text-sm font-semibold md:text-base">Order Status</h3>
                    {statuses.length === 0 ? (
                        <p className="py-16 text-center text-sm text-muted">No orders yet</p>
                    ) : (
                        <>
                            <div className="h-48">
                                <ResponsiveContainer width="100%" height="100%">
                                    <PieChart>
                                        <Pie data={statuses} dataKey="value" nameKey="name" innerRadius={50} outerRadius={80} stroke="var(--surface)">
                                            {statuses.map((s) => <Cell key={s.name} fill={STATUS_TONE[s.name] ?? 'var(--muted)'} />)}
                                        </Pie>
                                        <Tooltip contentStyle={{ background: 'var(--surface)', border: '1px solid var(--line)', borderRadius: 8, color: 'var(--ink)' }} />
                                    </PieChart>
                                </ResponsiveContainer>
                            </div>
                            <ul className="mt-3 space-y-1 text-sm">
                                {statuses.map((s) => (
                                    <li key={s.name} className="flex items-center justify-between">
                                        <span className="flex items-center gap-2">
                                            <span className="h-2.5 w-2.5 rounded-full" style={{ background: STATUS_TONE[s.name] ?? 'var(--muted)' }} />
                                            {label(s.name)}
                                        </span>
                                        <span className="text-muted">{s.value}</span>
                                    </li>
                                ))}
                            </ul>
                        </>
                    )}
                </Card>
            </div>

            <Card className="mb-6">
                <div className="flex items-center justify-between border-b border-line p-4 md:p-6">
                    <h3 className="text-sm font-semibold md:text-base">Recent Orders</h3>
                    <a href="/admin/orders" className="text-xs text-primary hover:underline md:text-sm">View All</a>
                </div>
                {recentOrders.length === 0 ? (
                    <p className="p-8 text-center text-sm text-muted">No orders yet</p>
                ) : (
                    <>
                        <div className="divide-y divide-line md:hidden">
                            {recentOrders.map((o) => (
                                <a key={o.id} href={o.url} className="block p-4 hover:bg-surface-2">
                                    <div className="mb-2 flex items-start justify-between gap-3">
                                        <div className="min-w-0">
                                            <p className="text-sm font-medium text-primary">{o.number}</p>
                                            <p className="truncate text-xs text-muted">{o.customer}</p>
                                        </div>
                                        <p className="text-sm font-bold">{o.total}</p>
                                    </div>
                                    <div className="flex items-center justify-between">
                                        <StatusBadge status={o.status} />
                                        <span className="text-xs text-muted">{o.date}</span>
                                    </div>
                                </a>
                            ))}
                        </div>
                        <div className="hidden overflow-x-auto md:block">
                            <table className="w-full text-sm">
                                <thead className="bg-surface-2 text-left text-xs uppercase text-muted">
                                    <tr>
                                        {['Order', 'Customer', 'Status', 'Total', 'Date'].map((h) => <th key={h} className="px-6 py-3 font-medium">{h}</th>)}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-line">
                                    {recentOrders.map((o) => (
                                        <tr key={o.id} className="hover:bg-surface-2">
                                            <td className="px-6 py-4"><a href={o.url} className="font-medium text-primary hover:underline">{o.number}</a></td>
                                            <td className="px-6 py-4">{o.customer}</td>
                                            <td className="px-6 py-4"><StatusBadge status={o.status} /></td>
                                            <td className="px-6 py-4 font-medium">{o.total}</td>
                                            <td className="px-6 py-4 text-muted">{o.date}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </>
                )}
            </Card>

            <div className="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <Card>
                    <h3 className="border-b border-line p-4 text-sm font-semibold md:p-6 md:text-base">Top Selling Products</h3>
                    <ul className="divide-y divide-line px-4 md:px-6">
                        {topProducts.length === 0 && <li className="py-8 text-center text-sm text-muted">No products yet</li>}
                        {topProducts.map((p) => (
                            <li key={p.id} className="flex items-center justify-between gap-3 py-3">
                                <div className="min-w-0">
                                    <p className="truncate text-sm font-medium">{p.name}</p>
                                    <p className="text-xs text-muted">{p.sold} sold</p>
                                </div>
                                <p className="text-sm font-semibold">{p.price}</p>
                            </li>
                        ))}
                    </ul>
                </Card>

                <Card>
                    <h3 className="border-b border-line p-4 text-sm font-semibold md:p-6 md:text-base">Recent Reviews</h3>
                    <ul className="divide-y divide-line px-4 md:px-6">
                        {recentReviews.length === 0 && <li className="py-8 text-center text-sm text-muted">No reviews yet</li>}
                        {recentReviews.map((r) => (
                            <li key={r.id} className="py-3">
                                <div className="flex items-center justify-between gap-3">
                                    <p className="truncate text-sm font-medium">{r.user} <span className="font-normal text-muted">on {r.product}</span></p>
                                    <span className="flex shrink-0 items-center gap-1 text-xs text-warning"><Star size={12} fill="currentColor" />{r.rating}</span>
                                </div>
                                {r.comment && <p className="mt-1 text-xs text-muted">{r.comment}</p>}
                            </li>
                        ))}
                    </ul>
                </Card>
            </div>
        </AdminLayout>
    );
}
