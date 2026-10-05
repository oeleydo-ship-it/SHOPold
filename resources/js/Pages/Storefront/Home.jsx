import Seo from '../../Storefront/Layout/Seo';
import Hero from '../../Storefront/Home/Hero';
import {
    Arrivals, Blog, Categories, Featured, Features, FlashDeals, Newsletter, Promo, TopRated, TwoColumnBanners,
} from '../../Storefront/Home/Sections';
import { useStore } from '../../Storefront/lib/store';
import { useThemed } from '../../Storefront/theme/registry';

/** Home page: sections in the order set in Settings > Homepage; each section can be replaced by a theme ("Home.<Name>"). */
export default function Home(props) {
    const { store } = useStore();
    const components = {
        hero: useThemed('Home.Hero', Hero),
        features: useThemed('Home.Features', Features),
        categories: useThemed('Home.Categories', Categories),
        featured: useThemed('Home.Featured', Featured),
        flash_deals: useThemed('Home.FlashDeals', FlashDeals),
        top_rated: useThemed('Home.TopRated', TopRated),
        two_column_banners: useThemed('Home.TwoColumnBanners', TwoColumnBanners),
        promo: useThemed('Home.Promo', Promo),
        arrivals: useThemed('Home.Arrivals', Arrivals),
        blog: useThemed('Home.Blog', Blog),
        newsletter: useThemed('Home.Newsletter', Newsletter),
    };
    const data = {
        hero: { hero: props.hero },
        features: { home: props.home },
        categories: { home: props.home, categories: props.categories },
        featured: { home: props.home, products: props.featured },
        flash_deals: { home: props.home, products: props.flashDeals },
        top_rated: { home: props.home, products: props.topRated },
        two_column_banners: { home: props.home },
        promo: { home: props.home },
        arrivals: { home: props.home, products: props.arrivals },
        blog: { home: props.home, posts: props.blogPosts },
        newsletter: { home: props.home },
    };

    return (
        <>
            <Seo canonical={props.seo?.canonical} jsonLd={{
                '@context': 'https://schema.org', '@type': 'WebSite', name: store.name, url: props.seo?.canonical,
                potentialAction: { '@type': 'SearchAction', target: `${props.seo?.canonical?.replace(/\/$/, '')}/shop?search={search_term_string}`, 'query-input': 'required name=search_term_string' },
            }} />
            <div className="home-page">
                {props.sections.map((key) => {
                    const Section = components[key];
                    return Section ? <Section key={key} {...data[key]} /> : null;
                })}
            </div>
        </>
    );
}
