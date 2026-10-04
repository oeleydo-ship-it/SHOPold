import '../css/app.css';
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';

createInertiaApp({
    // "Orders - Vendor", "Deliveries - Driver", "Products - Admin"
    title: (title) => {
        const area = /^\/(vendor|driver)(\/|$)/.exec(window.location.pathname)?.[1];
        const suffix = area ? area[0].toUpperCase() + area.slice(1) : 'Admin';
        return title ? `${title} - ${suffix}` : suffix;
    },
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.jsx');
        return pages[`./Pages/${name}.jsx`]();
    },
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
});
