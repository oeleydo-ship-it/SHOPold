import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Bot, ExternalLink, Lock, Send, ShoppingCart, Trash2, User } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { TicketPriority, TicketStatus } from '../../../Components/helpdesk';
import { Badge, Button, Card, CardHeader, Field, Select, Textarea } from '../../../Components/ui';
import { confirmDelete } from '../../../Components/crud';

const STATUSES = [['open', 'Open'], ['in_progress', 'In Progress'], ['waiting', 'Waiting for Customer'], ['resolved', 'Resolved'], ['closed', 'Closed']];
const PRIORITIES = [['low', 'Low'], ['medium', 'Medium'], ['high', 'High'], ['urgent', 'Urgent']];

function Reply({ ticket }) {
    const form = useForm({ message: '', is_internal_note: false });
    const submit = (e) => {
        e.preventDefault();
        form.post(`/admin/helpdesk/${ticket.id}/reply`, { preserveScroll: true, onSuccess: () => form.reset() });
    };
    return (
        <Card>
            <CardHeader title="Add Reply" />
            <form onSubmit={submit} className="space-y-3 p-4 md:p-6">
                <Textarea rows={4} required maxLength={5000} placeholder="Type your reply…" value={form.data.message} onChange={(e) => form.setData('message', e.target.value)} aria-label="Reply message" />
                {form.errors.message && <p className="text-xs text-danger">{form.errors.message}</p>}
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <label className="flex items-center gap-2 text-sm">
                        <input type="checkbox" checked={form.data.is_internal_note} onChange={(e) => form.setData('is_internal_note', e.target.checked)} className="h-4 w-4 accent-[var(--primary)]" />
                        Internal note (not visible to the customer)
                    </label>
                    <Button type="submit" disabled={form.processing || !form.data.message.trim()}><Send size={14} />{form.data.is_internal_note ? 'Add Note' : 'Send Reply'}</Button>
                </div>
            </form>
        </Card>
    );
}

/** One small form that PATCHes a single field. */
function Mini({ label, ticket, field, url, options, children }) {
    const form = useForm({ [field]: ticket[field] ?? '' });
    return (
        <form onSubmit={(e) => { e.preventDefault(); form.patch(url, { preserveScroll: true }); }} className="space-y-2">
            <Field label={label}>
                <Select value={form.data[field]} onChange={(e) => form.setData(field, e.target.value)}>
                    {children ?? options.map(([v, l]) => <option key={v} value={v}>{l}</option>)}
                </Select>
            </Field>
            {form.errors[field] && <p className="text-xs text-danger">{form.errors[field]}</p>}
            <Button type="submit" tone="neutral" className="w-full" disabled={form.processing}>Update</Button>
        </form>
    );
}

export default function Show({ ticket, admins }) {
    const base = `/admin/helpdesk/${ticket.id}`;
    const ai = ticket.ai_conversation;

    return (
        <AdminLayout title="Ticket">
            <Head title={`Ticket ${ticket.number}`} />
            <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
                <Link href="/admin/helpdesk" className="inline-flex items-center gap-2 text-sm text-muted hover:text-ink"><ArrowLeft size={16} />Back to tickets</Link>
                <div className="flex gap-2"><TicketStatus status={ticket.status} /><TicketPriority priority={ticket.priority} /></div>
            </div>

            <div className="grid gap-5 lg:grid-cols-3">
                <div className="space-y-5 lg:col-span-2">
                    <Card className="p-4 md:p-6">
                        <h2 className="text-lg font-bold">{ticket.subject}</h2>
                        <p className="text-sm text-muted">Ticket #{ticket.number}</p>
                        <div className="mt-4 flex items-center gap-2 text-sm text-muted"><User size={14} /><span className="font-medium text-ink">{ticket.customer.name}</span>{ticket.customer.email && <span>· {ticket.customer.email}</span>}<span>· {ticket.created_at}</span></div>
                        <p className="mt-4 whitespace-pre-wrap rounded-lg bg-surface-2 p-4 text-sm">{ticket.description}</p>
                        {ticket.by_ai && (
                            <p className="mt-3 flex flex-wrap items-center gap-2 text-sm text-primary"><Bot size={14} />Created by the AI assistant
                                {ai && <Link href={`/admin/ai-conversations/${ai.id}`} className="underline">View Conversation</Link>}</p>
                        )}
                    </Card>

                    {ai && ai.messages.length > 0 && (
                        <Card>
                            <CardHeader title="AI Conversation Preview"><Link href={`/admin/ai-conversations/${ai.id}`} className="text-sm text-primary hover:underline">View all {ai.total}</Link></CardHeader>
                            <ul className="space-y-2 p-4 md:p-6">
                                {ai.messages.map((m) => (
                                    <li key={m.id} className={`rounded-lg p-3 text-sm ${m.role === 'assistant' ? 'bg-primary-soft/30' : 'bg-surface-2'}`}>
                                        <span className="mb-0.5 block text-xs font-medium text-muted">{m.role === 'assistant' ? 'Assistant' : 'Customer'}</span>{m.content}
                                    </li>
                                ))}
                            </ul>
                        </Card>
                    )}

                    {ticket.replies.length > 0 && (
                        <Card>
                            <CardHeader title="Conversation" />
                            <ul className="divide-y divide-line">
                                {ticket.replies.map((r) => (
                                    <li key={r.id} className={`p-4 md:px-6 ${r.internal ? 'bg-warning/5' : r.is_admin ? 'bg-primary-soft/10' : ''}`}>
                                        <div className="mb-1 flex flex-wrap items-center gap-2 text-sm">
                                            <span className="font-medium">{r.name}</span>
                                            {r.is_admin && <Badge tone="primary">Staff</Badge>}
                                            {r.internal && <Badge tone="warning"><span className="inline-flex items-center gap-1"><Lock size={10} />Internal note</span></Badge>}
                                            <span className="text-xs text-muted">{r.at}</span>
                                        </div>
                                        <p className="whitespace-pre-wrap text-sm">{r.message}</p>
                                    </li>
                                ))}
                            </ul>
                        </Card>
                    )}

                    <Reply ticket={ticket} />
                </div>

                <div className="space-y-5">
                    <Card>
                        <CardHeader title="Manage Ticket" />
                        <div className="space-y-4 p-4 md:p-6">
                            <Mini label="Status" ticket={ticket} field="status" url={`${base}/status`} options={STATUSES} />
                            <Mini label="Priority" ticket={ticket} field="priority" url={`${base}/priority`} options={PRIORITIES} />
                            <Mini label="Assign To" ticket={ticket} field="assigned_to" url={`${base}/assign`}>
                                <option value="">Unassigned</option>
                                {admins.map((a) => <option key={a.id} value={a.id}>{a.name}</option>)}
                            </Mini>
                        </div>
                    </Card>

                    <Card>
                        <CardHeader title="Ticket Info" />
                        <dl className="space-y-2 p-4 text-sm md:p-6">
                            {[['Category', ticket.category], ['Created', ticket.created_short], ['Last updated', ticket.updated_ago], ['Replies', ticket.replies.length]].map(([k, v]) => (
                                <div key={k} className="flex justify-between gap-3"><dt className="text-muted">{k}</dt><dd className="font-medium">{v}</dd></div>
                            ))}
                        </dl>
                    </Card>

                    <Card>
                        <CardHeader title="Customer" />
                        <div className="space-y-1 p-4 text-sm md:p-6">
                            <p className="font-medium">{ticket.customer.name}</p><p className="text-muted">{ticket.customer.email}</p>
                            {ticket.customer.id && <Link href={`/admin/users/${ticket.customer.id}/edit`} className="mt-2 inline-flex items-center gap-1 text-primary hover:underline">View profile <ExternalLink size={12} /></Link>}
                        </div>
                    </Card>

                    {ticket.order && (
                        <Card>
                            <CardHeader title="Related Order" />
                            <Link href={`/admin/orders/${ticket.order.id}`} className="block p-4 text-sm hover:bg-surface-2 md:p-6">
                                <p className="flex items-center gap-2 font-medium text-primary"><ShoppingCart size={14} />{ticket.order.number}</p>
                                <p className="text-muted">{ticket.order.date}</p><p className="text-muted">Status: {ticket.order.status}</p>
                            </Link>
                        </Card>
                    )}

                    <Button tone="neutral" className="w-full !border-danger !text-danger" onClick={() => confirmDelete(base, 'Are you sure you want to delete this ticket? This action cannot be undone.')}>
                        <Trash2 size={16} />Delete Ticket
                    </Button>
                </div>
            </div>
        </AdminLayout>
    );
}
