import { useEffect, useRef, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import {
    Check, ChevronDown, Gauge, Globe, Heart, LayoutGrid, LogOut, Search, Settings, ShoppingBag,
    ShoppingCart, Store, Truck, User, UserCog,
} from 'lucide-react';
import SfLink from '../lib/SfLink';
import { cx, useShop, useStore, useT } from '../lib/store';

const NAV_KEYS = { home: 'home', 'shop.index': 'shop', about: 'about', contact: 'contact', 'blog.index': 'blog' };

/** Header menu items with labels translated for the built-in routes. */
export function useNavItems() {
    const { nav } = useStore();
    const t = useT();
    return nav.map((item) => ({ ...item, label: NAV_KEYS[item.route] ? t(NAV_KEYS[item.route], item.label) : item.label }));
}

/** Small click-to-open menu; closes on outside click and Escape. */
export function Dropdown({ button, children, align = 'right', className = '', menuClass = 'bg-white border border-gray-100' }) {
    const [open, setOpen] = useState(false);
    const ref = useRef(null);
    useEffect(() => {
        if (!open) return undefined;
        const onDoc = (e) => { if (!ref.current?.contains(e.target)) setOpen(false); };
        const onKey = (e) => { if (e.key === 'Escape') setOpen(false); };
        document.addEventListener('mousedown', onDoc);
        document.addEventListener('keydown', onKey);
        return () => { document.removeEventListener('mousedown', onDoc); document.removeEventListener('keydown', onKey); };
    }, [open]);

    return (
        <div ref={ref} className={cx('relative', className)}>
            {button({ open, toggle: () => setOpen((o) => !o) })}
            {open && (
                <div className={cx('absolute z-50 mt-2 min-w-40 rounded-lg py-1 shadow-xl', align === 'right' ? 'end-0' : 'start-0', menuClass)}
                    onClick={() => setOpen(false)}>
                    {children}
                </div>
            )}
        </div>
    );
}

export function Logo({ size = 'h-10', light = false, nameClass = 'text-xl font-bold', showName = true }) {
    const { store } = useStore();
    return (
        <SfLink href="/" className="flex shrink-0 items-center gap-3" aria-label={store.name}>
            {store.logo
                ? <img src={store.logo} alt={store.name} className={cx(size, 'w-auto object-contain', light && 'brightness-0 invert')} />
                : <span className={cx('flex aspect-square items-center justify-center rounded-lg', size, light ? 'border border-white/30 bg-white/20' : 'bg-primary-600')}><ShoppingBag className="text-white" size={20} /></span>}
            {showName && !store.hideName && <span className={cx(nameClass, light ? 'text-white' : 'sf-header-text')}>{store.name}</span>}
        </SfLink>
    );
}

export function NavLinks({ className = '', linkClass = 'sf-nav-link font-medium transition', activeClass = 'sf-nav-active' }) {
    const items = useNavItems();
    return (
        <nav className={className} aria-label="Main">
            {items.map((item) => (
                <SfLink key={`${item.url}-${item.label}`} href={item.url} aria-current={item.active ? 'page' : undefined} className={cx(linkClass, item.active && activeClass)}>
                    {item.label}
                </SfLink>
            ))}
        </nav>
    );
}

/** Product search; goes to the shop listing with ?search=. */
export function SearchForm({ className = '', inputClass, buttonClass, placeholder, buttonInside = true, icon = true }) {
    const { search } = useStore();
    const t = useT();
    const [q, setQ] = useState(search ?? '');
    useEffect(() => setQ(search ?? ''), [search]);
    const submit = (e) => {
        e.preventDefault();
        router.get('/shop', q.trim() ? { search: q.trim() } : {});
    };
    return (
        <form role="search" onSubmit={submit} className={cx('relative flex w-full', className)}>
            <input type="search" name="search" value={q} onChange={(e) => setQ(e.target.value)} aria-label={t('search', 'Search')}
                placeholder={placeholder ?? t('search_for', 'Search products...')}
                className={inputClass ?? 'w-full rounded-full border border-gray-300 py-2 ps-4 pe-10 focus:border-transparent focus:ring-2 focus:ring-primary-500 focus:outline-none'} />
            <button type="submit" aria-label={t('search', 'Search')}
                className={buttonClass ?? (buttonInside ? 'absolute end-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-primary-600' : '')}>
                {icon && <Search size={18} />}
            </button>
        </form>
    );
}

export function LanguageMenu({ buttonClass = 'flex items-center gap-1', showIcon = true, menuClass }) {
    const { locale } = useStore();
    const codes = Object.keys(locale.languages ?? {});
    if (!locale.enabled || codes.length < 2) return null;
    return (
        <Dropdown menuClass={menuClass} button={({ toggle, open }) => (
            <button type="button" onClick={toggle} aria-expanded={open} aria-haspopup="menu" className={buttonClass}>
                {showIcon && <Globe size={14} />}<span>{locale.languages[locale.current] ?? locale.current}</span><ChevronDown size={12} />
            </button>
        )}>
            {codes.map((code) => (
                <a key={code} href={`/locale/${code}`} className={cx('flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600', locale.current === code && 'bg-primary-50 font-medium text-primary-600')}>
                    <span className="w-4">{locale.current === code && <Check size={14} />}</span>{locale.languages[code]}
                </a>
            ))}
        </Dropdown>
    );
}

export function CurrencyMenu({ buttonClass = 'flex items-center gap-1', menuClass, short = false }) {
    const { currency } = useStore();
    if (!currency.multi || currency.enabled.length < 2) return null;
    return (
        <Dropdown menuClass={menuClass} button={({ toggle, open }) => (
            <button type="button" onClick={toggle} aria-expanded={open} aria-haspopup="menu" className={buttonClass}>
                <span>{short ? currency.current : `${currency.current} (${currency.symbol})`}</span><ChevronDown size={12} />
            </button>
        )}>
            {currency.enabled.map((c) => (
                <a key={c.code} href={`/currency/${c.code}`} className={cx('flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600', currency.current === c.code && 'bg-primary-50 font-medium text-primary-600')}>
                    <span className="w-4">{currency.current === c.code && <Check size={14} />}</span>{c.code} ({c.symbol})
                </a>
            ))}
        </Dropdown>
    );
}

export function CartLink({ className = 'sf-header-icon relative p-2', label, iconSize = 20 }) {
    const { cartCount } = useShop();
    const t = useT();
    return (
        <a href="/cart" className={cx('flex items-center gap-2', className)} aria-label={`${t('cart', 'Cart')} (${cartCount})`}>
            <span className="relative">
                <ShoppingCart size={iconSize} />
                {cartCount > 0 && <span className="absolute -end-2.5 -top-2.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-primary-600 px-1 text-xs text-white">{cartCount}</span>}
            </span>
            {label && <span className="text-sm font-medium">{label}</span>}
        </a>
    );
}

export function WishlistLink({ className = 'sf-header-icon p-2', label, iconSize = 20 }) {
    const { features, customer } = useStore();
    const t = useT();
    if (!features.wishlist || !customer) return null;
    return (
        <a href="/account/wishlist" className={cx('flex items-center gap-2', className)} aria-label={t('wishlist', 'Wishlist')}>
            <Heart size={iconSize} />{label && <span className="text-sm font-medium">{label}</span>}
        </a>
    );
}

// No compare page exists yet (the Blade header linked to "#"), so the React header leaves the compare icon out.

/** Links for a signed-in customer (dashboard, orders, profile, admin/driver/vendor, logout). */
export function useAccountLinks() {
    const { customer, features } = useStore();
    const t = useT();
    if (!customer) return [];
    const links = [
        { href: '/account', label: t('dashboard', 'Dashboard'), icon: Gauge },
        { href: '/account/orders', label: t('my_orders', 'My Orders'), icon: ShoppingBag },
        { href: '/account/profile', label: t('profile', 'Profile'), icon: UserCog },
    ];
    if (customer.isAdmin) links.push({ href: '/admin', label: 'Admin Panel', icon: Settings, divider: true });
    if (customer.isDriver && features.driverPortal) links.push({ href: '/driver', label: 'Driver Portal', icon: Truck, divider: true, tone: 'emerald' });
    if (features.multiVendor) {
        links.push(customer.hasStore
            ? { href: '/vendor', label: 'Vendor Panel', icon: Store, divider: true }
            : { href: '/vendor/register', label: 'Become a Vendor', icon: Store, divider: true, tone: 'emerald' });
    }
    return links;
}

export function logout() {
    router.post('/logout');
}

export function AccountMenu({ dark = false }) {
    const { customer, store } = useStore();
    const t = useT();
    const links = useAccountLinks();

    if (!customer) {
        return (
            <div className="hidden items-center gap-2 sm:flex">
                <a href="/login" className={cx('sf-btn inline-flex items-center px-4 py-2 transition', dark ? 'border border-gray-600 text-gray-300 hover:bg-gray-800 hover:text-white' : 'border border-primary-600 text-primary-600 hover:bg-primary-600 hover:text-white')}>{t('login', 'Login')}</a>
                {store.allowRegistration && <a href="/register" className="sf-btn inline-flex items-center bg-primary-600 px-4 py-2 text-white transition hover:bg-primary-700">{t('register', 'Register')}</a>}
            </div>
        );
    }

    const item = dark ? 'text-gray-300 hover:bg-gray-700 hover:text-white' : 'text-gray-700 hover:bg-gray-100';
    return (
        <Dropdown menuClass={cx('w-52', dark ? 'border border-gray-700 bg-gray-800' : 'border border-gray-100 bg-white')} button={({ toggle, open }) => (
            <button type="button" onClick={toggle} aria-expanded={open} aria-haspopup="menu" className={cx('flex items-center gap-2', dark ? 'text-gray-300 hover:text-white' : 'sf-header-text')}>
                <span className={cx('flex h-8 w-8 items-center justify-center rounded-full', dark ? 'bg-gray-700' : 'bg-primary-100 text-primary-600')}><User size={16} /></span>
                <span className="hidden font-medium sm:inline">{customer.name}</span><ChevronDown size={12} />
            </button>
        )}>
            {links.map((l) => (
                <div key={l.href}>
                    {l.divider && <hr className={cx('my-1', dark ? 'border-gray-700' : 'border-gray-100')} />}
                    <a href={l.href} className={cx('flex items-center gap-2 px-4 py-2 text-sm', l.tone === 'emerald' ? 'text-emerald-600 hover:bg-emerald-50' : item)}><l.icon size={15} />{l.label}</a>
                </div>
            ))}
            <hr className={cx('my-1', dark ? 'border-gray-700' : 'border-gray-100')} />
            <button type="button" onClick={logout} className={cx('flex w-full items-center gap-2 px-4 py-2 text-start text-sm', item)}><LogOut size={15} />{t('logout', 'Logout')}</button>
        </Dropdown>
    );
}

export function CategoriesMenu({ label, bg }) {
    const { categories } = useStore();
    const t = useT();
    return (
        <Dropdown align="left" menuClass="w-64 rounded-t-none bg-white border border-gray-100" button={({ toggle, open }) => (
            <button type="button" onClick={toggle} aria-expanded={open} aria-haspopup="menu" className="flex h-12 items-center gap-2 px-6 font-medium text-white" style={{ backgroundColor: bg }}>
                <LayoutGrid size={16} /><span>{label}</span><ChevronDown size={12} className="ms-2" />
            </button>
        )}>
            {categories.map((c) => (
                <SfLink key={c.id} href={`/shop/category/${c.slug}`} className="flex items-center gap-3 px-4 py-2.5 text-gray-700 hover:bg-primary-50 hover:text-primary-600">
                    {c.image ? <img src={c.image} alt="" className="h-6 w-6 rounded object-cover" /> : <LayoutGrid size={16} className="text-gray-400" />}
                    <span>{c.name}</span>
                </SfLink>
            ))}
            <hr className="my-1 border-gray-100" />
            <SfLink href="/shop" className="flex items-center gap-3 px-4 py-2.5 font-medium text-primary-600 hover:bg-primary-50"><LayoutGrid size={16} />{t('view_all_categories', 'View All Categories')}</SfLink>
        </Dropdown>
    );
}

/** Is this the home page (the transparent header only overlays the home hero)? */
export function useIsHome() {
    const { url } = usePage();
    return url.split('?')[0] === '/';
}
