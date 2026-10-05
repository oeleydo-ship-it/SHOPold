import { createInertiaApp } from '@inertiajs/react';
import { pageTitle, resolveStorefrontPage } from './Storefront/app';

// Server-side rendering of storefront pages. In development the Vite dev server serves it (via @inertiajs/vite);
// in production `npm run build` bundles it to bootstrap/ssr and `php artisan inertia:start-ssr` runs it.
createInertiaApp({
    title: pageTitle,
    resolve: resolveStorefrontPage,
    setup: ({ App, props }) => <App {...props} />,
});
