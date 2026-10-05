import '../css/storefront.css';
import { createInertiaApp } from '@inertiajs/react';
import { createRoot, hydrateRoot } from 'react-dom/client';
import { pageTitle, resolveStorefrontPage } from './Storefront/app';

// The shop (React storefront). Pages arrive server-rendered when the SSR server runs; React then takes over (hydrates).
createInertiaApp({
    title: pageTitle,
    resolve: resolveStorefrontPage,
    setup({ el, App, props }) {
        if (el.hasChildNodes()) {
            hydrateRoot(el, <App {...props} />);
        } else {
            createRoot(el).render(<App {...props} />);
        }
    },
    progress: { color: 'var(--sf-primary-600)' },
});
