import { ArrowLeft, Printer } from 'lucide-react';
import AccountLayout from '../../../Storefront/Account/AccountLayout';
import SfLink from '../../../Storefront/lib/SfLink';
import { useT } from '../../../Storefront/lib/store';

/** On-screen invoice for a paid order; the print button opens the printable Blade version. */
export default function Invoice({ store, invoice, seo }) {
    const t = useT();
    const actions = (
        <div className="flex gap-2">
            <a href={invoice.printUrl} target="_blank" rel="noopener" className="flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm text-white hover:bg-primary-700"><Printer size={16} />{t('print_invoice', 'Print / Download')}</a>
            <SfLink href="/account/orders" className="flex items-center gap-2 px-2 py-2 text-sm text-gray-600 hover:text-gray-800"><ArrowLeft size={16} />{t('back_to_orders', 'Back to Orders')}</SfLink>
        </div>
    );
    const T = invoice.totals;

    return (
        <AccountLayout title={`${t('invoice', 'Invoice')} #${invoice.number}`} seo={seo} actions={actions}>
            <div className="rounded-xl bg-white p-4 shadow-sm md:p-8">
                <div className="mb-8 flex flex-col justify-between gap-6 border-b pb-6 sm:flex-row">
                    <div>
                        <h2 className="text-xl font-bold text-gray-800 md:text-2xl">{store.name}</h2>
                        {[store.address, store.email, store.phone].filter(Boolean).map((l) => <p key={l} className="text-sm text-gray-600 md:text-base">{l}</p>)}
                    </div>
                    <div className="sm:text-end">
                        <p className="text-2xl font-bold text-gray-400 md:text-3xl">{t('invoice_caps', 'INVOICE')}</p>
                        <p className="mt-2 font-semibold">#{invoice.number}</p>
                        <p className="text-sm text-gray-600">{t('date', 'Date')}: {invoice.date}</p>
                    </div>
                </div>

                <div className="mb-8 grid gap-6 sm:grid-cols-2">
                    <div>
                        <h3 className="mb-2 text-xs font-semibold uppercase text-gray-500">{t('bill_to', 'Bill To')}</h3>
                        <p className="font-semibold">{invoice.billTo.name}</p>
                        <p className="text-sm text-gray-600">{invoice.billTo.email}</p>
                    </div>
                    <div>
                        <h3 className="mb-2 text-xs font-semibold uppercase text-gray-500">{t('ship_to', 'Ship To')}</h3>
                        <p className="font-semibold">{invoice.shipTo.name}</p>
                        {invoice.shipTo.lines.map((l) => <p key={l} className="text-sm text-gray-600">{l}</p>)}
                    </div>
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full min-w-[32rem] text-sm">
                        <thead className="border-b-2 text-xs uppercase text-gray-500">
                            <tr><th className="py-3 text-start">{t('item', 'Item')}</th><th className="py-3 text-center">{t('qty', 'Qty')}</th><th className="py-3 text-end">{t('price', 'Price')}</th><th className="py-3 text-end">{t('total', 'Total')}</th></tr>
                        </thead>
                        <tbody className="divide-y">
                            {invoice.items.map((i) => (
                                <tr key={i.id}>
                                    <td className="py-4">
                                        <p className="font-medium">{i.name}</p>
                                        {i.options.length > 0 && <p className="text-xs text-gray-500">{i.options.join(', ')}</p>}
                                        {i.sku && <p className="text-xs text-gray-500">SKU: {i.sku}</p>}
                                    </td>
                                    <td className="py-4 text-center">{i.quantity}</td>
                                    <td className="py-4 text-end">{i.price}</td>
                                    <td className="py-4 text-end font-medium">{i.total}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <dl className="ms-auto mt-6 w-full max-w-xs space-y-2 text-sm">
                    <div className="flex justify-between"><dt className="text-gray-600">{t('subtotal', 'Subtotal')}</dt><dd>{T.subtotal}</dd></div>
                    {T.discount && <div className="flex justify-between text-green-600"><dt>{t('discount', 'Discount')}</dt><dd>-{T.discount}</dd></div>}
                    <div className="flex justify-between"><dt className="text-gray-600">{t('shipping', 'Shipping')}</dt><dd>{T.shipping}</dd></div>
                    <div className="flex justify-between"><dt className="text-gray-600">{t('tax', 'Tax')}</dt><dd>{T.tax}</dd></div>
                    <div className="flex justify-between border-t-2 pt-2 text-lg font-bold"><dt>{t('total', 'Total')}</dt><dd>{T.total}</dd></div>
                </dl>

                <div className="mt-8 border-t pt-6">
                    <h3 className="mb-2 font-semibold">{t('payment_information', 'Payment Information')}</h3>
                    <p className="text-sm text-gray-600">{t('method', 'Method')}: {invoice.paymentMethod}</p>
                    {invoice.transactionId && <p className="break-all text-sm text-gray-600">{t('transaction_id', 'Transaction ID')}: {invoice.transactionId}</p>}
                </div>
            </div>
        </AccountLayout>
    );
}
