import { Link } from '@inertiajs/react';

/**
 * Storefront pages that already have a React version (extend this list as S3-S6 convert more pages).
 * Links to these navigate without a full reload; anything else (still Blade) gets a normal link.
 */
const REACT_PAGES = [
    /^\/$/,
    /^\/shop\/?$/,
    /^\/shop\/category\/[^/]+\/?$/,
    /^\/shop\/(?!category\/|vendor\/)[^/]+\/?$/, // product pages (S3)
    /^\/cart\/?$/, // S4
    /^\/checkout\/?$/,
    /^\/(login|register|email\/verify)\/?$/, // S5
    /^\/account(\/(?!orders\/[^/]+\/invoice).*)?$/, // invoices stay printable Blade pages
    /^\/helpdesk(\/.*)?$/,
    /^\/blog(\/.*)?$/, // S6
    /^\/(about|contact)\/?$/,
    /^\/page\/[^/]+\/?$/,
    /^\/shop\/vendor\/[^/]+\/?$/,
];

export function isReactPage(href) {
    if (typeof href !== 'string' || !href.startsWith('/') || href.startsWith('//')) return false;
    const path = href.split(/[?#]/)[0];
    return REACT_PAGES.some((re) => re.test(path));
}

/** <a> for Blade pages and external links, Inertia <Link> for React pages. */
export default function SfLink({ href, children, ...rest }) {
    if (isReactPage(href)) {
        return <Link href={href} {...rest}>{children}</Link>;
    }
    return <a href={href} {...rest}>{children}</a>;
}
