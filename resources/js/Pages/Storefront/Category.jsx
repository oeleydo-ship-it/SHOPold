import { Link } from '@inertiajs/react';
import Seo from '../../Storefront/Layout/Seo';
import { Breadcrumb, ProductGrid } from '../../Storefront/Shop/Listing';
import { useStore, useT } from '../../Storefront/lib/store';
import { useThemed } from '../../Storefront/theme/registry';

export function CategoryBanner({ category }) {
    return (
        <div className="mb-8 rounded-2xl bg-gradient-to-r from-primary-600 to-primary-800 p-8 text-white">
            <h1 className="mb-2 text-3xl font-bold">{category.name}</h1>
            {category.description && <p className="text-white/80">{category.description}</p>}
        </div>
    );
}

export function OtherCategories({ category, categories }) {
    const t = useT();
    const others = categories.filter((c) => c.id !== category.id && c.count > 0).slice(0, 12);
    if (others.length === 0) return null;
    return (
        <nav className="mb-8 flex flex-wrap gap-2" aria-label={t('categories', 'Categories')}>
            {others.map((c) => (
                <Link key={c.id} href={`/shop/category/${c.slug}`} className="inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-white px-4 py-2 text-sm text-gray-700 hover:border-primary-500 hover:text-primary-600">
                    {c.name}<span className="text-xs text-gray-400">{c.count}</span>
                </Link>
            ))}
        </nav>
    );
}

/** Category page. Blocks can be replaced per theme as "Category.<Block>". */
export default function Category({ category, products, categories, seo }) {
    const t = useT();
    const { store } = useStore();
    const Banner = useThemed('Category.Banner', CategoryBanner);
    const Others = useThemed('Category.OtherCategories', OtherCategories);

    return (
        <>
            <Seo title={seo.title} description={seo.description} image={seo.image} canonical={seo.canonical} jsonLd={{
                '@context': 'https://schema.org', '@type': 'BreadcrumbList', itemListElement: [
                    { '@type': 'ListItem', position: 1, name: store.name, item: seo.canonical.replace(/\/shop\/category\/.*$/, '/') },
                    { '@type': 'ListItem', position: 2, name: t('shop', 'Shop'), item: seo.canonical.replace(/\/category\/.*$/, '') },
                    { '@type': 'ListItem', position: 3, name: category.name, item: seo.canonical },
                ],
            }} />
            <div className="category-page sf-container py-8">
                <Breadcrumb items={[{ label: t('shop', 'Shop'), href: '/shop' }, { label: category.name }]} />
                <div className="mt-6"><Banner category={category} /></div>
                <Others category={category} categories={categories} />
                <ProductGrid products={products} />
            </div>
        </>
    );
}
