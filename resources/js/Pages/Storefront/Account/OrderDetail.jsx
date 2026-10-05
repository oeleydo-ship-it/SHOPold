import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
    ArrowLeft, Banknote, Check, ClipboardCheck, Cog, CreditCard, Download, FileText, Gift, ImageIcon, Info, MapPin, PackageOpen, Phone, Star, Store, Truck, Undo2, XCircle,
} from 'lucide-react';
import AccountLayout, { Badge } from '../../../Storefront/Account/AccountLayout';
import SfLink from '../../../Storefront/lib/SfLink';
import { cx, useT } from '../../../Storefront/lib/store';

const STEP_ICON = { pending: ClipboardCheck, processing: Cog, shipped: Truck, ready_for_pickup: Store, delivered: PackageOpen };
const ITEM_TONE = {
    pending: 'bg-yellow-100 text-yellow-700', processing: 'bg-blue-100 text-blue-700', shipped: 'bg-purple-100 text-purple-700',
    delivered: 'bg-green-100 text-green-700', cancelled: 'bg-red-100 text-red-700',
};
const box = 'rounded-xl bg-white p-4 shadow-sm md:p-6';

function Progress({ order }) {
    const t = useT();
    const current = order.steps.findIndex((s) => s.key === order.statusKey);
    const dates = [order.dates.placed, order.dates.processing, order.dates.shipped, order.dates.delivered];
    return (
        <div className="mb-6 rounded-xl border bg-gradient-to-r from-gray-50 to-white p-4">
            <h3 className="mb-4 text-sm font-semibold text-gray-700">{t('order_progress', 'Order Progress')}</h3>
            <ol className="relative grid grid-cols-4">
                <span className="absolute inset-x-[12.5%] top-4 h-1 rounded bg-gray-200 md:top-5" aria-hidden="true" />
                <span className="absolute start-[12.5%] top-4 h-1 rounded bg-green-500 transition-all md:top-5" style={{ width: `${Math.max(0, current) * 25}%` }} aria-hidden="true" />
                {order.steps.map((s, i) => {
                    const done = current >= i;
                    const Icon = done && i !== current ? Check : STEP_ICON[s.key] ?? Check;
                    return (
                        <li key={s.key} className="relative flex flex-col items-center" aria-current={i === current ? 'step' : undefined}>
                            <span className={cx('flex h-8 w-8 items-center justify-center rounded-full md:h-10 md:w-10', done ? 'bg-green-500 text-white shadow-lg shadow-green-200' : 'bg-gray-200 text-gray-400', i === current && 'ring-4 ring-green-200')}>
                                <Icon size={16} />
                            </span>
                            <span className={cx('mt-2 text-center text-[10px] font-medium md:text-xs', done ? 'text-green-600' : 'text-gray-400')}>{s.label}</span>
                            <span className={cx('text-[10px] md:text-xs', dates[i] ? 'text-gray-600' : 'text-gray-300')}>{dates[i] ?? '--'}</span>
                        </li>
                    );
                })}
            </ol>
        </div>
    );
}

function Items({ order }) {
    const t = useT();
    const f = order.fulfillment;
    return (
        <>
            {f.physical > 0 && (
                <div className="mb-4 rounded-lg bg-gray-50 p-3 md:p-4">
                    <div className="mb-2 flex items-center justify-between text-sm"><span className="font-medium text-gray-700">{t('item_fulfillment', 'Item Fulfillment')}</span><span className="text-gray-500">{f.delivered}/{f.physical} {t('delivered', 'Delivered')}</span></div>
                    <div className="h-2 w-full rounded-full bg-gray-200"><div className="h-2 rounded-full bg-green-500" style={{ width: `${(f.delivered / f.physical) * 100}%` }} /></div>
                    {f.shipped > f.delivered && f.shipped > 0 && <p className="mt-2 flex items-center gap-1 text-xs text-blue-600"><Truck size={12} />{f.shipped - f.delivered} {t('items_in_transit', 'item(s) in transit')}</p>}
                </div>
            )}
            <ul className="divide-y">
                {order.items.map((i) => (
                    <li key={i.id} className="flex gap-3 py-4 md:gap-4">
                        <span className="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-gray-100 text-gray-400 md:h-20 md:w-20">
                            {i.image ? <img src={i.image} alt="" className="h-full w-full object-cover" /> : <ImageIcon size={20} />}
                        </span>
                        <div className="min-w-0 flex-1">
                            {i.url ? <SfLink href={i.url} className="font-medium hover:text-primary-600">{i.name}</SfLink> : <p className="font-medium">{i.name}</p>}
                            {i.options.length > 0 && (
                                <div className="mt-1 flex flex-wrap gap-1">{i.options.map((o) => <span key={o.name} className="rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-600">{o.name}: {o.value}</span>)}</div>
                            )}
                            <p className="mt-1 text-sm text-gray-500">{t('qty', 'Qty')}: {i.quantity} × {i.price}</p>
                            {!i.digital && i.tracking && <p className="mt-1 flex items-center gap-1 text-xs text-blue-600"><Truck size={12} />{i.tracking}</p>}
                            {!i.digital && i.shippedAt && <p className="text-xs text-gray-400">{t('shipped', 'Shipped')}: {i.shippedAt}</p>}
                            {!i.digital && i.deliveredAt && <p className="text-xs text-gray-400">{t('delivered', 'Delivered')}: {i.deliveredAt}</p>}
                        </div>
                        <div className="text-end">
                            <p className="font-semibold">{i.total}</p>
                            {i.digital
                                ? <span className="mt-1 inline-flex items-center gap-1 rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-700"><Download size={12} />{t('digital', 'Digital')}</span>
                                : <span className={cx('mt-1 inline-block rounded-full px-2 py-1 text-xs font-medium', ITEM_TONE[i.fulfillment.key] ?? 'bg-gray-100 text-gray-700')}>{i.fulfillment.label}</span>}
                        </div>
                    </li>
                ))}
            </ul>
        </>
    );
}

function DriverPanel({ order }) {
    const t = useT();
    const d = order.driver;
    const [rating, setRating] = useState(5);
    const [comment, setComment] = useState('');
    const [tip, setTip] = useState(5);
    const [busy, setBusy] = useState(false);
    const post = (path, data) => router.post(`/account/orders/${order.id}/${path}`, data, { preserveScroll: true, onStart: () => setBusy(true), onFinish: () => setBusy(false) });

    return (
        <div className="grid gap-6 md:grid-cols-2">
            <div className={box}>
                <h3 className="mb-4 text-lg font-bold">{t('rate_delivery', 'Rate Your Delivery')}</h3>
                <p className="mb-3 flex items-center gap-2 font-medium"><Truck size={16} className="text-primary-600" />{d.name}</p>
                {d.rating ? (
                    <>
                        <p className="flex items-center gap-1" aria-label={`${d.rating}/5`}>{[1, 2, 3, 4, 5].map((n) => <Star key={n} size={18} className={n <= d.rating ? 'fill-yellow-400 text-yellow-400' : 'text-gray-300'} />)}<span className="ms-2 font-medium text-gray-700">{d.rating}/5</span></p>
                        {d.comment && <p className="mt-2 text-sm text-gray-600">“{d.comment}”</p>}
                    </>
                ) : (
                    <form onSubmit={(e) => { e.preventDefault(); post('rate-driver', { rating, comment }); }}>
                        <div className="mb-3 flex gap-1" role="radiogroup" aria-label={t('rating', 'Rating')}>
                            {[1, 2, 3, 4, 5].map((n) => (
                                <button key={n} type="button" role="radio" aria-checked={rating === n} aria-label={`${n}`} onClick={() => setRating(n)}>
                                    <Star size={26} className={n <= rating ? 'fill-yellow-400 text-yellow-400' : 'text-gray-300'} />
                                </button>
                            ))}
                        </div>
                        <textarea value={comment} onChange={(e) => setComment(e.target.value)} maxLength={500} rows={2} placeholder={t('optional_comment', 'Add a comment (optional)')}
                            className="mb-3 w-full rounded-lg border px-3 py-2 text-sm" />
                        <button type="submit" disabled={busy} className="sf-btn w-full rounded-lg bg-primary-600 py-2.5 font-medium text-white hover:bg-primary-700 disabled:opacity-60">{t('submit_rating', 'Submit Rating')}</button>
                    </form>
                )}
            </div>
            <div className={box}>
                <h3 className="mb-4 text-lg font-bold">{t('tip_driver', 'Tip Your Driver')}</h3>
                {d.tip ? (
                    <div className="flex items-center gap-3"><Gift className="text-green-600" size={28} /><div><p className="font-medium text-gray-700">{t('you_tipped', 'You tipped')} {d.tip}</p><p className="text-sm text-gray-500">{t('thanks_generosity', 'Thank you for your generosity!')}</p></div></div>
                ) : (
                    <form onSubmit={(e) => { e.preventDefault(); post('tip-driver', { amount: tip }); }}>
                        <p className="mb-4 text-sm text-gray-500">{t('tip_hint', 'Show appreciation for great service')}</p>
                        <div className="mb-4 grid grid-cols-4 gap-2">
                            {d.tipOptions.map((o) => (
                                <button key={o.value} type="button" aria-pressed={tip === o.value} onClick={() => setTip(o.value)}
                                    className={cx('rounded-lg border-2 py-2 text-sm font-medium', tip === o.value ? 'border-green-500 bg-green-50 text-green-700' : 'border-gray-200 hover:border-gray-300')}>{o.label}</button>
                            ))}
                        </div>
                        <button type="submit" disabled={busy} className="w-full rounded-lg bg-green-600 py-2.5 font-medium text-white hover:bg-green-700 disabled:opacity-60">{t('send_tip', 'Send Tip')}</button>
                    </form>
                )}
            </div>
        </div>
    );
}

export default function OrderDetail({ order, seo }) {
    const t = useT();
    const L = order.links;
    const actions = (
        <div className="flex flex-wrap gap-2">
            {L.invoice && <a href={L.invoice} className="flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm text-white hover:bg-green-700"><FileText size={16} />{t('view_invoice', 'View Invoice')}</a>}
            {L.downloads && <a href={L.downloads} className="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700"><Download size={16} />{t('downloads', 'Downloads')}</a>}
            <SfLink href="/account/orders" className="flex items-center gap-2 px-2 py-2 text-sm text-primary-600 hover:underline"><ArrowLeft size={16} />{t('back_to_orders', 'Back to Orders')}</SfLink>
        </div>
    );
    const place = order.pickup ? order.pickupLocation : order.address;
    const PayIcon = order.paymentMethod.card ? CreditCard : Banknote;

    return (
        <AccountLayout title={t('order_details', 'Order Details')} seo={seo} actions={actions} contentClass="grid gap-6 xl:grid-cols-3">
            <div className="space-y-6 xl:col-span-2">
                <section className={box}>
                    <div className="mb-4 flex flex-wrap items-center justify-between gap-2 border-b pb-4">
                        <div><h2 className="text-lg font-bold">{t('order', 'Order')} {order.number}</h2><p className="text-sm text-gray-500">{order.date}</p></div>
                        <div className="flex gap-2"><Badge status={order.status} /><Badge status={order.payment} /></div>
                    </div>
                    {order.statusKey === 'cancelled' ? (
                        <p className="mb-6 flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 p-4"><XCircle className="text-red-500" size={22} /><span><strong className="block text-red-700">{t('order_cancelled', 'Order Cancelled')}</strong><span className="text-sm text-red-500">{t('order_cancelled_text', 'This order has been cancelled')}</span></span></p>
                    ) : order.digitalOnly ? (
                        <div className="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-blue-200 bg-blue-50 p-4">
                            <p className="flex items-center gap-3"><Download className="text-blue-600" size={22} /><span><strong className="block text-blue-800">{t('digital_order', 'Digital Download Order')}</strong><span className="text-sm text-blue-600">{order.paid ? t('digital_ready', 'Your files are ready to download.') : t('digital_after_payment', 'Downloads will be available once payment is confirmed.')}</span></span></p>
                            {order.paid && L.downloads && <a href={L.downloads} className="rounded-lg bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">{t('download_files', 'Download Files')}</a>}
                        </div>
                    ) : <Progress order={order} />}
                    <Items order={order} />
                </section>

                {!order.digitalOnly && place && (
                    <section className={box}>
                        <h3 className="mb-3 flex items-center gap-2 text-lg font-bold">{order.pickup ? <><Store size={18} />{t('pickup_location', 'Pickup Location')}</> : <><MapPin size={18} />{t('shipping_address', 'Shipping Address')}</>}</h3>
                        {place.name && <p className="font-medium text-gray-700">{place.name}</p>}
                        {place.lines.map((l) => <p key={l} className="text-sm text-gray-500">{l}</p>)}
                        {place.phone && <p className="mt-2 flex items-center gap-1 text-sm text-gray-500"><Phone size={12} />{place.phone}</p>}
                        {place.instructions && <p className="mt-2 flex items-center gap-1 text-xs text-blue-600"><Info size={12} />{place.instructions}</p>}
                    </section>
                )}

                {order.driver && <DriverPanel order={order} />}
            </div>

            <aside>
                <div className={cx(box, 'sticky top-4')}>
                    <h3 className="mb-4 text-lg font-bold">{t('order_summary', 'Order Summary')}</h3>
                    <dl className="space-y-3 text-sm text-gray-600">
                        <div className="flex justify-between"><dt>{t('subtotal', 'Subtotal')}</dt><dd>{order.totals.subtotal}</dd></div>
                        {order.totals.discount && <div className="flex justify-between text-green-600"><dt>{t('discount', 'Discount')}</dt><dd>-{order.totals.discount}</dd></div>}
                        <div className="flex justify-between"><dt>{t('shipping', 'Shipping')}</dt><dd>{order.totals.shipping}</dd></div>
                        <div className="flex justify-between"><dt>{t('tax', 'Tax')}</dt><dd>{order.totals.tax}</dd></div>
                        <div className="flex justify-between border-t pt-3 text-lg font-bold text-gray-900"><dt>{t('total', 'Total')}</dt><dd>{order.totals.total}</dd></div>
                    </dl>
                    <div className="mt-6 border-t pt-4">
                        <p className="mb-2 text-sm text-gray-500">{t('payment_method', 'Payment Method')}</p>
                        <p className="flex items-center gap-2 text-sm font-medium">
                            <span className={cx('inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium', order.paymentMethod.card ? 'bg-indigo-100 text-indigo-800' : 'bg-green-100 text-green-800')}><PayIcon size={12} />{order.paymentMethod.card ? t('card', 'Card') : t('cash', 'Cash')}</span>
                            {order.paymentMethod.name}
                        </p>
                        {order.refund && (
                            <div className="mt-3 rounded-lg bg-red-50 p-3 text-xs text-red-600">
                                <p className="flex items-center gap-1 font-medium"><Undo2 size={12} />{t('refunded', 'Refunded')}</p>
                                {order.refund.date && <p>{order.refund.date}</p>}
                                {order.refund.amount && <p>{order.refund.amount}</p>}
                            </div>
                        )}
                    </div>
                </div>
            </aside>
        </AccountLayout>
    );
}
