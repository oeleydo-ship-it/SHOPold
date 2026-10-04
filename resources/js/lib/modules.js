import {
    LayoutDashboard, Package, ShoppingCart, ArrowLeftRight, Users, Tag, Star, Mail, Truck,
    Clock, BarChart3, FileText, Images, Headset, BookOpen, Store, Coins, Bike, Languages, Settings, Palette, Puzzle,
} from 'lucide-react';

/**
 * Admin module registry. One entry per sidebar item.
 *  - perm:    role group allowed to see it (see canAccess below); omitted = everyone
 *  - feature: key in shared `features` props that must be enabled
 *  - inertia: true once the page is converted to React (client-side navigation);
 *             otherwise it is a normal full-page link to the Blade version.
 *  - children: sub-links (only Products today)
 */
export const modules = [
    { id: 'dashboard', label: 'Dashboard', href: '/admin', icon: LayoutDashboard, inertia: true, exact: true },
    {
        id: 'products', label: 'Products', href: '/admin/products', icon: Package,
        children: [
            { label: 'All Products', href: '/admin/products', inertia: true },
            { label: 'Categories', href: '/admin/categories', inertia: true },
            { label: 'Brands', href: '/admin/brands', inertia: true },
            { label: 'Attributes', href: '/admin/attributes', inertia: true },
            { label: 'Tags', href: '/admin/tags', inertia: true },
            { label: 'Stock Management', href: '/admin/stock', inertia: true },
        ],
    },
    { id: 'orders', label: 'Orders', href: '/admin/orders', icon: ShoppingCart, perm: 'orders', inertia: true },
    { id: 'transactions', label: 'Transactions', href: '/admin/transactions', icon: ArrowLeftRight, perm: 'orders', inertia: true },
    { id: 'users', label: 'Users', href: '/admin/users', icon: Users, perm: 'users', inertia: true },
    { id: 'coupons', label: 'Coupons', href: '/admin/coupons', icon: Tag, perm: 'orders', inertia: true },
    { id: 'reviews', label: 'Reviews', href: '/admin/reviews', icon: Star, inertia: true },
    { id: 'newsletters', label: 'Newsletter', href: '/admin/newsletters', icon: Mail, perm: 'orders', inertia: true },
    { id: 'shipping', label: 'Shipping', href: '/admin/settings/shipping', icon: Truck, perm: 'orders', inertia: true },
    { id: 'delivery', label: 'Delivery', href: '/admin/delivery', icon: Clock, perm: 'orders', inertia: true },
    { id: 'reports', label: 'Reports', href: '/admin/reports', icon: BarChart3, perm: 'reports', inertia: true },
    { id: 'pages', label: 'Pages', href: '/admin/pages', icon: FileText, perm: 'content', inertia: true },
    { id: 'media', label: 'Media', href: '/admin/media', icon: Images, perm: 'content', inertia: true },
    { id: 'helpdesk', label: 'Helpdesk', href: '/admin/helpdesk', icon: Headset, perm: 'orders', inertia: true },
    { id: 'blog', label: 'Blog', href: '/admin/blog', icon: BookOpen, perm: 'content', feature: 'blog', inertia: true },
    { id: 'vendors', label: 'Vendors', href: '/admin/vendors', icon: Store, perm: 'super', feature: 'multiVendor', inertia: true },
    { id: 'payouts', label: 'Payout Requests', href: '/admin/payouts', icon: Coins, perm: 'super', feature: 'multiVendor', inertia: true },
    { id: 'loyalty', label: 'Loyalty Points', href: '/admin/loyalty', icon: Coins, perm: 'super', feature: 'loyalty', inertia: true },
    { id: 'drivers', label: 'Drivers', href: '/admin/drivers', icon: Bike, perm: 'orders', feature: 'driverPortal', inertia: true },
    { id: 'translations', label: 'Translations', href: '/admin/translations', icon: Languages, perm: 'super', feature: 'localization', inertia: true },
    { id: 'themes', label: 'Themes', href: '/admin/themes', icon: Palette, perm: 'super', inertia: true },
    { id: 'modules', label: 'Modules', href: '/admin/modules', icon: Puzzle, perm: 'super', inertia: true },
    { id: 'settings', label: 'Settings', href: '/admin/settings', icon: Settings, perm: 'super', inertia: true },
];

// Mirrors the role checks in layouts/admin.blade.php.
export function canAccess(perm, role) {
    if (!perm) return true;
    const superAdmin = role === 'super_admin';
    switch (perm) {
        case 'super': return superAdmin;
        case 'orders':
        case 'users':
        case 'reports': return superAdmin || role === 'manager';
        case 'content': return superAdmin || role === 'editor';
        default: return false;
    }
}

export function visibleModules(role, features) {
    return modules.filter((m) => canAccess(m.perm, role) && (!m.feature || features?.[m.feature]));
}
