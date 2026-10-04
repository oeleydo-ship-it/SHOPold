import { Head } from '@inertiajs/react';
import { ArrowDown, ArrowUp, ExternalLink, GripVertical } from 'lucide-react';
import SettingsLayout from '../../../Layouts/SettingsLayout';
import SchemaForm, { SchemaBlocks, SchemaField, SubHeading, useSchemaSettings } from '../../../Components/schemaForm';
import { LinkButton, Card } from '../../../Components/ui';
import { SaveButton, SettingsCard } from '../../../Components/settingsUi';

const text = (key, label, def = '', extra = {}) => ({ key, label, type: 'text', default: def, ...extra });
const area = (key, label, def = '') => ({ key, label, type: 'textarea', default: def, rows: 2, full: true });
const color = (key, label, def) => ({ key, label, type: 'color', default: def });
const count = (key, label, def, opts) => ({ key, label, type: 'select', default: String(def), options: opts.map((n) => [String(n), `${n} Products`]) });
const SECTION_NAMES = {
    hero: 'Hero Banner', features: 'Features Bar', categories: 'Categories', featured: 'Featured Products', flash_deals: 'Super Flash Deals',
    top_rated: 'Top Rated Products', two_column_banners: 'Two Column Banners', promo: 'Promo Banner', arrivals: 'New Arrivals', blog: 'Blog Posts', newsletter: 'Newsletter',
};
const ICONS = [['truck', '🚚 Truck (Shipping)'], ['undo', '↩️ Undo (Returns)'], ['shield-alt', '🛡️ Shield (Security)'], ['headset', '🎧 Headset (Support)'], ['gift', '🎁 Gift'], ['clock', '⏰ Clock'], ['credit-card', '💳 Credit Card'], ['heart', '❤️ Heart']];
const FEATURE_DEFAULTS = [
    ['truck', 'Free Shipping', 'On orders over $50'], ['undo', 'Easy Returns', '30-day return policy'],
    ['shield-alt', 'Secure Payment', '100% secure checkout'], ['headset', '24/7 Support', 'Dedicated support'],
];
const featureFields = FEATURE_DEFAULTS.flatMap(([icon, title, desc], i) => [
    { key: `homepage.feature_${i + 1}_icon`, label: `Feature ${i + 1} Icon`, type: 'select', default: icon, options: ICONS },
    text(`homepage.feature_${i + 1}_title`, 'Title', title),
    text(`homepage.feature_${i + 1}_desc`, 'Description', desc),
]);

const banner = (n, title, def) => [
    text(`homepage.banner${n}_badge`, 'Badge Text', 'GET 50% DISCOUNT'),
    text(`homepage.banner${n}_title`, 'Title', title),
    text(`homepage.banner${n}_button`, 'Button Text', 'Shop Now'),
    text(`homepage.banner${n}_link`, 'Button Link', def),
    { key: `homepage.banner${n}_bg_type`, label: 'Background Type', type: 'select', default: 'image', options: [['image', 'Image'], ['color', 'Color']] },
    { key: `homepage.banner${n}_image`, label: 'Background Image', type: 'image', collection: 'pages', showIf: (d) => d[`homepage.banner${n}_bg_type`] === 'image' },
    { ...color(`homepage.banner${n}_bg_color`, 'Background Color', '#111827'), showIf: (d) => d[`homepage.banner${n}_bg_type`] === 'color' },
];

const MAIN = [
    {
        id: 'hero', title: 'Hero Section', description: 'Main banner at the top of homepage', collapsible: true, startOpen: true,
        fields: [
            text('homepage.hero_badge', 'Badge Text', `New Collection ${new Date().getFullYear()}`),
            { key: 'homepage.hero_badge_icon', label: 'Badge Icon', type: 'select', default: 'star', options: [['star', '⭐ Star'], ['fire', '🔥 Fire'], ['sparkles', '✨ Sparkles'], ['gift', '🎁 Gift'], ['tag', '🏷️ Tag']] },
            text('homepage.hero_title', 'Hero Title', 'Discover Premium Products for Every Style', { full: true }),
            area('homepage.hero_subtitle', 'Hero Subtitle', 'Shop the latest trends with exclusive deals. Quality products, fast shipping, and exceptional service.'),
            text('homepage.hero_button_text', 'Primary Button Text', 'Shop Now'), text('homepage.hero_button_link', 'Primary Button Link', '/shop'),
            text('homepage.hero_secondary_text', 'Secondary Button Text', 'Learn More'), text('homepage.hero_secondary_link', 'Secondary Button Link', '/about'),
        ],
    },
    { id: 'features', title: 'Features Bar', description: 'Free shipping, returns, support icons', collapsible: true, toggleKey: 'homepage.show_features', toggleDefault: true, columns: 3, fields: featureFields },
    {
        id: 'categories', title: 'Categories Section', collapsible: true, toggleKey: 'homepage.show_categories', toggleDefault: true,
        fields: [
            text('homepage.categories_label', 'Section Label', 'Browse'), text('homepage.categories_title', 'Section Title', 'Shop by Category'),
            area('homepage.categories_desc', 'Section Description', 'Explore our curated collection of products across various categories'),
            { key: 'homepage.categories_item_font_size', label: 'Font Size (Mobile) px', type: 'number', default: 12, min: 8, max: 24 },
            { key: 'homepage.categories_item_font_size_md', label: 'Font Size (Desktop) px', type: 'number', default: 14, min: 8, max: 30 },
            { key: 'homepage.categories_item_font_weight', label: 'Font Weight', type: 'select', default: '500', options: [['400', '400 (Normal)'], ['500', '500 (Medium)'], ['600', '600 (Semibold)'], ['700', '700 (Bold)']] },
            color('homepage.categories_item_text_color', 'Text Color', '#1f2937'), color('homepage.categories_item_hover_color', 'Hover Color', '#2563eb'),
        ],
    },
    {
        id: 'featured', title: 'Featured Products', collapsible: true, toggleKey: 'homepage.show_featured', toggleDefault: true,
        fields: [text('homepage.featured_label', 'Section Label', 'Handpicked'), text('homepage.featured_title', 'Section Title', 'Featured Products'), count('homepage.featured_count', 'Number of Products', 8, [4, 8, 12]), text('homepage.featured_button', 'Button Text', 'View All')],
    },
    {
        id: 'flash', title: 'Super Flash Deals', collapsible: true, toggleKey: 'homepage.show_flash_deals', toggleDefault: true,
        fields: [text('homepage.flash_deals_title', 'Section Title', 'Super Flash Sale'), text('homepage.flash_deals_button', 'Button Text', 'Shop More'), count('homepage.flash_deals_count', 'Number of Products', 4, [4, 6, 8]), { key: 'homepage.flash_deals_end_date', label: 'Sale End Date', type: 'datetime', default: '' }],
    },
    {
        id: 'top_rated', title: 'Top Rated Products', collapsible: true, toggleKey: 'homepage.show_top_rated', toggleDefault: true,
        fields: [
            text('homepage.top_rated_title', 'Section Title', 'Top Rated'), count('homepage.top_rated_count', 'Number of Products', 6, [4, 6, 8]),
            text('homepage.top_rated_promo_badge', 'Promo Badge Text', 'GET 50% DISCOUNT'), text('homepage.top_rated_promo_title', 'Promo Title', 'Get Up to 20% off for Computer Gadget'),
            area('homepage.top_rated_promo_desc', 'Promo Description', 'We collect latest iphone mobile series and apple gadget collection.'),
            text('homepage.top_rated_promo_button', 'Promo Button Text', 'Explore More'), text('homepage.top_rated_promo_link', 'Promo Button Link', '/shop?category=electronics'),
            { key: 'homepage.top_rated_promo_image', label: 'Promo Image', type: 'image', collection: 'pages', full: true },
        ],
    },
    {
        id: 'banners', title: 'Two Column Banners', collapsible: true, toggleKey: 'homepage.show_two_column_banners', toggleDefault: true,
        fields: [...banner(1, 'Fresh Grocery Items Now Available', '/shop?category=grocery'), ...banner(2, 'New Home Appliance For Your Home', '/shop?category=appliances')],
    },
    {
        id: 'promo', title: 'Promotional Banner', description: 'Background is set under Global Styling', collapsible: true, toggleKey: 'homepage.show_promo', toggleDefault: true,
        fields: [
            text('homepage.promo_badge', 'Badge Text', 'Limited Time'), text('homepage.promo_discount', 'Discount Value', '50', { placeholder: 'e.g., 50' }),
            text('homepage.promo_title', 'Banner Title', 'Up to 50% Off on Selected Items', { full: true }),
            area('homepage.promo_desc', 'Banner Description', "Don't miss out on our biggest sale of the season. Shop now and save big!"),
            text('homepage.promo_button', 'Button Text', 'Shop Sale'), text('homepage.promo_link', 'Button Link', '/shop?sale=1'),
        ],
    },
    {
        id: 'arrivals', title: 'New Arrivals', collapsible: true, toggleKey: 'homepage.show_new_arrivals', toggleDefault: true,
        fields: [text('homepage.arrivals_label', 'Section Label', 'Just In'), text('homepage.arrivals_title', 'Section Title', 'New Arrivals'), count('homepage.arrivals_count', 'Number of Products', 8, [4, 8, 12]), text('homepage.arrivals_button', 'Button Text', 'View All')],
    },
    {
        id: 'blog', title: 'Blog Section', collapsible: true, toggleKey: 'homepage.show_blog', toggleDefault: false,
        fields: [
            text('homepage.blog_title', 'Section Title', 'Latest Blog Posts'), text('homepage.blog_button', 'Button Text', 'View All Posts'),
            area('homepage.blog_subtitle', 'Section Subtitle', 'Stay updated with our latest news and articles'),
            { key: 'homepage.blog_count', label: 'Number of Posts', type: 'select', default: '3', options: [2, 3, 4, 6].map((n) => [String(n), `${n} Posts`]) },
            { key: 'homepage.blog_columns', label: 'Grid Columns', type: 'select', default: '3', options: [2, 3, 4].map((n) => [String(n), `${n} Columns`]) },
        ],
    },
    {
        id: 'newsletter', title: 'Newsletter Section', collapsible: true, toggleKey: 'homepage.show_newsletter', toggleDefault: true,
        fields: [
            text('homepage.newsletter_badge', 'Badge Text', 'Stay Updated'), text('homepage.newsletter_title', 'Section Title', 'Subscribe to Our Newsletter'),
            area('homepage.newsletter_desc', 'Description', 'Get exclusive deals, new product announcements, and style tips delivered to your inbox.'),
            text('homepage.newsletter_placeholder', 'Placeholder Text', 'Enter your email address'), text('homepage.newsletter_button', 'Button Text', 'Subscribe'),
            text('homepage.newsletter_note', 'Footer Note', 'No spam, unsubscribe anytime.'),
        ],
    },
];

const bgBlock = (name, label, defType, defColor, imageKey, colorKey, typeKey) => [
    { key: typeKey, label, type: 'select', default: defType, options: [['color', 'Color'], ['image', 'Image']] },
    { ...color(colorKey, 'Color', defColor), showIf: (d) => d[typeKey] !== 'image' },
    { key: imageKey, label: 'Image', type: 'image', collection: 'pages', showIf: (d) => d[typeKey] === 'image' },
];

const SIDE = [
    {
        id: 'global', title: 'Global Styling', columns: 1,
        fields: [
            { key: 'homepage.heading_font', label: 'Heading Font', type: 'select', default: '', options: [['', 'Default (Theme Font)'], ['Playfair Display', 'Playfair Display (Elegant)'], ['Poppins', 'Poppins (Modern)'], ['Montserrat', 'Montserrat (Bold)'], ['Lora', 'Lora (Classic)'], ['Oswald', 'Oswald (Strong)'], ['Merriweather', 'Merriweather (Readable)'], ['Raleway', 'Raleway (Sleek)'], ['Cormorant Garamond', 'Cormorant Garamond (Luxury)']] },
            { key: 'homepage.font_family', label: 'Body Font', type: 'select', default: '', options: [['', 'Default (Theme Font)'], ['Inter', 'Inter'], ['Open Sans', 'Open Sans'], ['Roboto', 'Roboto'], ['Lato', 'Lato'], ['Source Sans Pro', 'Source Sans Pro'], ['Nunito', 'Nunito']] },
            color('homepage.hero_bg_color', 'Hero Background', '#2563eb'), color('homepage.features_bg_color', 'Features Bar BG', '#ffffff'),
            color('homepage.categories_bg_color', 'Categories BG', '#f9fafb'), color('homepage.featured_bg_color', 'Featured Products BG', '#ffffff'),
            ...bgBlock('promo', 'Promo Banner Background', 'color', '#1f2937', 'homepage.promo_bg_image', 'homepage.promo_bg_color', 'homepage.promo_bg_type'),
            color('homepage.arrivals_bg_color', 'New Arrivals BG', '#f9fafb'),
            ...bgBlock('newsletter', 'Newsletter Background', 'color', '#2563eb', 'homepage.newsletter_bg_image', 'homepage.newsletter_bg_color', 'homepage.newsletter_bg_type'),
        ],
    },
];

const ORDER = 'homepage.section_order';

function SectionOrder({ data, set }) {
    const order = data[ORDER];
    const move = (i, dir) => {
        const j = i + dir;
        if (j < 0 || j >= order.length) return;
        const next = [...order];
        [next[i], next[j]] = [next[j], next[i]];
        set(ORDER, next);
    };
    return (
        <SettingsCard title="Section Order" description="Use the arrows to reorder homepage sections">
            <ol className="space-y-2">
                {order.map((name, i) => (
                    <li key={name} className="flex items-center gap-2 rounded-lg border border-line bg-surface-2 px-3 py-2 text-sm">
                        <GripVertical size={14} className="text-muted" />
                        <span className="flex-1">{SECTION_NAMES[name] ?? name}</span>
                        <button type="button" onClick={() => move(i, -1)} disabled={i === 0} aria-label={`Move ${SECTION_NAMES[name]} up`} className="text-muted hover:text-ink disabled:opacity-30"><ArrowUp size={14} /></button>
                        <button type="button" onClick={() => move(i, 1)} disabled={i === order.length - 1} aria-label={`Move ${SECTION_NAMES[name]} down`} className="text-muted hover:text-ink disabled:opacity-30"><ArrowDown size={14} /></button>
                    </li>
                ))}
            </ol>
        </SettingsCard>
    );
}

export default function Homepage({ values, sectionOrder, defaultFlashEnd, homeUrl }) {
    // the flash-sale end date falls back to "a week from now" until one is saved
    const seeded = { ...values };
    if (!seeded['homepage.flash_deals_end_date']) seeded['homepage.flash_deals_end_date'] = defaultFlashEnd;
    const state = useSchemaSettings('homepage', [...MAIN, ...SIDE], seeded, { [ORDER]: sectionOrder });
    const { data, set, submit, processing } = state;

    return (
        <SettingsLayout>
            <Head title="Homepage Editor" />
            <div className="flex flex-wrap items-center justify-between gap-3">
                <p className="text-sm text-muted">Customize your homepage content and layout</p>
                <LinkButton external tone="neutral" href={homeUrl} target="_blank" rel="noreferrer"><ExternalLink size={16} />Preview Homepage</LinkButton>
            </div>

            <form onSubmit={(e) => { e.preventDefault(); submit((d) => ({ ...d, [ORDER]: d[ORDER].join(',') })); }} className="grid items-start gap-5 xl:grid-cols-3">
                <div className="space-y-4 xl:col-span-2">
                    <SchemaBlocks blocks={MAIN} data={data} set={set} ctx={{ data, set }} />
                </div>
                <div className="space-y-5 xl:sticky xl:top-20">
                    <SchemaBlocks blocks={SIDE} data={data} set={set} ctx={{ data, set }} />
                    <SectionOrder data={data} set={set} />
                    <Card className="p-4">
                        <h3 className="mb-3 text-sm font-semibold">Quick Actions</h3>
                        <div className="grid grid-cols-2 gap-2 text-sm">
                            {[['/admin/settings/appearance', 'Theme Settings', 'Colors, fonts, layout'], ['/admin/products', 'Manage Products', 'Add featured products'], ['/admin/categories', 'Manage Categories', 'Edit category images'], ['/admin/media', 'Media Library', 'Upload images']].map(([href, t, d]) => (
                                <a key={href} href={href} className="rounded-lg border border-line p-3 hover:bg-surface-2"><span className="block font-medium">{t}</span><span className="text-xs text-muted">{d}</span></a>
                            ))}
                        </div>
                    </Card>
                    <SaveButton processing={processing}>Save Homepage</SaveButton>
                </div>
            </form>
        </SettingsLayout>
    );
}
