import { useEffect, useState } from 'react';
import { Sparkles } from 'lucide-react';
import Seo from '../../Storefront/Layout/Seo';
import {
    Breadcrumb, Gallery, Info, MobileBar, ProductRowSection, Reviews, Tabs, useVariationChoice,
} from '../../Storefront/Product/Detail';
import { useStore, useT } from '../../Storefront/lib/store';
import { useThemed } from '../../Storefront/theme/registry';

/** Product page. Blocks can be replaced per theme as "Product.<Block>". */
export default function Product({ product, options, reviews, aiReviewAnalysis, related, recommended, crossSell, crossSellTitle, seo, aiProduct }) {
    const t = useT();
    const { currency } = useStore();
    const choice = useVariationChoice(product);
    const [quantity, setQuantity] = useState(1);
    useEffect(() => setQuantity(1), [product.id, choice.variation?.id]);
    // The AI chat widget reads the product being viewed.
    useEffect(() => { window.aiCurrentProduct = aiProduct; return () => { window.aiCurrentProduct = undefined; }; }, [aiProduct]);

    const B = {
        Breadcrumb: useThemed('Product.Breadcrumb', Breadcrumb),
        Gallery: useThemed('Product.Gallery', Gallery),
        Info: useThemed('Product.Info', Info),
        Tabs: useThemed('Product.Tabs', Tabs),
        Reviews: useThemed('Product.Reviews', Reviews),
        Row: useThemed('Product.Row', ProductRowSection),
        MobileBar: useThemed('Product.MobileBar', MobileBar),
    };

    const jsonLd = {
        '@context': 'https://schema.org',
        '@type': 'Product',
        name: product.name,
        image: product.images,
        description: seo.description,
        sku: product.sku || undefined,
        brand: product.brand ? { '@type': 'Brand', name: product.brand.name } : undefined,
        category: product.category?.name,
        offers: {
            '@type': 'Offer',
            url: product.url,
            priceCurrency: currency.current,
            price: product.priceValue,
            availability: product.inStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        },
        aggregateRating: product.reviews > 0 ? { '@type': 'AggregateRating', ratingValue: product.rating, reviewCount: product.reviews } : undefined,
    };

    return (
        <>
            <Seo title={seo.title} description={seo.description} image={seo.image} canonical={seo.canonical} jsonLd={jsonLd} />
            <div className="product-page sf-container py-8 pb-32 md:pb-8">
                <B.Breadcrumb product={product} />
                <div className="overflow-hidden rounded-2xl bg-white shadow-sm">
                    <div className="grid gap-8 p-6 md:grid-cols-2 lg:p-8">
                        <B.Gallery product={product} variation={choice.variation} />
                        <B.Info product={product} options={options} choice={choice} quantity={quantity} setQuantity={setQuantity} />
                    </div>
                    <B.Tabs product={product} />
                </div>
                {options.reviewsEnabled && <B.Reviews product={product} reviews={reviews} analysis={aiReviewAnalysis} />}
                <B.Row title={crossSellTitle || t('frequently_bought_together', 'Frequently Bought Together')} products={crossSell} />
                <B.Row title={t('related_products', 'Related Products')} products={related} />
                <B.Row title={t('recommended_for_you', 'Recommended For You')} products={recommended}
                    badge={<span className="flex items-center gap-1 rounded-full bg-purple-100 px-2 py-1 text-xs text-purple-700"><Sparkles size={12} />AI Powered</span>} />
            </div>
            <B.MobileBar product={product} options={options} variation={choice.variation} quantity={quantity} setQuantity={setQuantity} />
        </>
    );
}
