import { useEffect, useRef, useState } from 'react';
import {
    Banknote, Building2, CheckCircle2, Clock, CreditCard, ExternalLink, Info, Loader2, Mail, MapPin, Phone, Send, Store, TriangleAlert, Truck, UserRound, Wallet,
} from 'lucide-react';
import { postJson } from '../lib/http';
import { cx, useT } from '../lib/store';

const input = 'w-full rounded-lg border px-4 py-2 focus:border-primary-500 focus:ring-primary-500';
const card = 'rounded-xl bg-white p-6 shadow-sm';

function Field({ label, error, className, children }) {
    return (
        <label className={cx('block', className)}>
            <span className="mb-1 block text-sm font-medium text-gray-700">{label}</span>
            {children}
            {error && <span className="mt-1 block text-sm text-red-500">{error}</span>}
        </label>
    );
}

/** "Checking out as guest" notice plus the email verification (one-time code) step. */
export function GuestPanel({ guest, form, verified, setVerified }) {
    const t = useT();
    const [sent, setSent] = useState(false);
    const [code, setCode] = useState('');
    const [busy, setBusy] = useState(null);
    const [msg, setMsg] = useState(null);
    const [expiry, setExpiry] = useState(guest.otpExpiry);
    const [cooldown, setCooldown] = useState(0);
    const email = form.data.shipping_email;

    useEffect(() => {
        if (cooldown <= 0) return undefined;
        const id = setTimeout(() => setCooldown((c) => c - 1), 1000);
        return () => clearTimeout(id);
    }, [cooldown]);

    if (!guest.isGuest) return null;

    const send = async () => {
        if (!email || busy) return;
        setBusy('send');
        setMsg(null);
        try {
            const res = await postJson('/checkout/guest/send-otp', { email });
            setSent(true);
            setExpiry(res.expiry ?? expiry);
            setCooldown(60);
            setMsg(['ok', res.message]);
        } catch (e) {
            setMsg(['error', e.body?.errors?.email?.[0] ?? e.message]);
        }
        setBusy(null);
    };
    const verify = async () => {
        if (code.length < 4 || busy) return;
        setBusy('verify');
        setMsg(null);
        try {
            const res = await postJson('/checkout/guest/verify-otp', { email, otp: code });
            setVerified(true);
            setMsg(['ok', res.message]);
        } catch (e) {
            const left = e.body?.attempts_remaining;
            setMsg(['error', left !== undefined ? `${e.message} (${left} attempts remaining)` : e.message]);
        }
        setBusy(null);
    };

    return (
        <>
            <div className="mb-6 flex items-start gap-3 rounded-xl border border-blue-200 bg-blue-50 p-4">
                <UserRound className="mt-0.5 shrink-0 text-blue-500" size={22} />
                <div>
                    <h2 className="font-semibold text-blue-900">{t('checking_out_as_guest', 'Checking out as Guest')}</h2>
                    <p className="text-sm text-blue-700">
                        {t('guest_checkout_hint', "You're checking out without an account.")}{' '}
                        <a href="/login" className="underline hover:text-blue-900">{t('login', 'Login')}</a> {t('or', 'or')}{' '}
                        <a href="/register" className="underline hover:text-blue-900">{t('create_account', 'create an account')}</a> {t('to_track_orders', 'to track your orders easily.')}
                    </p>
                </div>
            </div>

            {guest.otpRequired && (verified ? (
                <div className="mb-6 flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 p-4">
                    <CheckCircle2 className="shrink-0 text-green-600" size={28} />
                    <div>
                        <h2 className="font-semibold text-green-900">{t('email_verified', 'Email Verified')}</h2>
                        <p className="text-sm text-green-700">{t('your_email', 'Your email')} <strong>{email}</strong> {t('email_verified_text', 'has been verified. You can now complete your order.')}</p>
                    </div>
                </div>
            ) : (
                <div className={cx(card, 'mb-6')}>
                    <div className="mb-4 flex items-center gap-3">
                        <span className="flex h-10 w-10 items-center justify-center rounded-full bg-primary-100 text-primary-600"><Mail size={18} /></span>
                        <div>
                            <h2 className="text-lg font-bold">{t('verify_your_email', 'Verify Your Email')}</h2>
                            <p className="text-sm text-gray-500">{t('verify_email_checkout', 'We need to verify your email before you can complete checkout')}</p>
                        </div>
                    </div>
                    <div className="space-y-4">
                        <Field label={`${t('email_address', 'Email Address')} *`}>
                            <div className="flex gap-2">
                                <input type="email" value={email} disabled={sent} onChange={(e) => form.setData('shipping_email', e.target.value)}
                                    placeholder={t('enter_email', 'Enter your email')} className={cx(input, 'flex-1 disabled:bg-gray-100')} />
                                <button type="button" onClick={send} disabled={busy === 'send' || !email || sent}
                                    className="flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-white hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-50">
                                    {busy === 'send' ? <Loader2 size={16} className="animate-spin" /> : <Send size={16} />}
                                    {sent ? t('code_sent', 'Code Sent') : t('send_code', 'Send Code')}
                                </button>
                            </div>
                        </Field>
                        {sent && (
                            <div>
                                <Field label={`${t('verification_code', 'Verification Code')} *`}>
                                    <div className="flex gap-2">
                                        <input type="text" inputMode="numeric" autoComplete="one-time-code" maxLength={8} value={code} onChange={(e) => setCode(e.target.value.trim())}
                                            placeholder={t('enter_code', 'Enter the code')} className={cx(input, 'flex-1 text-center font-mono text-2xl tracking-widest')} />
                                        <button type="button" onClick={verify} disabled={busy === 'verify' || code.length < 4}
                                            className="flex items-center gap-2 rounded-lg bg-green-600 px-6 py-2 text-white hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50">
                                            {busy === 'verify' ? <Loader2 size={16} className="animate-spin" /> : <CheckCircle2 size={16} />}{t('verify', 'Verify')}
                                        </button>
                                    </div>
                                </Field>
                                <div className="mt-2 flex items-center justify-between text-sm">
                                    <span className="flex items-center gap-1 text-gray-500"><Clock size={14} />{t('code_expires_in', 'Code expires in')} {expiry} {t('minutes', 'minutes')}</span>
                                    <button type="button" disabled={cooldown > 0} onClick={() => { setSent(false); setCode(''); }}
                                        className="text-primary-600 hover:text-primary-700 disabled:text-gray-400">
                                        {cooldown > 0 ? `${t('resend_in', 'Resend in')} ${cooldown}s` : t('change_email_resend', 'Change email / resend')}
                                    </button>
                                </div>
                            </div>
                        )}
                        {msg && (
                            <p role="status" className={cx('rounded-lg border p-3 text-sm', msg[0] === 'ok' ? 'border-green-200 bg-green-50 text-green-700' : 'border-red-200 bg-red-50 text-red-700')}>{msg[1]}</p>
                        )}
                    </div>
                </div>
            ))}
        </>
    );
}

export function ShippingInfo({ form, countries, emailLocked, pickup, onCountry }) {
    const t = useT();
    const { data, setData, errors } = form;
    const bind = (name) => ({ name, value: data[name] ?? '', onChange: (e) => setData(name, e.target.value) });
    return (
        <div className={card}>
            <h2 className="mb-4 text-lg font-bold">{t('shipping_information', 'Shipping Information')}</h2>
            <div className="grid gap-4 md:grid-cols-2">
                <Field label={`${t('full_name', 'Full Name')} *`} error={errors.shipping_name} className="md:col-span-2">
                    <input type="text" required autoComplete="name" {...bind('shipping_name')} className={input} />
                </Field>
                <Field label={`${t('email', 'Email')} *`} error={errors.shipping_email}>
                    <input type="email" required autoComplete="email" readOnly={emailLocked} {...bind('shipping_email')} className={cx(input, emailLocked && 'bg-gray-100')} />
                </Field>
                <Field label={t('phone', 'Phone')} error={errors.shipping_phone}>
                    <input type="tel" autoComplete="tel" {...bind('shipping_phone')} className={input} />
                </Field>
                {!pickup && (
                    <>
                        <Field label={`${t('address', 'Address')} *`} error={errors.shipping_address} className="md:col-span-2">
                            <textarea rows={2} required autoComplete="street-address" {...bind('shipping_address')} className={input} />
                        </Field>
                        <Field label={`${t('city', 'City')} *`} error={errors.shipping_city}>
                            <input type="text" required autoComplete="address-level2" {...bind('shipping_city')} className={input} />
                        </Field>
                        <Field label={t('state', 'State')} error={errors.shipping_state}>
                            <input type="text" autoComplete="address-level1" {...bind('shipping_state')} className={input} />
                        </Field>
                        <Field label={`${t('zip_code', 'ZIP Code')} *`} error={errors.shipping_zip}>
                            <input type="text" required autoComplete="postal-code" {...bind('shipping_zip')} className={input} />
                        </Field>
                        <Field label={`${t('country', 'Country')} *`} error={errors.shipping_country}>
                            {countries.locked ? (
                                <>
                                    <input type="text" readOnly value={countries.list[0] ?? ''} className={cx(input, 'cursor-not-allowed bg-gray-100 text-gray-600')} />
                                    <span className="mt-1 flex items-center gap-1 text-xs text-gray-500"><Info size={12} />{t('ship_only_within', 'We currently only ship within')} {countries.list[0]}</span>
                                </>
                            ) : (
                                <select required autoComplete="country-name" value={data.shipping_country} onChange={(e) => { setData('shipping_country', e.target.value); onCountry(e.target.value); }} className={input}>
                                    {countries.list.map((c) => <option key={c} value={c}>{c}</option>)}
                                </select>
                            )}
                        </Field>
                    </>
                )}
            </div>
        </div>
    );
}

function Choice({ checked, children, className, ...rest }) {
    return (
        <label className={cx('flex cursor-pointer items-start rounded-lg border p-4 transition hover:border-primary-500', checked && 'border-primary-500 bg-primary-50', className)}>
            <input type="radio" checked={checked} className="mt-1 text-primary-600" {...rest} />
            <div className="ms-3 flex-1">{children}</div>
        </label>
    );
}

export function Delivery({ form, methods, loading, pickupLocations }) {
    const t = useT();
    const { data, setData, errors } = form;
    const pickup = data.delivery_type === 'pickup';
    return (
        <div className={card}>
            <h2 className="mb-4 text-lg font-bold">{t('delivery_method', 'Delivery Method')}</h2>
            {pickupLocations.length > 0 && (
                <div className="mb-6 grid gap-4 sm:grid-cols-2">
                    {[['shipping', Truck, t('delivery', 'Delivery'), t('ship_to_address', 'Ship to your address')],
                        ['pickup', Store, t('store_pickup', 'Store Pickup'), t('pick_up_from_store', 'Pick up from store')]].map(([value, Icon, title, text]) => (
                        <Choice key={value} name="delivery_type" value={value} checked={data.delivery_type === value} onChange={() => setData('delivery_type', value)} className="border-2">
                            <span className="flex items-center gap-2 font-medium"><Icon size={16} className="text-primary-500" />{title}</span>
                            <span className="text-sm text-gray-500">{text}</span>
                        </Choice>
                    ))}
                </div>
            )}

            {!pickup && (loading ? (
                <p className="flex items-center justify-center gap-2 py-4 text-gray-500"><Loader2 size={16} className="animate-spin" />{t('loading_shipping', 'Loading shipping options...')}</p>
            ) : methods.length > 0 ? (
                <div className="space-y-3">
                    {methods.map((m) => (
                        <Choice key={m.id} name="shipping_method" value={m.id} checked={String(data.shipping_method) === String(m.id)} onChange={() => setData('shipping_method', m.id)}>
                            <span className="flex items-center justify-between gap-2"><span className="font-medium">{m.name}</span><span className="font-semibold text-primary-600">{m.cost}</span></span>
                            {m.description && <span className="block text-sm text-gray-500">{m.description}</span>}
                            {m.deliveryTime && <span className="mt-1 flex items-center gap-1 text-xs text-gray-400"><Clock size={12} />{m.deliveryTime}</span>}
                        </Choice>
                    ))}
                </div>
            ) : (
                <p className="flex items-center gap-2 rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-yellow-800"><TriangleAlert size={16} />{t('no_shipping_methods', 'No shipping methods available for your location. Please contact support.')}</p>
            ))}

            {pickup && (
                <div className="space-y-3">
                    <p className="mb-3 text-sm text-gray-600">{t('select_pickup', 'Select a store location to pick up your order.')} <span className="font-medium text-green-600">{t('free_pickup', 'Free pickup!')}</span></p>
                    {pickupLocations.map((l) => (
                        <Choice key={l.id} name="pickup_location_id" value={l.id} checked={String(data.pickup_location_id) === String(l.id)} onChange={() => setData('pickup_location_id', l.id)}>
                            <span className="flex items-center justify-between gap-2"><span className="font-medium">{l.name}</span><span className="font-semibold text-green-600">{t('free', 'FREE')}</span></span>
                            <span className="flex items-center gap-1 text-sm text-gray-500"><MapPin size={12} />{l.address}</span>
                            {l.phone && <span className="mt-1 flex items-center gap-1 text-xs text-gray-400"><Phone size={12} />{l.phone}</span>}
                            {l.instructions && <span className="mt-1 flex items-center gap-1 text-xs text-blue-600"><Info size={12} />{l.instructions}</span>}
                        </Choice>
                    ))}
                    {errors.pickup_location_id && <p className="text-sm text-red-500">{errors.pickup_location_id}</p>}
                </div>
            )}
        </div>
    );
}

const PAY_ICON = { cod: Banknote, stripe: CreditCard, bank_transfer: Building2, check: Mail, paypal: Wallet, paytm: Wallet };

/** Loads Stripe.js from js.stripe.com once (Stripe requires it to be loaded from there). */
let stripeScript;
function loadStripe() {
    if (window.Stripe) return Promise.resolve(window.Stripe);
    stripeScript ??= new Promise((resolve, reject) => {
        const s = document.createElement('script');
        s.src = 'https://js.stripe.com/v3/';
        s.onload = () => resolve(window.Stripe);
        s.onerror = () => { stripeScript = undefined; reject(new Error('Could not load the card form. Please check your connection.')); };
        document.head.appendChild(s);
    });
    return stripeScript;
}

/** Stripe card field. Exposes createPaymentMethod() through stripeRef for the submit handler. */
function StripeCard({ publicKey, stripeRef }) {
    const t = useT();
    const el = useRef(null);
    const [error, setError] = useState('');

    useEffect(() => {
        let cardElement;
        let cancelled = false;
        loadStripe().then((Stripe) => {
            if (cancelled || !el.current) return;
            const stripe = Stripe(publicKey);
            cardElement = stripe.elements().create('card', {
                style: { base: { fontSize: '16px', color: '#374151', '::placeholder': { color: '#9CA3AF' } }, invalid: { color: '#EF4444' } },
            });
            cardElement.mount(el.current);
            cardElement.on('change', (e) => setError(e.error ? e.error.message : ''));
            stripeRef.current = async () => {
                const { paymentMethod, error: err } = await stripe.createPaymentMethod({ type: 'card', card: cardElement });
                if (err) setError(err.message);
                return paymentMethod?.id ?? null;
            };
        }).catch((e) => setError(e.message));
        return () => { cancelled = true; stripeRef.current = null; cardElement?.destroy(); };
    }, [publicKey, stripeRef]);

    return (
        <div className="mt-2 rounded-lg bg-gray-50 p-4">
            <h3 className="mb-3 font-medium">{t('card_details', 'Card Details')}</h3>
            <div ref={el} className="rounded-lg border bg-white p-3" />
            {error && <p role="alert" className="mt-2 text-sm text-red-500">{error}</p>}
        </div>
    );
}

export function Payment({ form, payments, stripeRef }) {
    const t = useT();
    const { data, setData, errors } = form;
    if (!payments.length) {
        return (
            <div className={card}>
                <h2 className="mb-4 text-lg font-bold">{t('payment_method', 'Payment Method')}</h2>
                <p className="flex items-center gap-2 rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-yellow-800"><TriangleAlert size={16} />{t('no_payment_methods', 'No payment methods available. Please contact support.')}</p>
            </div>
        );
    }
    const selected = payments.find((p) => p.key === data.payment_method);
    return (
        <div className={card}>
            <h2 className="mb-4 text-lg font-bold">{t('payment_method', 'Payment Method')}</h2>
            <div className="space-y-3">
                {payments.map((p) => {
                    const Icon = PAY_ICON[p.key] ?? CreditCard;
                    return (
                        <Choice key={p.key} name="payment_method" value={p.key} checked={data.payment_method === p.key} onChange={() => setData('payment_method', p.key)}>
                            <span className="flex flex-wrap items-center gap-2 font-medium">
                                <Icon size={16} className="text-gray-500" />{p.name}
                                {p.fee && <span className="rounded bg-yellow-100 px-2 py-0.5 text-xs text-yellow-700">+{p.fee} {t('fee', 'fee')}</span>}
                            </span>
                            <span className="block text-sm text-gray-500">{p.description}</span>
                        </Choice>
                    );
                })}
                {errors.payment_method && <p className="text-sm text-red-500">{errors.payment_method}</p>}

                {selected?.stripeKey && <StripeCard publicKey={selected.stripeKey} stripeRef={stripeRef} />}
                {selected?.details && (
                    <div className="mt-2 rounded-lg bg-gray-50 p-4 text-sm">
                        <h3 className="mb-2 font-medium">{selected.name}</h3>
                        {Object.entries(selected.details).map(([k, v]) => <p key={k} className="whitespace-pre-line"><strong>{k}:</strong> {v}</p>)}
                        {selected.instructions && <p className="mt-2 text-gray-600">{selected.instructions}</p>}
                    </div>
                )}
                {selected?.redirect && (
                    <p className="mt-2 flex items-center gap-2 rounded-lg bg-gray-50 p-4 text-sm text-gray-600">
                        <ExternalLink size={14} />{t('redirect_to_gateway', 'You will be redirected to')} {selected.redirect} {t('to_complete_payment', 'to complete your payment securely.')}
                    </p>
                )}
            </div>
        </div>
    );
}

export function Notes({ form }) {
    const t = useT();
    return (
        <div className={card}>
            <h2 className="mb-4 text-lg font-bold">{t('order_notes', 'Order Notes (Optional)')}</h2>
            <textarea name="notes" rows={3} value={form.data.notes} onChange={(e) => form.setData('notes', e.target.value)}
                placeholder={t('order_notes_placeholder', 'Special instructions for your order...')} aria-label={t('order_notes', 'Order Notes')} className={input} />
        </div>
    );
}

export function Summary({ items, totals, shipping, total, disabled, processing }) {
    const t = useT();
    return (
        <div className={cx(card, 'sticky top-24')}>
            <h2 className="mb-4 text-lg font-bold">{t('order_summary', 'Order Summary')}</h2>
            <ul className="mb-4 divide-y">
                {items.map((item) => (
                    <li key={item.id} className="flex gap-3 py-3">
                        <div className="h-16 w-16 shrink-0 overflow-hidden rounded-lg bg-gray-100">
                            {item.image && <img src={item.image} alt="" className="h-full w-full object-cover" />}
                        </div>
                        <div className="min-w-0 flex-1">
                            <p className="text-sm font-medium">{item.name}</p>
                            {item.detail && <p className="text-xs text-gray-500">{item.detail}</p>}
                            <p className="text-sm text-gray-500">{t('qty', 'Qty')}: {item.quantity}</p>
                        </div>
                        <p className="font-medium">{item.subtotal}</p>
                    </li>
                ))}
            </ul>
            <dl className="space-y-2 text-sm">
                <div className="flex justify-between"><dt>{t('subtotal', 'Subtotal')}</dt><dd>{totals.subtotal}</dd></div>
                {totals.discount && <div className="flex justify-between text-green-600"><dt>{t('discount', 'Discount')}</dt><dd>-{totals.discount}</dd></div>}
                <div className="flex justify-between"><dt>{t('shipping', 'Shipping')}</dt><dd>{shipping}</dd></div>
                <div className="flex justify-between"><dt>{t('tax', 'Tax')}</dt><dd>{totals.tax}</dd></div>
                <div className="flex justify-between border-t pt-2 text-lg font-bold"><dt>{t('total', 'Total')}</dt><dd>{total}</dd></div>
            </dl>
            <button type="submit" disabled={disabled || processing}
                className="sf-btn mt-6 flex w-full items-center justify-center gap-2 rounded-lg bg-primary-600 py-3 font-semibold text-white transition hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-60">
                {processing && <Loader2 size={18} className="animate-spin" />}
                {processing ? t('processing', 'Processing...') : t('place_order', 'Place Order')}
            </button>
        </div>
    );
}
