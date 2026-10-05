import { useEffect } from 'react';
import {
    ArrowRight, CheckCircle2, ChevronRight, Globe, Home, Info, LayoutGrid, Link as LinkIcon, LogIn, LogOut, Mail,
    Newspaper, Phone, ShoppingBag, Store, Tag, User, X,
} from 'lucide-react';
import SfLink from '../lib/SfLink';
import { cx, safeUrl, useStore, useT } from '../lib/store';
import { Logo, logout, useAccountLinks, useNavItems } from './parts';
import { SocialIcon } from '../Footer/Footer';

const NAV_ICONS = { home: Home, 'shop.index': Store, about: Info, contact: Mail, 'blog.index': Newspaper };

/** Slide-out menu for phones: account, navigation, categories, account links, language, contact. */
export default function MobileMenu({ open, onClose }) {
    const { customer, store, categories, locale, social } = useStore();
    const t = useT();
    const nav = useNavItems();
    const accountLinks = useAccountLinks();

    useEffect(() => {
        if (!open) return undefined;
        const onKey = (e) => e.key === 'Escape' && onClose();
        document.addEventListener('keydown', onKey);
        document.body.style.overflow = 'hidden';
        return () => { document.removeEventListener('keydown', onKey); document.body.style.overflow = ''; };
    }, [open, onClose]);

    const codes = Object.keys(locale.languages ?? {});

    return (
        <div className={cx('md:hidden', !open && 'pointer-events-none')} aria-hidden={!open}>
            <div onClick={onClose} className={cx('fixed inset-0 z-40 bg-black/60 backdrop-blur-sm transition-opacity duration-300', open ? 'opacity-100' : 'opacity-0')} />
            <aside role="dialog" aria-modal="true" aria-label={t('menu', 'Menu')}
                className={cx('fixed start-0 top-0 z-50 flex h-full w-[300px] max-w-[85vw] flex-col bg-white shadow-2xl transition-transform duration-300', open ? 'translate-x-0' : 'ltr:-translate-x-full rtl:translate-x-full')}>
                <div className="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <Logo size="h-9" nameClass="text-lg font-bold text-gray-900" />
                    <button type="button" onClick={onClose} aria-label={t('close', 'Close')} className="flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200"><X size={18} /></button>
                </div>

                <div className="border-b border-primary-100 bg-gradient-to-r from-primary-50 to-primary-100/50 px-5 py-4">
                    {customer ? (
                        <div className="flex items-center gap-3">
                            <span className="flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-primary-500 to-primary-700 text-white shadow-lg"><User size={20} /></span>
                            <div className="min-w-0 flex-1">
                                <p className="truncate font-semibold text-gray-900">{customer.name}</p>
                                <a href="/account" className="flex items-center gap-1 text-sm font-medium text-primary-600">{t('my_account', 'My Account')}<ArrowRight size={12} /></a>
                            </div>
                        </div>
                    ) : (
                        <div className="flex gap-3">
                            <a href="/login" className="flex flex-1 items-center justify-center gap-2 rounded-xl bg-primary-600 px-4 py-2.5 font-semibold text-white shadow-md"><LogIn size={16} />{t('login', 'Login')}</a>
                            {store.allowRegistration && <a href="/register" className="flex-1 rounded-xl border-2 border-primary-600 px-4 py-2.5 text-center font-semibold text-primary-600">{t('register', 'Register')}</a>}
                        </div>
                    )}
                </div>

                <div className="flex-1 overflow-y-auto overscroll-contain">
                    <nav className="px-3 py-4" aria-label="Mobile">
                        <p className="mb-2 px-3 text-xs font-semibold tracking-wider text-gray-400 uppercase">{t('menu', 'Menu')}</p>
                        {nav.map((item) => {
                            const Icon = NAV_ICONS[item.route] ?? LinkIcon;
                            return (
                                <SfLink key={`${item.url}-${item.label}`} href={item.url} onClick={onClose} aria-current={item.active ? 'page' : undefined}
                                    className={cx('flex items-center gap-3 rounded-xl px-3 py-3 transition', item.active ? 'bg-primary-50 text-primary-600' : 'text-gray-700 hover:bg-gray-50')}>
                                    <span className={cx('flex h-9 w-9 items-center justify-center rounded-lg', item.active ? 'bg-primary-100 text-primary-600' : 'bg-gray-100 text-gray-500')}><Icon size={16} /></span>
                                    <span className="flex-1 font-medium">{item.label}</span>
                                    <ChevronRight size={14} className={cx('rtl:rotate-180', item.active ? 'text-primary-400' : 'text-gray-300')} />
                                </SfLink>
                            );
                        })}
                    </nav>

                    {categories.length > 0 && (
                        <div className="border-t border-gray-100 px-3 py-4">
                            <p className="mb-2 px-3 text-xs font-semibold tracking-wider text-gray-400 uppercase">{t('categories', 'Categories')}</p>
                            <div className="grid grid-cols-2 gap-2">
                                {categories.slice(0, 6).map((c) => (
                                    <SfLink key={c.id} href={`/shop/category/${c.slug}`} onClick={onClose} className="group flex items-center gap-2 rounded-xl bg-gray-50 p-3 hover:bg-primary-50">
                                        {c.image ? <img src={c.image} alt="" className="h-8 w-8 rounded-lg object-cover shadow-sm" /> : <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-200 text-gray-500"><Tag size={12} /></span>}
                                        <span className="truncate text-sm font-medium text-gray-700 group-hover:text-primary-600">{c.name}</span>
                                    </SfLink>
                                ))}
                            </div>
                            <SfLink href="/shop" onClick={onClose} className="mt-3 flex items-center justify-center gap-2 rounded-xl py-2.5 font-semibold text-primary-600 hover:bg-primary-50"><LayoutGrid size={16} />{t('view_all_categories', 'View All Categories')}</SfLink>
                        </div>
                    )}

                    {customer && (
                        <div className="border-t border-gray-100 px-3 py-4">
                            <p className="mb-2 px-3 text-xs font-semibold tracking-wider text-gray-400 uppercase">{t('my_account', 'My Account')}</p>
                            {accountLinks.filter((l) => l.href !== '/account').map((l) => (
                                <a key={l.href} href={l.href} className={cx('flex items-center gap-3 rounded-xl px-3 py-2.5 hover:bg-gray-50', l.tone === 'emerald' ? 'text-emerald-700' : 'text-gray-700')}>
                                    <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100"><l.icon size={14} /></span>
                                    <span className="font-medium">{l.label}</span>
                                </a>
                            ))}
                        </div>
                    )}

                    {locale.enabled && codes.length > 1 && (
                        <div className="border-t border-gray-100 px-3 py-4">
                            <p className="mb-2 flex items-center gap-1 px-3 text-xs font-semibold tracking-wider text-gray-400 uppercase"><Globe size={12} />{t('language', 'Language')}</p>
                            <div className="grid grid-cols-2 gap-2">
                                {codes.map((code) => (
                                    <a key={code} href={`/locale/${code}`}
                                        className={cx('flex items-center justify-center gap-2 rounded-xl p-3 text-sm', locale.current === code ? 'border-2 border-primary-300 bg-primary-100 font-semibold text-primary-700' : 'bg-gray-50 text-gray-700 hover:bg-primary-50')}>
                                        {locale.current === code && <CheckCircle2 size={14} />}{locale.languages[code]}
                                    </a>
                                ))}
                            </div>
                        </div>
                    )}
                </div>

                <div className="border-t border-gray-100 bg-gray-50 p-4">
                    {customer ? (
                        <button type="button" onClick={logout} className="flex w-full items-center justify-center gap-2 rounded-xl bg-red-50 py-3 font-semibold text-red-600 hover:bg-red-100"><LogOut size={16} />{t('logout', 'Logout')}</button>
                    ) : (
                        <div className="text-center">
                            {store.phone && <a href={`tel:${store.phone}`} className="flex items-center justify-center gap-2 py-2 text-sm font-medium text-gray-600"><Phone size={14} />{store.phone}</a>}
                            <div className="mt-2 flex items-center justify-center gap-4">
                                {['facebook', 'instagram', 'twitter'].filter((n) => social[n]).map((n) => (
                                    <a key={n} href={safeUrl(social[n])} target="_blank" rel="noopener noreferrer" aria-label={n} className="flex h-9 w-9 items-center justify-center rounded-full bg-gray-200 text-gray-500 hover:bg-primary-100 hover:text-primary-600"><SocialIcon network={n} size={15} /></a>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </aside>
        </div>
    );
}
