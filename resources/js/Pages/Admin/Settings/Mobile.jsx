import { Head } from '@inertiajs/react';
import { ArrowDown, ArrowUp, Plus, Trash2 } from 'lucide-react';
import SettingsLayout from '../../../Layouts/SettingsLayout';
import SchemaForm, { ColorField, useSchemaSettings } from '../../../Components/schemaForm';
import MediaPicker from '../../../Components/MediaPicker';
import { Button, Card, Field, Input, Select, Textarea } from '../../../Components/ui';

const toggle = (key, label, hint, def = false) => ({ key, label, type: 'toggle', default: def, hint });
const num = (key, label, def, min, max, hint, suffix = 'px') => ({ key, label, type: 'number', default: def, min, max, suffix, hint });
const select = (key, label, def, options, hint) => ({ key, label, type: 'select', default: def, options, hint });

const SLIDES = 'products.mobile_hero_slides';
const blankSlide = { image_path: '', badge: '', title: '', subtitle: '', button_text: 'Shop Now', button_link: '/shop', text_color: '#ffffff', text_align: 'left', overlay_opacity: '0.3' };

function MobileSlides({ slides, onChange }) {
    const patch = (i, p) => onChange(slides.map((s, n) => (n === i ? { ...s, ...p } : s)));
    const move = (i, dir) => {
        const j = i + dir;
        if (j < 0 || j >= slides.length) return;
        const next = [...slides];
        [next[i], next[j]] = [next[j], next[i]];
        onChange(next);
    };
    return (
        <div className="space-y-4">
            {slides.length === 0 && <p className="rounded-lg border-2 border-dashed border-line p-6 text-center text-sm text-muted">No mobile slides yet — desktop slides are used until you add some.</p>}
            {slides.map((s, i) => (
                <div key={i} className="rounded-lg border border-line p-4">
                    <div className="mb-3 flex items-center justify-between">
                        <h4 className="text-sm font-semibold">Slide {i + 1}</h4>
                        <div className="flex gap-2">
                            <button type="button" onClick={() => move(i, -1)} disabled={i === 0} aria-label={`Move slide ${i + 1} up`} className="text-muted hover:text-ink disabled:opacity-30"><ArrowUp size={16} /></button>
                            <button type="button" onClick={() => move(i, 1)} disabled={i === slides.length - 1} aria-label={`Move slide ${i + 1} down`} className="text-muted hover:text-ink disabled:opacity-30"><ArrowDown size={16} /></button>
                            <button type="button" onClick={() => onChange(slides.filter((_, n) => n !== i))} aria-label={`Remove slide ${i + 1}`} className="text-danger"><Trash2 size={16} /></button>
                        </div>
                    </div>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="md:col-span-2">
                            <p className="mb-1 text-sm font-medium">Slide Image</p>
                            <MediaPicker value={s.image_path} onChange={(path) => patch(i, { image_path: path })} collection="pages" label={s.image_path ? 'Change Image' : 'Select Image'} />
                        </div>
                        <Field label="Badge Text"><Input value={s.badge ?? ''} onChange={(e) => patch(i, { badge: e.target.value })} /></Field>
                        <Field label="Title"><Input value={s.title ?? ''} onChange={(e) => patch(i, { title: e.target.value })} /></Field>
                        <Field label="Subtitle" className="md:col-span-2"><Textarea rows={2} value={s.subtitle ?? ''} onChange={(e) => patch(i, { subtitle: e.target.value })} /></Field>
                        <Field label="Button Text"><Input value={s.button_text ?? ''} onChange={(e) => patch(i, { button_text: e.target.value })} /></Field>
                        <Field label="Button Link"><Input value={s.button_link ?? ''} onChange={(e) => patch(i, { button_link: e.target.value })} /></Field>
                        <Field label="Text Color"><ColorField value={s.text_color} onChange={(v) => patch(i, { text_color: v })} label="Text color" /></Field>
                        <Field label="Text Align">
                            <Select value={s.text_align ?? 'left'} onChange={(e) => patch(i, { text_align: e.target.value })}>
                                <option value="left">Left</option><option value="center">Center</option><option value="right">Right</option>
                            </Select>
                        </Field>
                        <Field label={`Overlay Opacity: ${s.overlay_opacity}`} className="md:col-span-2">
                            <input type="range" min="0" max="0.8" step="0.1" value={s.overlay_opacity ?? 0.3} onChange={(e) => patch(i, { overlay_opacity: e.target.value })} className="w-full accent-[var(--primary)]" />
                        </Field>
                    </div>
                </div>
            ))}
            <Button tone="neutral" onClick={() => onChange([...slides, { ...blankSlide }])}><Plus size={14} />Add Mobile Slide</Button>
        </div>
    );
}

const BLOCKS = [
    {
        id: 'typography', title: 'Mobile Typography', description: 'Customize font sizes for mobile devices (in pixels)',
        fields: [
            num('products.mobile_title_size_px', 'Card Title Size', 13, 10, 24, 'Product card title (default: 13px)'),
            num('products.mobile_detail_title_px', 'Detail Page Title', 20, 14, 36, 'Product detail title (default: 20px)'),
            num('products.mobile_price_px', 'Price Size', 16, 12, 28, 'Product price (default: 16px)'),
            num('products.mobile_short_desc_px', 'Short Description Size', 14, 10, 20, 'Short description (default: 14px)'),
            num('products.mobile_long_desc_px', 'Long Description Size', 14, 10, 20, 'Long description (default: 14px)'),
            num('products.mobile_category_px', 'Category Label Size', 10, 8, 16, 'Category label (default: 10px)'),
            num('products.mobile_title_lines', 'Title Max Lines', 2, 1, 4, 'Max lines for title (default: 2)', 'lines'),
        ],
    },
    {
        id: 'card', title: 'Product Card Layout', description: 'Configure product card appearance on mobile',
        fields: [
            select('products.mobile_columns', 'Products Per Row', '2', [['1', '1 Column'], ['2', '2 Columns']]),
            select('products.mobile_image_ratio', 'Image Aspect Ratio', 'square', [['square', 'Square (1:1)'], ['portrait', 'Portrait (3:4)'], ['landscape', 'Landscape (4:3)']]),
            num('products.mobile_card_padding_px', 'Card Padding', 12, 4, 24),
            num('products.mobile_card_gap_px', 'Card Gap', 12, 4, 24),
            toggle('products.mobile_hide_rating', 'Hide Rating'), toggle('products.mobile_hide_category', 'Hide Category'),
            toggle('products.mobile_hide_stock', 'Hide Stock Status'), toggle('products.mobile_show_cart_btn', 'Show Add to Cart', undefined, true),
        ],
    },
    {
        id: 'homepage', title: 'Homepage Mobile Layout', description: 'Configure homepage sections on mobile devices',
        fields: [
            select('products.homepage_mobile_columns', 'Featured Products', '2', [['1', '1 Column'], ['2', '2 Columns']], 'Products per row on homepage'),
            { key: 'products.homepage_mobile_count', label: 'Products to Show', type: 'number', default: 4, min: 2, max: 12, hint: 'Number of products on mobile' },
            toggle('products.homepage_mobile_scroll', 'Enable Horizontal Scroll', 'Swipe horizontally instead of grid on mobile'),
            toggle('products.homepage_mobile_compact', 'Compact Cards on Homepage', 'Use smaller cards on homepage sections'),
        ],
    },
    {
        id: 'sticky', title: 'Mobile Sticky Buttons', description: 'Configure sticky action buttons on product pages',
        fields: [
            { ...toggle('products.mobile_sticky_buttons', 'Enable Sticky Mobile Buttons', 'Show fixed action buttons at the bottom on mobile devices', true), full: true },
            { key: 'products.mobile_button_layout', label: 'Mobile Sticky Button Layout', type: 'cards', default: 'full', columns: 3, full: true, options: [['full', 'Full Width Buttons', 'Add to Cart + Buy Now'], ['cart_only', 'Add to Cart Only', 'A single button'], ['with_icons', 'With Wishlist & Compare Icons', 'Buttons plus icons']] },
            { ...toggle('products.mobile_show_quantity', 'Show Quantity Selector on Mobile', 'Display quantity +/- buttons in mobile sticky bar'), full: true },
        ],
    },
    {
        id: 'header', title: 'Mobile Header', description: 'Configure mobile header appearance', columns: 1,
        fields: [
            toggle('products.mobile_show_promo', 'Show Promo Banner', 'Display promotional text at the top of mobile header', true),
            toggle('products.mobile_show_search', 'Show Search Bar', 'Display search bar below the header icons', true),
            toggle('products.mobile_show_language', 'Show Language Switcher', 'Display language selector in mobile header', true),
        ],
    },
    {
        id: 'hero', title: 'Mobile Hero Section', description: 'Configure hero/slider appearance on mobile',
        fields: [
            { ...select('products.mobile_hero_height', 'Mobile Hero Height', 'medium', [['small', 'Small (320px)'], ['medium', 'Medium (380px)'], ['large', 'Large (450px)'], ['full', 'Full Screen']]), full: true },
            toggle('products.mobile_hero_arrows', 'Show Navigation Arrows', 'Display prev/next arrows on mobile slider', true),
            toggle('products.mobile_hero_secondary_btn', 'Show Secondary Button', 'Display secondary CTA button on mobile hero'),
            toggle('products.mobile_hero_features', 'Show Features Row', 'Display feature icons (Free Shipping, etc.) on mobile'),
        ],
    },
    {
        id: 'slides', title: 'Mobile Hero Slides', description: 'Create separate hero slides for mobile devices. If no mobile slides are configured, desktop slides will be used.',
        columns: 1,
        fields: [toggle('products.mobile_hero_enabled', 'Use Separate Mobile Hero', 'Enable to show different hero content on mobile devices')],
        extraFields: [{ key: SLIDES, type: 'raw', default: [] }],
        render: ({ data, set }) => data['products.mobile_hero_enabled'] && <MobileSlides slides={data[SLIDES]} onChange={(s) => set(SLIDES, s)} />,
    },
];

export default function Mobile({ values, slides }) {
    const state = useSchemaSettings('products', BLOCKS, values, { [SLIDES]: slides });

    return (
        <SettingsLayout>
            <Head title="Mobile Display Settings" />
            <p className="text-sm text-muted">Customize how your store looks on mobile devices</p>
            <SchemaForm
                blocks={BLOCKS} state={state} saveLabel="Save Mobile Settings"
                // the server stores slides as a cleaned JSON string
                transform={(d) => ({ ...d, [SLIDES]: JSON.stringify(d[SLIDES]) })}
            />
            <Card className="p-4 md:p-6">
                <h3 className="mb-2 text-sm font-semibold">Mobile Preview Tips</h3>
                <ul className="list-inside list-disc space-y-1 text-sm text-muted">
                    <li>Use browser DevTools (F12) to preview mobile layouts</li>
                    <li>Test on actual devices for best results</li>
                    <li>Clear cache after making changes</li>
                </ul>
                <div className="mt-4 flex flex-wrap gap-4 border-t border-line pt-4 text-sm">
                    <a href="/admin/settings/products" className="text-primary hover:underline">Product Settings</a>
                    <a href="/admin/settings/appearance" className="text-primary hover:underline">Appearance</a>
                    <a href="/admin/settings/homepage" className="text-primary hover:underline">Homepage Editor</a>
                </div>
            </Card>
        </SettingsLayout>
    );
}
