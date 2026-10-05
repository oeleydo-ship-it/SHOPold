import { useState } from 'react';
import { router } from '@inertiajs/react';
import { ArrowLeft, ImageIcon, ShoppingBag, ShoppingCart, Tag, Trash2, X } from 'lucide-react';
import SfLink from '../lib/SfLink';
import { cx, useStore, useT } from '../lib/store';
import { ProductRowSection } from '../Product/Detail';

/** Cart endpoints answer with a redirect back to /cart, so Inertia simply shows the new cart. */
const send = (method, url, data = {}) => router.visit(url, { method, data, preserveScroll: true });

export function CartItems({ items }) {
    const t = useT();
    return (
        <div className="overflow-hidden rounded-xl bg-white shadow-sm">
            <ul className="divide-y">
                {items.map((item) => (
                    <li key={item.id} className="flex gap-4 p-4 sm:p-6">
                        <div className="h-20 w-20 shrink-0 overflow-hidden rounded-lg bg-gray-100 sm:h-24 sm:w-24">
                            {item.image
                                ? <img src={item.image} alt={item.name} className="h-full w-full object-cover" />
                                : <span className="flex h-full w-full items-center justify-center text-gray-400"><ImageIcon size={24} /></span>}
                        </div>
                        <div className="min-w-0 flex-1">
                            <SfLink href={item.url} className="font-semibold text-gray-900 hover:text-primary-600">{item.name}</SfLink>
                            {item.detail && <p className="mt-1 text-sm text-gray-500">{item.detail}</p>}
                            <p className="mt-2 font-semibold text-primary-600">{item.unit}</p>
                        </div>
                        <div className="flex flex-col items-end gap-2">
                            <select value={item.quantity} aria-label={`${t('quantity', 'Quantity')}: ${item.name}`}
                                onChange={(e) => send('post', '/cart/update', { item_id: item.id, quantity: Number(e.target.value) })}
                                className="rounded-lg border px-3 py-2 text-sm">
                                {Array.from({ length: Math.max(1, item.max) }, (_, i) => i + 1).map((n) => <option key={n} value={n}>{n}</option>)}
                            </select>
                            <p className="font-semibold">{item.subtotal}</p>
                            <button type="button" onClick={() => send('post', '/cart/remove', { item_id: item.id })}
                                className="flex items-center gap-1 text-sm text-red-500 hover:text-red-700">
                                <Trash2 size={14} />{t('remove', 'Remove')}
                            </button>
                        </div>
                    </li>
                ))}
            </ul>
        </div>
    );
}

export function CartActions() {
    const t = useT();
    const clear = () => {
        if (window.confirm(t('clear_cart_confirm', 'Remove every item from your cart?'))) send('post', '/cart/clear');
    };
    return (
        <div className="mt-4 flex justify-between">
            <SfLink href="/shop" className="flex items-center gap-2 text-primary-600 hover:text-primary-700"><ArrowLeft size={16} />{t('continue_shopping', 'Continue Shopping')}</SfLink>
            <button type="button" onClick={clear} className="flex items-center gap-2 text-red-500 hover:text-red-700"><Trash2 size={16} />{t('clear_cart', 'Clear Cart')}</button>
        </div>
    );
}

export function CouponForm({ coupon }) {
    const t = useT();
    const [code, setCode] = useState('');
    if (coupon) {
        return (
            <div className="mb-4 flex items-center justify-between rounded-lg bg-green-50 p-3">
                <span className="flex items-center gap-2 text-green-700"><Tag size={16} />{coupon.code}</span>
                <button type="button" onClick={() => send('delete', '/cart/coupon')} aria-label={t('remove_coupon', 'Remove coupon')} className="text-red-500 hover:text-red-700"><X size={16} /></button>
            </div>
        );
    }
    const apply = (e) => {
        e.preventDefault();
        if (code.trim()) send('post', '/cart/coupon', { coupon_code: code.trim() });
    };
    return (
        <form onSubmit={apply} className="mb-4 flex gap-2">
            <input type="text" value={code} onChange={(e) => setCode(e.target.value)} placeholder={t('coupon_code', 'Coupon code')} aria-label={t('coupon_code', 'Coupon code')}
                className="min-w-0 flex-1 rounded-lg border px-4 py-2 text-sm" />
            <button type="submit" className="rounded-lg bg-gray-900 px-4 py-2 text-sm text-white transition hover:bg-gray-800">{t('apply', 'Apply')}</button>
        </form>
    );
}

export function CartSummary({ coupon, totals, guestCheckout }) {
    const t = useT();
    const { customer } = useStore();
    const btn = 'sf-btn mt-6 block w-full rounded-lg bg-primary-600 py-3 text-center font-semibold text-white transition hover:bg-primary-700';
    return (
        <div className="sticky top-24 rounded-xl bg-white p-6 shadow-sm">
            <h2 className="mb-4 text-lg font-bold">{t('order_summary', 'Order Summary')}</h2>
            <CouponForm coupon={coupon} />
            <dl className="space-y-3 text-gray-600">
                <div className="flex justify-between"><dt>{t('subtotal', 'Subtotal')}</dt><dd className="font-medium text-gray-900">{totals.subtotal}</dd></div>
                {totals.discount && <div className="flex justify-between text-green-600"><dt>{t('discount', 'Discount')}</dt><dd>-{totals.discount}</dd></div>}
                <div className="flex justify-between"><dt>{t('shipping', 'Shipping')}</dt><dd className="text-gray-900">{t('calculated_at_checkout', 'Calculated at checkout')}</dd></div>
                <div className="flex justify-between border-t pt-3 text-lg font-bold text-gray-900"><dt>{t('total', 'Total')}</dt><dd>{totals.total}</dd></div>
            </dl>
            {customer || guestCheckout ? (
                <>
                    <SfLink href="/checkout" className={btn}>{t('proceed_to_checkout', 'Proceed to Checkout')}</SfLink>
                    {!customer && <p className="mt-2 text-center text-sm text-gray-500">{t('checkout_as_guest_or', 'Checkout as guest or')} <a href="/login" className="text-primary-600 hover:underline">{t('login', 'Login')}</a></p>}
                </>
            ) : (
                <>
                    <a href="/login" className={btn}>{t('login_to_checkout', 'Login to Checkout')}</a>
                    <p className="mt-2 text-center text-sm text-gray-500">{t('no_account', "Don't have an account?")} <a href="/register" className="text-primary-600 hover:underline">{t('register', 'Register')}</a></p>
                </>
            )}
        </div>
    );
}

export function CartEmpty() {
    const t = useT();
    return (
        <div className="rounded-xl bg-white p-12 text-center shadow-sm">
            <ShoppingCart size={64} className="mx-auto mb-4 text-gray-300" />
            <h2 className="mb-2 text-xl font-semibold text-gray-900">{t('cart_empty', 'Your cart is empty')}</h2>
            <p className="mb-6 text-gray-600">{t('cart_empty_text', "Looks like you haven't added any items yet")}</p>
            <SfLink href="/shop" className={cx('sf-btn inline-flex items-center gap-2 rounded-lg bg-primary-600 px-6 py-3 text-white transition hover:bg-primary-700')}>
                <ShoppingBag size={18} />{t('start_shopping', 'Start Shopping')}
            </SfLink>
        </div>
    );
}

export function CartCrossSell({ products }) {
    const t = useT();
    return <ProductRowSection title={t('you_may_also_like', 'You May Also Like')} products={products} />;
}
