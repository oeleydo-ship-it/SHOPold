import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { CheckCircle2, Download, Mail, Search, UserX } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { Badge, Button, Card, Input, LinkButton, Pagination, Select } from '../../../Components/ui';
import { DataTable, RowActions } from '../../../Components/crud';

function Stat({ label, value, icon: Icon, tone }) {
    return (
        <Card className="flex items-center gap-4 p-4 md:p-5">
            <span className="flex h-11 w-11 items-center justify-center rounded-lg" style={{ color: tone, background: `color-mix(in srgb, ${tone} 15%, transparent)` }}><Icon size={20} /></span>
            <div><p className="text-sm text-muted">{label}</p><p className="text-2xl font-bold">{value}</p></div>
        </Card>
    );
}

export default function Index({ subscribers, filters, totalActive, totalUnsubscribed }) {
    const [form, setForm] = useState({ search: filters.search ?? '', status: filters.status ?? '' });
    const go = (e) => {
        e.preventDefault();
        router.get('/admin/newsletters', Object.fromEntries(Object.entries(form).filter(([, v]) => v)), { preserveState: true });
    };

    const columns = [
        { key: 'email', label: 'Email', render: (s) => <span className="flex items-center gap-3 font-medium"><Mail size={16} className="text-muted" />{s.email}</span> },
        {
            key: 'status', label: 'Status',
            render: (s) => <Badge tone={s.unsubscribed ? 'muted' : 'success'}>{s.unsubscribed ? 'Unsubscribed' : 'Active'}</Badge>,
        },
        { key: 'subscribed_at', label: 'Subscribed At', render: (s) => <span className="text-muted">{s.subscribed_at ?? '-'}</span> },
        {
            key: 'actions', label: '', className: 'text-right',
            render: (s) => <RowActions deleteHref={`/admin/newsletters/${s.id}`} deleteMessage={`Delete subscriber ${s.email}?`} />,
        },
    ];

    return (
        <AdminLayout title="Newsletter Subscribers">
            <Head title="Newsletter Subscribers" />
            <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                <p className="text-sm text-muted">Manage your newsletter subscribers</p>
                {/* CSV download is a plain file response, so it must be a normal link, not an Inertia visit */}
                <LinkButton external href="/admin/newsletters/export"><Download size={16} />Export CSV</LinkButton>
            </div>

            <div className="mb-4 grid gap-3 sm:grid-cols-3">
                <Stat label="Total Subscribers" value={totalActive + totalUnsubscribed} icon={Mail} tone="var(--primary)" />
                <Stat label="Active" value={totalActive} icon={CheckCircle2} tone="var(--success)" />
                <Stat label="Unsubscribed" value={totalUnsubscribed} icon={UserX} tone="var(--muted)" />
            </div>

            <Card className="mb-4 p-3 md:p-4">
                <form onSubmit={go} className="flex flex-col gap-3 sm:flex-row">
                    <div className="relative flex-1">
                        <Search size={16} className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
                        <Input value={form.search} onChange={(e) => setForm({ ...form, search: e.target.value })} placeholder="Search by email…" className="pl-9" aria-label="Search by email" />
                    </div>
                    <Select value={form.status} onChange={(e) => setForm({ ...form, status: e.target.value })} className="sm:!w-auto" aria-label="Status">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="unsubscribed">Unsubscribed</option>
                    </Select>
                    <Button type="submit">Filter</Button>
                </form>
            </Card>

            <Card className="overflow-hidden">
                <DataTable columns={columns} rows={subscribers.data} empty="No subscribers found." />
                <Pagination links={subscribers.links} from={subscribers.from} to={subscribers.to} total={subscribers.total} />
            </Card>
        </AdminLayout>
    );
}
