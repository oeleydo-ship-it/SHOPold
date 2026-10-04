import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Download, Printer } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { Card, LinkButton, PaymentStatusBadge, titleCase } from '../../../Components/ui';

export default function Show({ store, invoice }) {
    const row = (label, value, cls = '') => (
        <div className={`flex justify-between py-1.5 ${cls}`}><span className="text-muted">{label}</span><span>{value}</span></div>
    );

    return (
        <AdminLayout title={`Invoice #${invoice.number}`}>
            <Head title={`Invoice ${invoice.number}`} />
            <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
                <Link href={`/admin/orders/${invoice.id}`} className="inline-flex items-center gap-1 text-sm text-muted hover:text-ink"><ArrowLeft size={16} />Back to order</Link>
                <div className="flex gap-2">
                    <LinkButton external href={`/admin/invoices/${invoice.id}/download`}><Download size={16} />Download PDF</LinkButton>
                    <LinkButton external tone="neutral" href={`/admin/invoices/${invoice.id}/print`} target="_blank" rel="noreferrer"><Printer size={16} />Print</LinkButton>
                </div>
            </div>

            <Card className="mx-auto max-w-4xl p-5 md:p-10">
                <div className="mb-8 flex flex-col justify-between gap-6 sm:flex-row">
                    <div className="text-sm">
                        <h2 className="text-2xl font-bold">{store.name}</h2>
                        {[store.address, store.email, store.phone].filter(Boolean).map((l) => <p key={l} className="text-muted">{l}</p>)}
                    </div>
                    <div className="text-sm sm:text-right">
                        <p className="text-3xl font-bold tracking-wide text-muted">INVOICE</p>
                        <p className="mt-2 text-lg font-semibold">#{invoice.number}</p>
                        <p className="text-muted">Date: {invoice.date}</p>
                        <p className="text-muted">Due: {invoice.due}</p>
                    </div>
                </div>

                <div className="mb-8 grid gap-6 text-sm sm:grid-cols-2">
                    <div>
                        <h3 className="mb-2 text-xs font-semibold uppercase text-muted">Bill to</h3>
                        <p className="font-semibold">{invoice.bill_to.name}</p>
                        <p className="text-muted">{invoice.bill_to.email}</p>
                    </div>
                    <div>
                        <h3 className="mb-2 text-xs font-semibold uppercase text-muted">Ship to</h3>
                        <p className="font-semibold">{invoice.ship_to.name}</p>
                        {invoice.ship_to.lines.map((l) => <p key={l} className="text-muted">{l}</p>)}
                    </div>
                </div>

                <div className="mb-8 overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="border-b-2 border-line text-left text-muted">
                            <tr><th className="py-3">Item</th><th className="py-3 text-center">Qty</th><th className="py-3 text-right">Price</th><th className="py-3 text-right">Total</th></tr>
                        </thead>
                        <tbody className="divide-y divide-line">
                            {invoice.items.map((i) => (
                                <tr key={i.id}>
                                    <td className="py-3">
                                        <p className="font-medium">{i.name}</p>
                                        {Object.keys(i.options).length > 0 && <p className="text-xs text-primary">{Object.entries(i.options).map(([k, v]) => `${k}: ${v}`).join(', ')}</p>}
                                        {i.sku && <p className="text-xs text-muted">SKU: {i.sku}</p>}
                                    </td>
                                    <td className="py-3 text-center">{i.quantity}</td>
                                    <td className="py-3 text-right">{i.price}</td>
                                    <td className="py-3 text-right font-medium">{i.total}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="flex justify-end text-sm">
                    <div className="w-full max-w-xs">
                        {row('Subtotal', invoice.subtotal)}
                        {invoice.discount && row('Discount', `−${invoice.discount}`, 'text-success')}
                        {row('Shipping', invoice.shipping)}
                        {row('Tax', invoice.tax)}
                        <div className="mt-1 flex justify-between border-t-2 border-line pt-3 text-lg font-bold"><span>Total</span><span>{invoice.total}</span></div>
                    </div>
                </div>

                <div className="mt-8 flex flex-col justify-between gap-4 border-t border-line pt-6 text-sm sm:flex-row">
                    <div>
                        <h3 className="mb-1 font-semibold">Payment</h3>
                        <p className="text-muted">Method: {titleCase(invoice.payment_method ?? '')}</p>
                        <p className="mt-1 text-muted">Status: <PaymentStatusBadge status={invoice.payment_status} /></p>
                    </div>
                    <p className="self-end text-muted">Thank you for your business!</p>
                </div>
            </Card>
        </AdminLayout>
    );
}
