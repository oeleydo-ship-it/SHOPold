import { ArrowRight, ChevronRight, Heart, ShoppingBag, ShoppingCart, Truck, UserRound } from 'lucide-react';
import AccountLayout, { Badge } from '../../../Storefront/Account/AccountLayout';
import SfLink from '../../../Storefront/lib/SfLink';
import { useStore, useT } from '../../../Storefront/lib/store';

export function OrdersTable({ orders }) {
    const t = useT();
    return (
        <>
            <ul className="divide-y md:hidden">
                {orders.map((o) => (
                    <li key={o.id}>
                        <SfLink href={o.url} className="flex items-center justify-between gap-3 p-4 transition hover:bg-gray-50">
                            <div className="min-w-0">
                                <p className="truncate text-sm font-medium text-gray-900">{o.number}</p>
                                <p className="mt-0.5 text-xs text-gray-500">{o.date}</p>
                                <Badge status={o.status} className="mt-2" />
                            </div>
                            <div className="text-end">
                                <p className="font-bold text-gray-900">{o.total}</p>
                                <p className="mt-1 flex items-center justify-end text-xs text-primary-600">{t('view', 'View')}<ChevronRight size={12} /></p>
                            </div>
                        </SfLink>
                    </li>
                ))}
            </ul>
            <table className="hidden w-full md:table">
                <thead className="bg-gray-50 text-start text-xs font-medium uppercase text-gray-500">
                    <tr>
                        {[t('order', 'Order'), t('date', 'Date'), t('status', 'Status'), t('total', 'Total'), t('action', 'Action')].map((h) => <th key={h} className="px-6 py-3 text-start">{h}</th>)}
                    </tr>
                </thead>
                <tbody className="divide-y">
                    {orders.map((o) => (
                        <tr key={o.id} className="hover:bg-gray-50">
                            <td className="px-6 py-4 font-medium">{o.number}</td>
                            <td className="px-6 py-4 text-gray-500">{o.date}</td>
                            <td className="px-6 py-4"><Badge status={o.status} /></td>
                            <td className="px-6 py-4 font-medium">{o.total}</td>
                            <td className="px-6 py-4"><SfLink href={o.url} className="text-primary-600 hover:underline">{t('view', 'View')}</SfLink></td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </>
    );
}

export default function Dashboard({ stats, recentOrders, seo }) {
    const t = useT();
    const { customer, features } = useStore();
    const cards = [
        [ShoppingBag, 'bg-primary-100 text-primary-600', stats.orders, t('orders', 'Orders')],
        features.wishlist && [Heart, 'bg-red-100 text-red-600', stats.wishlist, t('wishlist', 'Wishlist')],
    ].filter(Boolean);

    return (
        <AccountLayout title={t('my_account', 'My Account')} seo={seo}>
            {customer.isDriver && features.driverPortal && (
                <div className="flex flex-col gap-4 rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 p-5 text-white sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex items-center gap-3">
                        <span className="flex h-12 w-12 items-center justify-center rounded-full bg-white/20"><Truck size={22} /></span>
                        <div>
                            <h2 className="text-lg font-bold">{t('driver_portal', 'Driver Portal')}</h2>
                            <p className="text-sm text-emerald-100">{t('manage_deliveries', 'Manage your deliveries')}</p>
                        </div>
                    </div>
                    <a href="/driver" className="flex items-center justify-center gap-2 rounded-lg bg-white px-5 py-2.5 font-semibold text-emerald-600 hover:bg-emerald-50"><ArrowRight size={16} />{t('go_to_portal', 'Go to Portal')}</a>
                </div>
            )}

            <div className="grid grid-cols-2 gap-3 md:grid-cols-3 md:gap-6">
                {cards.map(([Icon, tone, value, label]) => (
                    <div key={label} className="flex items-center gap-3 rounded-xl bg-white p-4 shadow-sm md:p-6">
                        <span className={`flex h-10 w-10 items-center justify-center rounded-full md:h-12 md:w-12 ${tone}`}><Icon size={20} /></span>
                        <div><p className="text-xl font-bold text-gray-900 md:text-2xl">{value}</p><p className="text-xs text-gray-500 md:text-sm">{label}</p></div>
                    </div>
                ))}
                <div className="col-span-2 flex items-center gap-3 rounded-xl bg-white p-4 shadow-sm md:col-span-1 md:p-6">
                    <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-600 md:h-12 md:w-12"><UserRound size={20} /></span>
                    <div className="min-w-0"><p className="truncate font-medium text-gray-900">{customer.name}</p><p className="truncate text-xs text-gray-500 md:text-sm">{customer.email}</p></div>
                </div>
            </div>

            <section className="overflow-hidden rounded-xl bg-white shadow-sm">
                <div className="flex items-center justify-between border-b p-4 md:p-6">
                    <h2 className="text-base font-bold md:text-lg">{t('recent_orders', 'Recent Orders')}</h2>
                    <SfLink href="/account/orders" className="text-sm text-primary-600 hover:underline">{t('view_all', 'View All')}</SfLink>
                </div>
                {recentOrders.length > 0 ? <OrdersTable orders={recentOrders} /> : (
                    <div className="p-8 text-center">
                        <span className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 text-gray-400"><ShoppingBag size={26} /></span>
                        <p className="mb-4 text-gray-500">{t('no_orders_yet', 'No orders yet')}</p>
                        <SfLink href="/shop" className="sf-btn inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm text-white hover:bg-primary-700"><ShoppingCart size={16} />{t('start_shopping', 'Start Shopping')}</SfLink>
                    </div>
                )}
            </section>
        </AccountLayout>
    );
}
