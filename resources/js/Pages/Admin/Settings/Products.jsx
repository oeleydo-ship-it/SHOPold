import { Head } from '@inertiajs/react';
import { Smartphone } from 'lucide-react';
import SettingsLayout from '../../../Layouts/SettingsLayout';
import SchemaForm, { SchemaField, SubHeading, useSchemaSettings } from '../../../Components/schemaForm';
import { Card } from '../../../Components/ui';

const px = (key, label, def, min, max, hint) => ({ key, label, type: 'number', default: def, min, max, suffix: 'px', hint });
const t = (key, label, hint, def = false) => ({ key, label, type: 'toggle', default: def, hint });

const SHARE = [
    ['products.share_facebook', 'Facebook', true], ['products.share_twitter', 'X (Twitter)', true], ['products.share_whatsapp', 'WhatsApp', true],
    ['products.share_pinterest', 'Pinterest', true], ['products.share_linkedin', 'LinkedIn', false], ['products.share_email', 'Email', true],
    ['products.share_copy', 'Copy Link', true],
];

function ButtonPreview({ data, prefix }) {
    const isCart = prefix === 'add_to_cart';
    const style = isCart
        ? { background: data['products.add_to_cart_bg_color'], color: data['products.add_to_cart_text_color'], border: `1px solid ${data['products.add_to_cart_border_color']}` }
        : { background: data['products.buy_now_bg_color'], color: data['products.buy_now_text_color'], border: '1px solid transparent' };
    return <button type="button" tabIndex={-1} className="rounded-lg px-5 py-2 text-sm font-medium" style={style}>{isCart ? 'Add to Cart' : 'Buy Now'}</button>;
}

const BLOCKS = [
    {
        id: 'inventory', title: 'Inventory Management', columns: 1,
        fields: [
            t('products.stock_management', 'Enable Stock Management', 'Track inventory quantities for products', true),
            { key: 'products.low_stock_threshold', label: 'Low Stock Threshold', type: 'number', default: 5, min: 0, hint: 'Products with stock at or below this quantity will be flagged as low stock' },
            t('products.hide_out_of_stock', 'Hide Out-of-Stock Products', 'Hide products with zero stock from the catalog'),
            t('products.allow_backorders', 'Allow Backorders', 'Allow customers to purchase products that are out of stock'),
        ],
    },
    {
        id: 'display', title: 'Product Display', columns: 1,
        fields: [
            t('products.show_sku', 'Show SKU on Product Pages', 'Display product SKU on the product detail page', true),
            t('products.show_stock_quantity', 'Show Stock Quantity', 'Display available stock quantity on product pages'),
            t('products.show_category', 'Show Category', 'Display product category on the product detail page', true),
            t('products.show_tags', 'Show Tags', 'Display product tags on the product detail page', true),
            t('products.show_brand', 'Show Brand', 'Display product brand on the product detail page', true),
            { key: 'products.placeholder_image', label: 'Placeholder Image', type: 'image', collection: 'products', buttonLabel: 'Select Placeholder Image', hint: 'Default image shown for products without images' },
            { key: 'products.related_products_count', label: 'Related Products Count', type: 'number', default: 4, min: 0, max: 12, hint: 'Number of related products to display on product pages' },
        ],
    },
    {
        id: 'typography', title: 'Desktop Typography', description: 'Customize font sizes for desktop view (in pixels)',
        fields: [
            px('products.desktop_title_px', 'Product Title Size', 28, 18, 48, 'Product detail page title (default: 28px)'),
            px('products.desktop_price_px', 'Price Size', 24, 16, 40, 'Product price (default: 24px)'),
            px('products.desktop_short_desc_px', 'Short Description Size', 16, 12, 24, 'Short description text (default: 16px)'),
            px('products.desktop_long_desc_px', 'Long Description Size', 16, 12, 24, 'Long description/content (default: 16px)'),
            px('products.desktop_card_title_px', 'Card Title Size', 16, 12, 24, 'Product card title (default: 16px)'),
            px('products.desktop_card_price_px', 'Card Price Size', 18, 14, 28, 'Product card price (default: 18px)'),
        ],
    },
    {
        id: 'titles', title: 'Product Card Title Display', description: 'Control how product titles are displayed on product cards',
        fields: [
            { key: 'products.desktop_title_lines', label: 'Desktop Title Max Lines', type: 'number', default: 2, min: 1, max: 4, suffix: 'lines', hint: 'Max lines before truncation (default: 2)' },
            { key: 'products.mobile_title_lines', label: 'Mobile Title Max Lines', type: 'number', default: 2, min: 1, max: 4, suffix: 'lines', hint: 'Max lines on mobile (default: 2)' },
            { key: 'products.title_char_limit', label: 'Desktop Title Character Limit', type: 'number', default: 0, min: 0, max: 500, suffix: 'chars', hint: 'Max characters on desktop (0 = unlimited)' },
            { key: 'products.mobile_title_char_limit', label: 'Mobile Title Character Limit', type: 'number', default: 0, min: 0, max: 500, suffix: 'chars', hint: 'Max characters on mobile (0 = unlimited)' },
        ],
        render: () => <p className="text-xs text-muted">Titles longer than the limit are truncated with "…". Line clamp and character limit work together.</p>,
    },
    {
        id: 'buttons', title: 'Button Display', columns: 1,
        fields: [
            t('products.show_add_to_cart', 'Show Add to Cart Button', 'Display the Add to Cart button on product pages', true),
            t('products.show_buy_now', 'Show Buy Now Button', 'Display a Buy Now button for direct checkout', true),
            t('products.show_compare', 'Show Compare Button', 'Display the Compare button on product pages', true),
            t('products.show_wishlist', 'Show Wishlist Button', 'Display the Add to Wishlist button on product pages', true),
        ],
    },
    {
        id: 'colors', title: 'Button Colors', columns: 1,
        fields: [t('products.use_custom_button_colors', 'Use Custom Button Colors', 'Enable to use the colors below instead of theme defaults')],
        extraFields: [
            ['products.add_to_cart_bg_color', '#ffffff'], ['products.add_to_cart_text_color', '#2563eb'], ['products.add_to_cart_border_color', '#2563eb'], ['products.add_to_cart_hover_bg', '#eff6ff'],
            ['products.buy_now_bg_color', '#2563eb'], ['products.buy_now_text_color', '#ffffff'], ['products.buy_now_hover_bg', '#1d4ed8'],
        ].map(([key, def]) => ({ key, type: 'color', default: def })),
        render: ({ data, set }) => data['products.use_custom_button_colors'] && (
            <div className="grid gap-6 md:grid-cols-2">
                {[
                    ['add_to_cart', 'Add to Cart Button', [['bg_color', 'Background Color'], ['text_color', 'Text Color'], ['border_color', 'Border Color'], ['hover_bg', 'Hover Background']]],
                    ['buy_now', 'Buy Now Button', [['bg_color', 'Background Color'], ['text_color', 'Text Color'], ['hover_bg', 'Hover Background']]],
                ].map(([prefix, title, fields]) => (
                    <div key={prefix} className="space-y-3 rounded-lg border border-line p-4">
                        <SubHeading>{title}</SubHeading>
                        {fields.map(([k, label]) => (
                            <SchemaField key={k} data={data} set={set} f={{ key: `products.${prefix}_${k}`, label, type: 'color' }} />
                        ))}
                        <div><p className="mb-1 text-sm font-medium">Preview</p><ButtonPreview data={data} prefix={prefix} /></div>
                    </div>
                ))}
            </div>
        ),
    },
    {
        id: 'share', title: 'Social Share',
        fields: [t('products.show_share', 'Show Share Buttons', 'Display social share buttons on product pages', true)],
        render: ({ data, set }) => data['products.show_share'] && (
            <fieldset>
                <legend className="mb-2 text-sm font-medium">Share Platforms</legend>
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    {SHARE.map(([key, label, def]) => (
                        <label key={key} className="flex items-center gap-2 text-sm">
                            <input type="checkbox" checked={data[key] ?? def} onChange={(e) => set(key, e.target.checked)} className="h-4 w-4 accent-[var(--primary)]" />{label}
                        </label>
                    ))}
                </div>
            </fieldset>
        ),
        extraFields: SHARE.map(([key, , def]) => ({ key, type: 'toggle', default: def })),
    },
    {
        id: 'reviews', title: 'Review Settings', columns: 1,
        fields: [
            t('products.review_moderation', 'Review Moderation', 'Require approval before displaying new reviews', true),
            t('products.verified_reviews_only', 'Verified Purchase Reviews Only', 'Only allow reviews from customers who purchased the product'),
            { key: 'products.min_review_length', label: 'Minimum Review Length', type: 'number', default: 10, min: 0, hint: 'Minimum number of characters required for review text' },
        ],
    },
];

export default function Products({ values }) {
    const state = useSchemaSettings('products', BLOCKS, values);

    return (
        <SettingsLayout>
            <Head title="Product Settings" />
            <SchemaForm blocks={BLOCKS} state={state} />

            <Card className="p-4 md:p-6">
                <a href="/admin/settings/mobile" className="flex items-center gap-3 hover:text-primary">
                    <span className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-soft/50 text-primary"><Smartphone size={18} /></span>
                    <span><span className="block font-semibold">Mobile Display Settings</span><span className="text-sm text-muted">Configure typography, layout, and buttons for mobile devices</span></span>
                </a>
                <div className="mt-4 flex flex-wrap gap-4 border-t border-line pt-4 text-sm">
                    <a href="/admin/products" className="text-primary hover:underline">Manage Products</a>
                    <a href="/admin/reviews" className="text-primary hover:underline">Manage Reviews</a>
                    <a href="/admin/settings/shop" className="text-primary hover:underline">Shop Settings</a>
                </div>
            </Card>
        </SettingsLayout>
    );
}
