import { useEffect, useMemo, useState } from 'react';
import { Link, router, useForm, usePage } from '@inertiajs/react';
import {
    Bot, Check, CheckCircle2, ChevronLeft, ChevronRight, Clock, Download, Expand, Eye, FileText, Frown, Heart, Home,
    Link2, ListChecks, Mail, Meh, MinusCircle, PlusCircle, ShoppingCart, Smile, ThumbsUp, X, XCircle,
} from 'lucide-react';
import SfLink from '../lib/SfLink';
import { postForm } from '../lib/http';
import { useAddToCart, useAddToWishlist } from '../lib/cart';
import { cx, useShop, useStore, useT } from '../lib/store';
import { useThemed } from '../theme/registry';
import { SocialIcon } from '../Footer/Footer';
import ProductCard from './ProductCard';
import Stars from './Stars';
import { Pagination } from '../Shop/Listing';

/* ---------- shared product-page state: chosen variation + quantity ---------- */

/** Variation picking: the variation whose values include every chosen option. */
export function useVariationChoice(product) {
    const [chosen, setChosen] = useState({});
    const variation = useMemo(() => {
        const ids = Object.values(chosen);
        if (product.type !== 'variable' || ids.length < product.attributes.length) return null;
        return product.variations.find((v) => ids.every((id) => v.values.includes(id))) ?? null;
    }, [chosen, product]);
    return { chosen, choose: (attrId, valueId) => setChosen((c) => ({ ...c, [attrId]: valueId })), variation };
}

export function Breadcrumb({ product }) {
    const t = useT();
    const items = [{ label: t('shop', 'Shop'), href: '/shop' }];
    if (product.category) items.push({ label: product.category.name, href: `/shop/category/${product.category.slug}` });
    items.push({ label: product.name });
    return (
        <nav className="mb-6 flex" aria-label="Breadcrumb">
            <ol className="flex flex-wrap items-center gap-2 text-sm">
                <li><Link href="/" aria-label={t('home', 'Home')} className="text-gray-500 hover:text-primary-600"><Home size={15} /></Link></li>
                {items.map((i) => (
                    <li key={i.label} className="flex items-center gap-2">
                        <ChevronRight size={12} className="text-gray-300 rtl:rotate-180" />
                        {i.href ? <Link href={i.href} className="text-gray-500 hover:text-primary-600">{i.label}</Link> : <span aria-current="page" className="line-clamp-1 font-medium text-gray-900">{i.label}</span>}
                    </li>
                ))}
            </ol>
        </nav>
    );
}

/* ---------- gallery with hover zoom, arrows, thumbnails and a fullscreen viewer ---------- */

export function Gallery({ product, variation }) {
    const images = variation?.image ? [variation.image] : product.images;
    const [index, setIndex] = useState(0);
    const [zoom, setZoom] = useState(null);
    const [lightbox, setLightbox] = useState(false);
    useEffect(() => setIndex(0), [variation?.id]);
    useEffect(() => {
        if (!lightbox) return undefined;
        const onKey = (e) => {
            if (e.key === 'Escape') setLightbox(false);
            if (e.key === 'ArrowRight') setIndex((i) => (i + 1) % images.length);
            if (e.key === 'ArrowLeft') setIndex((i) => (i - 1 + images.length) % images.length);
        };
        document.addEventListener('keydown', onKey);
        return () => document.removeEventListener('keydown', onKey);
    }, [lightbox, images.length]);
    const step = (d) => setIndex((i) => (i + d + images.length) % images.length);
    const current = images[index] ?? images[0];

    return (
        <div>
            <div className="relative mb-4 aspect-square cursor-zoom-in overflow-hidden rounded-xl bg-gray-50"
                onMouseLeave={() => setZoom(null)}
                onMouseMove={(e) => { const r = e.currentTarget.getBoundingClientRect(); setZoom({ x: ((e.clientX - r.left) / r.width) * 100, y: ((e.clientY - r.top) / r.height) * 100 }); }}>
                <img src={current} alt={product.name} fetchPriority="high" className="h-full w-full object-contain transition-transform duration-200"
                    style={zoom ? { transform: 'scale(2)', transformOrigin: `${zoom.x}% ${zoom.y}%` } : undefined} />
                {images.length > 1 && (
                    <>
                        <button type="button" onClick={() => step(-1)} aria-label="Previous image" className="absolute start-2 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 shadow-lg hover:bg-white"><ChevronLeft size={18} className="rtl:rotate-180" /></button>
                        <button type="button" onClick={() => step(1)} aria-label="Next image" className="absolute end-2 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 shadow-lg hover:bg-white"><ChevronRight size={18} className="rtl:rotate-180" /></button>
                    </>
                )}
                <button type="button" onClick={() => setLightbox(true)} aria-label="View full screen" className="absolute end-2 bottom-2 flex h-10 w-10 items-center justify-center rounded-full bg-white/90 shadow-lg hover:bg-white"><Expand size={16} /></button>
            </div>
            {images.length > 1 && (
                <div className="grid grid-cols-5 gap-2">
                    {images.map((img, i) => (
                        <button key={img} type="button" onClick={() => setIndex(i)} aria-label={`Image ${i + 1}`} aria-current={i === index}
                            className={cx('aspect-square overflow-hidden rounded-lg bg-gray-50 transition-all', i === index ? 'ring-2 ring-primary-500 ring-offset-2' : 'ring-1 ring-gray-200 hover:ring-primary-300')}>
                            <img src={img} alt="" loading="lazy" className="h-full w-full object-contain" />
                        </button>
                    ))}
                </div>
            )}
            {lightbox && (
                <div role="dialog" aria-modal="true" aria-label={product.name} className="fixed inset-0 z-[90] flex items-center justify-center bg-black/90 p-4" onClick={() => setLightbox(false)}>
                    <img src={current} alt={product.name} className="max-h-full max-w-full object-contain" onClick={(e) => e.stopPropagation()} />
                    <button type="button" onClick={() => setLightbox(false)} aria-label="Close" className="absolute end-4 top-4 text-white"><X size={28} /></button>
                    {images.length > 1 && (
                        <>
                            <button type="button" onClick={(e) => { e.stopPropagation(); step(-1); }} aria-label="Previous image" className="absolute start-4 top-1/2 -translate-y-1/2 text-white"><ChevronLeft size={36} className="rtl:rotate-180" /></button>
                            <button type="button" onClick={(e) => { e.stopPropagation(); step(1); }} aria-label="Next image" className="absolute end-4 top-1/2 -translate-y-1/2 text-white"><ChevronRight size={36} className="rtl:rotate-180" /></button>
                        </>
                    )}
                </div>
            )}
        </div>
    );
}

function useCountdown(end) {
    const [left, setLeft] = useState(null);
    useEffect(() => {
        if (!end) return undefined;
        const tick = () => setLeft(Math.max(0, new Date(end).getTime() - Date.now()));
        tick();
        const id = setInterval(tick, 1000);
        return () => clearInterval(id);
    }, [end]);
    const ms = left ?? 0;
    return [Math.floor(ms / 86400000), Math.floor((ms % 86400000) / 3600000), Math.floor((ms % 3600000) / 60000), Math.floor((ms % 60000) / 1000)];
}

export function QuantityInput({ value, onChange, max, small }) {
    const t = useT();
    const clamp = (n) => Math.max(1, Math.min(max || 9999, Number.isFinite(n) ? n : 1));
    return (
        <div className="flex w-fit items-center rounded-md border border-gray-300">
            <button type="button" onClick={() => onChange(clamp(value - 1))} aria-label={t('decrease', 'Decrease quantity')} className={cx('text-gray-400 hover:text-gray-600', small ? 'px-3 py-1.5' : 'px-4 py-3')}>−</button>
            <input type="number" min="1" max={max || undefined} value={value} onChange={(e) => onChange(clamp(parseInt(e.target.value, 10)))} aria-label={t('quantity', 'Quantity')}
                className={cx('border-0 text-center font-medium text-gray-800 focus:ring-0 focus:outline-none', small ? 'w-10 py-1 text-sm' : 'w-12')} />
            <button type="button" onClick={() => onChange(clamp(value + 1))} aria-label={t('increase', 'Increase quantity')} className={cx('text-gray-400 hover:text-gray-600', small ? 'px-3 py-1.5' : 'px-4 py-3')}>+</button>
        </div>
    );
}

/** Add to cart + Buy now for the current product/variation/quantity (used by the info block and the mobile bar). */
export function useBuy(product, variation, quantity) {
    const addToCart = useAddToCart();
    const [busy, setBusy] = useState(null);
    const needsChoice = product.type === 'variable' && !variation;
    const soldOut = variation ? variation.quantity < 1 : !product.inStock;
    return {
        needsChoice, soldOut, busy,
        add: async () => {
            if (needsChoice || soldOut) return;
            setBusy('cart');
            await addToCart({ ...product, variable: false }, quantity, variation?.id ?? null);
            setBusy(null);
        },
        buy: () => {
            if (needsChoice || soldOut) return;
            setBusy('buy');
            postForm('/cart/buy-now', { product_id: product.id, quantity, variation_id: variation?.id });
        },
    };
}

function BuyButtons({ product, options, buy, compact = false }) {
    const t = useT();
    const b = options.buttons;
    const disabled = buy.needsChoice || buy.soldOut;
    const cartStyle = b ? { backgroundColor: b.cartBg, color: b.cartText, borderColor: b.cartBorder } : undefined;
    const buyStyle = b ? { backgroundColor: b.buyBg, color: b.buyText } : undefined;
    return (
        <>
            {options.showAddToCart && (
                <button type="button" onClick={buy.add} disabled={disabled || buy.busy === 'cart'} style={cartStyle}
                    className={cx('sf-btn w-full rounded-md border-2 font-medium transition', compact ? 'py-3 text-sm' : 'mb-3 py-3.5', !b && (disabled ? 'cursor-not-allowed border-gray-300 text-gray-400' : 'border-primary-600 text-primary-600 hover:bg-primary-50'), b && disabled && 'cursor-not-allowed opacity-50')}>
                    {buy.busy === 'cart' ? t('adding', 'Adding...') : t('add_to_cart', 'Add to cart')}
                </button>
            )}
            {options.showBuyNow && (
                <button type="button" onClick={buy.buy} disabled={disabled || buy.busy === 'buy'} style={buyStyle}
                    className={cx('sf-btn w-full rounded-md font-medium text-white transition', compact ? 'py-3 text-sm' : 'py-3.5', !b && (disabled ? 'cursor-not-allowed bg-gray-400' : 'bg-primary-600 hover:bg-primary-700'), b && disabled && 'cursor-not-allowed opacity-50')}>
                    {buy.busy === 'buy' ? t('processing', 'Processing...') : t('buy_now', 'Buy it now')}
                </button>
            )}
        </>
    );
}

function Share({ product, list }) {
    const t = useT();
    const { toast } = useShop();
    const u = encodeURIComponent(product.url);
    const n = encodeURIComponent(product.name);
    const links = {
        facebook: [`https://www.facebook.com/sharer/sharer.php?u=${u}`, 'Facebook', 'hover:bg-blue-600'],
        twitter: [`https://twitter.com/intent/tweet?url=${u}&text=${n}`, 'X', 'hover:bg-black'],
        whatsapp: [`https://wa.me/?text=${encodeURIComponent(`${product.name} ${product.url}`)}`, 'WhatsApp', 'hover:bg-green-500'],
        pinterest: [`https://pinterest.com/pin/create/button/?url=${u}&media=${encodeURIComponent(product.images[0])}&description=${n}`, 'Pinterest', 'hover:bg-red-600'],
        linkedin: [`https://www.linkedin.com/shareArticle?mini=true&url=${u}&title=${n}`, 'LinkedIn', 'hover:bg-blue-700'],
        email: [`mailto:?subject=${n}&body=${encodeURIComponent(`Check out this product: ${product.url}`)}`, 'Email', 'hover:bg-gray-700'],
    };
    const copy = () => navigator.clipboard?.writeText(product.url).then(() => toast('success', t('link_copied', 'Link copied to clipboard!')));
    const btn = 'flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 text-gray-600 transition hover:text-white';
    return (
        <div className="mt-4 flex items-center gap-3 border-t pt-4">
            <span className="text-sm font-medium text-gray-500">{t('share', 'Share')}:</span>
            <div className="flex items-center gap-2">
                {list.filter((k) => links[k]).map((k) => (
                    <a key={k} href={links[k][0]} target={k === 'email' ? undefined : '_blank'} rel="noopener noreferrer" title={`Share on ${links[k][1]}`} aria-label={`Share on ${links[k][1]}`} className={cx(btn, links[k][2])}>
                        {k === 'email' ? <Mail size={14} /> : <SocialIcon network={k} size={14} />}
                    </a>
                ))}
                {list.includes('copy') && <button type="button" onClick={copy} title={t('copy_link', 'Copy link')} aria-label={t('copy_link', 'Copy link')} className={cx(btn, 'hover:bg-primary-600')}><Link2 size={14} /></button>}
            </div>
        </div>
    );
}

export function Info({ product, options, choice, quantity, setQuantity }) {
    const t = useT();
    const { customer, features } = useStore();
    const addToWishlist = useAddToWishlist();
    const { variation, chosen, choose } = choice;
    const buy = useBuy(product, variation, quantity);
    const [d, h, m, s] = useCountdown(product.saleEnds);
    const ty = options.type;
    const css = `.sf-pd-title{font-weight:${ty.titleWeight};color:${ty.titleColor};font-size:${ty.titleMobilePx}px${ty.titleFont ? `;font-family:'${ty.titleFont}',sans-serif` : ''}}`
        + `.sf-pd-price{font-size:${ty.priceMobilePx}px}.sf-pd-short{font-size:${ty.shortMobilePx}px}.sf-pd-long{font-size:${ty.longMobilePx}px}`
        + `@media(min-width:768px){.sf-pd-title{font-size:${ty.titlePx}px}.sf-pd-price{font-size:${ty.pricePx}px}.sf-pd-short{font-size:${ty.shortPx}px}.sf-pd-long{font-size:${ty.longPx}px}}`
        + `.sf-pd-brand{color:${ty.brandColor};${ty.brandUpper ? 'text-transform:uppercase' : ''}}`;
    const sku = variation?.sku || product.sku;
    const maxQty = variation ? variation.quantity : product.quantity || undefined;

    return (
        <div>
            <style>{css}</style>
            {ty.titleFont && <link rel="stylesheet" href={`https://fonts.googleapis.com/css2?family=${ty.titleFont.replace(/ /g, '+')}:wght@400;500;600;700;800&display=swap`} />}
            {(options.showBrand && product.brand) || (options.showCategory && product.category) ? (
                <div className="mb-2 flex items-center gap-3 text-sm font-medium tracking-wide">
                    {options.showBrand && product.brand && <Link href={`/shop?brand=${product.brand.slug}`} className="sf-pd-brand">{product.brand.name}</Link>}
                    {options.showBrand && product.brand && options.showCategory && product.category && <span className="text-gray-300">|</span>}
                    {options.showCategory && product.category && <Link href={`/shop/category/${product.category.slug}`} className="sf-pd-brand">{product.category.name}</Link>}
                </div>
            ) : null}
            <h1 className="sf-pd-title mt-2 mb-4">{product.name}</h1>
            {options.showTags && product.tags.length > 0 && (
                <div className="mb-4 flex flex-wrap gap-2">
                    {product.tags.map((tag) => <Link key={tag.slug} href={`/shop?tag=${tag.slug}`} className="rounded-full px-2 py-1 text-xs hover:opacity-80" style={{ backgroundColor: `${tag.color}20`, color: tag.color, border: `1px solid ${tag.color}40` }}>{tag.name}</Link>)}
                </div>
            )}
            {product.reviews > 0 && <div className="mb-4 flex items-center gap-2"><Stars rating={product.rating} size={16} className="text-yellow-400" /><a href="#reviews" className="text-gray-600 hover:underline">({product.reviews} {t('reviews', 'reviews')})</a></div>}

            <div className="mb-6">
                <span className="sf-pd-price font-bold" style={{ color: product.regular || variation?.regular ? ty.saleColor : ty.priceColor }}>{variation?.price ?? product.price}</span>
                {(variation ? variation.regular : product.regular) && <span className="ms-2 text-lg text-gray-400 line-through md:text-xl">{variation ? variation.regular : product.regular}</span>}
                {!variation && product.discount > 0 && <span className="ms-2 rounded-full bg-red-100 px-3 py-1 text-sm font-semibold text-red-600">{t('save', 'Save')} {product.discount}%</span>}
                {product.saleEnds && (
                    <div className="mt-3 flex flex-wrap items-center gap-3 rounded-lg border border-red-200 bg-red-50 p-3 text-red-700">
                        <span className="flex items-center gap-2 font-medium"><Clock size={16} />{t('sale_ends_in', 'Sale ends in')}:</span>
                        <span className="flex gap-2 font-mono">{[[d, 'd'], [h, 'h'], [m, 'm'], [s, 's']].map(([v, u]) => <span key={u} className="rounded bg-red-600 px-2 py-1 text-white">{v}{u}</span>)}</span>
                    </div>
                )}
            </div>

            {product.short && <p className="sf-pd-short mb-6 leading-relaxed text-gray-600">{product.short}</p>}

            <div className="mb-6 flex flex-wrap items-center gap-4">
                <span className="text-gray-600">{t('availability', 'Availability')}:</span>
                {product.inStock
                    ? <span className="flex items-center gap-1 font-medium text-green-600"><CheckCircle2 size={16} />{t('in_stock', 'In Stock')}{options.showStockQty && product.type !== 'variable' && ` (${product.quantity} ${t('available', 'available')})`}</span>
                    : <span className="flex items-center gap-1 font-medium text-red-600"><XCircle size={16} />{t('out_of_stock', 'Out of Stock')}</span>}
                {product.digital && <span className="flex items-center gap-1 font-medium text-purple-600"><Download size={16} />{t('digital_product', 'Digital Product')}</span>}
            </div>

            {product.type === 'variable' && product.attributes.map((a) => (
                <fieldset key={a.id} className="mb-4">
                    <legend className="mb-2 block text-sm font-medium text-gray-700">{a.name}</legend>
                    <div className="flex flex-wrap gap-2">
                        {a.values.map((v) => (a.type === 'color' && v.color ? (
                            <button key={v.id} type="button" onClick={() => choose(a.id, v.id)} title={v.label} aria-label={v.label} aria-pressed={chosen[a.id] === v.id}
                                className={cx('h-10 w-10 rounded-full border-2 border-gray-300 transition', chosen[a.id] === v.id && 'ring-2 ring-primary-500 ring-offset-2')} style={{ backgroundColor: v.color }} />
                        ) : (
                            <button key={v.id} type="button" onClick={() => choose(a.id, v.id)} aria-pressed={chosen[a.id] === v.id}
                                className={cx('rounded-lg border px-4 py-2 font-medium transition', chosen[a.id] === v.id ? 'border-primary-600 bg-primary-600 text-white' : 'border-gray-300 bg-white text-gray-700 hover:border-primary-500')}>{v.label}</button>
                        )))}
                    </div>
                </fieldset>
            ))}
            {variation && (
                <div className="mb-4 space-y-1 rounded-lg bg-gray-50 p-3 text-sm">
                    <div className="flex justify-between"><span className="text-gray-600">{t('selected', 'Selected')}:</span><span className="font-medium">{variation.name}</span></div>
                    <div className="flex justify-between"><span className="text-gray-600">{t('price', 'Price')}:</span><span className="font-bold text-primary-600">{variation.price}</span></div>
                    <div className="flex justify-between"><span className="text-gray-600">{t('stock', 'Stock')}:</span>
                        <span className={variation.quantity > 0 ? 'text-green-600' : 'text-red-600'}>{variation.quantity > 0 ? (options.showStockQty ? `${variation.quantity} ${t('available', 'available')}` : t('in_stock', 'In Stock')) : t('out_of_stock', 'Out of stock')}</span>
                    </div>
                </div>
            )}
            {product.type === 'variable' && Object.keys(chosen).length === product.attributes.length && !variation && (
                <p className="mb-4 text-sm text-red-600">{t('combination_unavailable', 'This combination is not available.')}</p>
            )}

            {(product.inStock || product.type === 'variable') && (
                <div id="inline-cart-buttons" className="mb-4">
                    <span className="mb-2 block text-sm text-gray-600">{t('quantity', 'Quantity')}</span>
                    <div className="mb-6"><QuantityInput value={quantity} onChange={setQuantity} max={maxQty} /></div>
                    <BuyButtons product={product} options={options} buy={buy} />
                    {buy.needsChoice && <p className="mt-2 text-sm text-gray-500">{t('select_options_first', 'Choose the options above first.')}</p>}
                </div>
            )}

            {options.showWishlist && features.wishlist && (
                <div className="mb-6 flex items-center gap-4 text-sm">
                    {customer
                        ? <button type="button" onClick={() => addToWishlist(product)} className="flex items-center gap-2 text-gray-600 hover:text-red-500"><Heart size={16} />{t('add_to_wishlist', 'Add to wishlist')}</button>
                        : <a href="/login" className="flex items-center gap-2 text-gray-600 hover:text-red-500"><Heart size={16} />{t('add_to_wishlist', 'Add to wishlist')}</a>}
                </div>
            )}

            <dl className="space-y-2 border-t pt-4 text-sm">
                {options.showSku && sku && <div className="flex gap-2"><dt className="font-medium text-gray-500">SKU:</dt><dd className="text-gray-700">{sku}</dd></div>}
                {options.showCategory && product.category && <div className="flex gap-2"><dt className="font-medium text-gray-500">{t('category', 'Category')}:</dt><dd><Link href={`/shop/category/${product.category.slug}`} className="text-gray-700 hover:text-primary-600">{product.category.name}</Link></dd></div>}
                {options.showTags && product.tags.length > 0 && <div className="flex gap-2"><dt className="font-medium text-gray-500">{t('tags', 'Tags')}:</dt><dd className="text-gray-700">{product.tags.map((tag, i) => <span key={tag.slug}>{i > 0 && ', '}<Link href={`/shop?tag=${tag.slug}`} className="hover:text-primary-600">{tag.name}</Link></span>)}</dd></div>}
            </dl>
            {options.showShare && options.share.length > 0 && <Share product={product} list={options.share} />}
        </div>
    );
}

export function Tabs({ product }) {
    const t = useT();
    const [tab, setTab] = useState('description');
    const tabBtn = (key) => cx('flex items-center gap-2 px-6 py-4 font-medium transition', tab === key ? 'border-b-2 border-primary-600 bg-white text-primary-600' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-800');
    return (
        <div className="border-t">
            <div className="flex border-b bg-gray-50" role="tablist">
                <button type="button" role="tab" aria-selected={tab === 'description'} onClick={() => setTab('description')} className={tabBtn('description')}><FileText size={16} />{t('description', 'Description')}</button>
                {product.features.length > 0 && <button type="button" role="tab" aria-selected={tab === 'features'} onClick={() => setTab('features')} className={tabBtn('features')}><ListChecks size={16} />{t('features', 'Features')}</button>}
            </div>
            <div className="p-6 lg:p-8" role="tabpanel">
                {tab === 'description'
                    // description is sanitised server-side (safe_html)
                    ? <div className="sf-pd-long prose max-w-none leading-relaxed text-gray-600" dangerouslySetInnerHTML={{ __html: product.description }} />
                    : (
                        <table className="w-full"><tbody className="divide-y divide-gray-200">
                            {product.features.map((f, i) => <tr key={f.name} className={i % 2 ? 'bg-gray-50' : 'bg-white'}><th scope="row" className="w-1/3 px-4 py-3 text-start text-sm font-medium text-gray-900">{f.name}</th><td className="px-4 py-3 text-sm text-gray-600">{f.value}</td></tr>)}
                        </tbody></table>
                    )}
            </div>
        </div>
    );
}

function AiAnalysis({ a }) {
    const mood = a.overall_sentiment;
    const Icon = mood === 'positive' ? Smile : mood === 'negative' ? Frown : Meh;
    const tone = {
        positive: ['bg-green-100 text-green-600', 'text-green-700'],
        negative: ['bg-red-100 text-red-600', 'text-red-700'],
    }[mood] ?? ['bg-yellow-100 text-yellow-600', 'text-yellow-700'];
    return (
        <div className="mb-8 rounded-xl border border-purple-100 bg-gradient-to-r from-purple-50 to-indigo-50 p-5">
            <div className="mb-4 flex items-center gap-2"><Bot size={18} className="text-purple-600" /><h4 className="font-semibold text-purple-900">AI Review Analysis</h4><span className="rounded-full bg-purple-100 px-2 py-0.5 text-xs text-purple-700">Beta</span></div>
            <div className="grid gap-4 md:grid-cols-2">
                {mood && <div className="flex items-center gap-3"><span className={cx('flex h-10 w-10 items-center justify-center rounded-full', tone[0])}><Icon size={18} /></span><div><p className="text-sm text-gray-500">Overall sentiment</p><p className={cx('font-semibold capitalize', tone[1])}>{mood}</p></div></div>}
                {a.recommendation_rate != null && <div className="flex items-center gap-3"><span className="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-blue-600"><ThumbsUp size={18} /></span><div><p className="text-sm text-gray-500">Would recommend</p><p className="font-semibold text-blue-700">{a.recommendation_rate}%</p></div></div>}
            </div>
            {a.summary && <p className="mt-4 text-sm text-gray-700 italic">“{a.summary}”</p>}
            <div className="mt-4 grid gap-4 md:grid-cols-2">
                {a.common_pros?.length > 0 && <div><p className="mb-2 flex items-center gap-1 text-sm font-medium text-green-700"><PlusCircle size={14} />Pros</p><ul className="space-y-1 text-sm text-gray-600">{a.common_pros.slice(0, 3).map((p) => <li key={p} className="flex gap-2"><Check size={14} className="mt-0.5 text-green-500" />{p}</li>)}</ul></div>}
                {a.common_cons?.length > 0 && <div><p className="mb-2 flex items-center gap-1 text-sm font-medium text-red-700"><MinusCircle size={14} />Cons</p><ul className="space-y-1 text-sm text-gray-600">{a.common_cons.slice(0, 3).map((c) => <li key={c} className="flex gap-2"><X size={14} className="mt-0.5 text-red-500" />{c}</li>)}</ul></div>}
            </div>
        </div>
    );
}

export function Reviews({ product, reviews, analysis }) {
    const t = useT();
    const { customer } = useStore();
    const form = useForm({ rating: '5', title: '', comment: '' });
    const submit = (e) => {
        e.preventDefault();
        form.post(`/shop/${product.id}/review`, { preserveScroll: true, onSuccess: () => form.reset() });
    };
    return (
        <section id="reviews" className="mt-8 rounded-2xl bg-white p-6 shadow-sm lg:p-8">
            <h2 className="mb-6 text-xl font-bold">{t('customer_reviews', 'Customer Reviews')}</h2>
            {analysis && <AiAnalysis a={analysis} />}
            {customer ? (
                <form onSubmit={submit} className="mb-8 rounded-xl bg-gray-50 p-6">
                    <h3 className="mb-4 font-semibold">{t('write_review', 'Write a Review')}</h3>
                    <label className="mb-4 block"><span className="mb-2 block text-sm font-medium text-gray-700">{t('your_rating', 'Rating')}</span>
                        <select value={form.data.rating} onChange={(e) => form.setData('rating', e.target.value)} className="rounded-lg border px-4 py-2">
                            {[[5, 'Excellent'], [4, 'Good'], [3, 'Average'], [2, 'Poor'], [1, 'Terrible']].map(([v, l]) => <option key={v} value={v}>{v} - {l}</option>)}
                        </select>
                    </label>
                    <label className="mb-4 block"><span className="mb-2 block text-sm font-medium text-gray-700">{t('title_optional', 'Title (optional)')}</span>
                        <input type="text" maxLength={255} value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} className="w-full rounded-lg border px-4 py-2" />
                    </label>
                    <label className="mb-4 block"><span className="mb-2 block text-sm font-medium text-gray-700">{t('your_review', 'Your Review')}</span>
                        <textarea rows={4} required minLength={10} value={form.data.comment} onChange={(e) => form.setData('comment', e.target.value)} className="w-full rounded-lg border px-4 py-2" />
                        {form.errors.comment && <span className="mt-1 block text-sm text-red-600">{form.errors.comment}</span>}
                    </label>
                    <button type="submit" disabled={form.processing} className="sf-btn rounded-lg bg-primary-600 px-6 py-2 text-white hover:bg-primary-700 disabled:opacity-60">{t('submit_review', 'Submit Review')}</button>
                </form>
            ) : <p className="mb-6 text-sm text-gray-600"><a href="/login" className="text-primary-600 hover:underline">{t('login', 'Log in')}</a> {t('to_write_review', 'to write a review.')}</p>}
            {reviews.data.length > 0 ? (
                <>
                    <div className="space-y-6">
                        {reviews.data.map((r) => (
                            <article key={r.id} className="border-b pb-6">
                                <div className="mb-2 flex items-center gap-2"><Stars rating={r.rating} size={14} className="text-yellow-400" empty="text-gray-300" />{r.title && <span className="font-semibold">{r.title}</span>}</div>
                                <p className="mb-2 text-gray-600">{r.comment}</p>
                                <p className="text-sm text-gray-500">{t('by', 'By')} {r.author} · {r.date}</p>
                            </article>
                        ))}
                    </div>
                    <Pagination products={reviews} />
                </>
            ) : <p className="text-gray-500">{t('be_first_review', 'No reviews yet. Be the first to review this product!')}</p>}
        </section>
    );
}

export function ProductRowSection({ title, products, badge }) {
    const Card = useThemed('ProductCard', ProductCard);
    if (!products.length) return null;
    return (
        <section className="mt-12">
            <div className="mb-6 flex items-center gap-3"><h2 className="text-2xl font-bold">{title}</h2>{badge}</div>
            <div className="grid auto-rows-fr grid-cols-2 gap-4 md:grid-cols-4 md:gap-6">{products.map((p) => <Card key={p.id} product={p} />)}</div>
        </section>
    );
}

/** Sticky add-to-cart bar on phones; shows once the inline buttons scroll out of view. */
export function MobileBar({ product, options, variation, quantity, setQuantity }) {
    const t = useT();
    const { customer, features } = useStore();
    const addToWishlist = useAddToWishlist();
    const buy = useBuy(product, variation, quantity);
    const [visible, setVisible] = useState(false);
    useEffect(() => {
        const target = document.getElementById('inline-cart-buttons');
        if (!target || !('IntersectionObserver' in window)) { setVisible(true); return undefined; }
        const io = new IntersectionObserver(([e]) => setVisible(!e.isIntersecting && e.boundingClientRect.top < 0));
        io.observe(target);
        return () => io.disconnect();
    }, []);
    if (!options.stickyBar || !product.inStock) return null;
    const layout = options.mobileLayout;

    return (
        <div className={cx('fixed inset-x-0 bottom-0 z-40 border-t border-gray-200 bg-white px-4 py-3 shadow-lg transition-transform duration-300 md:hidden', visible ? 'translate-y-0' : 'translate-y-full')} style={{ paddingBottom: 'max(0.75rem, env(safe-area-inset-bottom))' }}>
            <div className="mb-2 flex items-center justify-between">
                <div><span className={cx('text-lg font-bold', product.regular ? 'text-primary-600' : 'text-gray-900')}>{variation?.price ?? product.price}</span>{!variation && product.regular && <span className="ms-1 text-sm text-gray-400 line-through">{product.regular}</span>}</div>
                {options.mobileQuantity && <QuantityInput value={quantity} onChange={setQuantity} max={variation ? variation.quantity : product.quantity || undefined} small />}
            </div>
            <div className="flex gap-3">
                {layout === 'with_icons' && options.showWishlist && features.wishlist && customer && (
                    <button type="button" onClick={() => addToWishlist(product)} aria-label={t('add_to_wishlist', 'Add to wishlist')} className="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-gray-300 text-gray-600 hover:border-red-500 hover:text-red-500"><Heart size={18} /></button>
                )}
                {buy.needsChoice ? (
                    <a href="#inline-cart-buttons" className="sf-btn flex flex-1 items-center justify-center gap-2 rounded-md bg-primary-600 py-3 text-sm font-medium text-white"><Eye size={16} />{t('select_options', 'Select options')}</a>
                ) : layout === 'cart_only' ? (
                    <div className="flex-1"><BuyButtons product={product} options={{ ...options, showBuyNow: false }} buy={buy} compact /></div>
                ) : (
                    <div className="flex flex-1 gap-3 [&>button]:flex-1"><BuyButtons product={product} options={options} buy={buy} compact /></div>
                )}
            </div>
        </div>
    );
}
