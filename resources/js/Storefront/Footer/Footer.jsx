import { useState } from 'react';
import { Globe, Mail, MapPin, MessageCircle, Phone, Pin, ShoppingBag } from 'lucide-react';
import { postJson } from '../lib/http';
import SfLink from '../lib/SfLink';
import { cx, safeUrl, useShop, useStore, useT } from '../lib/store';

/* Brand marks (lucide no longer ships them). 24x24 single-path icons. */
const BRAND_PATHS = {
    facebook: 'M14 13.5h2.5l1-4H14v-2c0-1.03 0-2 2-2h1.5V2.14A28 28 0 0 0 14.6 2C11.9 2 10 3.66 10 6.7v2.8H7v4h3V22h4z',
    twitter: 'M17.75 3h3.07l-6.7 7.66L22 21h-6.17l-4.83-6.32L5.47 21H2.4l7.17-8.2L2 3h6.33l4.36 5.77zm-1.08 16.17h1.7L7.4 4.73H5.58z',
    instagram: 'M12 2.2c3.2 0 3.58 0 4.85.07 3.25.15 4.77 1.69 4.92 4.92.06 1.27.07 1.65.07 4.85s0 3.58-.07 4.85c-.15 3.23-1.66 4.77-4.92 4.92-1.27.06-1.65.07-4.85.07s-3.58 0-4.85-.07c-3.26-.15-4.77-1.7-4.92-4.92C2.17 15.58 2.2 15.2 2.2 12s0-3.58.07-4.85C2.42 3.92 3.94 2.38 7.15 2.27 8.42 2.21 8.8 2.2 12 2.2M12 0C8.74 0 8.33 0 7.05.07 2.7.27.27 2.69.07 7.05 0 8.33 0 8.74 0 12s0 3.67.07 4.95c.2 4.36 2.62 6.78 6.98 6.98C8.33 24 8.74 24 12 24s3.67 0 4.95-.07c4.35-.2 6.78-2.62 6.98-6.98C24 15.67 24 15.26 24 12s0-3.67-.07-4.95C23.73 2.7 21.31.27 16.95.07 15.67 0 15.26 0 12 0m0 5.84a6.16 6.16 0 1 0 0 12.32 6.16 6.16 0 0 0 0-12.32M12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8m6.4-11.85a1.44 1.44 0 1 0 0 2.88 1.44 1.44 0 0 0 0-2.88',
    youtube: 'M23.5 6.2a3 3 0 0 0-2.1-2.1C19.5 3.6 12 3.6 12 3.6s-7.5 0-9.4.5A3 3 0 0 0 .5 6.2 31 31 0 0 0 0 12a31 31 0 0 0 .5 5.8 3 3 0 0 0 2.1 2.1c1.9.5 9.4.5 9.4.5s7.5 0 9.4-.5a3 3 0 0 0 2.1-2.1A31 31 0 0 0 24 12a31 31 0 0 0-.5-5.8M9.6 15.6V8.4l6.3 3.6z',
    linkedin: 'M20.45 20.45h-3.56v-5.57c0-1.33-.02-3.04-1.85-3.04-1.85 0-2.14 1.45-2.14 2.94v5.67H9.35V9h3.41v1.56h.05a3.74 3.74 0 0 1 3.37-1.85c3.6 0 4.27 2.37 4.27 5.46zM5.34 7.43a2.06 2.06 0 1 1 0-4.13 2.06 2.06 0 0 1 0 4.13M7.12 20.45H3.56V9h3.56zM22.22 0H1.77C.79 0 0 .77 0 1.73v20.54C0 23.23.79 24 1.77 24h20.45c.98 0 1.78-.77 1.78-1.73V1.73C24 .77 23.2 0 22.22 0',
    tiktok: 'M12.53.02C13.84 0 15.14.01 16.44 0c.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03a10.7 10.7 0 0 1-4.2-.97c-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75a7.6 7.6 0 0 1-1.35 3.94 7.5 7.5 0 0 1-5.95 3.21 7.3 7.3 0 0 1-4.1-1.03 7.6 7.6 0 0 1-3.68-5.78c-.02-.5-.03-1-.01-1.49a7.5 7.5 0 0 1 8.73-6.68c.02 1.48-.04 2.96-.04 4.44a3.4 3.4 0 0 0-4.35 2.1c-.21.51-.15 1.07-.14 1.61a3.4 3.4 0 0 0 3.5 2.88 3.4 3.4 0 0 0 2.8-1.58c.18-.32.39-.65.4-1.03.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07',
};

export function SocialIcon({ network, size = 16 }) {
    const d = BRAND_PATHS[network];
    if (!d) {
        // share targets without a brand mark here get a generic icon
        const Icon = { whatsapp: MessageCircle, pinterest: Pin }[network] ?? Globe;
        return <Icon size={size} />;
    }
    return <svg width={size} height={size} viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d={d} /></svg>;
}

function NewsletterForm({ dark = true }) {
    const t = useT();
    const { toast } = useShop();
    const [email, setEmail] = useState('');
    const [busy, setBusy] = useState(false);
    const submit = async (e) => {
        e.preventDefault();
        setBusy(true);
        try {
            const res = await postJson('/newsletter/subscribe', { email });
            toast('success', res.message || t('newsletter_success', 'Thank you for subscribing!'));
            setEmail('');
        } catch (err) {
            toast('error', err.message);
        } finally {
            setBusy(false);
        }
    };
    return (
        <form onSubmit={submit} className="flex w-full gap-2 md:w-auto">
            <input type="email" required value={email} onChange={(e) => setEmail(e.target.value)} aria-label={t('enter_email', 'Your email address')}
                placeholder={t('enter_email', 'Your email address')}
                className={cx('flex-1 rounded-lg px-4 py-2.5 focus:outline-none md:w-80', dark ? 'border border-white/20 bg-white/10 text-white placeholder-white/50 focus:border-white/60' : 'border border-gray-300')} />
            <button type="submit" disabled={busy} className="sf-btn rounded-lg bg-primary-600 px-5 py-2.5 font-semibold text-white hover:bg-primary-700 disabled:opacity-60">{t('subscribe', 'Subscribe')}</button>
        </form>
    );
}

function FooterLink({ link }) {
    return <li><SfLink href={safeUrl(link.url)} className="sf-footer-link">{link.label}</SfLink></li>;
}

/**
 * Shared storefront footer (same data as the Blade footer: App\View\StoreFooter). Layouts: default (4 columns),
 * minimal (centred) and expanded (newsletter band + 4 columns), from Settings > Appearance > Footer.
 */
export default function Footer() {
    const { footer: ft } = useStore();
    const t = useT();
    const css = `.sf-footer a{transition:color .15s}.sf-footer a:hover{color:${ft.hover} !important}.sf-footer .sf-footer-heading{color:#fff}`;
    const socials = ft.showSocial && ft.social.length > 0 && (
        <div className={cx('flex gap-4', ft.style === 'minimal' ? 'mb-5 justify-center' : 'mt-4')}>
            {ft.social.map((s) => (
                <a key={s.network} href={safeUrl(s.url)} target="_blank" rel="noopener noreferrer" aria-label={s.network}><SocialIcon network={s.network} /></a>
            ))}
        </div>
    );
    const brand = (
        <div className={cx('flex items-center gap-2', ft.style === 'minimal' ? 'mb-4 justify-center' : 'mb-4')}>
            {ft.logo ? <img src={ft.logo} alt={ft.storeName} className="h-10 w-auto" />
                : ft.style !== 'minimal' && <span className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-600"><ShoppingBag size={20} className="text-white" /></span>}
            {!ft.hideName && <span className="sf-footer-heading text-xl font-bold">{ft.storeName}</span>}
        </div>
    );

    return (
        <footer className="sf-footer store-footer mt-auto" style={{ backgroundColor: ft.bg, color: ft.text }}>
            <style>{css}</style>
            {ft.style === 'expanded' && ft.showNewsletter && (
                <div className="border-b border-white/10">
                    <div className="sf-container flex flex-col gap-6 py-10 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h4 className="sf-footer-heading text-xl font-bold">{t('newsletter', 'Newsletter')}</h4>
                            <p className="mt-1 text-sm">{t('subscribe_newsletter_desc', 'Get the latest offers and news in your inbox.')}</p>
                        </div>
                        <NewsletterForm />
                    </div>
                </div>
            )}

            {ft.style === 'minimal' ? (
                <div className="sf-container py-10 text-center">
                    {brand}
                    {ft.showTagline && <p className="mx-auto mb-5 max-w-xl text-sm">{ft.tagline}</p>}
                    <nav className="mb-5 flex flex-wrap justify-center gap-x-6 gap-y-2 text-sm" aria-label="Footer">
                        {[...ft.quick, ...ft.service].map((l) => <SfLink key={`${l.url}-${l.label}`} href={safeUrl(l.url)}>{l.label}</SfLink>)}
                    </nav>
                    {socials}
                    <p className="text-xs">{ft.copyright}</p>
                </div>
            ) : (
                <div className="sf-container py-12">
                    <div className="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-4">
                        <div>
                            {brand}
                            {ft.showTagline && <p className="text-sm">{ft.tagline}</p>}
                            {socials}
                        </div>
                        <div>
                            <h4 className="sf-footer-heading mb-4 text-lg font-semibold">{t('quick_links', 'Quick Links')}</h4>
                            <ul className="space-y-2">{ft.quick.map((l) => <FooterLink key={`${l.url}-${l.label}`} link={l} />)}</ul>
                        </div>
                        <div>
                            <h4 className="sf-footer-heading mb-4 text-lg font-semibold">{t('customer_service', 'Customer Service')}</h4>
                            <ul className="space-y-2">{ft.service.map((l) => <FooterLink key={`${l.url}-${l.label}`} link={l} />)}</ul>
                        </div>
                        <div>
                            <h4 className="sf-footer-heading mb-4 text-lg font-semibold">{t('contact_us', 'Contact Us')}</h4>
                            <ul className="space-y-3">
                                {ft.address && <li className="flex items-start gap-3"><MapPin size={16} className="mt-1 shrink-0 text-primary-500" /><span>{ft.address}</span></li>}
                                {ft.phone && <li className="flex items-center gap-3"><Phone size={16} className="text-primary-500" /><span>{ft.phone}</span></li>}
                                {ft.email && <li className="flex items-center gap-3"><Mail size={16} className="text-primary-500" /><span>{ft.email}</span></li>}
                            </ul>
                        </div>
                    </div>
                    <hr className="my-8 border-white/10" />
                    <div className="flex flex-col items-center justify-between gap-4 md:flex-row">
                        <p className="text-sm">{ft.copyright}</p>
                        <div className="flex items-center gap-4">
                            {ft.payments.map((p) => <img key={p.name} src={p.src} alt={p.name} className="h-6" loading="lazy" />)}
                        </div>
                    </div>
                </div>
            )}
        </footer>
    );
}
