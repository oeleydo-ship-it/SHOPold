import StoreLayout from './Layout/StoreLayout';

/** Page titles are composed by <Seo> (it knows the store name), so Inertia's title callback passes them through. */
export const pageTitle = (title) => title;

const pages = import.meta.glob('../Pages/Storefront/**/*.jsx');

/** "Storefront/Home" -> Pages/Storefront/Home.jsx, wrapped in the persistent store layout (header state survives navigation). */
export async function resolveStorefrontPage(name) {
    const loader = pages[`../Pages/${name}.jsx`];
    if (!loader) {
        throw new Error(`Storefront page not found: ${name}`);
    }
    const page = await loader();
    page.default.layout ??= (content) => <StoreLayout>{content}</StoreLayout>;

    return page;
}
