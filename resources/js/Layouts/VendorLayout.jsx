import { usePage } from '@inertiajs/react';
import { Boxes, CreditCard, LayoutDashboard, Package, Settings, ShoppingCart, Wallet } from 'lucide-react';
import PortalLayout from './PortalLayout';
import { Badge } from '../Components/ui';

const STATUS_TONE = { approved: 'success', pending: 'warning', suspended: 'danger', rejected: 'danger' };

/** Vendor portal frame. Until the store is approved only the dashboard is reachable (VendorMiddleware). */
export default function VendorLayout({ title, children, actions }) {
    const { vendor } = usePage().props;
    const approved = vendor?.status === 'approved';
    const nav = [
        { label: 'Dashboard', href: '/vendor', icon: LayoutDashboard, exact: true },
        ...(approved ? [
            { label: 'Products', href: '/vendor/products', icon: Package },
            { label: 'Orders', href: '/vendor/orders', icon: ShoppingCart, count: vendor?.pending_orders },
            { label: 'Stock', href: '/vendor/stock', icon: Boxes },
            { label: 'Payouts', href: '/vendor/payouts', icon: Wallet },
            { label: 'Store settings', href: '/vendor/settings', icon: Settings },
            { label: 'Payout details', href: '/vendor/payment-settings', icon: CreditCard },
        ] : []),
    ];

    return (
        <PortalLayout title={title} nav={nav} home="/vendor" badge={vendor?.store_name} actions={actions}
            subtitle={vendor ? <span className="inline-flex items-center gap-1">Vendor <Badge tone={STATUS_TONE[vendor.status] ?? 'muted'}>{vendor.status}</Badge></span> : 'Vendor'}>
            {children}
        </PortalLayout>
    );
}
