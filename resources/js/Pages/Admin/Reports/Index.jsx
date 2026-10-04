import { Head, Link, router } from '@inertiajs/react';
import { ArrowDownRight, ArrowUpRight, DollarSign, ShoppingCart, TrendingUp, UserPlus } from 'lucide-react';
import { Area, AreaChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import AdminLayout from '../../../Layouts/AdminLayout';
import StatCard from '../../../Components/StatCard';
import { Card, CardHeader, OrderStatusBadge, PaymentStatusBadge, Select } from '../../../Components/ui';

const STATUSES = [['pending', 'var(--warning)'], ['processing', 'var(--info)'], ['shipped', 'var(--primary)'], ['delivered', 'var(--success)'], ['cancelled', 'var(--danger)']];
const PERIODS = [['7', 'Last 7 days'], ['30', 'Last 30 days'], ['90', 'Last 90 days'], ['365', 'Last year']];

function Change({ value }) {
    const up = value >= 0;
    const Icon = up ? ArrowUpRight : ArrowDownRight;
    return <span className={`mt-1 flex items-center gap-1 text-xs ${up ? 'text-success' : 'text-danger'}`}><Icon size={14} />{Math.abs(value)}% vs previous period</span>;
}

export default function Index({ period, stats, daily, topProducts, ordersByStatus, topCategories, recentOrders }) {
    const maxCat = Math.max(1, ...topCategories.map((c) => c.raw));

    return (
        <AdminLayout title="Reports & Analytics">
            <Head title="Reports & Analytics" />

            <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
                <div className="flex gap-3 text-sm">
                    <Link href="/admin/reports/sales" className="text-primary hover:underline">Sales report</Link>
                    <Link href="/admin/reports/products" className="text-primary hover:underline">Products report</Link>
                    <Link href="/admin/reports/customers" className="text-primary hover:underline">Customers report</Link>
                </div>
                <Select value={period} onChange={(e) => router.get('/admin/reports', { period: e.target.value }, { preserveState: true })} className="!w-auto" aria-label="Period">
                    {PERIODS.map(([v, l]) => <option key={v} value={v}>{l}</option>)}
                </Select>
            </div>

            <div className="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
                <StatCard label="Total Revenue" value={stats.revenue} icon={DollarSign} tone="success" sub={<Change value={stats.revenueChange} />} />
                <StatCard label="Total Orders" value={stats.orders.toLocaleString()} icon={ShoppingCart} tone="info" sub={<Change value={stats.ordersChange} />} />
                <StatCard label="Avg Order Value" value={stats.avgOrderValue} icon={TrendingUp} tone="primary" />
                <StatCard label="New Customers" value={stats.newCustomers.toLocaleString()} icon={UserPlus} tone="warning" />
            </div>

            <div className="mb-5 grid gap-5 lg:grid-cols-3">
                <Card className="p-4 md:p-6 lg:col-span-2">
                    <h3 className="mb-4 text-sm font-semibold md:text-base">Revenue Overview</h3>
                    {daily.length === 0 ? <p className="py-16 text-center text-sm text-muted">No paid orders in this period</p> : (
                        <div className="h-64" role="img" aria-label="Daily revenue chart">
                            <ResponsiveContainer width="100%" height="100%">
                                <AreaChart data={daily}>
                                    <defs><linearGradient id="rv" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stopColor="var(--primary)" stopOpacity={0.35} /><stop offset="100%" stopColor="var(--primary)" stopOpacity={0} /></linearGradient></defs>
                                    <CartesianGrid stroke="var(--line)" vertical={false} />
                                    <XAxis dataKey="date" stroke="var(--muted)" fontSize={12} tickLine={false} axisLine={false} />
                                    <YAxis stroke="var(--muted)" fontSize={12} tickLine={false} axisLine={false} width={56} tickFormatter={(v) => v.toLocaleString()} />
                                    <Tooltip contentStyle={{ background: 'var(--surface)', border: '1px solid var(--line)', borderRadius: 8, color: 'var(--ink)' }} formatter={(v, n) => [Number(v).toLocaleString(), n === 'revenue' ? 'Revenue' : 'Orders']} />
                                    <Area type="monotone" dataKey="revenue" stroke="var(--primary)" strokeWidth={2} fill="url(#rv)" />
                                </AreaChart>
                            </ResponsiveContainer>
                        </div>
                    )}
                </Card>
                <Card className="p-4 md:p-6">
                    <h3 className="mb-4 text-sm font-semibold md:text-base">Orders by Status</h3>
                    <ul className="space-y-3">
                        {STATUSES.map(([s, c]) => (
                            <li key={s} className="flex items-center justify-between text-sm">
                                <span className="flex items-center gap-2 capitalize"><span className="h-2.5 w-2.5 rounded-full" style={{ background: c }} />{s}</span>
                                <span className="font-medium">{ordersByStatus[s] ?? 0}</span>
                            </li>
                        ))}
                    </ul>
                </Card>
            </div>

            <div className="mb-5 grid gap-5 lg:grid-cols-2">
                <Card>
                    <CardHeader title="Top Selling Products"><Link href="/admin/reports/products" className="text-sm text-primary hover:underline">View All</Link></CardHeader>
                    {topProducts.length === 0 ? <p className="p-8 text-center text-sm text-muted">No sales data yet</p> : (
                        <ul className="divide-y divide-line px-4 md:px-6">
                            {topProducts.map((p) => (
                                <li key={p.id} className="flex items-center gap-3 py-3">
                                    <div className="h-10 w-10 shrink-0 overflow-hidden rounded bg-surface-2">{p.image && <img src={p.image} alt="" className="h-full w-full object-cover" />}</div>
                                    <div className="min-w-0 flex-1"><p className="truncate text-sm font-medium">{p.name}</p><p className="text-xs text-muted">{p.sold} sold</p></div>
                                    <span className="text-sm font-semibold">{p.revenue}</span>
                                </li>
                            ))}
                        </ul>
                    )}
                </Card>
                <Card>
                    <CardHeader title="Top Categories" />
                    {topCategories.length === 0 ? <p className="p-8 text-center text-sm text-muted">No category data yet</p> : (
                        <ul className="space-y-4 p-4 md:p-6">
                            {topCategories.map((c) => (
                                <li key={c.name}>
                                    <div className="mb-1 flex justify-between text-sm"><span>{c.name}</span><span className="font-medium">{c.revenue}</span></div>
                                    <div className="h-2 overflow-hidden rounded-full bg-line"><div className="h-2 rounded-full bg-primary" style={{ width: `${(c.raw / maxCat) * 100}%` }} /></div>
                                </li>
                            ))}
                        </ul>
                    )}
                </Card>
            </div>

            <Card>
                <CardHeader title="Recent Orders"><Link href="/admin/orders" className="text-sm text-primary hover:underline">View All</Link></CardHeader>
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="bg-surface-2 text-left text-xs uppercase text-muted"><tr>{['Order', 'Customer', 'Status', 'Payment', 'Total', 'Date'].map((h) => <th key={h} className="px-4 py-3 font-medium md:px-6">{h}</th>)}</tr></thead>
                        <tbody className="divide-y divide-line">
                            {recentOrders.map((o) => (
                                <tr key={o.id} className="hover:bg-surface-2">
                                    <td className="px-4 py-3 md:px-6"><Link href={`/admin/orders/${o.id}`} className="font-medium text-primary hover:underline">{o.number}</Link></td>
                                    <td className="px-4 py-3 md:px-6">{o.customer}</td>
                                    <td className="px-4 py-3 md:px-6"><OrderStatusBadge status={o.status} /></td>
                                    <td className="px-4 py-3 md:px-6"><PaymentStatusBadge status={o.payment_status} /></td>
                                    <td className="px-4 py-3 font-medium md:px-6">{o.total}</td>
                                    <td className="px-4 py-3 text-muted md:px-6">{o.date}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </Card>
        </AdminLayout>
    );
}
