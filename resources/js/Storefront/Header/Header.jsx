import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { Headset, Mail, Menu, Phone, Search, Tag, User } from 'lucide-react';
import SfLink from '../lib/SfLink';
import { cx, safeUrl, useStore, useT } from '../lib/store';
import MobileMenu from './MobileMenu';
import {
    AccountMenu, CartLink, CategoriesMenu, CurrencyMenu, LanguageMenu, Logo, NavLinks, SearchForm, WishlistLink, useIsHome,
} from './parts';
import { SocialIcon } from '../Footer/Footer';

const HEIGHT = { compact: 'h-14', large: 'h-20', normal: 'h-16' };
const GAP = { tight: 'gap-4', wide: 'gap-12', normal: 'gap-8' };

/** Colours from Settings > Appearance > Header, applied through CSS variables so every style uses them. */
function HeaderStyles({ o, transparent }) {
    const text = transparent ? '#ffffff' : o.header_text_color || '#374151';
    const hover = transparent ? '#ffffff' : o.header_hover_color || '#2563eb';
    const css = `.sf-header .sf-header-text,.sf-header .sf-header-icon,.sf-header .sf-nav-link{color:${text}}`
        + `.sf-header .sf-header-icon:hover,.sf-header .sf-nav-link:hover,.sf-header .sf-nav-active{color:${hover}}`
        + (transparent ? '.sf-header .sf-nav-link{opacity:.9}.sf-header .sf-nav-link:hover{opacity:1}' : '');
    return <style>{css}</style>;
}

function TopBar({ o, hideSwitchers }) {
    const { store, social } = useStore();
    return (
        <div className="hidden text-sm text-white md:block" style={{ backgroundColor: o.header_topbar_bg || '#1f2937' }}>
            <div className="sf-container flex h-10 items-center justify-between gap-4">
                <div className="flex items-center gap-4">
                    {store.phone && <span className="flex items-center gap-2"><Phone size={14} />{store.phone}</span>}
                    {store.email && <span className="hidden items-center gap-2 sm:flex"><Mail size={14} />{store.email}</span>}
                </div>
                {o.header_promo_text && <div className="hidden flex-1 text-center font-medium text-yellow-400 md:block">{o.header_promo_text}</div>}
                <div className="flex items-center gap-3">
                    {!hideSwitchers && <LanguageMenu buttonClass="flex items-center gap-1 text-white/90 hover:text-white" />}
                    {!hideSwitchers && <CurrencyMenu buttonClass="flex items-center gap-1 text-white/90 hover:text-white" />}
                    {Object.entries(social).slice(0, 6).map(([network, url]) => (
                        <a key={network} href={safeUrl(url)} target="_blank" rel="noopener noreferrer" aria-label={network} className="hover:text-primary-400"><SocialIcon network={network} size={14} /></a>
                    ))}
                </div>
            </div>
        </div>
    );
}

function MobileHeader({ o, onMenu }) {
    const { theme, store, customer } = useStore();
    const t = useT();
    const promo = theme.mobile.showPromo && o.header_promo_text;
    return (
        <div className="md:hidden">
            {promo && (
                <div className="overflow-hidden py-2 text-xs font-medium text-white" style={{ backgroundColor: o.header_topbar_bg || '#7c3aed' }}>
                    <div className="inline-flex animate-marquee whitespace-nowrap hover:[animation-play-state:paused]">
                        {[0, 1].map((i) => <span key={i} className="inline-flex items-center gap-2 px-4"><Tag size={12} />{o.header_promo_text}</span>)}
                    </div>
                </div>
            )}
            <div style={{ backgroundColor: o.header_bg_color || '#ffffff' }}>
                <div className="relative flex h-16 items-center px-4">
                    <button type="button" onClick={onMenu} className="sf-header-icon p-2" aria-label={t('menu', 'Menu')}><Menu size={26} /></button>
                    <div className="absolute left-1/2 -translate-x-1/2"><Logo size="h-9" showName={!store.logo} /></div>
                    <div className="ms-auto flex items-center">
                        <CurrencyMenu short buttonClass="sf-header-icon flex items-center gap-1 p-2 text-sm" />
                        <a href={customer ? '/account' : '/login'} className="sf-header-icon p-2" aria-label={t('my_account', 'My Account')}><User size={20} /></a>
                        <WishlistLink />
                        <CartLink />
                    </div>
                </div>
                {theme.mobile.showSearch && (
                    <div className="px-4 pb-4">
                        <SearchForm buttonInside={false} placeholder={t('search_for', 'What are you looking for...')}
                            inputClass="h-12 w-full rounded-s-lg border border-gray-300 ps-4 pe-3 text-base focus:border-primary-500 focus:ring-2 focus:ring-primary-500 focus:outline-none"
                            buttonClass="flex h-12 w-12 shrink-0 items-center justify-center rounded-e-lg bg-primary-600 text-white hover:bg-primary-700" />
                    </div>
                )}
            </div>
        </div>
    );
}

/** Icons + switchers on the right of the default/minimal/centered desktop headers. */
function Actions({ withSwitchers = false, searchToggle, accountIcon = false, loginText = false }) {
    const { customer } = useStore();
    const t = useT();
    return (
        <div className="flex items-center gap-3">
            {withSwitchers && (
                <>
                    <LanguageMenu buttonClass="flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-100 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-primary-50 hover:text-primary-600" />
                    <CurrencyMenu buttonClass="flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-100 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-primary-50 hover:text-primary-600" />
                </>
            )}
            {searchToggle && <button type="button" onClick={searchToggle} className="sf-header-icon p-2" aria-label={t('search', 'Search')}><Search size={20} /></button>}
            <WishlistLink />
            <CartLink />
            {accountIcon
                ? (customer ? <a href="/account" className="sf-header-icon p-2" aria-label={t('my_account', 'My Account')}><User size={20} /></a>
                    : loginText ? <a href="/login" className="sf-header-text text-sm font-medium">{t('login', 'Login')}</a>
                        : <a href="/login" className="sf-header-icon p-2" aria-label={t('login', 'Login')}><User size={20} /></a>)
                : <AccountMenu />}
        </div>
    );
}

function ExpandSearch({ open, dark }) {
    if (!open) return null;
    return (
        <div className="pb-4">
            <SearchForm inputClass={cx('w-full rounded-lg border px-4 py-2 focus:ring-2 focus:outline-none', dark ? 'border-white/30 bg-white/10 text-white placeholder-white/60 focus:ring-white/50' : 'border-gray-300 focus:ring-primary-500')} buttonClass="hidden" />
        </div>
    );
}

export default function Header() {
    const { theme } = useStore();
    const t = useT();
    const o = theme.options;
    const style = o.header_style || 'default';
    const isHome = useIsHome();
    const transparent = style === 'transparent' && isHome;
    const [menuOpen, setMenuOpen] = useState(false);
    const [searchOpen, setSearchOpen] = useState(false);
    const height = HEIGHT[o.header_height] ?? HEIGHT.normal;
    const gap = GAP[o.header_spacing] ?? GAP.normal;

    // Close the menus when Inertia navigates.
    useEffect(() => router.on('navigate', () => { setMenuOpen(false); setSearchOpen(false); }), []);

    const showTopbar = o.show_topbar !== false && o.show_topbar !== '0' && !['mega', 'transparent', 'dark'].includes(style);

    return (
        <>
            {showTopbar && <TopBar o={o} hideSwitchers={style === 'default'} />}
            <header className={cx('sf-header z-50', transparent ? 'absolute inset-x-0 top-0 bg-transparent' : cx('shadow-sm', (o.sticky_header ?? true) && o.sticky_header !== '0' && 'sticky top-0'))}
                style={transparent ? undefined : { backgroundColor: o.header_bg_color || '#ffffff' }}>
                <HeaderStyles o={o} transparent={transparent} />
                <MobileHeader o={o} onMenu={() => setMenuOpen(true)} />

                {style === 'centered' && (
                    <div className="sf-container hidden md:block">
                        <div className="relative flex h-14 items-center justify-between border-b border-black/10">
                            <SearchForm className="max-w-xs" placeholder={t('search', 'Search') + '...'} inputClass="w-full rounded-full border border-gray-300 py-2 ps-4 pe-10 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                            <div className="absolute left-1/2 -translate-x-1/2"><Logo /></div>
                            <Actions accountIcon />
                        </div>
                        <NavLinks className={cx('hidden h-12 items-center justify-center lg:flex', gap)} />
                    </div>
                )}

                {style === 'minimal' && (
                    <div className="sf-container hidden md:block">
                        <div className={cx('flex items-center justify-between', height)}>
                            <Logo />
                            <NavLinks className={cx('ms-4 hidden items-center lg:flex', gap)} />
                            <Actions accountIcon loginText searchToggle={() => setSearchOpen((s) => !s)} />
                        </div>
                        <ExpandSearch open={searchOpen} />
                    </div>
                )}

                {style === 'mega' && <MegaHeader o={o} gap={gap} />}

                {transparent && (
                    <>
                        {o.show_topbar !== false && o.header_promo_text && (
                            <div className="text-xs text-white" style={{ backgroundColor: o.header_topbar_bg || '#1f2937' }}>
                                <div className="sf-container flex h-9 items-center justify-center font-medium text-yellow-400"><Tag size={12} className="me-2" />{o.header_promo_text}</div>
                            </div>
                        )}
                        <div className="sf-container hidden md:block">
                            <div className={cx('flex items-center justify-between', height)}>
                                <Logo light />
                                <NavLinks className={cx('hidden items-center lg:flex', gap)} />
                                <div className="flex items-center gap-4 text-white">
                                    <button type="button" onClick={() => setSearchOpen((s) => !s)} className="sf-header-icon p-2" aria-label={t('search', 'Search')}><Search size={20} /></button>
                                    <WishlistLink />
                                    <CartLink />
                                    <AccountMenu />
                                </div>
                            </div>
                            <ExpandSearch open={searchOpen} dark />
                        </div>
                    </>
                )}

                {style === 'dark' && <DarkHeader o={o} height={height} gap={gap} />}

                {(style === 'default' || (style === 'transparent' && !isHome) || !['centered', 'minimal', 'mega', 'transparent', 'dark'].includes(style)) && (
                    <div className="sf-container hidden md:block">
                        <div className={cx('flex items-center justify-between', height)}>
                            <div className="me-8"><Logo /></div>
                            <NavLinks className={cx('ms-4 hidden items-center lg:flex', gap)} />
                            <SearchForm className="mx-8 hidden max-w-md flex-1 md:flex" />
                            <Actions withSwitchers />
                        </div>
                    </div>
                )}
            </header>
            <MobileMenu open={menuOpen} onClose={() => setMenuOpen(false)} />
        </>
    );
}

function MegaHeader({ o, gap }) {
    const { store } = useStore();
    const t = useT();
    const catBg = o.header_categories_bg || '#2563eb';
    return (
        <>
            <div className="hidden text-xs text-white md:block" style={{ backgroundColor: o.header_topbar_bg || '#1f2937' }}>
                <div className="sf-container flex h-9 items-center justify-between gap-4">
                    <div className="flex items-center gap-4">
                        {store.phone && <span className="flex items-center gap-1.5"><Headset size={13} />{t('support_24_7_header', '24/7 Support')}</span>}
                        {store.email && <span className="hidden items-center gap-1.5 sm:flex"><Mail size={13} />{store.email}</span>}
                    </div>
                    <div className="hidden flex-1 text-center font-medium text-yellow-400 md:block">{o.header_promo_text}</div>
                    <div className="flex items-center gap-3 text-gray-300">
                        <LanguageMenu buttonClass="hidden items-center gap-1 hover:text-white lg:flex" />
                        <CurrencyMenu buttonClass="flex items-center gap-1 hover:text-white" />
                        <a href="/account" className="flex items-center gap-1 hover:text-primary-400"><User size={13} />{t('my_account', 'My Account')}</a>
                    </div>
                </div>
            </div>
            <div className="hidden border-b border-gray-100 bg-white md:block">
                <div className="sf-container flex h-20 items-center justify-between gap-8">
                    <Logo size="h-12" nameClass="hidden text-2xl font-bold text-gray-900 sm:inline" />
                    <SearchForm className="mx-8 hidden max-w-2xl flex-1 md:flex" placeholder={t('search_for', 'Search products...')} buttonInside={false}
                        inputClass="h-12 w-full rounded-s-lg border border-gray-300 ps-4 pe-4 focus:border-primary-500 focus:ring-2 focus:ring-primary-500 focus:outline-none"
                        buttonClass="flex h-12 items-center rounded-e-lg px-6 text-white" />
                    <style>{`.sf-header form[role=search] button[type=submit]{background-color:${catBg}}`}</style>
                    <div className="flex items-center gap-4 lg:gap-6 text-gray-700">
                        {(o.header_show_wishlist ?? true) && <WishlistLink label={<span className="hidden lg:inline">{t('wishlist', 'Wishlist')}</span>} />}
                        <CartLink label={<span className="hidden lg:inline">{t('cart', 'Cart')}</span>} />
                    </div>
                </div>
            </div>
            <div className="hidden shadow-sm lg:block" style={{ backgroundColor: o.header_menu_bg || '#ffffff' }}>
                <style>{`.sf-mega-nav a{color:${o.header_menu_text || '#374151'}}.sf-mega-nav a:hover{color:${o.header_menu_hover || '#2563eb'}}.sf-mega-nav a[aria-current=page]{color:${o.header_menu_active || '#2563eb'}}`}</style>
                <div className="sf-container flex h-12 items-center">
                    <CategoriesMenu label={t('browse_all_categories', 'Browse All Categories')} bg={catBg} />
                    <NavLinks className={cx('sf-mega-nav ms-8 flex items-center', gap)} linkClass="py-3 font-medium transition" activeClass="" />
                </div>
            </div>
        </>
    );
}

function DarkHeader({ o, height, gap }) {
    const { store, social } = useStore();
    const t = useT();
    return (
        <>
            {o.show_topbar !== false && (
                <div className="hidden text-xs text-gray-300 md:block" style={{ backgroundColor: o.header_topbar_bg || '#111827' }}>
                    <div className="sf-container flex h-9 items-center justify-between gap-4">
                        <div className="flex items-center gap-4">
                            {store.phone && <span className="flex items-center gap-1.5"><Phone size={13} />{store.phone}</span>}
                            {store.email && <span className="hidden items-center gap-1.5 sm:flex"><Mail size={13} />{store.email}</span>}
                        </div>
                        {o.header_promo_text && <div className="hidden flex-1 text-center font-medium text-yellow-400 md:block">{o.header_promo_text}</div>}
                        <div className="flex items-center gap-3">
                            <LanguageMenu buttonClass="flex items-center gap-1 hover:text-white" />
                            <CurrencyMenu buttonClass="flex items-center gap-1 hover:text-white" />
                            {['facebook', 'instagram', 'twitter'].filter((n) => social[n]).map((n) => (
                                <a key={n} href={safeUrl(social[n])} target="_blank" rel="noopener noreferrer" aria-label={n} className="hover:text-white"><SocialIcon network={n} size={13} /></a>
                            ))}
                        </div>
                    </div>
                </div>
            )}
            <div className="hidden bg-gray-900 md:block">
                <div className={cx('sf-container flex items-center justify-between', height)}>
                    <Logo light />
                    <NavLinks className={cx('hidden items-center lg:flex', gap)} linkClass="font-medium text-gray-300 transition hover:text-white" activeClass="text-white" />
                    <SearchForm className="mx-8 hidden max-w-md flex-1 md:flex"
                        inputClass="w-full rounded-full border border-gray-700 bg-gray-800 py-2 ps-4 pe-10 text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                    <div className="flex items-center gap-4 text-gray-300">
                        <WishlistLink className="p-2 hover:text-white" />
                        <CartLink className="relative p-2 hover:text-white" />
                        <AccountMenu dark />
                    </div>
                </div>
            </div>
        </>
    );
}
