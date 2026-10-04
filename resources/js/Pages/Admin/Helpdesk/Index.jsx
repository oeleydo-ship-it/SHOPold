import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { CircleDot, CheckCircle2, Clock, Eye, Hourglass, Bot, Search } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import StatCard from '../../../Components/StatCard';
import { TicketPriority, TicketStatus } from '../../../Components/helpdesk';
import { Badge, Button, Card, Input, LinkButton, Pagination, Select } from '../../../Components/ui';
import { DataTable, RowActions } from '../../../Components/crud';

export default function Index({ tickets, stats, filters }) {
    const [form, setForm] = useState({ search: filters.search ?? '', status: filters.status ?? '', priority: filters.priority ?? '' });
    const hasFilters = Object.values(filters).some(Boolean);
    const set = (k) => (e) => setForm((f) => ({ ...f, [k]: e.target.value }));
    const go = (e) => { e.preventDefault(); router.get('/admin/helpdesk', Object.fromEntries(Object.entries(form).filter(([, v]) => v)), { preserveState: true }); };

    const columns = [
        {
            key: 'subject', label: 'Ticket',
            render: (t) => (
                <div>
                    <Link href={`/admin/helpdesk/${t.id}`} className="font-medium hover:text-primary">{t.subject}</Link>
                    <p className="flex items-center gap-2 text-xs text-muted">{t.number}{t.by_ai && <Badge tone="primary"><span className="inline-flex items-center gap-1"><Bot size={10} />AI</span></Badge>}</p>
                </div>
            ),
        },
        { key: 'customer', label: 'Customer', render: (t) => <div><p>{t.customer}</p><p className="text-xs text-muted">{t.email}</p></div> },
        { key: 'status', label: 'Status', render: (t) => <TicketStatus status={t.status} /> },
        { key: 'priority', label: 'Priority', render: (t) => <TicketPriority priority={t.priority} /> },
        { key: 'assigned', label: 'Assigned To', render: (t) => <span className="text-muted">{t.assigned ?? 'Unassigned'}</span> },
        { key: 'date', label: 'Created', render: (t) => <div className="text-muted">{t.date}<p className="text-xs">{t.ago}</p></div> },
        {
            key: 'actions', label: '', className: 'text-right',
            render: (t) => (
                <RowActions deleteHref={`/admin/helpdesk/${t.id}`} deleteMessage="Delete this ticket?">
                    <Link href={`/admin/helpdesk/${t.id}`} title="View" aria-label={`View ${t.number}`} className="text-muted hover:text-ink"><Eye size={18} /></Link>
                </RowActions>
            ),
        },
    ];

    return (
        <AdminLayout title="Helpdesk Tickets">
            <Head title="Helpdesk Tickets" />
            <div className="mb-4 flex justify-end"><LinkButton tone="neutral" href="/admin/ai-conversations"><Bot size={16} />AI Conversations</LinkButton></div>

            <div className="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
                <StatCard label="Open" value={stats.open} icon={CircleDot} tone="info" />
                <StatCard label="In Progress" value={stats.in_progress} icon={Clock} tone="primary" />
                <StatCard label="Waiting" value={stats.waiting} icon={Hourglass} tone="warning" />
                <StatCard label="Resolved" value={stats.resolved} icon={CheckCircle2} tone="success" />
            </div>

            <Card className="mb-4 p-3 md:p-4">
                <form onSubmit={go} className="flex flex-col gap-3 md:flex-row">
                    <div className="relative flex-1">
                        <Search size={16} className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
                        <Input value={form.search} onChange={set('search')} placeholder="Search tickets…" className="pl-9" aria-label="Search tickets" />
                    </div>
                    <Select value={form.status} onChange={set('status')} className="md:!w-auto" aria-label="Status">
                        <option value="">All Status</option>
                        {[['open', 'Open'], ['in_progress', 'In Progress'], ['waiting', 'Waiting'], ['resolved', 'Resolved'], ['closed', 'Closed']].map(([v, l]) => <option key={v} value={v}>{l}</option>)}
                    </Select>
                    <Select value={form.priority} onChange={set('priority')} className="md:!w-auto" aria-label="Priority">
                        <option value="">All Priority</option>
                        {['low', 'medium', 'high', 'urgent'].map((v) => <option key={v} value={v}>{v[0].toUpperCase() + v.slice(1)}</option>)}
                    </Select>
                    <div className="flex gap-2">
                        <Button type="submit" className="flex-1">Filter</Button>
                        {hasFilters && <Button tone="neutral" onClick={() => { setForm({ search: '', status: '', priority: '' }); router.get('/admin/helpdesk'); }}>Clear</Button>}
                    </div>
                </form>
            </Card>

            <Card className="overflow-hidden">
                <DataTable columns={columns} rows={tickets.data} empty="No tickets found." />
                <Pagination links={tickets.links} from={tickets.from} to={tickets.to} total={tickets.total} />
            </Card>
        </AdminLayout>
    );
}
