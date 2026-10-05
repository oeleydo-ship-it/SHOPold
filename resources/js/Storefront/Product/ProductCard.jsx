import { CheckCircle2, Eye, Heart, ImageIcon, ShoppingCart, Star } from 'lucide-react';
import SfLink from '../lib/SfLink';
import { useAddToCart, useAddToWishlist } from '../lib/cart';
import { cx, useStore, useT } from '../lib/store';
import Stars from './Stars';

const RATIO = { portrait: 'aspect-[3/4]', landscape: 'aspect-[4/3]' };
const CLAMP = { 1: 'line-clamp-1', 2: 'line-clamp-2', 3: 'line-clamp-3', 4: 'line-clamp-4' };
const SM_CLAMP = { 1: 'sm:line-clamp-1', 2: 'sm:line-clamp-2', 3: 'sm:line-clamp-3', 4: 'sm:line-clamp-4' };

const cut = (text, limit) => (limit > 0 && text.length > limit ? `${text.slice(0, limit)}...` : text);

/**
 * Product card for grids. Sizes and what to hide on phones come from Settings > Products (shared "card" prop);
 * they are applied as CSS variables so one stylesheet rule serves every card.
 */
export default function ProductCard({ product: p }) {
    const { card: c, customer, features } = useStore();
    const t = useT();
    const addToCart = useAddToCart();
    const addToWishlist = useAddToWishlist();
    const lines = Math.min(4, Math.max(1, c.mobileTitleLines || 2));
    const dLines = Math.min(4, Math.max(1, c.desktopTitleLines || 2));
    const vars = {
        '--pc-title': `${c.mobileTitlePx}px`, '--pc-title-d': `${c.desktopTitlePx}px`,
        '--pc-price': `${c.mobilePricePx}px`, '--pc-price-d': `${c.desktopPricePx}px`,
        '--pc-cat': `${c.mobileCategoryPx}px`,
        '--pc-pad': `${Math.max(c.mobilePaddingPx, 12)}px`,
        '--pc-title-h': `${c.mobileTitlePx * 1.375 * lines}px`, '--pc-title-h-d': `${c.desktopTitlePx * 1.375 * dLines}px`,
    };

    return (
        <div style={vars} className="sf-card group relative flex h-full min-w-0 flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white transition-all duration-300 hover:border-primary-300 hover:shadow-md">
            <div className={cx('sf-card-media relative shrink-0 overflow-hidden bg-white sm:aspect-square', RATIO[c.mobileImageRatio] ?? 'aspect-square')}>
                <SfLink href={p.url} className="block h-full w-full" tabIndex={-1} aria-hidden="true">
                    {p.image
                        ? <img src={p.image} alt="" loading="lazy" width="300" height="300" className="h-full w-full object-contain p-4 transition-transform duration-500 ease-out group-hover:scale-105" />
                        : <span className="flex h-full w-full items-center justify-center text-gray-200"><ImageIcon size={48} /></span>}
                </SfLink>

                <div className="absolute start-3 top-3 z-10 flex flex-col gap-1.5">
                    {p.discount > 0 && <span className="sf-card-badge sf-card-sale rounded-md bg-red-500 px-2 py-1 text-[10px] font-semibold text-white sm:text-xs">-{p.discount}%</span>}
                    {p.isNew && <span className="sf-card-badge rounded-md bg-primary-600 px-2 py-1 text-[10px] font-semibold text-white sm:text-xs">{t('new', 'NEW')}</span>}
                </div>
                {p.featured && <span className="sf-card-badge absolute end-3 top-3 z-10 flex h-7 w-7 items-center justify-center rounded-md bg-amber-500 text-white" title={t('featured', 'Featured')}><Star size={11} fill="currentColor" strokeWidth={0} /></span>}

                <div className="sf-card-actions absolute inset-x-0 bottom-0 hidden translate-y-2 border-t border-gray-100 bg-white/90 p-3 opacity-0 transition-all duration-200 group-hover:translate-y-0 group-hover:opacity-100 focus-within:translate-y-0 focus-within:opacity-100 sm:block">
                    <div className="sf-card-actions-row flex items-center justify-center gap-2">
                        <button type="button" onClick={() => addToCart(p)} title={t('add_to_cart', 'Add to Cart')} aria-label={`${t('add_to_cart', 'Add to Cart')}: ${p.name}`}
                            className="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 hover:border-primary-600 hover:bg-primary-600 hover:text-white"><ShoppingCart size={15} /></button>
                        <SfLink href={p.url} title={t('view_details', 'View details')} aria-label={`${t('view_details', 'View details')}: ${p.name}`}
                            className="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 hover:border-gray-800 hover:bg-gray-800 hover:text-white"><Eye size={15} /></SfLink>
                        {customer && features.wishlist && (
                            <button type="button" onClick={() => addToWishlist(p)} title={t('add_to_wishlist', 'Add to wishlist')} aria-label={`${t('add_to_wishlist', 'Add to wishlist')}: ${p.name}`}
                                className="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 hover:border-rose-500 hover:bg-rose-500 hover:text-white"><Heart size={15} /></button>
                        )}
                    </div>
                </div>
            </div>

            <div className="flex flex-col overflow-hidden px-[var(--pc-pad)] pt-[var(--pc-pad)] pb-[max(var(--pc-pad),14px)] sm:grow sm:px-6 sm:pt-5 sm:pb-4">
                {p.category && (
                    <SfLink href={p.category.url} className={cx('mb-2 truncate text-[length:var(--pc-cat)] font-semibold tracking-wider text-primary-600 uppercase hover:text-primary-700 sm:inline-block sm:text-xs', c.mobileHideCategory ? 'hidden' : 'inline-block')}>
                        {p.category.name}
                    </SfLink>
                )}
                <h3 className="mb-1 min-h-[var(--pc-title-h)] min-w-0 sm:mb-2 sm:min-h-[var(--pc-title-h-d)]">
                    <SfLink href={p.url} title={p.name} className={cx('block text-[length:var(--pc-title)] leading-snug font-semibold text-gray-900 hover:text-primary-600 sm:text-[length:var(--pc-title-d)]', CLAMP[lines], SM_CLAMP[dLines])}>
                        <span className="sm:hidden">{cut(p.name, c.mobileTitleCharLimit)}</span>
                        <span className="hidden sm:inline">{cut(p.name, c.titleCharLimit)}</span>
                    </SfLink>
                </h3>

                <div className={cx('mb-1 items-center gap-1.5', c.mobileHideRating || p.reviews === 0 ? 'hidden sm:flex' : 'flex')}>
                    {p.reviews > 0 ? (
                        <><Stars rating={p.rating} /><span className="text-[10px] text-gray-500 sm:text-xs">({p.reviews})</span></>
                    ) : (
                        <><Stars rating={0} empty="text-gray-200" /><span className="text-xs text-gray-400">{t('no_reviews', 'No reviews')}</span></>
                    )}
                </div>

                <div className="mt-1 min-w-0 sm:mt-auto">
                    <div className="flex items-end justify-between gap-2">
                        <div className="min-w-0 flex-1">
                            <div className="flex flex-wrap items-baseline gap-1 sm:gap-2">
                                <span className={cx('sf-card-price text-[length:var(--pc-price)] font-bold whitespace-nowrap sm:text-[length:var(--pc-price-d)]', p.regular ? 'text-primary-600' : 'text-gray-900')}>{p.price}</span>
                                {p.regular && <span className="text-[10px] whitespace-nowrap text-gray-400 line-through sm:text-sm">{p.regular}</span>}
                            </div>
                        </div>
                        {!c.mobileHideStock && (p.inStock
                            ? <span className="hidden shrink-0 items-center gap-1 text-xs font-medium whitespace-nowrap text-emerald-600 sm:inline-flex"><CheckCircle2 size={12} />{t('in_stock', 'In Stock')}</span>
                            : <span className="shrink-0 rounded-lg bg-rose-50 px-2 py-1 text-[10px] font-semibold whitespace-nowrap text-rose-600 sm:text-xs">{t('out_of_stock', 'Out of Stock')}</span>)}
                    </div>
                    {c.mobileShowCartBtn && (
                        <button type="button" onClick={() => addToCart(p)} className="sf-btn sf-card-cta mt-4 flex w-full items-center justify-center gap-2 rounded-lg bg-primary-600 py-2.5 text-sm font-semibold text-white hover:bg-primary-700 active:scale-[0.98] sm:hidden">
                            <ShoppingCart size={15} />{p.variable ? t('select_options', 'Select options') : t('add_to_cart', 'Add to Cart')}
                        </button>
                    )}
                </div>
            </div>
        </div>
    );
}
