import { useEffect, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { ExternalLink, LogOut, Menu, ShoppingBag, Store, X } from 'lucide-react';
import ThemeSwitcher from '../Components/ThemeSwitcher';

/**
 * Shared frame for the vendor and driver portals: sidebar (desktop) / slide-out menu (mobile), header with the
 * page title, theme switcher and flash toasts. `nav` is a list of { label, href, icon, exact?, count?, tab? }.
 * With `tabs`, the items marked `tab` also form a bottom tab bar on phones (driver portal: one-handed use).
 */

function isActive(url, href, exact) {
    const path = url.split('?')[0].replace(/\/$/, '') || '/';
    return exact ? path === href : path === href || path.startsWith(href + '/');
}

export default function PortalLayout({ title, nav, home, badge, subtitle, actions, children, tabs = false }) {
    const { props, url } = usePage();
    const { app, auth, flash } = props;
    const [mobileOpen, setMobileOpen] = useState(false);
    const [toast, setToast] = useState(null);

    useEffect(() => {
        const t = flash?.success ? { type: 'success', text: flash.success }
            : flash?.error ? { type: 'error', text: flash.error }
                : flash?.warning ? { type: 'warning', text: flash.warning } : null;
        setToast(t);
        if (t) {
            const id = setTimeout(() => setToast(null), 5000);
            return () => clearTimeout(id);
        }
    }, [flash?.success, flash?.error, flash?.warning]);

    const brand = (
        <Link href={home} className="flex min-w-0 items-center gap-2 text-white">
            {app.logo ? <img src={app.logo} alt={app.name} className="h-9 w-auto object-contain" />
                : <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary text-on-primary"><ShoppingBag size={18} /></span>}
            <span className="min-w-0">
                <span className="block truncate text-base font-bold leading-tight">{app.name}</span>
                {badge && <span className="block truncate text-xs text-sidebar-ink">{badge}</span>}
            </span>
        </Link>
    );

    const menu = (
        <>
            <nav className="flex-1 space-y-1 overflow-y-auto p-3" aria-label="Portal">
                {nav.map((item) => {
                    const Icon = item.icon;
                    const active = isActive(url, item.href, item.exact);
                    // pages not converted to React yet are opened with a normal page load
                    const Tag = item.inertia === false ? 'a' : Link;
                    return (
                        <Tag key={item.href} href={item.href} onClick={() => setMobileOpen(false)}
                            aria-current={active ? 'page' : undefined}
                            className={`flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm ${active ? 'bg-primary text-on-primary' : 'text-sidebar-ink hover:bg-sidebar-hover hover:text-white'}`}>
                            <Icon size={18} />{item.label}
                            {item.count > 0 && <span className="ml-auto rounded-full bg-warning px-2 text-xs text-white">{item.count}</span>}
                        </Tag>
                    );
                })}
                <a href="/" className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-sidebar-ink hover:bg-sidebar-hover hover:text-white">
                    <Store size={18} />Back to store<ExternalLink size={12} className="ml-auto opacity-60" />
                </a>
            </nav>
            <div className="border-t border-sidebar-hover p-3">
                <button type="button" onClick={() => router.post('/logout')}
                    className="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-sidebar-ink hover:bg-sidebar-hover hover:text-white">
                    <LogOut size={18} />Logout
                </button>
            </div>
        </>
    );

    return (
        <div className="flex min-h-screen bg-bg text-ink">
            <aside className="sticky top-0 hidden h-screen w-64 shrink-0 flex-col bg-sidebar lg:flex">
                <div className="flex h-16 items-center border-b border-sidebar-hover px-4">{brand}</div>
                {menu}
            </aside>

            {mobileOpen && (
                <div className="fixed inset-0 z-40 lg:hidden">
                    <div className="absolute inset-0 bg-black/50" onClick={() => setMobileOpen(false)} />
                    <aside className="relative flex h-full w-72 max-w-[85%] flex-col bg-sidebar">
                        <div className="flex h-16 items-center justify-between gap-2 border-b border-sidebar-hover px-4">
                            {brand}
                            <button type="button" onClick={() => setMobileOpen(false)} aria-label="Close menu" className="text-sidebar-ink"><X size={20} /></button>
                        </div>
                        {menu}
                    </aside>
                </div>
            )}

            <div className="flex min-w-0 flex-1 flex-col">
                <header className="sticky top-0 z-30 flex h-16 items-center justify-between gap-3 border-b border-line bg-surface px-4 md:px-6">
                    <div className="flex min-w-0 items-center gap-3">
                        <button type="button" onClick={() => setMobileOpen(true)} aria-label="Open menu" className="text-muted lg:hidden"><Menu size={22} /></button>
                        <h1 className="truncate text-lg font-semibold">{title}</h1>
                    </div>
                    <div className="flex items-center gap-3">
                        {actions}
                        <ThemeSwitcher fallback={app.defaultTheme} />
                        <div className="hidden text-right text-sm sm:block">
                            <p className="font-medium leading-tight">{auth.user?.name}</p>
                            {subtitle && <p className="text-xs text-muted">{subtitle}</p>}
                        </div>
                    </div>
                </header>

                <main className={`flex-1 p-4 md:p-6 ${tabs ? 'pb-24 lg:pb-6' : ''}`}>{children}</main>
            </div>

            {tabs && (
                <nav aria-label="Quick navigation" className="fixed inset-x-0 bottom-0 z-30 flex border-t border-line bg-surface pb-[env(safe-area-inset-bottom)] lg:hidden">
                    {nav.filter((i) => i.tab).map((item) => {
                        const Icon = item.icon;
                        const active = isActive(url, item.href, item.exact);
                        return (
                            <Link key={item.href} href={item.href} aria-current={active ? 'page' : undefined}
                                className={`relative flex min-h-14 flex-1 flex-col items-center justify-center gap-0.5 text-[11px] ${active ? 'text-primary' : 'text-muted'}`}>
                                <Icon size={20} />{item.short ?? item.label}
                                {item.count > 0 && <span className="absolute right-1/4 top-1.5 rounded-full bg-danger px-1.5 text-[10px] leading-4 text-white">{item.count}</span>}
                            </Link>
                        );
                    })}
                </nav>
            )}

            {toast && (
                <div role="status" className={`fixed right-4 top-4 z-50 flex max-w-sm items-center gap-3 rounded-lg px-4 py-3 text-sm text-white shadow-lg ${toast.type === 'success' ? 'bg-success' : toast.type === 'warning' ? 'bg-warning' : 'bg-danger'}`}>
                    {toast.text}
                    <button type="button" onClick={() => setToast(null)} aria-label="Dismiss"><X size={14} /></button>
                </div>
            )}
        </div>
    );
}
