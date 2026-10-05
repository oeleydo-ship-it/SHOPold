import { useEffect, useRef, useState } from 'react';
import {
    ArrowRight, Bell, CalendarDays, CheckCircle2, ChevronLeft, ChevronRight, Clock, Eye, Flame, Folder, Gift, Heart,
    LayoutGrid, Loader2, Mail, Newspaper, Percent, ShoppingCart, Sparkles, Star, Tag, Zap,
} from 'lucide-react';
import SfLink from '../lib/SfLink';
import { postJson } from '../lib/http';
import { settingIcon } from '../lib/icons';
import { useAddToCart, useAddToWishlist } from '../lib/cart';
import { cx, safeUrl, useShop, useStore, useT } from '../lib/store';
import { useThemed } from '../theme/registry';
import ProductCard from '../Product/ProductCard';
import Stars from '../Product/Stars';

const on = (v, fallback = true) => (v === undefined || v === null || v === '' ? fallback : !['0', 'false', false, 0].includes(v));
const bg = (color, fallback) => (color || fallback ? { backgroundColor: color || fallback } : undefined);

function SectionHeading({ label, labelClass, icon: Icon, title, children }) {
    return (
        <div className="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between md:mb-12">
            <div>
                <span className={cx('mb-2 inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold tracking-wider uppercase', labelClass)}><Icon size={12} />{label}</span>
                <h2 className="text-2xl font-bold text-gray-900 md:text-3xl lg:text-4xl">{title}</h2>
            </div>
            {children}
        </div>
    );
}

function OutlineLink({ href, children, className = 'hidden md:inline-flex' }) {
    return (
        <SfLink href={href} className={cx('sf-btn group items-center gap-2 rounded-xl border-2 border-primary-600 px-6 py-3 font-semibold text-primary-600 transition-all duration-300 hover:bg-primary-600 hover:text-white', className)}>
            {children}<ArrowRight size={16} className="transition-transform group-hover:translate-x-1 rtl:rotate-180" />
        </SfLink>
    );
}

export function Features({ home }) {
    const t = useT();
    if (!on(home.show_features)) return null;
    const defaults = [
        ['truck', t('free_shipping', 'Free Shipping'), `${t('on_orders_over', 'On orders over')} $50`],
        ['undo', t('easy_returns', 'Easy Returns'), `30-${t('day_return_policy', 'day return policy')}`],
        ['shield-alt', t('secure_payment', 'Secure Payment'), t('secure_checkout', '100% secure checkout')],
        ['headset', t('support_24_7', '24/7 Support'), t('dedicated_support', 'Dedicated support')],
    ];
    return (
        <section className="sf-features border-b border-gray-100 py-8 md:py-12" style={bg(home.features_bg_color)}>
            <div className="sf-container grid grid-cols-2 gap-4 md:gap-6 lg:grid-cols-4 lg:gap-8">
                {defaults.map(([icon, title, desc], i) => {
                    const n = i + 1;
                    const Icon = settingIcon(home[`feature_${n}_icon`] || icon);
                    return (
                        <div key={n} className="group rounded-2xl p-4 text-center md:p-6">
                            <span className="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-xl bg-primary-600 shadow-sm transition-colors group-hover:bg-primary-700 md:mb-4 md:h-16 md:w-16"><Icon size={22} className="text-white" /></span>
                            <h3 className="mb-1 text-sm font-semibold text-gray-900 md:text-base">{home[`feature_${n}_title`] || title}</h3>
                            <p className="text-xs leading-relaxed text-gray-500 md:text-sm">{home[`feature_${n}_desc`] || desc}</p>
                        </div>
                    );
                })}
            </div>
        </section>
    );
}

export function Categories({ home, categories }) {
    const t = useT();
    if (!on(home.show_categories) || categories.length === 0) return null;
    const size = Math.max(8, Math.min(30, parseInt(home.categories_item_font_size, 10) || 12));
    const sizeMd = Math.max(8, Math.min(36, parseInt(home.categories_item_font_size_md, 10) || 14));
    const weight = String(home.categories_item_font_weight || '500').replace(/[^0-9]/g, '') || '500';
    const css = `.sf-cat-label{font-size:${size}px;font-weight:${weight};color:${home.categories_item_text_color || '#1f2937'}}`
        + `@media(min-width:768px){.sf-cat-label{font-size:${sizeMd}px}}.group:hover .sf-cat-label{color:${home.categories_item_hover_color || 'var(--sf-primary-600)'}}`;
    return (
        <section className="py-12 md:py-16" style={bg(home.categories_bg_color)}>
            <style>{css}</style>
            <div className="sf-container">
                <div className="mb-8 md:mb-10">
                    <span className="mb-3 inline-flex items-center gap-1.5 rounded-full bg-primary-600 px-3 py-1.5 text-xs font-semibold tracking-wider text-white uppercase"><LayoutGrid size={12} />{home.categories_label || t('browse', 'Browse')}</span>
                    <h2 className="text-2xl font-bold text-gray-900 md:text-3xl lg:text-4xl">{home.categories_title || t('shop_by_category', 'Shop by Category')}</h2>
                </div>
                <div className="grid grid-cols-4 gap-4 sm:grid-cols-5 md:grid-cols-6 md:gap-6 lg:grid-cols-8 xl:grid-cols-10">
                    {categories.map((c) => (
                        <SfLink key={c.id} href={`/shop/category/${c.slug}`} className="group text-center">
                            <span className="mx-auto mb-2 block h-[4.8rem] w-[4.8rem] overflow-hidden rounded-full border-2 border-gray-100 bg-white transition-all duration-300 group-hover:scale-105 group-hover:border-primary-400 group-hover:shadow-lg md:h-[6rem] md:w-[6rem]">
                                {c.image ? <img src={c.image} alt="" loading="lazy" className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-110" />
                                    : <span className="flex h-full w-full items-center justify-center bg-primary-50 text-primary-300"><Folder size={24} /></span>}
                            </span>
                            <h3 className="sf-cat-label mx-auto max-w-[96px] truncate leading-tight transition-colors md:max-w-[120px]">{c.name}</h3>
                            {c.count > 0 && <p className="mt-0.5 text-xs text-gray-400">{c.count}</p>}
                        </SfLink>
                    ))}
                </div>
            </div>
        </section>
    );
}

/** Product grid used by Featured and New Arrivals (phone layout from Settings > Products > Homepage). */
function ProductRow({ products }) {
    const { card } = useStore();
    const Card = useThemed('ProductCard', ProductCard);
    const count = card.homepageMobileCount || 4;
    if (card.homepageMobileScroll) {
        return (
            <>
                <div className="scrollbar-hide -mx-4 overflow-x-auto px-4 pb-4 md:hidden">
                    <div className="flex" style={{ gap: `${card.mobileGapPx}px` }}>
                        {products.slice(0, count).map((p) => <div key={p.id} className="w-[160px] shrink-0"><Card product={p} /></div>)}
                    </div>
                </div>
                <div className="hidden auto-rows-fr gap-6 md:grid md:grid-cols-3 lg:grid-cols-4">{products.map((p) => <Card key={p.id} product={p} />)}</div>
            </>
        );
    }
    return (
        <div className={cx('grid auto-rows-fr gap-[var(--g)] md:grid-cols-3 md:gap-6 lg:grid-cols-4', card.homepageMobileColumns === '1' ? 'grid-cols-1' : 'grid-cols-2')} style={{ '--g': `${card.mobileGapPx}px` }}>
            {products.map((p, i) => <div key={p.id} className={cx('h-full', i >= count && 'hidden md:block')}><Card product={p} /></div>)}
        </div>
    );
}

export function Featured({ home, products }) {
    const t = useT();
    if (!on(home.show_featured) || products.length === 0) return null;
    const button = home.featured_button || t('view_all', 'View All');
    return (
        <section className="py-12 md:py-20" style={bg(home.featured_bg_color)}>
            <div className="sf-container">
                <SectionHeading label={home.featured_label || t('handpicked', 'Handpicked')} labelClass="bg-primary-100 text-primary-700" icon={Star} title={home.featured_title || t('featured_products', 'Featured Products')}>
                    <OutlineLink href="/shop">{button}</OutlineLink>
                </SectionHeading>
                <ProductRow products={products} />
                <div className="mt-8 text-center md:hidden"><SfLink href="/shop" className="sf-btn inline-flex items-center gap-2 rounded-xl bg-primary-600 px-6 py-3 font-semibold text-white shadow-lg">{button}<ArrowRight size={16} className="rtl:rotate-180" /></SfLink></div>
            </div>
        </section>
    );
}

export function Arrivals({ home, products }) {
    const t = useT();
    if (!on(home.show_new_arrivals) || products.length === 0) return null;
    const button = home.arrivals_button || t('view_all', 'View All');
    return (
        <section className="py-12 md:py-20" style={bg(home.arrivals_bg_color, '#f9fafb')}>
            <div className="sf-container">
                <SectionHeading label={home.arrivals_label || t('just_in', 'Just In')} labelClass="bg-green-100 text-green-700" icon={Sparkles} title={home.arrivals_title || t('new_arrivals', 'New Arrivals')}>
                    <OutlineLink href="/shop?sort=newest">{button}</OutlineLink>
                </SectionHeading>
                <ProductRow products={products} />
                <div className="mt-8 text-center md:hidden"><SfLink href="/shop?sort=newest" className="sf-btn inline-flex items-center gap-2 rounded-xl bg-primary-600 px-6 py-3 font-semibold text-white shadow-lg">{button}<ArrowRight size={16} className="rtl:rotate-180" /></SfLink></div>
            </div>
        </section>
    );
}

export function Promo({ home }) {
    if (!on(home.show_promo)) return null;
    const image = home.promo_bg_type === 'image' && home.promo_bg_image;
    return (
        <section className="relative overflow-hidden py-10 md:py-16" style={image ? undefined : bg(home.promo_bg_color, '#111827')}>
            {image && <div className="absolute inset-0"><img src={image} alt="" loading="lazy" className="h-full w-full object-cover" /><div className="absolute inset-0 bg-black/50" /></div>}
            <div className="sf-container relative z-10 grid items-center gap-8 md:grid-cols-2 md:gap-12">
                <div className="text-center text-white md:text-start">
                    <span className="mb-4 inline-flex items-center gap-2 rounded-full bg-primary-600 px-4 py-1.5 text-xs font-semibold sm:text-sm md:mb-6"><Clock size={14} />{home.promo_badge || 'Limited Time'}</span>
                    <h2 className="mb-4 text-2xl leading-tight font-bold sm:text-3xl md:mb-6 md:text-4xl lg:text-5xl">{home.promo_title || 'Up to 50% Off on Selected Items'}</h2>
                    <p className="mx-auto mb-6 max-w-lg text-sm leading-relaxed text-gray-300 sm:text-base md:mx-0 md:mb-8 md:text-lg">{home.promo_desc}</p>
                    <SfLink href={safeUrl(home.promo_link, '/shop')} className="sf-btn group inline-flex items-center gap-2 rounded-lg bg-white px-6 py-3 font-semibold text-gray-900 hover:bg-primary-50 sm:px-8 sm:py-4">
                        {home.promo_button || 'Shop Sale'}<ArrowRight size={16} className="transition-transform group-hover:translate-x-1 rtl:rotate-180" />
                    </SfLink>
                </div>
                <div className="flex justify-center md:justify-end">
                    <div className="flex h-40 w-40 flex-col items-center justify-center rounded-full border-4 border-white/20 bg-primary-600 sm:h-48 sm:w-48 md:h-56 md:w-56 lg:h-64 lg:w-64">
                        <span className="text-5xl leading-none font-black text-white sm:text-6xl md:text-7xl lg:text-8xl">{home.promo_discount || '50'}</span>
                        <span className="flex items-baseline gap-1"><span className="text-2xl font-bold text-white/90 sm:text-3xl md:text-4xl">%</span><span className="text-lg font-semibold tracking-wider text-white/80 uppercase sm:text-xl md:text-2xl">OFF</span></span>
                    </div>
                </div>
            </div>
        </section>
    );
}

/** Live countdown to `end` (ISO date). Renders 00s on the server, then ticks in the browser. */
function useCountdown(end) {
    const [left, setLeft] = useState(null);
    useEffect(() => {
        const tick = () => setLeft(Math.max(0, new Date(end).getTime() - Date.now()));
        tick();
        const id = setInterval(tick, 1000);
        return () => clearInterval(id);
    }, [end]);
    const pad = (n) => String(n).padStart(2, '0');
    const ms = left ?? 0;
    return {
        days: pad(Math.floor(ms / 86400000)), hours: pad(Math.floor((ms % 86400000) / 3600000)),
        minutes: pad(Math.floor((ms % 3600000) / 60000)), seconds: pad(Math.floor((ms % 60000) / 1000)),
    };
}

function DealCard({ p, end }) {
    const t = useT();
    const addToCart = useAddToCart();
    const c = useCountdown(end);
    return (
        <div className="sf-card group overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition-all duration-500 hover:border-primary-200 hover:shadow-xl">
            <div className="relative aspect-square overflow-hidden bg-white">
                {p.image && <img src={p.image} alt="" loading="lazy" className="h-full w-full object-contain p-4 transition-transform duration-700 group-hover:scale-110" />}
                <span className="absolute end-3 top-3 flex items-center gap-1 rounded-full bg-gradient-to-r from-red-500 to-pink-500 px-2.5 py-1 text-xs font-bold text-white shadow-lg"><Zap size={11} />{p.discount}%</span>
                <div className="absolute inset-0 flex items-center justify-center gap-2 bg-black/40 opacity-0 transition-all duration-300 group-hover:opacity-100 focus-within:opacity-100">
                    <button type="button" onClick={() => addToCart(p)} aria-label={`${t('add_to_cart', 'Add to Cart')}: ${p.name}`} className="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-gray-900 shadow-lg hover:bg-primary-600 hover:text-white"><ShoppingCart size={16} /></button>
                    <SfLink href={p.url} aria-label={p.name} className="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-gray-900 shadow-lg hover:bg-gray-800 hover:text-white"><Eye size={16} /></SfLink>
                </div>
            </div>
            <div className="bg-gradient-to-r from-gray-900 to-gray-800 px-4 py-2.5">
                <div className="flex items-center justify-center gap-3 text-xs">
                    <span className="font-medium text-gray-400">{t('ends_in', 'Ends in')}:</span>
                    <span className="flex gap-1.5 font-bold text-white">{[[c.days, 'd'], [c.hours, 'h'], [c.minutes, 'm'], [c.seconds, 's']].map(([v, u]) => <span key={u} className="rounded-md bg-red-500 px-2 py-1">{v}{u}</span>)}</span>
                </div>
            </div>
            <div className="p-4">
                {p.category && <SfLink href={p.category.url} className="text-xs font-semibold text-primary-600 hover:text-primary-700">{p.category.name}</SfLink>}
                <h3 className="mt-1.5"><SfLink href={p.url} className="line-clamp-2 text-sm leading-snug font-semibold text-gray-900 hover:text-primary-600">{p.name}</SfLink></h3>
                <div className="mt-2 flex items-center gap-1.5"><Stars rating={p.rating} className="text-yellow-400" /><span className="text-xs text-gray-500">({p.reviews})</span></div>
                <div className="mt-3 flex items-baseline gap-2"><span className="text-xl font-bold text-primary-600">{p.price}</span>{p.regular && <span className="text-sm text-gray-400 line-through">{p.regular}</span>}</div>
            </div>
        </div>
    );
}

export function FlashDeals({ home, products }) {
    const t = useT();
    const c = useCountdown(home.flash_deals_end_date);
    if (!on(home.show_flash_deals)) return null;
    const title = home.flash_deals_title || t('flash_deals', 'Super Flash Sale');
    const button = home.flash_deals_button || t('shop_now', 'Shop Now');
    const header = (
        <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between md:mb-8">
            <div>
                <span className="mb-2 inline-flex items-center gap-1.5 rounded-full bg-red-100 px-3 py-1 text-xs font-semibold tracking-wider text-red-700 uppercase"><Zap size={12} />{t('limited_time', 'Limited Time')}</span>
                <h2 className="text-2xl font-bold text-gray-900 md:text-3xl lg:text-4xl">{title}</h2>
            </div>
            <SfLink href="/shop?on_sale=1" className="sf-btn group inline-flex items-center gap-2 rounded-xl bg-primary-600 px-5 py-2.5 font-semibold text-white shadow-lg hover:bg-primary-700">{button}<ArrowRight size={16} className="transition-transform group-hover:translate-x-1 rtl:rotate-180" /></SfLink>
        </div>
    );
    if (products.length === 0) {
        return (
            <section className="bg-gradient-to-b from-white to-primary-50/30 py-12 md:py-20">
                <div className="sf-container">
                    {header}
                    <div className="rounded-2xl border border-gray-100 bg-white py-16 text-center shadow-sm">
                        <span className="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-red-100 text-red-400"><Zap size={32} /></span>
                        <p className="text-lg text-gray-500">{t('no_flash_deals', 'No flash deals available at the moment. Check back soon!')}</p>
                    </div>
                </div>
            </section>
        );
    }
    const [main, ...rest] = products;
    return (
        <section className="bg-gradient-to-b from-white to-primary-50/30 py-12 md:py-20">
            <div className="sf-container grid gap-6 lg:grid-cols-4 lg:gap-8">
                <div className="order-2 lg:order-1">
                    <div className="group relative h-full overflow-hidden rounded-3xl bg-gradient-to-br from-primary-600 via-primary-700 to-primary-800 shadow-2xl">
                        <div className="relative z-10 p-5 md:p-6">
                            <span className="mb-3 inline-flex items-center gap-1.5 rounded-full bg-white/20 px-3 py-1 text-xs font-bold text-white backdrop-blur-sm"><Flame size={12} className="text-yellow-400" />HOT DEAL</span>
                            <h3 className="mb-2 line-clamp-2 text-lg font-bold text-white md:text-xl">{main.name}</h3>
                            <div className="mb-3 flex items-center gap-1.5"><Stars rating={main.rating} size={14} className="text-yellow-400" empty="text-white/30" /><span className="text-xs text-white/70">({main.reviews})</span></div>
                            <div className="mb-2 flex items-baseline gap-2"><span className="text-2xl font-black text-white md:text-3xl">{main.price}</span>{main.regular && <span className="text-sm text-white/60 line-through">{main.regular}</span>}</div>
                        </div>
                        <div className="relative mx-4 aspect-square overflow-hidden rounded-2xl bg-white/10 backdrop-blur-sm">
                            {main.image && <img src={main.image} alt="" loading="lazy" className="h-full w-full object-contain p-4 transition-transform duration-700 group-hover:scale-110" />}
                            <span className="absolute end-3 top-3 rounded-full bg-gradient-to-r from-red-500 to-pink-500 px-3 py-1.5 text-sm font-bold text-white shadow-lg">-{main.discount}%</span>
                        </div>
                        <div className="mt-2 p-4 md:p-5">
                            <p className="mb-3 text-center text-xs font-medium tracking-wider text-white/70 uppercase">{t('ends_in', 'Ends in')}</p>
                            <div className="grid grid-cols-4 gap-2 text-center">
                                {[[c.days, t('days', 'Days')], [c.hours, t('hours', 'Hours')], [c.minutes, t('mins', 'Mins')], [c.seconds, t('secs', 'Secs')]].map(([v, l]) => (
                                    <div key={l}><div className="rounded-xl bg-white/20 py-2.5 shadow-inner backdrop-blur-sm"><span className="text-xl font-black text-white md:text-2xl">{v}</span></div><span className="mt-1 block text-[10px] text-white/60">{l}</span></div>
                                ))}
                            </div>
                            <SfLink href={main.url} className="group/btn mt-4 flex w-full items-center justify-center gap-2 rounded-xl bg-white py-3 font-bold text-primary-700 shadow-lg hover:bg-primary-50">{t('shop_now', 'Shop Now')}<ArrowRight size={16} className="transition-transform group-hover/btn:translate-x-1 rtl:rotate-180" /></SfLink>
                        </div>
                    </div>
                </div>
                <div className="order-1 lg:order-2 lg:col-span-3">
                    {header}
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-5 lg:grid-cols-3">{rest.map((p) => <DealCard key={p.id} p={p} end={home.flash_deals_end_date} />)}</div>
                </div>
            </div>
        </section>
    );
}

export function TopRated({ home, products }) {
    const t = useT();
    const { customer, features } = useStore();
    const addToCart = useAddToCart();
    const addToWishlist = useAddToWishlist();
    const scroller = useRef(null);
    if (!on(home.show_top_rated)) return null;
    const title = home.top_rated_title || t('top_rated', 'Top Rated');
    if (products.length === 0) {
        return (
            <section className="bg-gradient-to-b from-white to-primary-50/30 py-12 md:py-20">
                <div className="sf-container">
                    <h2 className="mb-6 text-2xl font-bold text-gray-900 md:text-3xl">{title}</h2>
                    <div className="rounded-2xl border border-gray-100 bg-white py-16 text-center shadow-sm">
                        <span className="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-yellow-100 text-yellow-400"><Star size={32} /></span>
                        <p className="text-lg text-gray-500">{t('no_rated_products', 'No rated products yet. Be the first to review!')}</p>
                    </div>
                </div>
            </section>
        );
    }
    const scroll = (dir) => scroller.current?.scrollBy({ left: dir * 320, behavior: 'smooth' });
    return (
        <section className="bg-gradient-to-b from-white to-primary-50/30 py-12 md:py-20">
            <div className="sf-container">
                <div className="mb-6 flex items-center justify-between gap-4 md:mb-10">
                    <div className="min-w-0 flex-1">
                        <span className="mb-2 inline-flex items-center gap-1.5 rounded-full bg-yellow-100 px-3 py-1 text-xs font-semibold text-yellow-700"><Star size={12} />{t('customer_favorites', 'Customer Favorites')}</span>
                        <h2 className="truncate text-xl font-bold text-gray-900 sm:text-2xl md:text-3xl lg:text-4xl">{title}</h2>
                    </div>
                    <div className="flex shrink-0 items-center gap-2">
                        {[[-1, ChevronLeft, 'Previous'], [1, ChevronRight, 'Next']].map(([d, Icon, label]) => (
                            <button key={d} type="button" onClick={() => scroll(d)} aria-label={label} className="flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 shadow-sm hover:border-primary-500 hover:bg-primary-50 hover:text-primary-600 sm:h-10 sm:w-10"><Icon size={16} className="rtl:rotate-180" /></button>
                        ))}
                    </div>
                </div>
                <div className="grid gap-6 lg:grid-cols-3 lg:gap-8">
                    <div ref={scroller} className="order-2 grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-5 lg:order-1 lg:col-span-2">
                        {products.map((p) => (
                            <div key={p.id} className="sf-card group flex overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition-all duration-500 hover:border-primary-200 hover:shadow-xl">
                                <div className="relative h-28 w-28 shrink-0 overflow-hidden bg-white p-3 sm:h-32 sm:w-32 md:h-36 md:w-36">
                                    {p.image ? <img src={p.image} alt="" loading="lazy" className="h-full w-full object-contain transition-transform duration-500 group-hover:scale-110" /> : null}
                                    {p.discount > 0 && <span className="absolute start-2 top-2 rounded-full bg-gradient-to-r from-red-500 to-pink-500 px-2 py-0.5 text-[10px] font-bold text-white shadow-sm">-{p.discount}%</span>}
                                </div>
                                <div className="flex min-w-0 flex-1 flex-col justify-between p-4">
                                    <div>
                                        {p.category && <SfLink href={p.category.url} className="text-xs font-medium text-primary-600 hover:text-primary-700">{p.category.name}</SfLink>}
                                        <h3 className="mt-1.5"><SfLink href={p.url} className="line-clamp-2 text-sm leading-tight font-semibold text-gray-900 hover:text-primary-600 md:text-base">{p.name}</SfLink></h3>
                                        <div className="mt-2 flex items-center gap-1.5"><Stars rating={p.rating} className="text-yellow-400" /><span className="text-xs font-medium text-gray-500">({p.reviews})</span></div>
                                    </div>
                                    <div className="mt-3 flex items-center justify-between">
                                        <div className="flex items-baseline gap-2"><span className={cx('text-lg font-bold md:text-xl', p.regular ? 'text-primary-600' : 'text-gray-900')}>{p.price}</span>{p.regular && <span className="text-xs text-gray-400 line-through">{p.regular}</span>}</div>
                                        <div className="flex items-center gap-1.5 opacity-0 transition-opacity duration-300 group-hover:opacity-100 focus-within:opacity-100">
                                            <button type="button" onClick={() => addToCart(p)} aria-label={`${t('add_to_cart', 'Add to Cart')}: ${p.name}`} className="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-100 text-primary-600 hover:bg-primary-600 hover:text-white"><ShoppingCart size={13} /></button>
                                            <SfLink href={p.url} aria-label={p.name} className="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-800 hover:text-white"><Eye size={13} /></SfLink>
                                            {customer && features.wishlist && <button type="button" onClick={() => addToWishlist(p)} aria-label={`${t('add_to_wishlist', 'Add to wishlist')}: ${p.name}`} className="flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-400 hover:bg-red-500 hover:text-white"><Heart size={13} /></button>}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                    <div className="order-1 lg:order-2">
                        <div className="group relative h-full overflow-hidden rounded-3xl bg-gradient-to-br from-primary-50 via-blue-50 to-indigo-100 shadow-lg transition-shadow hover:shadow-xl">
                            <div className="relative z-10 p-6 md:p-8">
                                <span className="inline-flex items-center gap-1.5 rounded-full bg-primary-600 px-3 py-1.5 text-xs font-bold tracking-wider text-white uppercase shadow-lg"><Zap size={12} />{home.top_rated_promo_badge}</span>
                                <h3 className="mt-4 mb-3 text-xl leading-tight font-bold text-gray-900 md:text-2xl lg:text-3xl">{home.top_rated_promo_title}</h3>
                                <p className="mb-6 text-sm leading-relaxed text-gray-600 md:text-base">{home.top_rated_promo_desc}</p>
                                <SfLink href={safeUrl(home.top_rated_promo_link, '/shop')} className="sf-btn group/b inline-flex items-center gap-2 rounded-xl bg-primary-600 px-6 py-3 text-sm font-semibold text-white shadow-lg hover:bg-primary-700 md:text-base">{home.top_rated_promo_button}<ArrowRight size={16} className="transition-transform group-hover/b:translate-x-1 rtl:rotate-180" /></SfLink>
                            </div>
                            <div className="relative mt-4 h-52 overflow-hidden rounded-2xl md:h-64 lg:h-72">
                                <img src={home.top_rated_promo_image || 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=400&h=300&fit=crop'} alt="" loading="lazy" className="absolute inset-0 h-full w-full object-cover transition-transform duration-700 group-hover:scale-105" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}

export function TwoColumnBanners({ home }) {
    if (!on(home.show_two_column_banners)) return null;
    const banners = [
        { n: 1, Icon: Tag, fallback: 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=600&h=400&fit=crop' },
        { n: 2, Icon: Percent, fallback: 'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=600&h=400&fit=crop' },
    ];
    return (
        <section className="py-10 md:py-16">
            <div className="sf-container grid gap-5 md:grid-cols-2 md:gap-8">
                {banners.map(({ n, Icon, fallback }) => {
                    const k = (f) => home[`banner${n}_${f}`];
                    return (
                        <div key={n} className="group relative h-56 overflow-hidden rounded-3xl shadow-lg transition-all duration-500 hover:shadow-2xl sm:h-72 md:h-80 lg:h-96">
                            {k('bg_type') === 'color'
                                ? <div className="absolute inset-0" style={{ backgroundColor: k('bg_color') || '#111827' }} />
                                : <img src={k('image') || fallback} alt="" loading="lazy" className="absolute inset-0 h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-110" />}
                            <div className="absolute inset-0 bg-gradient-to-r from-black/45 via-black/20 to-transparent" />
                            <div className="absolute inset-0 flex flex-col justify-end p-5 sm:p-6 md:justify-center md:p-8 lg:p-10">
                                <span className="mb-3 inline-flex w-fit items-center gap-1.5 rounded-full border border-white/20 bg-white/20 px-3 py-1.5 text-[10px] font-bold tracking-wider text-white uppercase backdrop-blur-sm sm:text-xs"><Icon size={12} />{k('badge')}</span>
                                <h3 className="mb-3 max-w-sm text-xl leading-tight font-bold text-white drop-shadow-lg sm:text-2xl md:mb-4 md:text-3xl lg:text-4xl">{k('title')}</h3>
                                <SfLink href={safeUrl(k('link'), '/shop')} className="sf-btn group/btn inline-flex w-fit items-center gap-2 rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-gray-900 shadow-lg hover:-translate-y-0.5 hover:bg-primary-600 hover:text-white sm:px-6 sm:py-3">{k('button')}<ArrowRight size={14} className="transition-transform group-hover/btn:translate-x-1 rtl:rotate-180" /></SfLink>
                            </div>
                        </div>
                    );
                })}
            </div>
        </section>
    );
}

export function Blog({ home, posts }) {
    const t = useT();
    if (!home.show_blog || posts.length === 0) return null;
    const cols = { 1: 'lg:grid-cols-1', 2: 'lg:grid-cols-2', 3: 'lg:grid-cols-3', 4: 'lg:grid-cols-4' }[parseInt(home.blog_columns, 10)] ?? 'lg:grid-cols-3';
    return (
        <section className="bg-gray-50 py-12 md:py-16">
            <div className="sf-container">
                <div className="mb-10 text-center">
                    <h2 className="text-2xl font-bold text-gray-900 md:text-3xl">{home.blog_title || 'Latest Blog Posts'}</h2>
                    {(home.blog_subtitle ?? 'Stay updated with our latest news and articles') && <p className="mx-auto mt-2 max-w-2xl text-gray-600">{home.blog_subtitle ?? 'Stay updated with our latest news and articles'}</p>}
                </div>
                <div className={cx('grid grid-cols-1 gap-6 md:grid-cols-2 md:gap-8', cols)}>
                    {posts.map((post) => (
                        <article key={post.id} className="group overflow-hidden rounded-xl bg-white shadow-sm transition-all duration-300 hover:shadow-lg">
                            <a href={`/blog/${post.slug}`} className="block aspect-[16/10] overflow-hidden" tabIndex={-1} aria-hidden="true">
                                {post.image ? <img src={post.image} alt="" loading="lazy" className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" />
                                    : <span className="flex h-full w-full items-center justify-center bg-gradient-to-br from-primary-100 to-primary-200 text-primary-400"><Newspaper size={40} /></span>}
                            </a>
                            <div className="p-5 md:p-6">
                                <div className="mb-3 flex items-center gap-3 text-xs text-gray-500">
                                    {post.category && <a href={`/blog/category/${post.category.slug}`} className="rounded-full bg-primary-100 px-2 py-1 text-primary-700 hover:bg-primary-200">{post.category.name}</a>}
                                    <span className="flex items-center gap-1"><CalendarDays size={12} />{post.date}</span>
                                </div>
                                <h3 className="mb-2 line-clamp-2 text-lg font-semibold text-gray-900 group-hover:text-primary-600"><a href={`/blog/${post.slug}`}>{post.title}</a></h3>
                                {post.excerpt && <p className="mb-4 line-clamp-3 text-sm text-gray-600">{post.excerpt}</p>}
                                <a href={`/blog/${post.slug}`} className="group/l inline-flex items-center gap-2 text-sm font-medium text-primary-600 hover:text-primary-700">{t('read_more', 'Read More')}<ArrowRight size={12} className="transition-transform group-hover/l:translate-x-1 rtl:rotate-180" /></a>
                            </div>
                        </article>
                    ))}
                </div>
                <div className="mt-10 text-center"><a href="/blog" className="sf-btn inline-flex items-center gap-2 rounded-lg bg-primary-600 px-6 py-3 font-medium text-white hover:bg-primary-700">{home.blog_button || 'View All Posts'}<ArrowRight size={16} className="rtl:rotate-180" /></a></div>
            </div>
        </section>
    );
}

export function Newsletter({ home }) {
    const t = useT();
    const { features } = useStore();
    const { toast } = useShop();
    const [email, setEmail] = useState('');
    const [state, setState] = useState({ busy: false, message: null, ok: true });
    if (!features.newsletter || !on(home.show_newsletter)) return null;

    const submit = async (e) => {
        e.preventDefault();
        setState({ busy: true, message: null, ok: true });
        try {
            const res = await postJson('/newsletter/subscribe', { email });
            setState({ busy: false, message: res.message || t('newsletter_success', 'Thank you for subscribing!'), ok: true });
            setEmail('');
        } catch (err) {
            setState({ busy: false, message: err.message, ok: false });
            toast('error', err.message);
        }
    };
    const image = home.newsletter_bg_type === 'image' && home.newsletter_bg_image;

    return (
        <section className="relative overflow-hidden py-8 md:py-12" style={image ? undefined : bg(home.newsletter_bg_color, '#1f2937')}>
            {image && <div className="absolute inset-0"><img src={image} alt="" loading="lazy" className="h-full w-full object-cover" /><div className="absolute inset-0 bg-black/50" /></div>}
            <div className="sf-container relative z-10 text-center">
                <span className="mb-4 inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/20 px-4 py-2 text-xs font-medium text-white backdrop-blur-sm sm:text-sm"><Mail size={14} />{home.newsletter_badge || t('stay_updated', 'Stay Updated')}</span>
                <h2 className="mb-3 text-2xl leading-tight font-bold text-white sm:text-3xl md:mb-4 md:text-4xl lg:text-5xl">{home.newsletter_title || t('subscribe_newsletter', 'Subscribe to Our Newsletter')}</h2>
                <p className="mx-auto mb-5 max-w-2xl text-sm leading-relaxed text-white/80 sm:text-base md:mb-6 md:text-lg">{home.newsletter_desc || t('newsletter_desc', 'Get exclusive deals')}</p>
                <form onSubmit={submit} className="mx-auto max-w-xl">
                    <div className="flex flex-col gap-3 rounded-2xl border border-white/20 bg-white/10 p-2 backdrop-blur-sm sm:flex-row sm:gap-0 sm:rounded-full">
                        <label className="relative flex-1">
                            <span className="sr-only">{t('enter_email', 'Enter your email')}</span>
                            <Mail size={16} className="absolute start-5 top-1/2 -translate-y-1/2 text-gray-400" />
                            <input type="email" required value={email} onChange={(e) => setEmail(e.target.value)} placeholder={home.newsletter_placeholder || t('enter_email', 'Enter your email')}
                                className="w-full rounded-xl border-0 bg-white py-3 ps-12 pe-4 text-sm text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-primary-300 sm:rounded-full sm:text-base" />
                        </label>
                        <button type="submit" disabled={state.busy} className="group flex items-center justify-center gap-2 rounded-xl bg-primary-600 px-6 py-3 font-semibold text-white shadow-lg hover:bg-primary-700 disabled:opacity-70 sm:ms-2 sm:rounded-full sm:px-8">
                            <span>{state.busy ? t('please_wait', 'Please wait...') : home.newsletter_button || t('subscribe', 'Subscribe')}</span>
                            {state.busy ? <Loader2 size={16} className="animate-spin" /> : <ArrowRight size={16} className="transition-transform group-hover:translate-x-1 rtl:rotate-180" />}
                        </button>
                    </div>
                    {state.message && <p role="status" className={cx('mt-4 flex items-center justify-center gap-2', state.ok ? 'text-green-400' : 'text-red-400')}><CheckCircle2 size={16} />{state.message}</p>}
                </form>
                <div className="mt-4 flex flex-col items-center justify-center gap-3 text-xs text-white/60 sm:flex-row sm:gap-6 sm:text-sm md:mt-5">
                    <span className="flex items-center gap-2"><CheckCircle2 size={14} className="text-green-400" />{home.newsletter_note || t('no_spam', 'No spam')}</span>
                    <span className="flex items-center gap-2"><Gift size={14} className="text-yellow-400" />{t('exclusive_offers', 'Exclusive offers')}</span>
                    <span className="flex items-center gap-2"><Bell size={14} className="text-blue-400" />{t('new_arrivals_alert', 'New arrivals alert')}</span>
                </div>
            </div>
        </section>
    );
}
