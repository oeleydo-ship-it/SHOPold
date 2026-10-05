import { useRef, useState } from 'react';
import { useForm } from '@inertiajs/react';
import Seo from '../../Storefront/Layout/Seo';
import { Delivery, GuestPanel, Notes, Payment, ShippingInfo, Summary } from '../../Storefront/Checkout/Blocks';
import { postJson } from '../../Storefront/lib/http';
import { useShop, useT } from '../../Storefront/lib/store';
import { useThemed } from '../../Storefront/theme/registry';

/** The JSON shipping endpoint uses snake_case names; the page props use camelCase. */
const fromApi = (m) => ({ id: m.id, name: m.name, description: m.description, deliveryTime: m.deliveryTime ?? m.delivery_time, cost: m.cost_formatted, total: m.total_formatted });

/** Checkout page. Blocks can be replaced per theme as "Checkout.<Block>". */
export default function Checkout({ items, totals, shippingMethods, pickupLocations, countries, defaults, guest, payments, seo }) {
    const t = useT();
    const { toast } = useShop();
    const [methods, setMethods] = useState(shippingMethods);
    const [loading, setLoading] = useState(false);
    const [verified, setVerified] = useState(!guest.otpRequired || guest.verified);
    const stripeRef = useRef(null);
    const form = useForm({
        ...defaults,
        delivery_type: 'shipping',
        shipping_method: shippingMethods[0]?.id ?? '',
        pickup_location_id: '',
        payment_method: payments[0]?.key ?? '',
        stripe_payment_method: '',
    });
    const pickup = form.data.delivery_type === 'pickup';
    const method = methods.find((m) => String(m.id) === String(form.data.shipping_method));

    const B = {
        Guest: useThemed('Checkout.Guest', GuestPanel),
        ShippingInfo: useThemed('Checkout.ShippingInfo', ShippingInfo),
        Delivery: useThemed('Checkout.Delivery', Delivery),
        Payment: useThemed('Checkout.Payment', Payment),
        Notes: useThemed('Checkout.Notes', Notes),
        Summary: useThemed('Checkout.Summary', Summary),
    };

    const changeCountry = async (country) => {
        setLoading(true);
        try {
            const res = await postJson('/checkout/shipping-methods', { country });
            const list = (res.methods ?? []).map(fromApi);
            setMethods(list);
            form.setData((d) => ({ ...d, shipping_country: country, shipping_method: list[0]?.id ?? '' }));
        } catch (e) {
            toast('error', e.message);
        }
        setLoading(false);
    };

    const submit = async (e) => {
        e.preventDefault();
        if (form.processing) return;
        let stripePm = '';
        if (form.data.payment_method === 'stripe') {
            if (!stripeRef.current) {
                toast('error', t('card_form_loading', 'The card form is still loading. Please try again.'));
                return;
            }
            stripePm = await stripeRef.current();
            if (!stripePm) return; // the card field shows the error
        }
        form.transform((d) => ({ ...d, stripe_payment_method: stripePm || undefined }));
        // The form keeps its values if the server sends us back with an error.
        form.post('/checkout', { preserveScroll: true, preserveState: true });
    };

    return (
        <>
            <Seo title={seo.title} robots={seo.robots} />
            <div className="sf-container py-8">
                <h1 className="mb-8 text-3xl font-bold">{t('checkout', 'Checkout')}</h1>
                <B.Guest guest={guest} form={form} verified={verified} setVerified={setVerified} />
                <form onSubmit={submit}>
                    <div className="grid gap-8 lg:grid-cols-3">
                        <div className={verified ? 'space-y-6 lg:col-span-2' : 'pointer-events-none space-y-6 opacity-50 lg:col-span-2'} aria-disabled={!verified}>
                            <B.ShippingInfo form={form} countries={countries} emailLocked={guest.isGuest && guest.otpRequired} pickup={pickup} onCountry={changeCountry} />
                            <B.Delivery form={form} methods={methods} loading={loading} pickupLocations={pickupLocations} />
                            <B.Payment form={form} payments={payments} stripeRef={stripeRef} />
                            <B.Notes form={form} />
                        </div>
                        <div className="lg:col-span-1">
                            <B.Summary items={items} totals={totals}
                                shipping={pickup ? t('free', 'FREE') : (method?.cost ?? totals.zero)}
                                total={pickup ? totals.pickupTotal : (method?.total ?? totals.pickupTotal)}
                                disabled={!verified || !payments.length || loading} processing={form.processing} />
                        </div>
                    </div>
                </form>
            </div>
        </>
    );
}
