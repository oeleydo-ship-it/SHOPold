import { CalendarDays, CheckCircle2, Clock, Download, FileText, ImageIcon, ShoppingBag, ShoppingCart, Truck } from 'lucide-react';
import AccountLayout, { Badge } from '../../../Storefront/Account/AccountLayout';
import { Pagination } from '../../../Storefront/Shop/Listing';
import SfLink from '../../../Storefront/lib/SfLink';
import { useT } from '../../../Storefront/lib/store';

function Delivery({ o }) {
    const t = useT();
    if (o.digitalOnly) return <span className="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-700"><Download size={12} />{t('digital', 'Digital')}</span>;
    if (!o.physical) return null;
    const done = o.delivered === o.physical;
    const Icon = done ? CheckCircle2 : Clock;
    return (
        <span className={`inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-medium ${done ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'}`}>
            <Icon size={12} />{o.delivered}/{o.physical} {t('delivered', 'Delivered')}
        </span>
    );
}

export default function Orders({ orders, seo }) {
    const t = useT();
    const list = orders.data;

    return (
        <AccountLayout title={t('my_orders', 'My Orders')} seo={seo}>
            {list.length > 0 ? (
                <>
                    <ul className="space-y-4">
                        {list.map((o) => (
                            <li key={o.id} className="rounded-xl bg-white p-4 shadow-sm md:p-6">
                                <div className="flex items-start gap-4 md:gap-6">
                                    <div className="hidden shrink-0 -space-x-3 sm:flex">
                                        {o.thumbs.map((src, i) => (
                                            <span key={i} className="flex h-14 w-14 items-center justify-center overflow-hidden rounded-lg border-2 border-white bg-gray-100 text-gray-400">
                                                {src ? <img src={src} alt="" className="h-full w-full object-cover" /> : <ImageIcon size={18} />}
                                            </span>
                                        ))}
                                        {o.itemCount > 3 && <span className="flex h-14 w-14 items-center justify-center rounded-lg border-2 border-white bg-gray-100 text-xs font-semibold text-gray-600">+{o.itemCount - 3}</span>}
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <div className="flex flex-wrap items-start justify-between gap-2">
                                            <div>
                                                <SfLink href={o.url} className="font-semibold text-gray-900 hover:text-primary-600">{o.number}</SfLink>
                                                <p className="mt-1 flex flex-wrap items-center gap-3 text-xs text-gray-500">
                                                    <span className="flex items-center gap-1"><CalendarDays size={12} />{o.date}</span>
                                                    <span className="flex items-center gap-1"><Clock size={12} />{o.time}</span>
                                                </p>
                                            </div>
                                            <div className="text-end">
                                                <p className="text-lg font-bold text-gray-900">{o.total}</p>
                                                <p className="text-xs text-gray-500">{o.itemCount} {o.itemCount === 1 ? t('item', 'item') : t('items', 'items')}</p>
                                            </div>
                                        </div>
                                        <p className="mt-2 line-clamp-1 text-sm text-gray-600">{o.names || t('order_items', 'Order items')}</p>
                                        <div className="mt-3 flex flex-wrap items-center justify-between gap-3">
                                            <div className="flex flex-wrap gap-2"><Badge status={o.status} /><Badge status={o.payment} /><Delivery o={o} /></div>
                                            <div className="flex gap-2">
                                                {o.paid && <a href={`${o.url}/invoice`} className="flex items-center gap-1 rounded-lg bg-green-50 px-3 py-2 text-sm font-medium text-green-700 hover:bg-green-100"><FileText size={14} />{t('invoice', 'Invoice')}</a>}
                                                <SfLink href={o.url} className="flex items-center gap-1 rounded-lg bg-primary-50 px-3 py-2 text-sm font-medium text-primary-600 hover:bg-primary-100"><Truck size={14} />{t('view_details', 'View Details')}</SfLink>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        ))}
                    </ul>
                    <Pagination products={orders} />
                </>
            ) : (
                <div className="rounded-xl bg-white p-12 text-center shadow-sm">
                    <span className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 text-gray-400"><ShoppingBag size={26} /></span>
                    <h2 className="mb-2 text-lg font-semibold">{t('no_orders_yet', 'No orders yet')}</h2>
                    <p className="mb-6 text-gray-500">{t('no_orders_text', 'When you place an order, it will appear here.')}</p>
                    <SfLink href="/shop" className="sf-btn inline-flex items-center gap-2 rounded-lg bg-primary-600 px-5 py-2.5 text-white hover:bg-primary-700"><ShoppingCart size={16} />{t('start_shopping', 'Start Shopping')}</SfLink>
                </div>
            )}
        </AccountLayout>
    );
}
