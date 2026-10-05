import { Box, MapPin, PackageOpen, Star, Store } from 'lucide-react';
import Seo from '../../Storefront/Layout/Seo';
import { SocialIcon } from '../../Storefront/Footer/Footer';
import ProductCard from '../../Storefront/Product/ProductCard';
import { Pagination } from '../../Storefront/Shop/Listing';
import SfLink from '../../Storefront/lib/SfLink';
import { useT } from '../../Storefront/lib/store';
import { useThemed } from '../../Storefront/theme/registry';

export function VendorBanner({ vendor, total }) {
    const t = useT();
    return (
        <section className="relative overflow-hidden bg-gradient-to-r from-gray-900 to-gray-800">
            {vendor.banner && <img src={vendor.banner} alt="" className="absolute inset-0 h-full w-full object-cover opacity-30" />}
            <div className="sf-container relative py-12 md:py-16">
                <div className="flex flex-col items-center gap-6 md:flex-row md:gap-8">
                    <span className="flex h-28 w-28 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white text-gray-400 shadow-lg md:h-32 md:w-32">
                        {vendor.logo ? <img src={vendor.logo} alt={vendor.name} className="h-full w-full object-cover" /> : <Store size={40} />}
                    </span>
                    <div className="min-w-0 flex-1 text-center text-white md:text-start">
                        <h1 className="mb-2 text-3xl font-bold md:text-4xl">{vendor.name}</h1>
                        {vendor.description && <p className="mb-4 max-w-2xl text-gray-300">{vendor.description}</p>}
                        <p className="flex flex-wrap items-center justify-center gap-x-4 gap-y-1 text-sm text-gray-200 md:justify-start">
                            {vendor.rating > 0 && (
                                <span className="flex items-center gap-1">
                                    <span className="flex text-yellow-400" aria-hidden="true">{[1, 2, 3, 4, 5].map((n) => <Star key={n} size={14} className={n <= Math.round(vendor.rating) ? 'fill-current' : 'opacity-30'} />)}</span>
                                    {vendor.rating.toFixed(1)} ({vendor.ratingCount} {vendor.ratingCount === 1 ? t('review', 'review') : t('reviews', 'reviews')})
                                </span>
                            )}
                            <span className="flex items-center gap-1"><Box size={14} />{total} {total === 1 ? t('product', 'product') : t('products', 'products')}</span>
                            {vendor.location && <span className="flex items-center gap-1"><MapPin size={14} />{vendor.location}</span>}
                        </p>
                    </div>
                    {Object.keys(vendor.social).length > 0 && (
                        <div className="flex gap-2">
                            {Object.entries(vendor.social).map(([k, url]) => (
                                <a key={k} href={url} target="_blank" rel="noopener noreferrer nofollow" aria-label={`${vendor.name} on ${k}`}
                                    className="flex h-10 w-10 items-center justify-center rounded-lg bg-white/10 text-white transition hover:bg-white/20"><SocialIcon network={k} size={16} /></a>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </section>
    );
}

/** Vendor store page. Blocks can be replaced per theme as "Vendor.Banner" (product cards use "ProductCard"). */
export default function Vendor({ vendor, products, seo }) {
    const t = useT();
    const Banner = useThemed('Vendor.Banner', VendorBanner);
    const Card = useThemed('ProductCard', ProductCard);

    return (
        <>
            <Seo title={seo.title} description={seo.description} image={seo.image} canonical={seo.canonical} />
            <Banner vendor={vendor} total={products.total} />
            <div className="sf-container py-10 md:py-12">
                <h2 className="mb-6 text-2xl font-bold text-gray-800">{t('products', 'Products')}</h2>
                {products.data.length > 0 ? (
                    <>
                        <div className="grid auto-rows-fr grid-cols-2 gap-4 md:grid-cols-3 md:gap-6 lg:grid-cols-4">{products.data.map((p) => <Card key={p.id} product={p} />)}</div>
                        <Pagination products={products} />
                    </>
                ) : (
                    <div className="rounded-xl bg-white p-12 text-center shadow-sm">
                        <PackageOpen size={56} className="mx-auto mb-4 text-gray-300" />
                        <p className="text-lg text-gray-500">{vendor.name} {t('no_vendor_products', "hasn't added any products yet.")}</p>
                        <SfLink href="/shop" className="sf-btn mt-6 inline-flex items-center rounded-lg bg-primary-600 px-6 py-3 text-white hover:bg-primary-700">{t('browse_shop', 'Browse the shop')}</SfLink>
                    </div>
                )}
            </div>
        </>
    );
}
