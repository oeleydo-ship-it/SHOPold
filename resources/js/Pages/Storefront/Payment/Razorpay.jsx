import { useEffect, useRef, useState } from 'react';
import { CreditCard, Lock, ShieldCheck } from 'lucide-react';
import Seo from '../../../Storefront/Layout/Seo';
import { postForm } from '../../../Storefront/lib/http';
import { useT } from '../../../Storefront/lib/store';

/** Loads Razorpay's checkout script (it must come from checkout.razorpay.com) and opens the payment window. */
export default function Razorpay({ order, checkout, callbackUrl, cancelUrl, seo }) {
    const t = useT();
    const rzp = useRef(null);
    const [error, setError] = useState('');

    useEffect(() => {
        const open = () => {
            rzp.current = new window.Razorpay({
                ...checkout,
                theme: { color: '#4F46E5' },
                // Razorpay's signed result goes to the server, which verifies it before marking the order paid.
                handler: (r) => postForm(callbackUrl, {
                    razorpay_payment_id: r.razorpay_payment_id, razorpay_order_id: r.razorpay_order_id, razorpay_signature: r.razorpay_signature,
                }),
                modal: { ondismiss: () => { window.location.href = cancelUrl; } },
            });
            rzp.current.open();
        };
        if (window.Razorpay) { open(); return undefined; }
        const s = document.createElement('script');
        s.src = 'https://checkout.razorpay.com/v1/checkout.js';
        s.onload = open;
        s.onerror = () => setError(t('payment_load_failed', 'Could not load the payment window. Please check your connection and try again.'));
        document.body.appendChild(s);
        return () => s.remove();
    }, [checkout, callbackUrl, cancelUrl]);

    return (
        <>
            <Seo title={seo.title} robots={seo.robots} />
            <div className="mx-auto max-w-lg px-4 py-16 text-center">
                <div className="rounded-xl bg-white p-8 shadow-sm">
                    <span className="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-indigo-100 text-indigo-600"><CreditCard size={28} /></span>
                    <h1 className="mb-2 text-2xl font-bold">{t('complete_payment', 'Complete Your Payment')}</h1>
                    <p className="mb-6 text-gray-600">{t('order', 'Order')} #{order.number}</p>
                    <div className="mb-6 flex items-center justify-between rounded-lg bg-gray-50 p-4">
                        <span className="text-gray-600">{t('amount_to_pay', 'Amount to Pay')}</span>
                        <span className="text-2xl font-bold text-primary-600">{order.total}</span>
                    </div>
                    {error && <p role="alert" className="mb-4 text-sm text-red-600">{error}</p>}
                    <button type="button" onClick={() => rzp.current?.open()} disabled={!!error}
                        className="flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 py-3 font-semibold text-white transition hover:bg-indigo-700 disabled:opacity-50">
                        <Lock size={16} />{t('pay_with_razorpay', 'Pay with Razorpay')}
                    </button>
                    <p className="mt-4 flex items-center justify-center gap-1 text-sm text-gray-500"><ShieldCheck size={14} />{t('secured_by_razorpay', 'Secured by Razorpay')}</p>
                </div>
            </div>
        </>
    );
}
