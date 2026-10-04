import { useEffect, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { ChevronDown, ExternalLink, LogOut, Menu, ShoppingBag, Store, X } from 'lucide-react';
import { visibleModules } from '../lib/modules';
import ThemeSwitcher from '../Components/ThemeSwitcher';

function isActive(url, href, exact) {
    const path = url.split('?')[0].replace(/\/$/, '') || '/';
    return exact ? path === href : path === href || path.startsWith(href + '/');
}

function ChildTag({ c, className }) {
    const Tag = c.inertia ? Link : 'a';
    return <Tag href={c.href} className={className}>{c.label}</Tag>;
}

function NavItem({ m, url, onNavigate }) {
    const Icon = m.icon;
    const active = isActive(url, m.href, m.exact);
    const Tag = m.inertia ? Link : 'a';
    const [open, setOpen] = useState(() => !!m.children?.some((c) => isActive(url, c.href)));

    if (m.children) {
        return (
            <div>
                <button
                    type="button"
                    onClick={() => setOpen((o) => !o)}
                    className="flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-sm text-sidebar-ink hover:bg-sidebar-hover hover:text-white"
                >
                    <span className="flex items-center gap-3"><Icon size={18} />{m.label}</span>
                    <ChevronDown size={14} className={`transition-transform ${open ? 'rotate-180' : ''}`} />
                </button>
                {open && (
                    <div className="ml-4 mt-1 space-y-0.5 border-l border-sidebar-hover pl-3">
                        {m.children.map((c) => (
                            <ChildTag
                                key={c.href}
                                c={c}
                                className={`block rounded-lg px-3 py-2 text-xs ${isActive(url, c.href) ? 'bg-sidebar-hover text-white' : 'text-sidebar-ink hover:bg-sidebar-hover hover:text-white'}`}
                            />
                        ))}
                    </div>
                )}
            </div>
        );
    }

    return (
        <Tag
            href={m.href}
            onClick={onNavigate}
            className={`flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm ${active ? 'bg-primary text-on-primary' : 'text-sidebar-ink hover:bg-sidebar-hover hover:text-white'}`}
        >
            <Icon size={18} />{m.label}
        </Tag>
    );
}

export default function AdminLayout({ title, children }) {
    const { props, url } = usePage();
    const { app, auth, features, flash } = props;
    const [mobileOpen, setMobileOpen] = useState(false);
    const [toast, setToast] = useState(null);
    const items = visibleModules(auth.user?.admin_role, features);

    useEffect(() => {
        const t = flash?.success
            ? { type: 'success', text: flash.success }
            : flash?.error ? { type: 'error', text: flash.error }
                : flash?.warning ? { type: 'warning', text: flash.warning } : null;
        setToast(t);
        if (t) {
            const id = setTimeout(() => setToast(null), 4000);
            return () => clearTimeout(id);
        }
    }, [flash?.success, flash?.error, flash?.warning]);

    const brand = (
        <Link href="/admin" className="flex items-center gap-2 text-white">
            {app.logo ? (
                <img src={app.logo} alt={app.name} className="h-9 w-auto object-contain" />
            ) : (
                <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-primary text-on-primary"><ShoppingBag size={18} /></span>
            )}
            {!app.hideName && <span className="truncate text-lg font-bold">{app.name}</span>}
        </Link>
    );

    const nav = (
        <>
            <nav className="flex-1 space-y-1 overflow-y-auto p-3">
                {items.map((m) => <NavItem key={m.id} m={m} url={url} onNavigate={() => setMobileOpen(false)} />)}
                <a href="/" target="_blank" rel="noreferrer" className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-sidebar-ink hover:bg-sidebar-hover hover:text-white">
                    <Store size={18} />View Store<ExternalLink size={12} className="ml-auto opacity-60" />
                </a>
            </nav>
            <div className="border-t border-sidebar-hover p-3">
                <button
                    type="button"
                    onClick={() => router.post('/logout')}
                    className="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-sidebar-ink hover:bg-sidebar-hover hover:text-white"
                >
                    <LogOut size={18} />Logout
                </button>
            </div>
        </>
    );

    return (
        <div className="flex min-h-screen bg-bg text-ink">
            <aside className="sticky top-0 hidden h-screen w-64 shrink-0 flex-col bg-sidebar lg:flex">
                <div className="flex h-16 items-center border-b border-sidebar-hover px-4">{brand}</div>
                {nav}
            </aside>

            {mobileOpen && (
                <div className="fixed inset-0 z-40 lg:hidden">
                    <div className="absolute inset-0 bg-black/50" onClick={() => setMobileOpen(false)} />
                    <aside className="relative flex h-full w-72 max-w-[85%] flex-col bg-sidebar">
                        <div className="flex h-16 items-center justify-between border-b border-sidebar-hover px-4">
                            {brand}
                            <button type="button" onClick={() => setMobileOpen(false)} aria-label="Close menu" className="text-sidebar-ink"><X size={20} /></button>
                        </div>
                        {nav}
                    </aside>
                </div>
            )}

            <div className="flex min-w-0 flex-1 flex-col">
                <header className="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-line bg-surface px-4 md:px-6">
                    <div className="flex items-center gap-3">
                        <button type="button" onClick={() => setMobileOpen(true)} aria-label="Open menu" className="text-muted lg:hidden"><Menu size={22} /></button>
                        <h1 className="text-lg font-semibold">{title}</h1>
                    </div>
                    <div className="flex items-center gap-3">
                        <ThemeSwitcher fallback={app.defaultTheme} />
                        <div className="hidden text-right text-sm sm:block">
                            <p className="font-medium leading-tight">{auth.user?.name}</p>
                            <p className="text-xs capitalize text-muted">{(auth.user?.admin_role || 'admin').replace('_', ' ')}</p>
                        </div>
                    </div>
                </header>

                <main className="flex-1 p-4 md:p-6">{children}</main>
            </div>

            {toast && (
                <div role="status" className={`fixed right-4 top-4 z-50 flex items-center gap-3 rounded-lg px-4 py-3 text-sm text-white shadow-lg ${toast.type === 'success' ? 'bg-success' : toast.type === 'warning' ? 'bg-warning' : 'bg-danger'}`}>
                    {toast.text}
                    <button type="button" onClick={() => setToast(null)} aria-label="Dismiss"><X size={14} /></button>
                </div>
            )}
        </div>
    );
}
