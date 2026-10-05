import { useEffect, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { Check, ChevronLeft, ChevronRight, Filter, Folder, Home, LayoutGrid, RotateCcw, Search, SlidersHorizontal, Tag, X } from 'lucide-react';
import { cx, useStore, useT } from '../lib/store';
import { useThemed } from '../theme/registry';
import ProductCard from '../Product/ProductCard';
import ProductCardList from '../Product/ProductCardList';

/** Current query string as an object, so filters can change one value and keep the rest. */
export function useQuery() {
    const { url } = usePage();
    const q = url.includes('?') ? url.slice(url.indexOf('?') + 1) : '';
    return Object.fromEntries(new URLSearchParams(q));
}

/** Go to the shop with the current filters changed by `patch` (null removes a filter, page resets). */
export function useFilterNav(base = '/shop') {
    const query = useQuery();
    return (patch, opts = {}) => {
        const next = { ...query, ...patch };
        delete next.page;
        Object.keys(next).forEach((k) => (next[k] === null || next[k] === '' || next[k] === undefined) && delete next[k]);
        router.get(base, next, { preserveScroll: opts.preserveScroll ?? false, preserveState: true });
    };
}

/** Grid/list and column choice, remembered per browser (only read after hydration, so SSR markup matches). */
export function useViewPrefs(defaultCols) {
    const [view, setView] = useState({ mode: 'grid', cols: defaultCols });
    useEffect(() => {
        try {
            setView({ mode: localStorage.getItem('shopViewMode') || 'grid', cols: localStorage.getItem('shopGridCols') || defaultCols });
        } catch { /* storage blocked */ }
    }, [defaultCols]);
    const save = (patch) => setView((v) => {
        const next = { ...v, ...patch };
        try {
            localStorage.setItem('shopViewMode', next.mode);
            localStorage.setItem('shopGridCols', next.cols);
        } catch { /* storage blocked */ }
        return next;
    });
    return [view, save];
}

export function Breadcrumb({ items }) {
    const t = useT();
    return (
        <nav className="mb-3 flex" aria-label="Breadcrumb">
            <ol className="flex flex-wrap items-center gap-2 text-sm">
                <li><Link href="/" className="text-gray-500 hover:text-primary-600" aria-label={t('home', 'Home')}><Home size={15} /></Link></li>
                {items.map((item) => (
                    <li key={item.label} className="flex items-center gap-2">
                        <ChevronRight size={12} className="text-gray-300 rtl:rotate-180" />
                        {item.href ? <Link href={item.href} className="text-gray-500 hover:text-primary-600">{item.label}</Link>
                            : <span aria-current="page" className={item.accent ? 'font-medium text-primary-600' : 'font-medium text-gray-900'}>{item.label}</span>}
                    </li>
                ))}
            </ol>
        </nav>
    );
}

export function ShopHero({ filters, categories, options }) {
    const t = useT();
    const go = useFilterNav();
    const current = categories.find((c) => c.slug === filters.category);
    const title = filters.search ? `${t('search', 'Search')}: "${filters.search}"` : current?.name ?? t('shop', 'Shop');
    const pill = (active) => cx('rounded-full px-4 py-2 text-sm font-medium transition-all duration-300', active ? 'bg-primary-600 text-white shadow-lg shadow-primary-600/30' : 'border border-gray-200 bg-white text-gray-700 hover:bg-gray-100');
    return (
        <div className="sf-shop-hero border-b border-gray-100 bg-gradient-to-br from-primary-50/40 to-primary-50">
            <div className="sf-container py-8 lg:py-12">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <Breadcrumb items={[{ label: t('shop', 'Shop') }, ...(current ? [{ label: current.name, accent: true }] : [])]} />
                        <h1 className="text-2xl font-bold text-gray-900 lg:text-3xl">{title}</h1>
                        {(options.descDesktop || options.descMobile) && (
                            <p className={cx('mt-1 text-gray-500', !options.descMobile && 'hidden md:block', !options.descDesktop && 'md:hidden')}>{t('shop_description', '')}</p>
                        )}
                    </div>
                    <div className="hidden flex-wrap items-center gap-2 lg:flex">
                        <button type="button" onClick={() => go({ category: null })} className={pill(!filters.category)}>{t('all_categories', 'All Categories')}</button>
                        {categories.slice(0, 5).map((c) => <button key={c.id} type="button" onClick={() => go({ category: c.slug })} className={pill(filters.category === c.slug)}>{c.name}</button>)}
                    </div>
                </div>
            </div>
        </div>
    );
}

export function ActiveFilters({ filters, categories, priceLabel }) {
    const t = useT();
    const go = useFilterNav();
    const current = categories.find((c) => c.slug === filters.category);
    if (!filters.search && !filters.category && !priceLabel && !filters.on_sale) return null;
    const chip = (key, Icon, label, patch) => (
        <span key={key} className="inline-flex items-center gap-1.5 rounded-full bg-primary-50 px-3 py-1.5 text-sm font-medium text-primary-700">
            <Icon size={12} />{label}
            <button type="button" onClick={() => go(patch)} aria-label={`${t('remove', 'Remove')} ${label}`} className="hover:text-primary-900"><X size={12} /></button>
        </span>
    );
    return (
        <div className="mb-6 flex flex-wrap items-center gap-2">
            <span className="me-2 text-sm text-gray-500">{t('filters', 'Filters')}:</span>
            {filters.search && chip('search', Search, `"${filters.search}"`, { search: null })}
            {current && chip('category', Folder, current.name, { category: null })}
            {priceLabel && chip('price', Tag, priceLabel, { min_price: null, max_price: null })}
            {filters.on_sale && chip('sale', Tag, t('on_sale', 'On sale'), { on_sale: null })}
            <Link href="/shop" className="ms-2 text-sm text-gray-500 underline hover:text-primary-600">{t('clear_filters', 'Clear filters')}</Link>
        </div>
    );
}

function PriceInputs({ value, onChange, compact }) {
    return (
        <div className="flex items-center gap-3">
            {['min_price', 'max_price'].map((k, i) => (
                <label key={k} className="flex-1">
                    <span className={cx('block text-xs text-gray-500', compact ? 'mb-1' : 'mb-1.5')}>{i === 0 ? 'Min' : 'Max'}</span>
                    <input type="number" min="0" step="any" inputMode="decimal" value={value[k]} placeholder={i === 0 ? '0' : 'Any'} onChange={(e) => onChange({ ...value, [k]: e.target.value })}
                        className="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                </label>
            ))}
        </div>
    );
}

export function Sidebar({ filters, categories }) {
    const t = useT();
    const go = useFilterNav();
    const [price, setPrice] = useState({ min_price: filters.min_price, max_price: filters.max_price });
    useEffect(() => setPrice({ min_price: filters.min_price, max_price: filters.max_price }), [filters.min_price, filters.max_price]);
    const hasFilters = filters.category || filters.min_price || filters.max_price;
    const row = (active) => cx('flex cursor-pointer items-center justify-between rounded-xl p-2.5 transition-all hover:bg-gray-50', active && 'border border-primary-100 bg-primary-50');

    return (
        <aside className="hidden shrink-0 lg:block lg:w-72">
            <div className="sticky top-24 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <div className="mb-6 flex items-center justify-between">
                    <h2 className="flex items-center gap-2 text-lg font-bold text-gray-900"><Filter size={18} className="text-primary-600" />{t('filters', 'Filters')}</h2>
                    {hasFilters && <button type="button" onClick={() => go({ category: null, min_price: null, max_price: null })} className="text-sm font-medium text-primary-600 hover:text-primary-700">{t('clear_filters', 'Clear filters')}</button>}
                </div>
                <fieldset className="mb-6">
                    <legend className="mb-4 flex items-center gap-2 font-semibold text-gray-900"><LayoutGrid size={16} className="text-gray-400" />{t('categories', 'Categories')}</legend>
                    <div className="max-h-64 space-y-1 overflow-y-auto pe-2">
                        <label className={row(!filters.category)}>
                            <span className="flex items-center gap-3"><input type="radio" name="category" checked={!filters.category} onChange={() => go({ category: null })} className="h-4 w-4 accent-[var(--sf-primary-600)]" /><span className="font-medium text-gray-700">{t('all_categories', 'All Categories')}</span></span>
                        </label>
                        {categories.map((c) => (
                            <label key={c.id} className={row(filters.category === c.slug)}>
                                <span className="flex items-center gap-3"><input type="radio" name="category" checked={filters.category === c.slug} onChange={() => go({ category: c.slug })} className="h-4 w-4 accent-[var(--sf-primary-600)]" /><span className="text-gray-700">{c.name}</span></span>
                                <span className="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-400">{c.count}</span>
                            </label>
                        ))}
                    </div>
                </fieldset>
                <div className="my-5 border-t border-gray-100" />
                <form onSubmit={(e) => { e.preventDefault(); go(price); }}>
                    <fieldset className="mb-6">
                        <legend className="mb-4 flex items-center gap-2 font-semibold text-gray-900"><Tag size={16} className="text-gray-400" />{t('price_range', 'Price range')}</legend>
                        <PriceInputs value={price} onChange={setPrice} />
                    </fieldset>
                    <button type="submit" className="sf-btn flex w-full items-center justify-center gap-2 rounded-xl bg-primary-600 py-3 font-medium text-white shadow-lg shadow-primary-600/30 hover:bg-primary-700"><Check size={16} />{t('apply_filters', 'Apply filters')}</button>
                </form>
            </div>
        </aside>
    );
}

export function MobileFilters({ filters, categories }) {
    const t = useT();
    const go = useFilterNav();
    const [open, setOpen] = useState(false);
    const [price, setPrice] = useState({ min_price: filters.min_price, max_price: filters.max_price });
    return (
        <div className="mb-4 lg:hidden">
            <div className="flex items-center gap-3">
                <select value={filters.category} onChange={(e) => go({ category: e.target.value || null })} aria-label={t('categories', 'Categories')}
                    className="w-full flex-1 rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm shadow-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500 focus:outline-none">
                    <option value="">{t('all_categories', 'All Categories')}</option>
                    {categories.map((c) => <option key={c.id} value={c.slug}>{c.name} ({c.count})</option>)}
                </select>
                <button type="button" onClick={() => setOpen((o) => !o)} aria-expanded={open}
                    className={cx('flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50', open && 'border-primary-500 ring-2 ring-primary-500')}>
                    <SlidersHorizontal size={16} />{t('filters', 'Filters')}
                    {(filters.min_price || filters.max_price) && <span className="h-2 w-2 animate-pulse rounded-full bg-primary-600" />}
                </button>
            </div>
            {open && (
                <form onSubmit={(e) => { e.preventDefault(); go(price); }} className="mt-3 rounded-2xl border border-gray-100 bg-white p-5 shadow-lg">
                    <h3 className="mb-4 flex items-center gap-2 font-semibold text-gray-900"><Tag size={16} className="text-primary-600" />{t('price_range', 'Price range')}</h3>
                    <div className="mb-5"><PriceInputs value={price} onChange={setPrice} compact /></div>
                    <div className="flex gap-2">
                        <button type="submit" className="sf-btn flex flex-1 items-center justify-center gap-2 rounded-xl bg-primary-600 py-3 font-medium text-white shadow-lg shadow-primary-600/30 hover:bg-primary-700"><Check size={16} />{t('apply_filters', 'Apply filters')}</button>
                        <button type="button" onClick={() => setOpen(false)} className="rounded-xl border border-gray-200 px-5 py-3 font-medium text-gray-600 hover:bg-gray-50">{t('cancel', 'Cancel')}</button>
                    </div>
                </form>
            )}
        </div>
    );
}

const COL_ICONS = { 2: [[1, 6], [9, 6]], 3: [[1, 4], [6, 4], [11, 4]], 4: [[1, 3], [5, 3], [9, 3], [13, 2]] };

export function SortBar({ products, filters, perPage, view, setView, options }) {
    const t = useT();
    const go = useFilterNav();
    const select = 'rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500 focus:outline-none';
    const btn = (active) => cx('rounded-lg p-2 transition-all', active ? 'bg-white text-primary-600 shadow-sm' : 'text-gray-400 hover:text-gray-600');
    return (
        <div className="mb-6 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
            <div className="flex flex-col items-center justify-between gap-4 sm:flex-row">
                <p className="text-sm text-gray-600">
                    {t('showing', 'Showing')} <span className="font-semibold text-gray-900">{products.from ?? 0}-{products.to ?? 0}</span> {t('of', 'of')} <span className="font-semibold text-gray-900">{products.total}</span> {t('products', 'products')}
                </p>
                <div className="flex flex-wrap items-center gap-3">
                    {options.showPerPage && (
                        <label className="flex items-center gap-2 text-sm text-gray-500">{t('showing', 'Showing')}:
                            <select value={perPage} onChange={(e) => go({ per_page: e.target.value })} className={select}>{[12, 24, 36, 48].map((n) => <option key={n} value={n}>{n}</option>)}</select>
                        </label>
                    )}
                    {options.showGridOptions && (
                        <div className="hidden items-center gap-1 rounded-xl bg-gray-100 p-1 md:flex" role="group" aria-label="Layout">
                            {['2', '3', '4'].map((cols) => (
                                <button key={cols} type="button" onClick={() => setView({ mode: 'grid', cols })} aria-pressed={view.mode === 'grid' && view.cols === cols} title={`${cols} columns`} className={btn(view.mode === 'grid' && view.cols === cols)}>
                                    <svg className="h-4 w-4" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">{COL_ICONS[cols].map(([x, w]) => <rect key={x} x={x} y="1" width={w} height="14" rx="0.5" />)}</svg>
                                </button>
                            ))}
                            <span className="mx-1 h-4 w-px bg-gray-300" />
                            <button type="button" onClick={() => setView({ mode: 'list' })} aria-pressed={view.mode === 'list'} title="List view" className={btn(view.mode === 'list')}>
                                <svg className="h-4 w-4" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><rect x="1" y="1" width="14" height="3" rx="0.5" /><rect x="1" y="6" width="14" height="3" rx="0.5" /><rect x="1" y="11" width="14" height="3" rx="0.5" /></svg>
                            </button>
                        </div>
                    )}
                    <label className="flex items-center gap-2 text-sm text-gray-500">{t('sort_by', 'Sort by')}:
                        <select value={filters.sort || 'newest'} onChange={(e) => go({ sort: e.target.value })} className={cx(select, 'min-w-[140px]')}>
                            <option value="newest">{t('newest', 'Newest')}</option>
                            <option value="price_low">{t('price_low_high', 'Price: low to high')}</option>
                            <option value="price_high">{t('price_high_low', 'Price: high to low')}</option>
                            <option value="name">{t('name', 'Name')}</option>
                        </select>
                    </label>
                </div>
            </div>
        </div>
    );
}

const GRID = { 2: 'sm:grid-cols-2', 3: 'sm:grid-cols-2 lg:grid-cols-3', 4: 'sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4' };

export function ProductGrid({ products, view = { mode: 'grid', cols: '4' }, emptyAction }) {
    const t = useT();
    const { card } = useStore();
    const Card = useThemed('ProductCard', ProductCard);
    const Row = useThemed('ProductCardList', ProductCardList);

    if (products.data.length === 0) {
        return (
            <div className="rounded-2xl border border-gray-100 bg-white p-12 text-center shadow-sm">
                <span className="mx-auto mb-6 flex h-24 w-24 items-center justify-center rounded-full bg-gray-100 text-gray-300"><Search size={40} /></span>
                <h2 className="mb-2 text-xl font-bold text-gray-900">{t('no_products_found', 'No products found')}</h2>
                <p className="mx-auto mb-6 max-w-md text-gray-500">{t('check_back_later', 'Check back later for new products')}</p>
                {emptyAction ?? <Link href="/shop" className="sf-btn inline-flex items-center gap-2 rounded-xl bg-primary-600 px-6 py-3 font-medium text-white shadow-lg shadow-primary-600/30 hover:bg-primary-700"><RotateCcw size={16} />{t('clear_filters', 'Clear filters')}</Link>}
            </div>
        );
    }

    return (
        <>
            {view.mode === 'list' ? (
                <div className="space-y-4">{products.data.map((p) => <Row key={p.id} product={p} />)}</div>
            ) : (
                <div className={cx('grid auto-rows-fr gap-[var(--g)] sm:gap-4 lg:gap-6', card.mobileColumns === '1' ? 'grid-cols-1' : 'grid-cols-2', GRID[view.cols] ?? GRID[4])} style={{ '--g': `${card.mobileGapPx}px` }}>
                    {products.data.map((p, i) => <div key={p.id} className="h-full animate-fade-up" style={{ animationDelay: `${Math.min(i, 12) * 50}ms` }}><Card product={p} /></div>)}
                </div>
            )}
            <Pagination products={products} />
        </>
    );
}

/** Laravel paginator links ("&laquo; Previous", numbers, "Next &raquo;") as Inertia links. */
export function Pagination({ products }) {
    const t = useT();
    if (!products.links || products.last_page <= 1) return null;
    const label = (l) => (l.label.includes('Previous') || l.label.includes('&laquo;') ? <><ChevronLeft size={16} className="rtl:rotate-180" /><span className="sr-only">{t('previous', 'Previous')}</span></>
        : l.label.includes('Next') || l.label.includes('&raquo;') ? <><span className="sr-only">{t('next', 'Next')}</span><ChevronRight size={16} className="rtl:rotate-180" /></> : l.label);
    return (
        <nav className="mt-8 flex flex-wrap items-center justify-center gap-1" aria-label="Pagination">
            {products.links.map((l, i) => (l.url ? (
                <Link key={i} href={l.url} preserveScroll={false} aria-current={l.active ? 'page' : undefined}
                    className={cx('flex h-10 min-w-10 items-center justify-center rounded-lg border px-3 text-sm font-medium transition', l.active ? 'border-primary-600 bg-primary-600 text-white' : 'border-gray-200 bg-white text-gray-700 hover:border-primary-300 hover:text-primary-600')}>
                    {label(l)}
                </Link>
            ) : (
                <span key={i} className="flex h-10 min-w-10 items-center justify-center rounded-lg border border-gray-100 px-3 text-sm text-gray-300">{label(l)}</span>
            )))}
        </nav>
    );
}
