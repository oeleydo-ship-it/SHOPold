import { Check } from 'lucide-react';
import Seo from '../../Storefront/Layout/Seo';
import SfLink from '../../Storefront/lib/SfLink';
import { useT } from '../../Storefront/lib/store';
import { useThemed } from '../../Storefront/theme/registry';

export function SuccessDetails({ order }) {
    const t = useT();
    const rows = [
        [t('order_number', 'Order Number'), order.number, 'text-lg'],
        [t('total_amount', 'Total Amount'), order.total, 'text-lg'],
        [t('payment_method', 'Payment Method'), order.payment],
        [t('shipping_to', 'Shipping To'), order.destination],
    ];
    return (
        <dl className="mb-8 grid gap-4 rounded-xl bg-gray-50 p-6 text-start md:grid-cols-2">
            {rows.map(([label, value, size]) => (
                <div key={label}><dt className="text-sm text-gray-500">{label}</dt><dd className={`font-semibold ${size ?? ''}`}>{value}</dd></div>
            ))}
        </dl>
    );
}

export function SuccessActions({ order }) {
    const t = useT();
    return (
        <div className="flex flex-col justify-center gap-4 sm:flex-row">
            {order.url && <a href={order.url} className="sf-btn rounded-lg bg-primary-600 px-6 py-3 font-semibold text-white transition hover:bg-primary-700">{t('view_order_details', 'View Order Details')}</a>}
            <SfLink href="/shop" className="sf-btn rounded-lg border border-gray-300 px-6 py-3 font-semibold text-gray-700 transition hover:bg-gray-50">{t('continue_shopping', 'Continue Shopping')}</SfLink>
        </div>
    );
}

/** Order confirmation. Blocks can be replaced per theme as "CheckoutSuccess.<Block>". */
export default function CheckoutSuccess({ order, seo }) {
    const t = useT();
    const Details = useThemed('CheckoutSuccess.Details', SuccessDetails);
    const Actions = useThemed('CheckoutSuccess.Actions', SuccessActions);
    return (
        <>
            <Seo title={seo.title} robots={seo.robots} />
            <div className="mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
                <div className="rounded-2xl bg-white p-8 text-center shadow-sm">
                    <span className="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-green-100 text-green-500"><Check size={40} /></span>
                    <h1 className="mb-2 text-3xl font-bold text-gray-900">{t('thank_you', 'Thank You!')}</h1>
                    <p className="mb-6 text-xl text-gray-600">{t('order_placed', 'Your order has been placed successfully')}</p>
                    <Details order={order} />
                    <p className="mb-8 text-gray-600">{t('confirmation_sent_to', 'A confirmation email has been sent to')} <strong>{order.email}</strong></p>
                    <Actions order={order} />
                </div>
            </div>
        </>
    );
}
