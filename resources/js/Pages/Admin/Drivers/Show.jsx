import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Pencil, Star } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import StatCard from '../../../Components/StatCard';
import { Badge, Button, Card, CardHeader, Field, Input, LinkButton, OrderStatusBadge, titleCase } from '../../../Components/ui';
import { DRIVER_TONE } from './Index';

const Row = ({ k, children }) => <div className="flex justify-between gap-3 text-sm"><dt className="text-muted">{k}</dt><dd className="break-all text-right">{children || '-'}</dd></div>;

export default function Show({ driver, stats, orders }) {
    const commission = useForm({ commission_rate: driver.commission_rate });
    const bonus = useForm({ amount: '', description: '' });

    return (
        <AdminLayout title="Driver">
            <Head title={driver.name} />
            <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
                <Link href="/admin/drivers" className="inline-flex items-center gap-2 text-sm text-muted hover:text-ink"><ArrowLeft size={16} />Back to Drivers</Link>
                <LinkButton href={`/admin/users/${driver.id}/edit`} tone="neutral"><Pencil size={16} />Edit Driver</LinkButton>
            </div>

            <div className="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-5">
                <StatCard label="Total Deliveries" value={stats.total_deliveries} />
                <StatCard label="Completed" value={stats.completed} tone="success" />
                <StatCard label="Active" value={stats.active} tone="warning" />
                <StatCard label="This Month" value={stats.this_month} tone="info" />
                <StatCard label="Total Earnings" value={driver.earnings} tone="primary" />
            </div>

            <div className="grid gap-5 lg:grid-cols-3">
                <div className="space-y-5 lg:col-span-2">
                    <Card>
                        <CardHeader title="Performance & Rating" />
                        <div className="grid gap-4 p-4 sm:grid-cols-3 md:p-6">
                            <div>
                                <div className="text-3xl font-bold">{driver.rating}</div>
                                <div className="flex text-warning" aria-label={`${driver.rating} out of 5`}>{[1, 2, 3, 4, 5].map((i) => <Star key={i} size={16} fill={i <= Math.round(driver.rating_raw) ? 'currentColor' : 'none'} />)}</div>
                                <p className="text-xs text-muted">{driver.ratings_count} ratings</p>
                            </div>
                            <div><div className="text-2xl font-bold">{stats.total_tips}</div><p className="text-xs text-muted">Total tips</p></div>
                            <div><div className="text-2xl font-bold">{driver.commission_rate}%</div><p className="text-xs text-muted">Commission rate</p></div>
                        </div>
                    </Card>

                    <Card>
                        <CardHeader title="Recent Assigned Orders" />
                        {orders.length === 0 ? <p className="p-6 text-sm text-muted">No orders assigned yet.</p> : (
                            <ul className="divide-y divide-line">
                                {orders.map((o) => (
                                    <li key={o.id} className="flex flex-wrap items-center justify-between gap-3 p-4 md:px-6">
                                        <div><Link href={`/admin/orders/${o.id}`} className="font-medium text-primary hover:underline">{o.number}</Link><p className="text-xs text-muted">{o.customer} · {o.place}</p></div>
                                        <div className="flex items-center gap-3"><span className="text-sm font-medium">{o.total}</span><OrderStatusBadge status={o.status} /></div>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Card>
                </div>

                <div className="space-y-5">
                    <Card>
                        <div className="p-4 md:p-6">
                            <div className="mb-4 flex items-center gap-3">
                                <span className="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-lg font-semibold text-primary">{driver.name?.[0]?.toUpperCase()}</span>
                                <div><h3 className="font-semibold">{driver.name}</h3><Badge tone={DRIVER_TONE[driver.status] ?? 'muted'}>{titleCase(driver.status ?? 'offline')}</Badge></div>
                            </div>
                            <dl className="space-y-2"><Row k="Email">{driver.email}</Row><Row k="Phone">{driver.phone}</Row><Row k="Vehicle">{driver.vehicle}</Row><Row k="License">{driver.license}</Row><Row k="Joined">{driver.joined}</Row></dl>
                        </div>
                    </Card>

                    <Card>
                        <CardHeader title="Commission Rate" />
                        <form onSubmit={(e) => { e.preventDefault(); commission.patch(`/admin/drivers/${driver.id}/commission`, { preserveScroll: true }); }} className="space-y-3 p-4 md:p-6">
                            <Field label="Rate (%)" error={commission.errors.commission_rate}><Input type="number" min="0" max="100" step="0.01" required value={commission.data.commission_rate} onChange={(e) => commission.setData('commission_rate', e.target.value)} /></Field>
                            <Button type="submit" className="w-full" disabled={commission.processing}>Update Rate</Button>
                        </form>
                    </Card>

                    <Card>
                        <CardHeader title="Add Bonus" />
                        <form onSubmit={(e) => { e.preventDefault(); bonus.post(`/admin/drivers/${driver.id}/bonus`, { preserveScroll: true, onSuccess: () => bonus.reset() }); }} className="space-y-3 p-4 md:p-6">
                            <Field label="Amount" error={bonus.errors.amount}><Input type="number" min="0.01" step="0.01" required value={bonus.data.amount} onChange={(e) => bonus.setData('amount', e.target.value)} /></Field>
                            <Field label="Description" error={bonus.errors.description}><Input required maxLength={255} placeholder="e.g., Performance bonus" value={bonus.data.description} onChange={(e) => bonus.setData('description', e.target.value)} /></Field>
                            <Button type="submit" className="w-full" disabled={bonus.processing}>Add Bonus</Button>
                        </form>
                    </Card>
                </div>
            </div>
        </AdminLayout>
    );
}
