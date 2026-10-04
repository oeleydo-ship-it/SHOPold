import { Head } from '@inertiajs/react';
import { ExternalLink, Globe } from 'lucide-react';
import SettingsLayout from '../../../Layouts/SettingsLayout';
import SchemaForm, { useSchemaSettings } from '../../../Components/schemaForm';
import { Card, LinkButton } from '../../../Components/ui';

const toggle = (key, label, hint, def = true) => ({ key, label, type: 'toggle', default: def, hint });
const select = (key, label, def, options, hint) => ({ key, label, type: 'select', default: def, options, hint });
const color = (key, label, def) => ({ key, label, type: 'color', default: def });

const FONTS = [['', 'Same as Theme'], ['Inter', 'Inter (Modern)'], ['Roboto', 'Roboto (Clean)'], ['Open Sans', 'Open Sans (Friendly)'], ['Poppins', 'Poppins (Geometric)'], ['Montserrat', 'Montserrat (Bold)'], ['Playfair Display', 'Playfair Display (Elegant)'], ['Oswald', 'Oswald (Condensed)'], ['Raleway', 'Raleway (Stylish)'], ['Lato', 'Lato (Professional)'], ['Nunito', 'Nunito (Rounded)']];
const TITLE_PX = { xl: 20, '2xl': 24, '3xl': 30, '4xl': 36 };
const PRICE_PX = { lg: 18, xl: 20, '2xl': 24, '3xl': 30 };

const BLOCKS = [
    {
        id: 'header', title: 'Shop Page Header', columns: 2,
        fields: [
            toggle('shop.show_description_desktop', 'Show on Desktop', 'Display description on larger screens'),
            toggle('shop.show_description_mobile', 'Show on Mobile', 'Display description on mobile devices'),
        ],
        render: ({ shopDescription }) => (
            <div>
                <p className="mb-1 text-sm font-medium">Shop Description</p>
                <div className="mb-2 rounded-lg border border-info/40 bg-info/10 p-3 text-sm">
                    <p className="flex items-center gap-2 text-info"><Globe size={14} />This text is automatically translated based on the customer's language.</p>
                    <p className="mt-1 text-xs text-muted">Edit translations in <a href="/admin/settings/localization" className="font-medium underline">Localization Settings</a> or language files.</p>
                </div>
                <p className="rounded-lg border border-line bg-surface-2 p-3 text-sm">{shopDescription}</p>
            </div>
        ),
    },
    {
        id: 'grid', title: 'Product Grid Settings',
        fields: [
            select('shop.products_per_page', 'Default Products Per Page', '12', [12, 24, 36, 48].map((n) => [String(n), `${n} Products`]), 'Number of products shown per page by default'),
            select('shop.grid_columns', 'Default Grid Columns', '3', [2, 3, 4].map((n) => [String(n), `${n} Columns`]), 'Default number of columns in product grid'),
        ],
    },
    {
        id: 'display', title: 'Display Options', columns: 2,
        fields: [
            toggle('shop.show_filters', 'Show Filters Sidebar', 'Display category and price filters'),
            toggle('shop.show_sorting', 'Show Sorting Options', 'Allow customers to sort products'),
            toggle('shop.show_per_page', 'Show Per Page Selector', 'Allow customers to change products per page'),
            toggle('shop.show_grid_options', 'Show Grid Column Options', 'Allow customers to change grid layout'),
        ],
    },
    {
        id: 'typography', title: 'Product Page Typography', description: 'Customize the font style and size for product detail pages',
        fields: [
            select('shop.product_title_font', 'Product Title Font', '', FONTS),
            select('shop.product_title_weight', 'Title Font Weight', '700', [['400', 'Normal (400)'], ['500', 'Medium (500)'], ['600', 'Semi-Bold (600)'], ['700', 'Bold (700)'], ['800', 'Extra Bold (800)']]),
            select('shop.product_title_size', 'Title Size (Desktop)', '2xl', [['xl', 'Small (20px)'], ['2xl', 'Medium (24px)'], ['3xl', 'Large (30px)'], ['4xl', 'Extra Large (36px)']]),
            color('shop.product_title_color', 'Title Color', '#1f2937'),
            select('shop.price_size', 'Price Size', '2xl', [['lg', 'Small (18px)'], ['xl', 'Medium (20px)'], ['2xl', 'Large (24px)'], ['3xl', 'Extra Large (30px)']]),
            color('shop.price_color', 'Price Color', '#1f2937'),
            color('shop.sale_price_color', 'Sale Price Color', '#dc2626'),
            select('shop.brand_style', 'Brand/Category Text Style', 'uppercase', [['uppercase', 'UPPERCASE'], ['capitalize', 'Capitalize'], ['normal', 'Normal']]),
            color('shop.brand_color', 'Brand/Category Color', '#2563eb'),
        ],
        render: ({ data }) => {
            const font = data['shop.product_title_font'];
            return (
                <div className="rounded-lg border border-line bg-surface-2 p-4" aria-label="Typography preview">
                    <p className="mb-2 text-sm font-medium">Preview</p>
                    <p className="text-xs" style={{ color: data['shop.brand_color'], textTransform: data['shop.brand_style'] === 'normal' ? 'none' : data['shop.brand_style'] }}>Apple</p>
                    <h2
                        style={{ color: data['shop.product_title_color'], fontWeight: data['shop.product_title_weight'], fontSize: TITLE_PX[data['shop.product_title_size']] ?? 24, fontFamily: font ? `'${font}', sans-serif` : undefined }}
                    >Apple MacBook Air (13-inch, Apple M1 chip with 8-Core CPU)</h2>
                    <p className="mt-2 flex items-baseline gap-3" style={{ fontSize: PRICE_PX[data['shop.price_size']] ?? 24 }}>
                        <span className="font-bold" style={{ color: data['shop.sale_price_color'] }}>AED 1,579</span>
                        <span className="text-sm line-through" style={{ color: data['shop.price_color'] }}>AED 2,299</span>
                        <span className="rounded bg-danger/10 px-2 py-0.5 text-xs font-medium text-danger">31% OFF</span>
                    </p>
                </div>
            );
        },
    },
    {
        id: 'cross', title: 'Cross-Selling Settings',
        fields: [
            { ...toggle('shop.cross_sell_enabled', 'Enable Cross-Selling', 'Show cross-sell product recommendations on product pages'), full: true },
            { key: 'shop.cross_sell_limit', label: 'Maximum Products to Display', type: 'number', default: 4, min: 1, max: 12, hint: 'Maximum number of cross-sell products to show (default: 4)' },
            { key: 'shop.cross_sell_title', label: 'Section Title', type: 'text', default: 'Frequently Bought Together', hint: 'Title displayed above cross-sell products' },
        ],
    },
];

export default function Shop({ values, shopDescription, shopUrl }) {
    const state = useSchemaSettings('shop', BLOCKS, values);

    return (
        <SettingsLayout>
            <Head title="Shop Page Settings" />
            <div className="flex flex-wrap items-center justify-between gap-3">
                <p className="text-sm text-muted">Configure how products are displayed in the shop</p>
                <LinkButton external tone="neutral" href={shopUrl} target="_blank" rel="noreferrer"><ExternalLink size={16} />View Shop</LinkButton>
            </div>
            <SchemaForm blocks={BLOCKS} state={state} ctx={{ shopDescription }} />
            <Card className="p-4">
                <h3 className="mb-3 text-sm font-semibold">Quick Links</h3>
                <div className="flex flex-wrap gap-4 text-sm">
                    <a href="/admin/settings/homepage" className="text-primary hover:underline">Homepage Settings</a>
                    <a href="/admin/settings/appearance" className="text-primary hover:underline">Appearance</a>
                    <a href="/admin/products" className="text-primary hover:underline">Manage Products</a>
                </div>
            </Card>
        </SettingsLayout>
    );
}
