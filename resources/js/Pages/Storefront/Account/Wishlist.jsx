import { router } from '@inertiajs/react';
import { Heart, ShoppingBag, X } from 'lucide-react';
import AccountLayout from '../../../Storefront/Account/AccountLayout';
import ProductCard from '../../../Storefront/Product/ProductCard';
import { Pagination } from '../../../Storefront/Shop/Listing';
import SfLink from '../../../Storefront/lib/SfLink';
import { useT } from '../../../Storefront/lib/store';
import { useThemed } from '../../../Storefront/theme/registry';

export default function Wishlist({ items, seo }) {
    const t = useT();
    const Card = useThemed('ProductCard', ProductCard);
    const list = items.data.filter(Boolean); // products deleted since they were saved are skipped
    const remove = (p) => router.delete('/account/wishlist', { data: { product_id: p.id }, preserveScroll: true });

    return (
        <AccountLayout title={t('my_wishlist', 'My Wishlist')} seo={seo}>
            {list.length > 0 ? (
                <>
                    <div className="grid auto-rows-fr grid-cols-2 gap-4 md:grid-cols-3 md:gap-6">
                        {list.map((p) => (
                            <div key={p.id} className="relative">
                                <Card product={p} />
                                <button type="button" onClick={() => remove(p)} aria-label={`${t('remove_from_wishlist', 'Remove from wishlist')}: ${p.name}`} title={t('remove_from_wishlist', 'Remove from wishlist')}
                                    className="absolute end-2 top-2 z-20 flex h-8 w-8 items-center justify-center rounded-full bg-white text-red-500 shadow hover:bg-red-50"><X size={16} /></button>
                            </div>
                        ))}
                    </div>
                    <Pagination products={items} />
                </>
            ) : (
                <div className="rounded-xl bg-white p-12 text-center shadow-sm">
                    <span className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-red-50 text-red-400"><Heart size={26} /></span>
                    <h2 className="mb-2 text-lg font-semibold">{t('wishlist_empty', 'Your wishlist is empty')}</h2>
                    <p className="mb-6 text-gray-500">{t('wishlist_empty_text', 'Save items you love to find them later.')}</p>
                    <SfLink href="/shop" className="sf-btn inline-flex items-center gap-2 rounded-lg bg-primary-600 px-5 py-2.5 text-white hover:bg-primary-700"><ShoppingBag size={16} />{t('start_shopping', 'Start Shopping')}</SfLink>
                </div>
            )}
        </AccountLayout>
    );
}
