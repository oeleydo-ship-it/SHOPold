import { router, useForm } from '@inertiajs/react';
import { Bot, Headset, Lock, UserRound } from 'lucide-react';
import AccountLayout, { Badge } from '../../../Storefront/Account/AccountLayout';
import SfLink from '../../../Storefront/lib/SfLink';
import { cx, useT } from '../../../Storefront/lib/store';

const box = 'rounded-xl bg-white p-4 shadow-sm md:p-6';

export default function Show({ ticket, seo }) {
    const t = useT();
    const form = useForm({ message: '' });
    const reply = (e) => {
        e.preventDefault();
        form.post(`/helpdesk/${ticket.id}/reply`, { preserveScroll: true, onSuccess: () => form.reset() });
    };
    const close = () => {
        if (window.confirm(t('confirm_close_ticket', 'Are you sure you want to close this ticket?'))) router.patch(`/helpdesk/${ticket.id}/close`, {}, { preserveScroll: true });
    };
    const actions = (
        <div className="flex gap-2">
            <SfLink href="/helpdesk" className="rounded-lg border border-gray-300 px-4 py-2 text-gray-700 hover:bg-gray-50">{t('back_to_tickets', 'Back to Tickets')}</SfLink>
            {ticket.canClose && <button type="button" onClick={close} className="rounded-lg bg-gray-600 px-4 py-2 text-white hover:bg-gray-700">{t('close_ticket', 'Close Ticket')}</button>}
        </div>
    );

    return (
        <AccountLayout title={`${t('ticket', 'Ticket')} ${ticket.number}`} seo={seo} actions={actions} contentClass="grid gap-6 xl:grid-cols-3">
            <div className="space-y-4 xl:col-span-2">
                <h2 className="text-xl font-bold">{ticket.subject}</h2>
                <article className={box}>
                    <header className="mb-3 flex flex-wrap items-center gap-2">
                        <span className="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-gray-600"><UserRound size={18} /></span>
                        <span className="font-medium">{ticket.author}</span>
                        <span className="text-sm text-gray-500">{ticket.created}</span>
                        {ticket.ai && <span className="inline-flex items-center gap-1 rounded-full bg-purple-100 px-2 py-0.5 text-xs text-purple-700"><Bot size={12} />{t('created_by_ai', 'Created by AI assistant')}</span>}
                    </header>
                    <p className="whitespace-pre-line text-gray-700">{ticket.description}</p>
                </article>

                {ticket.aiMessages.length > 0 && (
                    <section className={box}>
                        <h3 className="mb-4 flex items-center gap-2 text-lg font-bold"><Bot size={18} className="text-purple-600" />{t('ai_conversation', 'AI Chat History')}</h3>
                        <div className="max-h-96 space-y-3 overflow-y-auto">
                            {ticket.aiMessages.map((m) => (
                                <div key={m.id} className={cx('flex gap-3', m.assistant && 'flex-row-reverse')}>
                                    <span className={cx('flex h-8 w-8 shrink-0 items-center justify-center rounded-full', m.assistant ? 'bg-purple-100 text-purple-600' : 'bg-gray-100 text-gray-600')}>{m.assistant ? <Bot size={14} /> : <UserRound size={14} />}</span>
                                    <p className={cx('max-w-[80%] whitespace-pre-line rounded-lg px-4 py-2 text-sm', m.assistant ? 'bg-purple-50' : 'bg-gray-100')}>{m.content}</p>
                                </div>
                            ))}
                        </div>
                    </section>
                )}

                {ticket.replies.map((r) => (
                    <article key={r.id} className={cx(box, r.staff && 'border-s-4 border-primary-500')}>
                        <header className="mb-3 flex flex-wrap items-center gap-2">
                            <span className={cx('flex h-10 w-10 items-center justify-center rounded-full', r.staff ? 'bg-primary-100 text-primary-600' : 'bg-gray-100 text-gray-600')}>{r.staff ? <Headset size={18} /> : <UserRound size={18} />}</span>
                            <span className="font-medium">{r.author}</span>
                            {r.staff && <span className="rounded-full bg-primary-100 px-2 py-0.5 text-xs text-primary-700">{t('support_team', 'Support Team')}</span>}
                            <span className="text-sm text-gray-500">{r.date}</span>
                        </header>
                        <p className="whitespace-pre-line text-gray-700">{r.message}</p>
                    </article>
                ))}

                {ticket.canReply ? (
                    <form onSubmit={reply} className={box}>
                        <h3 className="mb-4 font-bold">{t('add_reply', 'Add Reply')}</h3>
                        <textarea value={form.data.message} onChange={(e) => form.setData('message', e.target.value)} rows={4} required maxLength={5000}
                            aria-label={t('add_reply', 'Add Reply')} placeholder={t('type_reply', 'Type your reply...')} className="w-full rounded-lg border px-4 py-2 focus:border-primary-500 focus:ring-primary-500" />
                        {form.errors.message && <p className="mt-1 text-sm text-red-500">{form.errors.message}</p>}
                        <div className="mt-3 flex justify-end">
                            <button type="submit" disabled={form.processing} className="sf-btn rounded-lg bg-primary-600 px-6 py-2 text-white hover:bg-primary-700 disabled:opacity-60">{t('send_reply', 'Send Reply')}</button>
                        </div>
                    </form>
                ) : (
                    <p className="flex items-center gap-2 rounded-xl bg-gray-100 p-4 text-gray-600"><Lock size={16} />{t('ticket_is', 'This ticket is')} {ticket.status.label.toLowerCase()}. {t('no_more_replies', 'No more replies can be added.')}</p>
                )}
            </div>

            <aside className="space-y-4">
                <div className={box}>
                    <h3 className="mb-4 font-bold">{t('ticket_details', 'Ticket Details')}</h3>
                    <dl className="space-y-3 text-sm">
                        <div><dt className="text-gray-500">{t('status', 'Status')}</dt><dd className="mt-1"><Badge status={ticket.status} /></dd></div>
                        <div><dt className="text-gray-500">{t('priority', 'Priority')}</dt><dd className="mt-1"><Badge status={ticket.priority} /></dd></div>
                        <div><dt className="text-gray-500">{t('category', 'Category')}</dt><dd className="mt-1 font-medium">{ticket.category}</dd></div>
                        <div><dt className="text-gray-500">{t('created', 'Created')}</dt><dd className="mt-1 font-medium">{ticket.created}</dd></div>
                        <div><dt className="text-gray-500">{t('last_updated', 'Last Updated')}</dt><dd className="mt-1 font-medium">{ticket.updated}</dd></div>
                    </dl>
                </div>
                {ticket.order && (
                    <div className={box}>
                        <h3 className="mb-4 font-bold">{t('related_order', 'Related Order')}</h3>
                        <SfLink href={ticket.order.url} className="block rounded-lg bg-gray-50 p-3 hover:bg-gray-100">
                            <p className="font-medium">{ticket.order.number}</p>
                            <p className="text-sm text-gray-500">{ticket.order.date}</p>
                            <p className="text-sm font-medium text-primary-600">{ticket.order.total}</p>
                        </SfLink>
                    </div>
                )}
            </aside>
        </AccountLayout>
    );
}
