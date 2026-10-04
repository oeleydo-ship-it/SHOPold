import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Bot, ExternalLink, Trash2, User } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { TicketStatus } from '../../../Components/helpdesk';
import { Button, Card, CardHeader } from '../../../Components/ui';
import { confirmDelete } from '../../../Components/crud';

export default function Show({ conversation: c }) {
    return (
        <AdminLayout title="AI Conversation">
            <Head title="AI Conversation" />
            <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
                <Link href="/admin/ai-conversations" className="inline-flex items-center gap-2 text-sm text-muted hover:text-ink"><ArrowLeft size={16} />Back to conversations</Link>
                <div className="flex items-center gap-3 text-sm text-muted">Session: <code>{c.session}</code><TicketStatus status={c.status} /></div>
            </div>

            <div className="grid gap-5 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardHeader title={`${c.message_count} messages`} />
                    {c.messages.length === 0 ? <p className="p-8 text-center text-sm text-muted">No messages in this conversation.</p> : (
                        <ol className="space-y-4 p-4 md:p-6">
                            {c.messages.map((m) => {
                                const bot = m.role === 'assistant';
                                return (
                                    <li key={m.id} className={`flex gap-3 ${bot ? 'flex-row-reverse' : ''}`}>
                                        <span className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-full ${bot ? 'bg-primary-soft text-primary' : 'bg-surface-2 text-muted'}`} aria-label={bot ? 'Assistant' : 'Customer'}>
                                            {bot ? <Bot size={16} /> : <User size={16} />}
                                        </span>
                                        <div className={`max-w-[80%] ${bot ? 'text-right' : ''}`}>
                                            <p className={`whitespace-pre-wrap rounded-2xl px-4 py-3 text-left text-sm ${bot ? 'rounded-tr-sm bg-primary-soft/40' : 'rounded-tl-sm bg-surface-2'}`}>{m.content}</p>
                                            <p className="mt-1 text-xs text-muted">{m.time}</p>
                                        </div>
                                    </li>
                                );
                            })}
                        </ol>
                    )}
                </Card>

                <div className="space-y-5">
                    <Card>
                        <CardHeader title="Customer" />
                        <div className="p-4 text-sm md:p-6">
                            {c.customer ? (
                                <>
                                    <p className="font-medium">{c.customer.name}</p><p className="text-muted">{c.customer.email}</p>
                                    <Link href={`/admin/users/${c.customer.id}/edit`} className="mt-2 inline-flex items-center gap-1 text-primary hover:underline">View profile <ExternalLink size={12} /></Link>
                                </>
                            ) : <p className="text-muted">Guest visitor</p>}
                        </div>
                    </Card>

                    <Card>
                        <CardHeader title="Details" />
                        <dl className="space-y-2 p-4 text-sm md:p-6">
                            {[['Messages', c.message_count], ['Started', c.created_at], ['Last message', c.last]].map(([k, v]) => <div key={k} className="flex justify-between gap-3"><dt className="text-muted">{k}</dt><dd className="font-medium">{v}</dd></div>)}
                        </dl>
                    </Card>

                    {c.ticket && (
                        <Card>
                            <CardHeader title="Created Ticket" />
                            <Link href={`/admin/helpdesk/${c.ticket.id}`} className="block p-4 text-sm hover:bg-surface-2 md:p-6">
                                <p className="font-medium text-primary">{c.ticket.number}</p><p className="text-muted">{c.ticket.subject}</p><p className="text-xs text-muted">{c.ticket.status}</p>
                            </Link>
                        </Card>
                    )}

                    <Button tone="neutral" className="w-full !border-danger !text-danger" onClick={() => confirmDelete(`/admin/ai-conversations/${c.id}`, 'Are you sure you want to delete this conversation?')}><Trash2 size={16} />Delete Conversation</Button>
                </div>
            </div>
        </AdminLayout>
    );
}
