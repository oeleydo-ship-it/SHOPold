import { Link, router, usePage } from '@inertiajs/react';
import {
    Bot, Box, Bug, Cog, CreditCard, Download, Globe, Home, Mail, Menu as MenuIcon, Palette, RefreshCw, Search,
    Share2, ShoppingBag, Smartphone, Store, ToggleRight, Trash2, Truck,
} from 'lucide-react';
import AdminLayout from './AdminLayout';
import { Badge, Button, Card } from '../Components/ui';

// inertia: true once the tab is a React page (client-side navigation); others are full-page links to Blade.
const TABS = [
    { href: '/admin/settings', label: 'General', icon: Store, inertia: true, exact: true },
    { href: '/admin/settings/localization', label: 'Localization', icon: Globe, inertia: true },
    { href: '/admin/settings/homepage', label: 'Homepage Editor', icon: Home, badge: ['New', 'success'], inertia: true },
    { href: '/admin/settings/appearance', label: 'Appearance', icon: Palette, inertia: true },
    { href: '/admin/settings/products', label: 'Products', icon: Box, inertia: true },
    { href: '/admin/settings/shop', label: 'Shop Page', icon: ShoppingBag, inertia: true },
    { href: '/admin/settings/mobile', label: 'Mobile Display', icon: Smartphone, badge: ['New', 'primary'], inertia: true },
    { href: '/admin/settings/payment', label: 'Payment', icon: CreditCard, inertia: true },
    { href: '/admin/settings/shipping', label: 'Shipping', icon: Truck, inertia: true },
    { href: '/admin/settings/seo', label: 'SEO', icon: Search, inertia: true },
    { href: '/admin/settings/social', label: 'Social Media', icon: Share2, inertia: true },
    { href: '/admin/settings/features', label: 'Features', icon: ToggleRight, inertia: true },
    { href: '/admin/settings/navigation', label: 'Navigation', icon: MenuIcon, inertia: true },
    { href: '/admin/settings/ai', label: 'AI Settings', icon: Bot, inertia: true },
    { href: '/admin/settings/email', label: 'Email', icon: Mail, inertia: true },
    { href: '/admin/settings/system', label: 'System', icon: Cog, inertia: true },
    { href: '/admin/settings/updates', label: 'Updates', icon: Download, inertia: true },
    { href: '/admin/settings/debug', label: 'Debug', icon: Bug, inertia: true, badge: ['Dev', 'warning'] },
];

function active(url, tab) {
    const path = url.split('?')[0].replace(/\/$/, '');
    return tab.exact ? path === tab.href : path === tab.href || path.startsWith(`${tab.href}/`);
}

export default function SettingsLayout({ title, children }) {
    const { url, props } = usePage();
    // Only super admins get the full Settings area; managers just use Shipping (the server enforces this too).
    const superAdmin = props.auth.user?.admin_role === 'super_admin';
    const tabs = superAdmin ? TABS : TABS.filter((t) => t.href === '/admin/settings/shipping');
    const post = (u) => router.post(u, {}, { preserveScroll: true });

    return (
        <AdminLayout title="Settings">
            <div className="grid gap-5 lg:grid-cols-4">
                <aside className="lg:col-span-1">
                    <Card className="p-4 lg:sticky lg:top-20">
                        {superAdmin && (
                            <div className="mb-4 space-y-2 border-b border-line pb-4">
                                <Button tone="neutral" className="w-full" onClick={() => post('/admin/settings/clear-cache')}><Trash2 size={14} />Clear Cache</Button>
                                <Button tone="neutral" className="w-full" onClick={() => post('/admin/settings/force-refresh')}><RefreshCw size={14} />Force Refresh</Button>
                            </div>
                        )}
                        {/* On small screens the tabs scroll sideways instead of stacking 18 rows */}
                        <nav aria-label="Settings sections" className="-mx-1 flex gap-1 overflow-x-auto px-1 pb-1 lg:mx-0 lg:block lg:space-y-1 lg:overflow-visible lg:px-0 lg:pb-0">
                            {tabs.map((t) => {
                                const Icon = t.icon;
                                const Tag = t.inertia ? Link : 'a';
                                const on = active(url, t);
                                return (
                                    <Tag
                                        key={t.href}
                                        href={t.href}
                                        aria-current={on ? 'page' : undefined}
                                        className={`flex shrink-0 items-center gap-3 whitespace-nowrap rounded-lg px-3 py-2 text-sm ${on ? 'bg-primary-soft/50 font-medium text-primary' : 'text-ink hover:bg-surface-2'}`}
                                    >
                                        <Icon size={16} />
                                        <span>{t.label}</span>
                                        {t.badge && <span className="ml-auto hidden lg:block"><Badge tone={t.badge[1]}>{t.badge[0]}</Badge></span>}
                                    </Tag>
                                );
                            })}
                        </nav>
                    </Card>
                </aside>
                <div className="min-w-0 space-y-5 lg:col-span-3">{children}</div>
            </div>
        </AdminLayout>
    );
}
