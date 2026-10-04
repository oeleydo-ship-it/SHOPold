import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { ArrowLeft, Star } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { Badge, Button, Card, CardHeader, LinkButton, Select } from '../../../Components/ui';
import { VENDOR_TONE, VendorLogo } from './Index';

const STATUSES = [['pending', 'Pending'], ['approved', 'Approved'], ['suspended', 'Suspended'], ['rejected', 'Rejected']];
const Item = ({ k, children }) => <div><p className="text-xs text-muted">{k}</p><p className="text-sm">{children}</p></div>;
const Line = ({ k, v, strong, tone }) => <div className={`flex justify-between gap-3 text-sm ${strong ? 'border-t border-line pt-2 font-semibold' : ''}`}><span className="text-muted">{k}</span><span className={tone === 'danger' ? 'text-danger' : ''}>{v}</span></div>;

export default function Show({ vendor, earnings }) {
    const [status, setStatus] = useState(vendor.status);

    return (
        <AdminLayout title="Vendor Details">
            <Head title={vendor.store_name} />
            <Link href="/admin/vendors" className="mb-5 inline-flex items-center gap-2 text-sm text-muted hover:text-ink"><ArrowLeft size={16} />Back to Vendors</Link>

            <div className="grid gap-5 lg:grid-cols-3">
                <div className="space-y-5 lg:col-span-2">
                    <Card className="p-4 md:p-6">
                        <div className="mb-5 flex items-center gap-4">
                            <VendorLogo src={vendor.logo} size="h-16 w-16" />
                            <div><h2 className="text-lg font-bold">{vendor.store_name}</h2><p className="text-sm text-muted">{vendor.email}</p><p className="text-sm text-muted">{vendor.phone}</p></div>
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Item k="Owner">{vendor.owner ?? 'N/A'}</Item>
                            <Item k="Registered">{vendor.registered}</Item>
                            <Item k="Address">{vendor.address || 'Not provided'}</Item>
                            <Item k="Commission Rate">{vendor.commission_rate}%</Item>
                        </div>
                        {vendor.description && <div className="mt-4"><Item k="Description"><span className="whitespace-pre-wrap">{vendor.description}</span></Item></div>}
                    </Card>

                    <Card>
                        <CardHeader title={`Products (${vendor.products_count})`} />
                        {vendor.products.length === 0 ? <p className="p-6 text-sm text-muted">No products yet</p> : (
                            <ul className="divide-y divide-line">
                                {vendor.products.map((p) => (
                                    <li key={p.id} className="flex items-center justify-between gap-3 p-4 md:px-6">
                                        <div><p className="text-sm font-medium">{p.name}</p><p className="text-xs text-muted">{p.price}</p></div>
                                        <Badge tone={p.is_active ? 'success' : 'muted'}>{p.is_active ? 'Active' : 'Inactive'}</Badge>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Card>
                </div>

                <div className="space-y-5">
                    <Card>
                        <CardHeader title="Status"><Badge tone={VENDOR_TONE[vendor.status] ?? 'muted'}>{vendor.status[0].toUpperCase() + vendor.status.slice(1)}</Badge></CardHeader>
                        <div className="space-y-3 p-4 md:p-6">
                            <Select aria-label="Vendor status" value={status} onChange={(e) => setStatus(e.target.value)}>{STATUSES.map(([v, l]) => <option key={v} value={v}>{l}</option>)}</Select>
                            <Button className="w-full" disabled={status === vendor.status} onClick={() => router.patch(`/admin/vendors/${vendor.id}/status`, { status }, { preserveScroll: true })}>Update Status</Button>
                        </div>
                    </Card>

                    <Card>
                        <CardHeader title="Earnings" />
                        <div className="space-y-2 p-4 md:p-6">
                            <Line k="Total Sales" v={earnings.sales} />
                            <Line k={`Platform Fee (${earnings.fee_rate}%)`} v={`-${earnings.fee}`} tone="danger" />
                            <Line k="Vendor Earnings" v={earnings.earnings} />
                            <Line k="Paid Out" v={earnings.paid_out} />
                            <Line k="Available Balance" v={earnings.balance} strong />
                            <LinkButton href={`/admin/vendors/${vendor.id}/payouts`} tone="neutral" className="mt-3 w-full">View Payouts</LinkButton>
                        </div>
                    </Card>

                    <Card>
                        <CardHeader title="Rating" />
                        <div className="flex items-center gap-4 p-4 md:p-6">
                            <div className="text-3xl font-bold">{vendor.rating}</div>
                            <div>
                                <div className="flex text-warning" aria-label={`${vendor.rating} out of 5`}>{[1, 2, 3, 4, 5].map((i) => <Star key={i} size={16} fill={i <= Math.round(Number(vendor.rating)) ? 'currentColor' : 'none'} />)}</div>
                                <p className="text-xs text-muted">{vendor.rating_count} reviews</p>
                            </div>
                        </div>
                    </Card>
                </div>
            </div>
        </AdminLayout>
    );
}
