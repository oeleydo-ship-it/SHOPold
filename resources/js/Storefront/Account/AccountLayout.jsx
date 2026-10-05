import { usePage } from '@inertiajs/react';
import { Gauge, Headset, Heart, Lock, LogOut, ShoppingBag, Store, Truck, UserCog, UserRound } from 'lucide-react';
import Seo from '../Layout/Seo';
import { logout } from '../Header/parts';
import SfLink from '../lib/SfLink';
import { cx, useStore, useT } from '../lib/store';
import { useThemed } from '../theme/registry';

/** Status colours (fixed class names so Tailwind compiles them). */
const TONE = {
    amber: 'bg-amber-100 text-amber-800', blue: 'bg-blue-100 text-blue-800', purple: 'bg-purple-100 text-purple-800',
    indigo: 'bg-indigo-100 text-indigo-800', orange: 'bg-orange-100 text-orange-800', green: 'bg-green-100 text-green-800', red: 'bg-red-100 text-red-800', gray: 'bg-gray-100 text-gray-700',
};

export function Badge({ status, className }) {
    if (!status) return null;
    return <span className={cx('inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium', TONE[status.tone] ?? TONE.gray, className)}>{status.label}</span>;
}

export function useAccountNav() {
    const { customer, features } = useStore();
    const t = useT();
    if (!customer) return [[], []];
    const nav = [
        { href: '/account', label: t('dashboard', 'Dashboard'), icon: Gauge, match: /^\/account\/?$/ },
        { href: '/account/orders', label: t('my_orders', 'My Orders'), icon: ShoppingBag, match: /^\/account\/orders/ },
        features.wishlist && { href: '/account/wishlist', label: t('wishlist', 'Wishlist'), icon: Heart, match: /^\/account\/wishlist/ },
        { href: '/account/profile', label: t('profile', 'Profile'), icon: UserCog, match: /^\/account\/profile/ },
        { href: '/account/password', label: t('change_password', 'Change Password'), icon: Lock, match: /^\/account\/password/ },
        { href: '/helpdesk', label: t('support_tickets', 'Support Tickets'), icon: Headset, match: /^\/helpdesk/ },
    ].filter(Boolean);
    const extra = [];
    if (customer.isDriver && features.driverPortal) extra.push({ href: '/driver', label: t('driver_portal', 'Driver Portal'), icon: Truck, tone: true });
    if (features.multiVendor) {
        extra.push(customer.hasStore
            ? { href: '/vendor', label: t('vendor_panel', 'Vendor Panel'), icon: Store, tone: true }
            : { href: '/vendor/register', label: t('become_vendor', 'Become a Vendor'), icon: Store });
    }
    return [nav, extra];
}

export function AccountNav() {
    const { url } = usePage();
    const { customer } = useStore();
    const t = useT();
    const [nav, extra] = useAccountNav();
    const path = url.split('?')[0];
    return (
        <>
            {/* Phones: compact header and a horizontal list */}
            <div className="mb-6 rounded-xl bg-white p-4 shadow-sm lg:hidden">
                <div className="mb-4 flex items-center gap-3 border-b pb-4">
                    <span className="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-primary-100 text-primary-600"><UserRound size={22} /></span>
                    <div className="min-w-0">
                        <p className="truncate font-semibold text-gray-900">{customer.name}</p>
                        <p className="truncate text-xs text-gray-500">{customer.email}</p>
                    </div>
                </div>
                <div className="-mx-4 overflow-x-auto px-4">
                    <div className="flex min-w-max gap-2">
                        {[...nav, ...extra].map((l) => (
                            <SfLink key={l.href} href={l.href} className={cx('flex items-center gap-2 whitespace-nowrap rounded-full px-4 py-2.5 text-sm font-medium transition',
                                l.match?.test(path) ? 'bg-primary-600 text-white' : l.tone ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' : 'bg-gray-100 text-gray-700 hover:bg-gray-200')}>
                                <l.icon size={15} />{l.label}
                            </SfLink>
                        ))}
                        <button type="button" onClick={logout} className="flex items-center gap-2 whitespace-nowrap rounded-full bg-red-100 px-4 py-2.5 text-sm font-medium text-red-700 hover:bg-red-200">
                            <LogOut size={15} />{t('logout', 'Logout')}
                        </button>
                    </div>
                </div>
            </div>

            {/* Desktop sidebar */}
            <aside className="hidden lg:block">
                <div className="sticky top-4 rounded-xl bg-white p-6 shadow-sm">
                    <div className="mb-6 text-center">
                        <span className="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-primary-100 text-primary-600"><UserRound size={34} /></span>
                        <p className="font-semibold text-gray-900">{customer.name}</p>
                        <p className="truncate text-sm text-gray-500">{customer.email}</p>
                    </div>
                    <nav className="space-y-1" aria-label={t('my_account', 'My Account')}>
                        {nav.map((l) => (
                            <SfLink key={l.href} href={l.href} aria-current={l.match.test(path) ? 'page' : undefined}
                                className={cx('flex items-center gap-3 rounded-lg px-4 py-3', l.match.test(path) ? 'bg-primary-50 text-primary-600' : 'text-gray-700 hover:bg-gray-50')}>
                                <l.icon size={18} />{l.label}
                            </SfLink>
                        ))}
                        {extra.length > 0 && <hr className="my-2" />}
                        {extra.map((l) => (
                            <SfLink key={l.href} href={l.href} className={cx('flex items-center gap-3 rounded-lg px-4 py-3', l.tone ? 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100' : 'text-emerald-600 hover:bg-emerald-50')}>
                                <l.icon size={18} />{l.label}
                            </SfLink>
                        ))}
                        <hr className="my-2" />
                        <button type="button" onClick={logout} className="flex w-full items-center gap-3 rounded-lg px-4 py-3 text-gray-700 hover:bg-gray-50">
                            <LogOut size={18} />{t('logout', 'Logout')}
                        </button>
                    </nav>
                </div>
            </aside>
        </>
    );
}

/** Account page frame: heading (+ optional actions), navigation and the content column. Theme override: "Account.Nav". */
export default function AccountLayout({ title, seo, actions, children, contentClass = 'space-y-6' }) {
    const Nav = useThemed('Account.Nav', AccountNav);
    return (
        <>
            <Seo title={seo?.title ?? title} robots={seo?.robots ?? 'noindex'} />
            <div className="account-page sf-container py-6 md:py-8">
                <div className="mb-6 flex flex-wrap items-center justify-between gap-3 md:mb-8">
                    <h1 className="text-2xl font-bold md:text-3xl">{title}</h1>
                    {actions}
                </div>
                <div className="grid gap-6 md:gap-8 lg:grid-cols-4">
                    <Nav />
                    <div className={cx('min-w-0 lg:col-span-3', contentClass)}>{children}</div>
                </div>
            </div>
        </>
    );
}
