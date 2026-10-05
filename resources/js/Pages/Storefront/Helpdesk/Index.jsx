import { Bot, Headset, Plus } from 'lucide-react';
import AccountLayout, { Badge } from '../../../Storefront/Account/AccountLayout';
import { Pagination } from '../../../Storefront/Shop/Listing';
import SfLink from '../../../Storefront/lib/SfLink';
import { useT } from '../../../Storefront/lib/store';

export function NewTicketButton() {
    const t = useT();
    return <SfLink href="/helpdesk/create" className="sf-btn inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-white hover:bg-primary-700"><Plus size={16} />{t('new_ticket', 'New Ticket')}</SfLink>;
}

export default function Index({ tickets, seo }) {
    const t = useT();
    return (
        <AccountLayout title={t('support_tickets', 'Support Tickets')} seo={seo} actions={<NewTicketButton />}>
            <div className="overflow-hidden rounded-xl bg-white shadow-sm">
                {tickets.data.length > 0 ? (
                    <ul className="divide-y">
                        {tickets.data.map((tk) => (
                            <li key={tk.id}>
                                <SfLink href={tk.url} className="flex flex-col gap-3 p-4 transition hover:bg-gray-50 sm:flex-row sm:items-start sm:justify-between">
                                    <div className="min-w-0">
                                        <p className="flex items-center gap-2 text-xs text-gray-500">
                                            <span className="font-mono">{tk.number}</span>
                                            {tk.ai && <span className="inline-flex items-center gap-1 rounded-full bg-purple-100 px-2 py-0.5 text-purple-700"><Bot size={12} />AI</span>}
                                        </p>
                                        <h2 className="truncate font-semibold text-gray-900">{tk.subject}</h2>
                                        <p className="mt-1 line-clamp-2 text-sm text-gray-500">{tk.excerpt}</p>
                                    </div>
                                    <div className="flex shrink-0 flex-wrap items-center gap-2 sm:flex-col sm:items-end">
                                        <Badge status={tk.status} /><Badge status={tk.priority} />
                                        <span className="text-xs text-gray-500">{tk.ago}</span>
                                    </div>
                                </SfLink>
                            </li>
                        ))}
                    </ul>
                ) : (
                    <div className="p-12 text-center">
                        <span className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 text-gray-400"><Headset size={26} /></span>
                        <h2 className="mb-2 text-lg font-semibold">{t('no_tickets', 'No support tickets yet')}</h2>
                        <p className="mb-6 text-gray-500">{t('no_tickets_text', 'Need help? Open a ticket and our team will get back to you.')}</p>
                        <NewTicketButton />
                    </div>
                )}
            </div>
            <Pagination products={tickets} />
        </AccountLayout>
    );
}
