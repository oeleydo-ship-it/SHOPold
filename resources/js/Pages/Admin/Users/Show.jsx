import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Pencil } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { Badge, Card, CardHeader, LinkButton, OrderStatusBadge } from '../../../Components/ui';
import { Avatar, RoleBadge } from './Index';

function Row({ label, children }) {
    return (
        <div className="flex justify-between gap-4 py-2 text-sm">
            <dt className="text-muted">{label}</dt>
            <dd className="text-right font-medium">{children || '-'}</dd>
        </div>
    );
}

export default function Show({ user }) {
    const hasAddress = user.address || user.city || user.country;
    const stats = [
        ['Orders', user.orders_count, 'text-primary'],
        ['Total Spent', user.total_spent, 'text-success'],
        ['Reviews', user.reviews_count, 'text-info'],
        ['Wishlist', user.wishlist_count, 'text-warning'],
    ];

    return (
        <AdminLayout title="User Details">
            <Head title={user.name} />
            <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
                <Link href="/admin/users" className="inline-flex items-center gap-2 text-sm text-muted hover:text-ink"><ArrowLeft size={16} />Back to Users</Link>
                <LinkButton href={`/admin/users/${user.id}/edit`}><Pencil size={16} />Edit User</LinkButton>
            </div>

            <div className="grid gap-5 lg:grid-cols-3">
                <div className="space-y-5">
                    <Card className="p-6 text-center">
                        <div className="mb-3 flex justify-center"><Avatar user={user} size="h-24 w-24 text-3xl" /></div>
                        <h2 className="text-xl font-bold">{user.name}</h2>
                        <p className="text-muted">{user.email}</p>
                        <div className="mt-3 flex justify-center gap-2">
                            <RoleBadge user={user} />
                            <Badge tone={user.is_active ? 'success' : 'danger'}>{user.is_active ? 'Active' : 'Inactive'}</Badge>
                        </div>
                    </Card>

                    <Card>
                        <CardHeader title="Contact" />
                        <dl className="divide-y divide-line px-4 py-2 md:px-6">
                            <Row label="Phone">{user.phone}</Row>
                            <Row label="Date of Birth">{user.date_of_birth}</Row>
                            <Row label="Gender">{user.gender && user.gender[0].toUpperCase() + user.gender.slice(1)}</Row>
                            <Row label="Company">{user.company}</Row>
                            {user.website && <Row label="Website"><a href={user.website} target="_blank" rel="noreferrer" className="text-primary hover:underline">{user.website}</a></Row>}
                        </dl>
                    </Card>

                    <Card>
                        <CardHeader title="Address" />
                        <div className="p-4 text-sm md:p-6">
                            {hasAddress ? (
                                <>
                                    <p>{user.address}</p>
                                    <p className="text-muted">{user.city}{user.state ? `, ${user.state}` : ''} {user.zip_code}</p>
                                    <p className="text-muted">{user.country}</p>
                                </>
                            ) : <p className="text-muted">No address on file.</p>}
                        </div>
                    </Card>
                </div>

                <div className="space-y-5 lg:col-span-2">
                    <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
                        {stats.map(([k, v, tone]) => (
                            <Card key={k} className="p-4 text-center">
                                <p className={`text-2xl font-bold ${tone}`}>{v}</p>
                                <p className="text-xs text-muted">{k}</p>
                            </Card>
                        ))}
                    </div>

                    <Card>
                        <CardHeader title="Activity" />
                        <dl className="divide-y divide-line px-4 py-2 md:px-6">
                            <Row label="Member since">{user.joined}</Row>
                            <Row label="Last login">{user.last_login ?? 'Never'}</Row>
                            {user.last_login_ip && <Row label="Last login IP">{user.last_login_ip}</Row>}
                        </dl>
                    </Card>

                    {user.notes && (
                        <Card>
                            <CardHeader title="Admin Notes" />
                            <p className="whitespace-pre-wrap p-4 text-sm md:p-6">{user.notes}</p>
                        </Card>
                    )}

                    <Card>
                        <CardHeader title="Recent Orders">
                            {user.orders_count > 5 && <Link href={`/admin/orders?search=${encodeURIComponent(user.email)}`} className="text-sm text-primary hover:underline">View All</Link>}
                        </CardHeader>
                        {user.recent_orders.length === 0 ? (
                            <p className="p-8 text-center text-sm text-muted">No orders yet.</p>
                        ) : (
                            <ul className="divide-y divide-line">
                                {user.recent_orders.map((o) => (
                                    <li key={o.id} className="flex items-center justify-between gap-3 px-4 py-3 md:px-6">
                                        <div>
                                            <Link href={`/admin/orders/${o.id}`} className="font-medium text-primary hover:underline">{o.number}</Link>
                                            <p className="text-xs text-muted">{o.date}</p>
                                        </div>
                                        <div className="flex items-center gap-3"><OrderStatusBadge status={o.status} /><span className="font-medium">{o.total}</span></div>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Card>
                </div>
            </div>
        </AdminLayout>
    );
}
