import { CheckCircle2, Eye, Heart, ImageIcon, ShoppingCart } from 'lucide-react';
import SfLink from '../lib/SfLink';
import { useAddToCart, useAddToWishlist } from '../lib/cart';
import { useStore, useT } from '../lib/store';
import Stars from './Stars';

/** Product row for the shop's list view. */
export default function ProductCardList({ product: p }) {
    const { customer, features } = useStore();
    const t = useT();
    const addToCart = useAddToCart();
    const addToWishlist = useAddToWishlist();

    return (
        <div className="sf-card group overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition-all duration-300 hover:border-primary-200 hover:shadow-lg">
            <div className="flex flex-col sm:flex-row">
                <div className="relative aspect-square w-full shrink-0 bg-gradient-to-br from-gray-50 to-gray-100 sm:aspect-auto sm:w-48 md:w-56">
                    <SfLink href={p.url} className="block h-full w-full" tabIndex={-1} aria-hidden="true">
                        {p.image
                            ? <img src={p.image} alt="" loading="lazy" className="h-full w-full object-contain p-4 transition-transform duration-500 group-hover:scale-105" />
                            : <span className="flex h-full min-h-[200px] w-full items-center justify-center text-gray-200"><ImageIcon size={40} /></span>}
                    </SfLink>
                    <div className="absolute start-3 top-3 flex flex-col gap-1.5">
                        {p.discount > 0 && <span className="rounded-lg bg-gradient-to-r from-rose-500 to-pink-500 px-2 py-1 text-xs font-bold text-white shadow">-{p.discount}%</span>}
                        {p.isNew && <span className="rounded-lg bg-gradient-to-r from-emerald-500 to-teal-500 px-2 py-1 text-xs font-bold text-white shadow">{t('new', 'NEW')}</span>}
                    </div>
                </div>
                <div className="flex flex-1 flex-col justify-between p-5">
                    <div>
                        {p.category && <SfLink href={p.category.url} className="mb-2 inline-block text-xs font-semibold tracking-wider text-primary-600 uppercase hover:text-primary-700">{p.category.name}</SfLink>}
                        <h3 className="mb-2"><SfLink href={p.url} className="text-lg font-semibold text-gray-900 hover:text-primary-600">{p.name}</SfLink></h3>
                        {p.short && <p className="mb-3 line-clamp-2 text-sm text-gray-500">{p.short}</p>}
                        <div className="mb-3 flex items-center gap-2">
                            <Stars rating={p.rating} size={14} />
                            <span className="text-sm text-gray-500">{p.reviews > 0 ? `(${p.reviews} ${t('reviews', 'reviews')})` : t('no_reviews', 'No reviews yet')}</span>
                        </div>
                    </div>
                    <div className="flex flex-col gap-4 border-t border-gray-100 pt-3 sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex flex-wrap items-baseline gap-3">
                            <span className={p.regular ? 'text-2xl font-bold text-primary-600' : 'text-2xl font-bold text-gray-900'}>{p.price}</span>
                            {p.regular && <span className="text-base text-gray-400 line-through">{p.regular}</span>}
                            {p.inStock
                                ? <span className="flex items-center gap-1 text-xs font-medium text-emerald-600"><CheckCircle2 size={12} />{t('in_stock', 'In Stock')}</span>
                                : <span className="rounded-lg bg-rose-50 px-2 py-1 text-xs font-semibold text-rose-600">{t('out_of_stock', 'Out of Stock')}</span>}
                        </div>
                        <div className="flex items-center gap-2">
                            <button type="button" onClick={() => addToCart(p)} className="sf-btn flex items-center gap-2 rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-primary-600/20 hover:bg-primary-700">
                                <ShoppingCart size={15} />{p.variable ? t('select_options', 'Select options') : t('add_to_cart', 'Add to Cart')}
                            </button>
                            <SfLink href={p.url} aria-label={`${t('view_details', 'View details')}: ${p.name}`} className="flex h-10 w-10 items-center justify-center rounded-xl bg-gray-100 text-gray-600 hover:bg-gray-200"><Eye size={16} /></SfLink>
                            {customer && features.wishlist && (
                                <button type="button" onClick={() => addToWishlist(p)} aria-label={`${t('add_to_wishlist', 'Add to wishlist')}: ${p.name}`} className="flex h-10 w-10 items-center justify-center rounded-xl bg-gray-100 text-gray-600 hover:bg-rose-100 hover:text-rose-500"><Heart size={16} /></button>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
