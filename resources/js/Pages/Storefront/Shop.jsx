import Seo from '../../Storefront/Layout/Seo';
import { ActiveFilters, MobileFilters, ProductGrid, ShopHero, Sidebar, SortBar, useViewPrefs } from '../../Storefront/Shop/Listing';
import { useThemed } from '../../Storefront/theme/registry';

/** Shop listing (also search results). Blocks can be replaced per theme as "Shop.<Block>". */
export default function Shop(props) {
    const { products, categories, filters, priceLabel, perPage, gridColumns, options, seo } = props;
    const [view, setView] = useViewPrefs(gridColumns);
    const Hero = useThemed('Shop.Hero', ShopHero);
    const Active = useThemed('Shop.ActiveFilters', ActiveFilters);
    const Mobile = useThemed('Shop.MobileFilters', MobileFilters);
    const Side = useThemed('Shop.Sidebar', Sidebar);
    const Sort = useThemed('Shop.SortBar', SortBar);
    const Grid = useThemed('Shop.ProductGrid', ProductGrid);

    return (
        <>
            <Seo title={seo.title} canonical={seo.canonical} />
            <div className="shop-page">
                <Hero filters={filters} categories={categories} options={options} />
                <div className="sf-container py-6 lg:py-8">
                    <Active filters={filters} categories={categories} priceLabel={priceLabel} />
                    <Mobile filters={filters} categories={categories} />
                    <div className="flex flex-col gap-8 lg:flex-row">
                        <Side filters={filters} categories={categories} />
                        <div className="min-w-0 flex-1">
                            <Sort products={products} filters={filters} perPage={perPage} view={view} setView={setView} options={options} />
                            <Grid products={products} view={view} />
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
