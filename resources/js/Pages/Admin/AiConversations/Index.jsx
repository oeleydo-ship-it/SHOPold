import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Eye, MessageSquare, Search, Ticket, Zap } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import StatCard from '../../../Components/StatCard';
import { TicketStatus } from '../../../Components/helpdesk';
import { Button, Card, Input, LinkButton, Pagination, Select } from '../../../Components/ui';
import { DataTable, RowActions } from '../../../Components/crud';

export default function Index({ conversations, stats, filters }) {
    const [form, setForm] = useState({ search: filters.search ?? '', status: filters.status ?? '', has_ticket: filters.has_ticket ?? '' });
    const hasFilters = Object.values(filters).some(Boolean);
    const set = (k) => (e) => setForm((f) => ({ ...f, [k]: e.target.value }));
    const go = (e) => { e.preventDefault(); router.get('/admin/ai-conversations', Object.fromEntries(Object.entries(form).filter(([, v]) => v)), { preserveState: true }); };

    const columns = [
        { key: 'customer', label: 'Customer', render: (c) => (c.customer ? <div><p className="font-medium">{c.customer.name}</p><p className="text-xs text-muted">{c.customer.email}</p></div> : <span className="text-muted">Guest</span>) },
        { key: 'session', label: 'Session', render: (c) => <code className="text-xs text-muted">{c.session}</code> },
        { key: 'messages', label: 'Messages' },
        { key: 'status', label: 'Status', render: (c) => <TicketStatus status={c.status} /> },
        { key: 'ticket', label: 'Ticket', render: (c) => (c.ticket ? <Link href={`/admin/helpdesk/${c.ticket.id}`} className="text-primary hover:underline">{c.ticket.number}</Link> : <span className="text-muted">—</span>) },
        { key: 'last', label: 'Last Message', render: (c) => <span className="text-muted">{c.last}</span> },
        {
            key: 'actions', label: '', className: 'text-right',
            render: (c) => (
                <RowActions deleteHref={`/admin/ai-conversations/${c.id}`} deleteMessage="Delete this conversation?">
                    <Link href={`/admin/ai-conversations/${c.id}`} title="View" aria-label="View conversation" className="text-muted hover:text-ink"><Eye size={18} /></Link>
                </RowActions>
            ),
        },
    ];

    return (
        <AdminLayout title="AI Conversations">
            <Head title="AI Conversations" />
            <div className="mb-4 flex justify-end"><LinkButton tone="neutral" href="/admin/helpdesk"><Ticket size={16} />Helpdesk Tickets</LinkButton></div>

            <div className="mb-4 grid gap-3 sm:grid-cols-3">
                <StatCard label="Total Conversations" value={stats.total} icon={MessageSquare} tone="primary" />
                <StatCard label="Active" value={stats.active} icon={Zap} tone="success" />
                <StatCard label="Converted to Ticket" value={stats.converted} icon={Ticket} tone="info" />
            </div>

            <Card className="mb-4 p-3 md:p-4">
                <form onSubmit={go} className="flex flex-col gap-3 md:flex-row">
                    <div className="relative flex-1">
                        <Search size={16} className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
                        <Input value={form.search} onChange={set('search')} placeholder="Search by customer…" className="pl-9" aria-label="Search by customer" />
                    </div>
                    <Select value={form.status} onChange={set('status')} className="md:!w-auto" aria-label="Status">
                        <option value="">All Status</option><option value="active">Active</option><option value="ended">Ended</option><option value="converted_to_ticket">Converted to Ticket</option>
                    </Select>
                    <Select value={form.has_ticket} onChange={set('has_ticket')} className="md:!w-auto" aria-label="Ticket">
                        <option value="">All</option><option value="yes">Has Ticket</option><option value="no">No Ticket</option>
                    </Select>
                    <div className="flex gap-2">
                        <Button type="submit" className="flex-1">Filter</Button>
                        {hasFilters && <Button tone="neutral" onClick={() => { setForm({ search: '', status: '', has_ticket: '' }); router.get('/admin/ai-conversations'); }}>Clear</Button>}
                    </div>
                </form>
            </Card>

            <Card className="overflow-hidden">
                <DataTable columns={columns} rows={conversations.data} empty="No conversations found." />
                <Pagination links={conversations.links} from={conversations.from} to={conversations.to} total={conversations.total} />
            </Card>
        </AdminLayout>
    );
}
