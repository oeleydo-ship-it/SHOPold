import { createContext, useContext } from 'react';
import { usePage } from '@inertiajs/react';

/** Shared storefront data (store, locale, currency, theme, nav, footer...) from Storefront::shared(). */
export function useStore() {
    return usePage().props.sf;
}

/** t('messages.key') style lookup in lang/<locale>/messages.php; falls back to the given text, then the key. */
export function useT() {
    const { lang } = useStore();
    return (key, fallback) => {
        const value = lang?.[key];
        return typeof value === 'string' && value !== '' ? value : (fallback ?? key);
    };
}

/** Cart count + toast helpers provided by StoreLayout. */
export const ShopContext = createContext({ cartCount: 0, setCartCount: () => {}, toast: () => {} });
export const useShop = () => useContext(ShopContext);

/** Join class names, skipping falsy values. */
export const cx = (...parts) => parts.filter(Boolean).join(' ');

/** Only plain web links are rendered as links (stored settings could hold "javascript:" etc.). */
export const safeUrl = (url, fallback = '#') => {
    const u = String(url ?? '').trim();
    if (u.startsWith('/') && !u.startsWith('//')) return u;
    return /^https?:\/\//i.test(u) ? u : fallback;
};
