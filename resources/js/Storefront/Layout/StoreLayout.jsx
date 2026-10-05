import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { CheckCircle2, Info, TriangleAlert, X, XCircle } from 'lucide-react';
import Header from '../Header/Header';
import Footer from '../Footer/Footer';
import { ShopContext, cx } from '../lib/store';
import { useThemed } from '../theme/registry';

const TOAST = {
    success: ['bg-green-600', CheckCircle2],
    error: ['bg-red-600', XCircle],
    warning: ['bg-amber-500', TriangleAlert],
    info: ['bg-blue-600', Info],
};

/** Persistent storefront frame: header, page, footer, toasts and the live cart count. */
export default function StoreLayout({ children }) {
    const { props } = usePage();
    const { sf, flash } = props;
    const [cartCount, setCartCount] = useState(sf.cart.count);
    const [toastState, setToast] = useState(null);
    const timer = useRef(null);

    // Server data wins after every visit (e.g. the cart page changed the count).
    useEffect(() => setCartCount(sf.cart.count), [sf.cart.count]);
    // Keep the token that JSON/form posts read in step with the session (it changes on login and logout).
    useEffect(() => {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta && props.csrf) meta.setAttribute('content', props.csrf);
    }, [props.csrf]);

    const toast = useCallback((type, message) => {
        if (!message) return;
        setToast({ type, message, id: Date.now() });
        clearTimeout(timer.current);
        timer.current = setTimeout(() => setToast(null), 4000);
    }, []);

    useEffect(() => {
        const f = ['success', 'error', 'warning', 'info'].find((k) => flash?.[k]);
        if (f) toast(f, flash[f]);
    }, [flash?.success, flash?.error, flash?.warning, flash?.info, toast]);

    const ThemedHeader = useThemed('Header', Header);
    const ThemedFooter = useThemed('Footer', Footer);
    const ctx = useMemo(() => ({ cartCount, setCartCount, toast }), [cartCount, toast]);
    const [bg, Icon] = TOAST[toastState?.type] ?? TOAST.info;

    return (
        <ShopContext.Provider value={ctx}>
            <div className={cx('flex min-h-screen flex-col', `sf-theme-${sf.theme.slug}`)}>
                <ThemedHeader />
                <main className="flex-1">{children}</main>
                <ThemedFooter />
            </div>
            {toastState && (
                <div key={toastState.id} role="status" aria-live="polite"
                    className={cx('fixed top-4 left-1/2 z-[100] flex max-w-[calc(100vw-2rem)] -translate-x-1/2 items-center gap-3 rounded-lg px-5 py-3 text-white shadow-lg', bg)}>
                    <Icon size={18} className="shrink-0" />
                    <span>{toastState.message}</span>
                    <button type="button" onClick={() => setToast(null)} aria-label="Dismiss" className="opacity-80 hover:opacity-100"><X size={16} /></button>
                </div>
            )}
        </ShopContext.Provider>
    );
}
