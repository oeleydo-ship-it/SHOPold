import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { ChevronDown, Loader2, RefreshCw, Server, Globe2, TriangleAlert, CheckCircle2 } from 'lucide-react';
import SettingsLayout from '../../../Layouts/SettingsLayout';
import { Button, Field, Input, Select, Textarea } from '../../../Components/ui';
import { Toggle } from '../../../Components/crud';
import { SaveButton, SettingsCard } from '../../../Components/settingsUi';
import { useSettings } from '../../../lib/settings';
import { jsonRequest } from '../../../lib/http';

const BOOLEANS = [
    'features.reviews_enabled', 'features.wishlist_enabled', 'features.compare_enabled',
    'features.guest_checkout', 'features.guest_checkout_otp', 'features.guest_checkout_phone_otp', 'features.guest_create_account_option',
    'features.newsletter_enabled', 'features.localization_enabled', 'features.multi_currency', 'features.live_currency_converter',
    'features.driver_portal', 'features.blog_enabled', 'features.blog_comments',
    'features.multi_vendor', 'features.vendor_products_approval', 'features.vendor_shipping',
    'features.loyalty_points', 'features.live_chat_enabled', 'features.ai_chat_enabled', 'features.ai_ssl_verify',
    'features.digital_products', 'features.digital_require_login',
];

/** A feature row with a switch; if it has children, a chevron expands the extra options. */
function Feature({ title, description, checked, onChange, disabled, children, startOpen = false, needsEnabled = false }) {
    const [open, setOpen] = useState(startOpen);
    const hasChildren = !!children;
    const showChildren = hasChildren && open && (!needsEnabled || checked);
    return (
        <div className="rounded-lg border border-line">
            <div className="flex items-center justify-between gap-4 p-4">
                <div>
                    <h3 className="font-semibold">{title}</h3>
                    <p className="text-sm text-muted">{description}</p>
                </div>
                <div className="flex shrink-0 items-center gap-3">
                    {hasChildren && (!needsEnabled || checked) && (
                        <button type="button" onClick={() => setOpen((o) => !o)} aria-expanded={open} aria-label={`${open ? 'Hide' : 'Show'} ${title} options`} className="text-muted hover:text-ink">
                            <ChevronDown size={18} className={`transition-transform ${open ? 'rotate-180' : ''}`} />
                        </button>
                    )}
                    <Toggle
                        checked={checked}
                        onChange={(v) => { if (disabled) return; onChange(v); if (v && hasChildren && needsEnabled) setOpen(true); }}
                        label={<span className="sr-only">{title}</span>}
                    />
                </div>
            </div>
            {showChildren && <div className="space-y-4 border-t border-line bg-surface-2/50 p-4">{children}</div>}
        </div>
    );
}

function Check({ label, hint, checked, onChange }) {
    return (
        <label className="flex items-start gap-2 text-sm">
            <input type="checkbox" checked={!!checked} onChange={(e) => onChange(e.target.checked)} className="mt-0.5 h-4 w-4 accent-[var(--primary)]" />
            <span>{label}{hint && <span className="block text-xs text-muted">{hint}</span>}</span>
        </label>
    );
}

function ExchangeRates({ currency }) {
    const [state, setState] = useState({ busy: false, ok: true, text: '' });
    const rates = Object.entries(currency.rates ?? {});
    const apis = [
        ['open.er-api.com', `https://open.er-api.com/v6/latest/${currency.base}`],
        ['frankfurter.app', `https://api.frankfurter.app/latest?from=${currency.base}`],
    ];

    // Fetch from the browser (works when the server's outgoing requests are blocked), then save to the server.
    const browserFetch = async () => {
        setState({ busy: true, ok: true, text: '' });
        let found = null;
        let used = '';
        for (const [name, url] of apis) {
            try {
                setState({ busy: true, ok: true, text: `Trying ${name}…` });
                const res = await fetch(url);
                if (res.ok) { found = (await res.json()).rates; used = name; break; }
            } catch { /* try the next provider */ }
        }
        if (!found) return setState({ busy: false, ok: false, text: 'Failed to fetch rates from all APIs. Please check your internet connection.' });

        const filtered = {};
        currency.enabled.forEach((c) => {
            const code = c.toUpperCase();
            if (code !== currency.base && found[code]) filtered[code] = found[code];
        });
        try {
            setState({ busy: true, ok: true, text: 'Saving rates to server…' });
            const saved = await jsonRequest('/admin/settings/save-manual-rates', { method: 'POST', body: { rates: filtered } });
            if (!saved.success) throw new Error(saved.message || 'Unknown error');
            setState({ busy: false, ok: true, text: `Rates updated via ${used}.` });
            router.reload({ only: ['currency'], preserveScroll: true });
        } catch (e) {
            setState({ busy: false, ok: false, text: `Failed to save rates: ${e.message}` });
        }
    };

    return (
        <div className="rounded-lg border border-line p-4">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 className="font-semibold">Exchange Rates</h3>
                    <p className="text-sm text-muted">{currency.updatedAt ? `Last updated: ${currency.updatedAt}` : 'Not yet fetched'}</p>
                </div>
                <div className="flex gap-2">
                    <Button tone="neutral" onClick={browserFetch} disabled={state.busy}>
                        {state.busy ? <Loader2 size={14} className="animate-spin" /> : <Globe2 size={14} />}{state.busy ? 'Fetching…' : 'Browser Fetch'}
                    </Button>
                    <Button tone="neutral" onClick={() => router.post('/admin/settings/refresh-exchange-rates', {}, { preserveScroll: true })}><Server size={14} />Server Fetch</Button>
                </div>
            </div>
            {state.text && <p role="status" className={`mt-3 text-sm ${state.ok ? 'text-success' : 'text-danger'}`}>{state.text}</p>}
            <div className="mt-3">
                {rates.length > 0 ? (
                    <div className="flex flex-wrap items-center gap-2 text-xs">
                        <span className="text-muted">Current rates (Base: {currency.ratesBase}):</span>
                        {rates.map(([code, rate]) => <span key={code} className="rounded bg-surface-2 px-2 py-1 font-mono">{code}: {Number(rate).toFixed(4)}</span>)}
                    </div>
                ) : (
                    <p className="flex items-center gap-2 text-sm text-warning"><TriangleAlert size={14} />No exchange rates found. Use "Browser Fetch" to get live rates.</p>
                )}
            </div>
            <p className="mt-3 text-xs text-muted"><strong>Browser Fetch</strong> uses your browser (bypasses a server firewall). <strong>Server Fetch</strong> fetches from the server (some hosts block it).</p>
        </div>
    );
}

export default function Features({ values, ai, currency }) {
    const { data, set, submit, processing } = useSettings('features', values, BOOLEANS);
    const f = (k) => ({ value: data[k] ?? '', onChange: (e) => set(k, e.target.value) });
    const sw = (k) => ({ checked: data[k], onChange: (v) => set(k, v) });
    const multiCurrency = data['features.multi_currency'];
    const provider = data['features.chat_provider'];

    return (
        <SettingsLayout>
            <Head title="Feature Settings" />
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    // The live converter is locked off without multi-currency (the old form did not submit it either).
                    submit((d) => ({ ...d, 'features.live_currency_converter': multiCurrency ? d['features.live_currency_converter'] : false }));
                }}
                className="space-y-5"
            >
                <SettingsCard title="Feature Toggles" description="Enable or disable features in your store.">
                    <div className="space-y-3">
                        <Feature title="Product Reviews" description="Allow customers to leave reviews on products" {...sw('features.reviews_enabled')} />
                        <Feature title="Wishlist" description="Allow customers to save products to wishlist" {...sw('features.wishlist_enabled')} />
                        <Feature title="Product Compare" description="Allow customers to compare products" {...sw('features.compare_enabled')} />

                        <Feature title="Guest Checkout" description="Allow customers to checkout without registering" needsEnabled startOpen={data['features.guest_checkout']} {...sw('features.guest_checkout')}>
                            <p className="rounded-lg border border-warning/40 bg-warning/10 p-3 text-sm text-muted"><strong className="text-warning">Guest Checkout Security</strong><br />Guest checkout requires email verification via OTP to prevent fraud and ensure order delivery notifications.</p>
                            <div className="grid gap-4 md:grid-cols-2">
                                <Check label="Require Email OTP" hint="Send verification code to guest email before checkout" {...sw('features.guest_checkout_otp')} />
                                <Check label="Require Phone OTP" hint="Send SMS verification (requires SMS gateway)" {...sw('features.guest_checkout_phone_otp')} />
                                <Field label="OTP Expiry (minutes)"><Input type="number" min="1" max="60" {...f('features.guest_otp_expiry')} /></Field>
                                <Field label="OTP Length">
                                    <Select value={String(data['features.guest_otp_length'])} onChange={(e) => set('features.guest_otp_length', e.target.value)}>
                                        <option value="4">4 digits</option>
                                        <option value="6">6 digits</option>
                                    </Select>
                                </Field>
                            </div>
                            <Check label="Offer account creation after guest checkout" hint="Show option to create account on order confirmation page" {...sw('features.guest_create_account_option')} />
                        </Feature>

                        <Feature title="Newsletter" description="Show newsletter subscription form" {...sw('features.newsletter_enabled')} />
                        <Feature title="Localization & Multi-Language" description="Enable multi-language support and translation features" {...sw('features.localization_enabled')} />
                        <Feature title="Multi-Currency" description="Allow customers to switch currencies and auto-convert prices" {...sw('features.multi_currency')} />
                        <Feature
                            title="Live Currency Converter" description="Automatically fetch and update exchange rates for all enabled currencies"
                            disabled={!multiCurrency} checked={multiCurrency && data['features.live_currency_converter']} onChange={(v) => set('features.live_currency_converter', v)}
                        />
                        {currency.enabled !== undefined && values['features.multi_currency'] && <ExchangeRates currency={currency} />}

                        <Feature title="Driver/Courier Portal" description="Enable dedicated portal for delivery drivers to manage shipments" {...sw('features.driver_portal')} />

                        <Feature title="Blog" description="Enable blog/articles section" {...sw('features.blog_enabled')}>
                            <div className="grid gap-4 md:grid-cols-2">
                                <Field label="Posts Per Page"><Input type="number" min="1" {...f('features.blog_posts_per_page')} /></Field>
                                <div className="flex items-end pb-2"><Check label="Enable comments on blog posts" {...sw('features.blog_comments')} /></div>
                            </div>
                        </Feature>

                        <Feature title="Multi-Vendor Marketplace" description="Enable multi-vendor marketplace features" {...sw('features.multi_vendor')}>
                            <div className="grid gap-4 md:grid-cols-2">
                                <Field label="Commission Rate (%)" hint="Platform commission on vendor sales"><Input type="number" min="0" max="100" step="0.1" {...f('features.vendor_commission')} /></Field>
                                <Field label="Vendor Registration">
                                    <Select {...f('features.vendor_registration')}>
                                        <option value="open">Open (Auto-approve)</option>
                                        <option value="approval">Requires Approval</option>
                                        <option value="closed">Closed (Invite Only)</option>
                                    </Select>
                                </Field>
                                <Check label="Require product approval" {...sw('features.vendor_products_approval')} />
                                <Check label="Vendors manage own shipping" {...sw('features.vendor_shipping')} />
                            </div>
                        </Feature>

                        <Feature title="Loyalty Points" description="Enable customer loyalty/reward points system" {...sw('features.loyalty_points')}>
                            <div className="grid gap-4 md:grid-cols-2">
                                <Field label="Points per $1 spent"><Input type="number" min="0" step="0.1" {...f('features.points_per_dollar')} /></Field>
                                <Field label="Points value ($ per point)" hint="e.g. 0.01 = 100 points = $1"><Input type="number" min="0" step="0.001" {...f('features.point_value')} /></Field>
                                <Field label="Minimum points to redeem"><Input type="number" min="0" {...f('features.min_points_redeem')} /></Field>
                                <Field label="Points expiry (days)" hint="0 = Never expire"><Input type="number" min="0" {...f('features.points_expiry')} /></Field>
                                <Field label="Signup bonus points"><Input type="number" min="0" {...f('features.signup_bonus_points')} /></Field>
                                <Field label="Referral bonus points"><Input type="number" min="0" {...f('features.referral_bonus_points')} /></Field>
                            </div>
                        </Feature>

                        <Feature title="Live Chat" description="Enable live chat widget for customer support" {...sw('features.live_chat_enabled')}>
                            <fieldset>
                                <legend className="mb-2 text-sm font-medium">Chat Provider</legend>
                                <div className="grid grid-cols-2 gap-3 md:grid-cols-4" role="radiogroup">
                                    {[['tawk', 'Tawk.to', 'Free'], ['crisp', 'Crisp', 'Free tier'], ['intercom', 'Intercom', 'Premium'], ['custom', 'Custom', 'Embed code']].map(([id, label, sub]) => (
                                        <button key={id} type="button" role="radio" aria-checked={provider === id} onClick={() => set('features.chat_provider', id)}
                                            className={`rounded-lg border-2 p-3 text-center ${provider === id ? 'border-primary bg-primary-soft/40' : 'border-line hover:bg-surface'}`}>
                                            <p className="text-sm font-medium">{label}</p><p className="text-xs text-muted">{sub}</p>
                                        </button>
                                    ))}
                                </div>
                            </fieldset>
                            {provider === 'tawk' && (
                                <div className="grid gap-4 md:grid-cols-2">
                                    <Field label="Tawk.to Property ID" hint="Tawk.to Dashboard → Administration → Chat Widget"><Input placeholder="e.g. 1234567890abcdef" {...f('features.tawk_property_id')} /></Field>
                                    <Field label="Widget ID"><Input placeholder="e.g. default" {...f('features.tawk_widget_id')} /></Field>
                                </div>
                            )}
                            {provider === 'crisp' && <Field label="Crisp Website ID" hint="Crisp Dashboard → Settings → Website Settings"><Input placeholder="e.g. 12345678-1234-1234-1234-123456789012" {...f('features.crisp_website_id')} /></Field>}
                            {provider === 'intercom' && <Field label="Intercom App ID"><Input placeholder="e.g. abc123de" {...f('features.intercom_app_id')} /></Field>}
                            {provider === 'custom' && (
                                <Field label="Custom Chat Widget Code" hint="Paste the full JavaScript embed code from your chat provider">
                                    <Textarea rows={6} placeholder="Paste your chat widget embed code here…" className="font-mono text-xs" {...f('features.custom_chat_code')} />
                                </Field>
                            )}
                        </Feature>

                        <Feature title="AI-Powered Chat Assistant" description="Enable AI chatbot for automated customer support" needsEnabled startOpen={data['features.ai_chat_enabled']} {...sw('features.ai_chat_enabled')}>
                            <p className={`flex items-start gap-2 rounded-lg border p-3 text-sm ${ai.configured ? 'border-success/40 bg-success/10' : 'border-warning/40 bg-warning/10'}`}>
                                {ai.configured ? <CheckCircle2 size={16} className="mt-0.5 shrink-0 text-success" /> : <TriangleAlert size={16} className="mt-0.5 shrink-0 text-warning" />}
                                <span>
                                    {ai.configured
                                        ? <>AI configured — using <strong>{ai.provider}</strong> from <a href="/admin/settings/ai" className="text-primary underline">AI Settings</a>.</>
                                        : <>AI not configured. Add your AI API key in <a href="/admin/settings/ai" className="text-primary underline">AI Settings</a> to enable the chat assistant.</>}
                                </span>
                            </p>
                            <Check label="Verify SSL Certificates" hint="Disable only if your server cannot validate SSL certificates (common on some Windows local setups)" {...sw('features.ai_ssl_verify')} />
                        </Feature>

                        <Feature title="Digital Products" description="Enable selling downloadable digital products (ebooks, software, music, etc.)" needsEnabled startOpen={data['features.digital_products']} {...sw('features.digital_products')}>
                            <p className="rounded-lg border border-info/40 bg-info/10 p-3 text-sm text-muted">Sell downloadable files like PDFs, software, music, and videos. Files are securely delivered after purchase.</p>
                            <div className="grid gap-4 md:grid-cols-2">
                                <Field label="Default Download Limit" hint="Leave empty for unlimited downloads"><Input type="number" min="1" placeholder="Unlimited" {...f('features.digital_download_limit')} /></Field>
                                <Field label="Download Link Expiry (days)" hint="Leave empty for links that never expire"><Input type="number" min="1" placeholder="Never expires" {...f('features.digital_download_expiry_days')} /></Field>
                                <Field label="Max File Size (MB)"><Input type="number" min="1" {...f('features.digital_max_file_size')} /></Field>
                                <div className="flex items-end pb-2"><Check label="Require login to download" {...sw('features.digital_require_login')} /></div>
                            </div>
                            <Field label="Allowed File Types" hint="Comma-separated list of allowed file extensions"><Input {...f('features.digital_allowed_types')} /></Field>
                        </Feature>
                    </div>
                </SettingsCard>
                <SaveButton processing={processing} />
            </form>
        </SettingsLayout>
    );
}
