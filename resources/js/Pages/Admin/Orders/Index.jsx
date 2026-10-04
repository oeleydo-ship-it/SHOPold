import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Check, Clock, Eye, Plus, Search, Truck } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import {
    Card, EmptyState, Input, LinkButton, OrderStatusBadge, Pagination, PaymentStatusBadge, Select, Button, Badge,
} from '../../../Components/ui';

function Fulfillment({ o }) {
    const all = o.items > 0 && o.delivered === o.items;
    const tone = all ? 'success' : o.pending > 0 ? 'warning' : 'info';
    const Icon = all ? Check : o.pending > 0 ? Clock : Truck;
    return (
        <Badge tone={tone}>
            <span className="inline-flex items-center gap-1"><Icon size={12} />{o.delivered}/{o.items}</span>
        </Badge>
    );
}

export default function Index({ orders, filters }) {
    const [form, setForm] = useState({
        search: filters.search ?? '',
        status: filters.status ?? '',
        payment_status: filters.payment_status ?? '',
    });
    const set = (k) => (e) => setForm((f) => ({ ...f, [k]: e.target.value }));

    const submit = (e) => {
        e.preventDefault();
        const query = Object.fromEntries(Object.entries(form).filter(([, v]) => v !== ''));
        router.get('/admin/orders', query, { preserveState: true });
    };

    const reset = () => {
        setForm({ search: '', status: '', payment_status: '' });
        router.get('/admin/orders');
    };

    return (
        <AdminLayout title="Orders">
            <Head title="Orders" />

            <div className="mb-4 flex justify-end">
                <LinkButton href="/admin/orders/create"><Plus size={16} />Create Order</LinkButton>
            </div>

            <Card className="mb-4 p-3 md:p-4">
                <form onSubmit={submit} className="flex flex-col gap-3 md:flex-row">
                    <div className="relative flex-1">
                        <Search size={16} className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
                        <Input value={form.search} onChange={set('search')} placeholder="Search orders, customers…" className="pl-9" aria-label="Search orders" />
                    </div>
                    <div className="grid grid-cols-2 gap-2 md:flex">
                        <Select value={form.status} onChange={set('status')} aria-label="Order status">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="processing">Processing</option>
                            <option value="ready_for_pickup">Ready for Pickup</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                        </Select>
                        <Select value={form.payment_status} onChange={set('payment_status')} aria-label="Payment status">
                            <option value="">All Payment</option>
                            <option value="pending">Pending</option>
                            <option value="paid">Paid</option>
                            <option value="failed">Failed</option>
                            <option value="refunded">Refunded</option>
                        </Select>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" tone="primary" className="flex-1">Filter</Button>
                        <Button tone="neutral" onClick={reset}>Reset</Button>
                    </div>
                </form>
            </Card>

            <Card className="overflow-hidden">
                {orders.data.length === 0 ? (
                    <EmptyState>No orders match your filters.</EmptyState>
                ) : (
                    <>
                        <div className="divide-y divide-line md:hidden">
                            {orders.data.map((o) => (
                                <Link key={o.id} href={`/admin/orders/${o.id}`} className="block p-4 hover:bg-surface-2">
                                    <div className="mb-2 flex items-start justify-between gap-3">
                                        <div className="min-w-0">
                                            <p className="text-sm font-semibold">{o.number}</p>
                                            <p className="truncate text-xs text-muted">{o.customer}</p>
                                            <p className="truncate text-xs text-muted">{o.email}</p>
                                        </div>
                                        <div className="shrink-0 text-right">
                                            <p className="font-bold">{o.total}</p>
                                            <p className="text-xs text-muted">{o.date}</p>
                                        </div>
                                    </div>
                                    <div className="flex flex-wrap gap-2">
                                        <OrderStatusBadge status={o.status} />
                                        <PaymentStatusBadge status={o.payment_status} />
                                        <Fulfillment o={o} />
                                    </div>
                                </Link>
                            ))}
                        </div>

                        <div className="hidden overflow-x-auto md:block">
                            <table className="w-full text-sm">
                                <thead className="bg-surface-2 text-left text-xs uppercase text-muted">
                                    <tr>
                                        {['Order', 'Customer', 'Status', 'Fulfillment', 'Payment', 'Total', 'Date', ''].map((h) => (
                                            <th key={h} className="px-6 py-3 font-medium">{h}</th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-line">
                                    {orders.data.map((o) => (
                                        <tr key={o.id} className="hover:bg-surface-2">
                                            <td className="px-6 py-4 font-medium">{o.number}</td>
                                            <td className="px-6 py-4">
                                                <p className="font-medium">{o.customer}</p>
                                                <p className="text-xs text-muted">{o.email}</p>
                                            </td>
                                            <td className="px-6 py-4"><OrderStatusBadge status={o.status} /></td>
                                            <td className="px-6 py-4"><Fulfillment o={o} /></td>
                                            <td className="px-6 py-4"><PaymentStatusBadge status={o.payment_status} /></td>
                                            <td className="px-6 py-4 font-medium">{o.total}</td>
                                            <td className="px-6 py-4 text-muted">{o.date}</td>
                                            <td className="px-6 py-4">
                                                <Link href={`/admin/orders/${o.id}`} className="text-primary hover:text-primary-hover" aria-label={`View ${o.number}`}>
                                                    <Eye size={18} />
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </>
                )}
                <Pagination links={orders.links} from={orders.from} to={orders.to} total={orders.total} />
            </Card>
        </AdminLayout>
    );
}
