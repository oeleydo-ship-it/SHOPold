import { useEffect } from 'react';
import { Head } from '@inertiajs/react';
import { Check } from 'lucide-react';
import SettingsLayout from '../../../Layouts/SettingsLayout';
import SchemaForm, { GridFields, SubHeading, useSchemaSettings } from '../../../Components/schemaForm';
import HeroSlides from '../../../Components/HeroSlides';
import { Button, Field } from '../../../Components/ui';

const text = (key, label, def = '', extra = {}) => ({ key, label, type: 'text', default: def, ...extra });
const color = (key, label, def) => ({ key, label, type: 'color', default: def });
const toggle = (key, label, def = false, hint) => ({ key, label, type: 'toggle', default: def, hint });
const select = (key, label, def, options, extra = {}) => ({ key, label, type: 'select', default: def, options, ...extra });
const is = (key, ...values) => (d) => values.includes(d[key]);

const HEADER_STYLES = [
    ['default', 'Default', 'Top bar + Navigation'], ['minimal', 'Minimal', 'Clean single bar'], ['centered', 'Centered', 'Logo centered'],
    ['mega', 'Mega Store', '3-row ecommerce style'], ['transparent', 'Transparent', 'Overlays hero section'], ['dark', 'Dark', 'Dark themed header'],
];
const HERO_STYLES = [
    ['gradient', 'Gradient', 'Color gradient background'], ['image', 'Image', 'Custom background image'], ['slider', 'Slider', 'Multiple slides'],
    ['product_showcase', 'Product Showcase', 'Split layout with product'], ['fullwidth', 'Fullwidth Banner', 'Edge-to-edge hero slider'], ['video', 'Video Background', 'Autoplay video hero'],
];
const FONTS = [['Inter', 'Inter (Modern)'], ['Roboto', 'Roboto (Clean)'], ['Open Sans', 'Open Sans (Friendly)'], ['Poppins', 'Poppins (Geometric)'], ['Nunito', 'Nunito (Rounded)'], ['Lato', 'Lato (Professional)'], ['Montserrat', 'Montserrat (Bold)'], ['Playfair Display', 'Playfair Display (Elegant)'], ['Source Sans Pro', 'Source Sans Pro (Readable)']];
const PRESETS = [
    ['Blue', '#2563eb', '#1e40af', '#f59e0b'], ['Green', '#059669', '#047857', '#f59e0b'], ['Purple', '#7c3aed', '#6d28d9', '#f59e0b'], ['Red', '#dc2626', '#b91c1c', '#f59e0b'],
    ['Orange', '#ea580c', '#c2410c', '#2563eb'], ['Cyan', '#0891b2', '#0e7490', '#f59e0b'], ['Dark', '#1f2937', '#111827', '#2563eb'], ['Pink', '#ec4899', '#db2777', '#8b5cf6'],
];

const SLIDER_SETTINGS = [
    select('theme.slider_interval', 'Auto-play Interval', '5000', [['3000', '3 seconds'], ['5000', '5 seconds'], ['7000', '7 seconds'], ['10000', '10 seconds']]),
    select('theme.slider_transition', 'Transition Effect', 'fade', [['fade', 'Fade'], ['slide', 'Slide'], ['zoom', 'Zoom']]),
    select('theme.slider_navigation', 'Show Navigation', 'both', [['both', 'Arrows & Dots'], ['arrows', 'Arrows Only'], ['dots', 'Dots Only'], ['none', 'None']]),
];

const SLIDES = 'theme.hero_slides';

/** A titled group of schema fields (hidden when `show` is false). */
function Group({ title, fields, data, set, columns = 2, show = true, children }) {
    if (!show) return null;
    return (
        <div className="space-y-4">
            <SubHeading>{title}</SubHeading>
            {fields && <GridFields fields={fields} data={data} set={set} columns={columns} />}
            {children}
        </div>
    );
}


// Every field is declared once here so the form state knows about it (defaults, switches, posting)
// and the render code below can reuse the same definitions.
const F = {
    headerOptions: [toggle('theme.sticky_header', 'Sticky Header', true, 'Header stays fixed when scrolling'), toggle('theme.show_topbar', 'Show Top Bar', true, 'Display top bar above header')],
    topBar: [
        text('theme.header_promo_text', 'Promo/Offer Message', 'Get up to 50% off using *SAVE50* this coupon code', { placeholder: 'e.g., Free shipping on orders over $50', hint: 'Displayed in the center of the top bar' }),
        color('theme.header_topbar_bg', 'Top Bar Background', '#1f2937'),
    ],
    headerColors: [color('theme.header_bg_color', 'Background Color', '#ffffff'), color('theme.header_text_color', 'Text Color', '#374151'), color('theme.header_hover_color', 'Link Hover Color', '#2563eb')],
    mega: [
        text('theme.header_categories_text', 'Categories Button Text', 'Browse All Categories'),
        select('theme.header_search_style', 'Search Style', 'simple', [['simple', 'Simple'], ['category', 'With Category Dropdown']]),
        color('theme.header_categories_bg', 'Categories Button Color', '#2563eb'),
        toggle('theme.header_show_wishlist', 'Show Wishlist Icon', true), toggle('theme.header_show_compare', 'Show Compare Icon', true),
        toggle('theme.header_show_account_text', 'Show "My Account" Text', true),
    ],
    menu: [
        color('theme.header_menu_bg', 'Menu Bar Background', '#ffffff'), color('theme.header_menu_text', 'Menu Link Color', '#374151'),
        color('theme.header_menu_hover', 'Menu Link Hover', '#2563eb'), color('theme.header_menu_active', 'Active Menu Link', '#2563eb'),
    ],
    heroContent: [
        text('theme.hero_title', 'Hero Title', 'Discover Premium Products for Every Style'), text('theme.hero_subtitle', 'Hero Subtitle', 'Shop the latest trends with exclusive deals'),
        text('theme.hero_button_text', 'Hero Button Text', 'Shop Now'), text('theme.hero_button_link', 'Hero Button Link', '/shop'),
    ],
    gradient: [color('theme.hero_gradient_start', 'Gradient Start Color', '#3b82f6'), color('theme.hero_gradient_end', 'Gradient End Color', '#1d4ed8')],
    showcase: [
        select('theme.showcase_product_source', 'Product Source', 'featured', [['featured', 'Featured Products'], ['new_arrivals', 'New Arrivals'], ['best_sellers', 'Best Sellers'], ['on_sale', 'On Sale'], ['random', 'Random Products']]),
        select('theme.showcase_product_count', 'Number of Products', '4', [2, 3, 4, 5, 6, 8].map((n) => [String(n), `${n} Products`])),
        color('theme.showcase_bg_color', 'Section Background', '#f9fafb'), color('theme.showcase_card_bg_color', 'Card Background', '#ffffff'), color('theme.showcase_image_bg_color', 'Image Area Background', '#f3f4f6'),
    ],
    fullwidth: [
        select('theme.hero_height', 'Banner Height', 'large', [['auto', 'Auto (Image Based)'], ['small', 'Small (400-450px)'], ['medium', 'Medium (500-600px)'], ['large', 'Large (600-700px)'], ['full', 'Full Screen']]),
        select('theme.fullwidth_content_position', 'Content Position', 'left', [['left', 'Left Aligned'], ['center', 'Center'], ['right', 'Right Aligned'], ['bottom-left', 'Bottom Left'], ['bottom-center', 'Bottom Center']]),
        select('theme.fullwidth_content_width', 'Content Width', 'medium', [['narrow', 'Narrow (400px)'], ['medium', 'Medium (600px)'], ['wide', 'Wide (800px)'], ['full', 'Full Width']]),
        toggle('theme.fullwidth_show_overlay', 'Show Gradient Overlay', false, 'Adds a gradient for better text readability'),
        toggle('theme.fullwidth_show_badge', 'Show Badge/Label', true, 'Display a small badge above the title'),
        toggle('theme.fullwidth_parallax', 'Parallax Effect', false, 'Subtle parallax scrolling effect'),
        color('theme.fullwidth_overlay_start', 'Overlay Start Color', '#000000'), color('theme.fullwidth_overlay_end', 'Overlay End Color', '#00000000'),
        select('theme.fullwidth_overlay_opacity', 'Overlay Opacity', '0.5', [['0.3', 'Light (30%)'], ['0.5', 'Medium (50%)'], ['0.7', 'Dark (70%)']]),
    ],
    video: [
        text('theme.video_url', 'Video URL', '', { placeholder: 'https://example.com/video.mp4', hint: 'Direct link to MP4 video file' }),
        text('theme.video_embed_id', 'YouTube/Vimeo ID (Alternative)', '', { placeholder: 'dQw4w9WgXcQ', hint: 'YouTube or Vimeo video ID for embedded video' }),
        toggle('theme.video_loop', 'Loop Video', true), toggle('theme.video_muted', 'Muted (Required for autoplay)', true), toggle('theme.video_show_controls', 'Show Video Controls', false),
        text('theme.video_fallback_image', 'Fallback Image', '', { placeholder: 'Path to fallback image' }),
    ],
    heroImage: [{ key: 'theme.hero_image', label: 'Hero Background Image', type: 'image', collection: 'pages', default: '', hint: 'Used by the Image style and as a backdrop for Gradient' }],
    overlay: [color('theme.hero_overlay_color', 'Overlay Color', '#000000'), { key: 'theme.hero_overlay_opacity', label: 'Overlay Opacity', type: 'range', min: 0, max: 100, suffix: '%', default: 40 }],
    textStyle: [color('theme.hero_text_color', 'Text Color', '#ffffff'), select('theme.hero_text_align', 'Text Alignment', 'left', [['left', 'Left'], ['center', 'Center'], ['right', 'Right']])],
    secondary: [text('theme.hero_secondary_text', 'Secondary Button Text', '', { placeholder: 'e.g., Learn More' }), text('theme.hero_secondary_link', 'Secondary Button Link', '', { placeholder: 'e.g., /about' })],
    additionalColors: [color('theme.success_color', 'Success Color', '#10b981'), color('theme.warning_color', 'Warning Color', '#f59e0b'), color('theme.error_color', 'Error Color', '#ef4444'), color('theme.info_color', 'Info Color', '#3b82f6')],
    layoutDebug: [toggle('theme.show_screen_indicator', 'Show Screen Size Indicator', false, 'Display a small badge showing current screen size (debug mode only)')],
};
// the slider settings are shown by two hero styles but are the same three settings
F.slider = SLIDER_SETTINGS;
const HERO_STATE_FIELDS = [
    ...F.heroContent, ...F.gradient, ...F.slider, ...F.showcase, ...F.fullwidth, ...F.video, ...F.heroImage, ...F.overlay, ...F.textStyle, ...F.secondary,
    { key: SLIDES, type: 'raw', default: [] },
];
const HEADER_STATE_FIELDS = [...F.headerOptions, ...F.topBar, ...F.headerColors, ...F.mega, ...F.menu];

const BLOCKS = [
    {
        id: 'header', title: 'Header Style',
        fields: [{ key: 'theme.header_style', type: 'cards', default: 'default', options: HEADER_STYLES, columns: 3, full: true }],
        extraFields: HEADER_STATE_FIELDS,
        render: ({ data, set }) => (
            <>
                <Group title="Header Options" data={data} set={set} fields={F.headerOptions} />
                <Group title="Top Bar Settings" data={data} set={set} fields={F.topBar} />
                <Group title="Header Colors" columns={3} data={data} set={set} fields={F.headerColors} />
                <Group title="Mega Store Header Options" show={data['theme.header_style'] === 'mega'} data={data} set={set} fields={F.mega}>
                    <Group title="Navigation Menu Bar Colors" columns={2} data={data} set={set} fields={F.menu} />
                </Group>
            </>
        ),
    },
    {
        id: 'hero', title: 'Homepage Layout',
        fields: [{ key: 'theme.hero_style', type: 'cards', default: 'gradient', options: HERO_STYLES, columns: 3, full: true }],
        extraFields: HERO_STATE_FIELDS,
        render: ({ data, set }) => {
            const style = data['theme.hero_style'];
            const g = { data, set };
            const overlayColor = data['theme.hero_overlay_color'];
            const hex = Math.round(Number(data['theme.hero_overlay_opacity']) * 2.55).toString(16).padStart(2, '0');
            return (
                <>
                    <Group title="Hero Content" show={style === 'gradient' || style === 'image'} {...g} fields={F.heroContent} />
                    <Group title="Gradient Colors" show={style === 'gradient'} {...g} fields={F.gradient} />
                    <Group title="Hero Slider Images" show={style === 'slider'} {...g} columns={3} fields={F.slider}>
                        <HeroSlides slides={data[SLIDES]} onChange={(sl) => set(SLIDES, sl)} />
                    </Group>
                    <Group title="Product Showcase Settings" show={style === 'product_showcase'} {...g} fields={F.showcase}>
                        <Group title="Slider Settings" {...g} columns={3} fields={F.slider} />
                    </Group>
                    <Group title="Fullwidth Banner Settings" show={style === 'fullwidth'} {...g} fields={F.fullwidth} />
                    <Group title="Video Background Settings" show={style === 'video'} {...g} fields={F.video} />
                    <Group title="Background Image" show={style === 'gradient' || style === 'image'} {...g} columns={1} fields={F.heroImage} />
                    <Group title="Image Overlay" show={style === 'image' || style === 'slider'} {...g} fields={F.overlay}>
                        <p className="text-xs text-muted">Control the overlay on top of hero images for better text readability.</p>
                        <div className="relative h-28 overflow-hidden rounded-lg bg-gradient-to-br from-slate-500 to-slate-800" aria-hidden="true">
                            <div className="absolute inset-0" style={{ background: `${overlayColor}${hex}` }} />
                            <div className="relative flex h-full flex-col items-center justify-center text-center text-white"><span className="font-bold">Preview Text</span><span className="text-xs opacity-80">See how your overlay looks</span></div>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            {[['Light', '#000000', 20], ['Dark', '#000000', 60], ['Blue', '#1e3a8a', 50], ['Purple', '#581c87', 50], ['None', '#000000', 0]].map(([l, c, o]) => (
                                <Button key={l} tone="neutral" onClick={() => { set('theme.hero_overlay_color', c); set('theme.hero_overlay_opacity', o); }}>{l}</Button>
                            ))}
                        </div>
                    </Group>
                    <Group title="Text Styling" {...g} fields={F.textStyle} />
                    <Group title="Secondary Button (Optional)" {...g} fields={F.secondary} />
                </>
            );
        },
    },
    {
        id: 'theme', title: 'Theme Selection', description: 'Select a theme to change the overall look',
        extraFields: [{ key: 'theme.active', type: 'raw', default: 'default' }],
        render: ({ data, set, themes }) => (
            <>
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3" role="radiogroup" aria-label="Theme">
                    {Object.entries(themes).map(([key, t]) => {
                        const on = data['theme.active'] === key;
                        const p = t.colors?.primary ?? '#2563eb';
                        const s = t.colors?.secondary ?? '#1e40af';
                        return (
                            <button key={key} type="button" role="radio" aria-checked={on} onClick={() => set('theme.active', key)}
                                className={`overflow-hidden rounded-card border-2 text-left transition ${on ? 'border-primary' : 'border-line hover:border-muted'}`}>
                                <div className="relative h-28 p-3" style={{ background: `linear-gradient(135deg, ${p}20, ${s}30)` }}>
                                    <div className="h-4 rounded" style={{ background: `${p}25` }} />
                                    <div className="mt-2 h-10 rounded" style={{ background: `linear-gradient(135deg, ${p}, ${s})` }} />
                                    {on && <span className="absolute right-2 top-2 flex h-6 w-6 items-center justify-center rounded-full bg-primary text-on-primary"><Check size={14} /></span>}
                                </div>
                                <div className="p-3">
                                    <h3 className="font-semibold">{t.name}</h3>
                                    <p className="text-xs text-muted">{t.description || 'No description'}</p>
                                    <div className="mt-2 flex items-center justify-between text-xs text-muted">
                                        <span>v{t.version}</span>
                                        <span className="flex gap-1"><span className="h-4 w-4 rounded-full" style={{ background: p }} /><span className="h-4 w-4 rounded-full" style={{ background: s }} /></span>
                                    </div>
                                </div>
                            </button>
                        );
                    })}
                </div>
                <div className="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-surface-2 p-4">
                    <div><h4 className="font-medium">Apply Theme Colors</h4><p className="text-sm text-muted">Apply the selected theme's default colors to the fields below (save to keep them)</p></div>
                    <Button tone="neutral" onClick={() => {
                        const c = themes[data['theme.active']]?.colors ?? {};
                        if (c.primary) set('theme.primary_color', c.primary);
                        if (c.secondary) set('theme.secondary_color', c.secondary);
                    }}>Apply Colors</Button>
                </div>
            </>
        ),
    },
    {
        id: 'colors', title: 'Colors', columns: 3,
        fields: [color('theme.primary_color', 'Primary Color', '#2563eb'), color('theme.secondary_color', 'Secondary Color', '#1e40af'), color('theme.accent_color', 'Accent Color', '#f59e0b')],
        extraFields: F.additionalColors,
        render: ({ data, set }) => (
            <>
                <Group title="Additional Colors" columns={2} data={data} set={set} fields={F.additionalColors} />
                <div>
                    <p className="mb-2 text-sm font-medium">Quick Presets</p>
                    <div className="flex flex-wrap gap-2">
                        {PRESETS.map(([name, p, s, a]) => (
                            <Button key={name} tone="neutral" onClick={() => { set('theme.primary_color', p); set('theme.secondary_color', s); set('theme.accent_color', a); }}>
                                <span className="h-3 w-3 rounded-full" style={{ background: p }} />{name}
                            </Button>
                        ))}
                        <Button tone="neutral" onClick={() => { set('theme.primary_color', '#2563eb'); set('theme.secondary_color', '#1e40af'); set('theme.accent_color', '#f59e0b'); }}>Reset</Button>
                    </div>
                </div>
                <div>
                    <p className="mb-2 text-sm font-medium">Live Preview</p>
                    <div className="flex flex-wrap items-center gap-3 rounded-lg bg-surface-2 p-4">
                        {[['Primary Button', 'theme.primary_color'], ['Secondary Button', 'theme.secondary_color'], ['Accent Button', 'theme.accent_color']].map(([l, k]) => (
                            <span key={k} className="rounded-lg px-4 py-2 text-sm font-medium text-white" style={{ background: data[k] }}>{l}</span>
                        ))}
                        <span className="text-sm font-medium underline" style={{ color: data['theme.primary_color'] }}>Link Style</span>
                    </div>
                </div>
            </>
        ),
    },
    {
        id: 'typography', title: 'Typography & Styling',
        fields: [
            select('theme.font_family', 'Primary Font', 'Inter', FONTS),
            select('theme.heading_font', 'Heading Font', '', [['', 'Same as Primary'], ['Inter', 'Inter'], ['Poppins', 'Poppins'], ['Montserrat', 'Montserrat'], ['Playfair Display', 'Playfair Display'], ['Oswald', 'Oswald'], ['Raleway', 'Raleway']]),
            select('theme.font_size', 'Base Font Size', '16', [['14', 'Small (14px)'], ['16', 'Medium (16px)'], ['18', 'Large (18px)']]),
            select('theme.button_style', 'Button Style', 'rounded', [['rounded', 'Rounded'], ['pill', 'Pill (Full Round)'], ['square', 'Square'], ['soft', 'Soft (Subtle Round)']]),
            select('theme.card_style', 'Card Style', 'shadow', [['shadow', 'Shadow'], ['border', 'Border'], ['flat', 'Flat'], ['elevated', 'Elevated']]),
            select('theme.border_radius', 'Border Radius', 'md', [['none', 'None (0px)'], ['sm', 'Small (4px)'], ['md', 'Medium (8px)'], ['lg', 'Large (12px)'], ['xl', 'Extra Large (16px)']]),
            select('theme.animation_speed', 'Animation Speed', 'normal', [['none', 'None'], ['fast', 'Fast (150ms)'], ['normal', 'Normal (300ms)'], ['slow', 'Slow (500ms)']]),
        ],
        render: ({ data }) => (
            <div className="rounded-lg bg-surface-2 p-4">
                <p className="mb-2 text-sm font-medium">Font Preview</p>
                <h4 className="text-xl font-bold" style={{ fontFamily: `'${data['theme.heading_font'] || data['theme.font_family']}', sans-serif` }}>The quick brown fox jumps over the lazy dog</h4>
                <p className="mt-1 text-sm text-muted" style={{ fontFamily: `'${data['theme.font_family']}', sans-serif`, fontSize: `${data['theme.font_size']}px` }}>Body text looks like this across your store.</p>
            </div>
        ),
    },
    {
        id: 'layout', title: 'Layout Width',
        fields: [{ key: 'theme.layout_width', type: 'cards', default: 'default', columns: 4, full: true, options: [['default', 'Default', '1280px · max-w-7xl'], ['wide', 'Wide', '1536px · max-w-screen-2xl'], ['full', 'Full Width', 'Edge-to-edge content'], ['auto', 'Auto (Responsive)', 'Adapts to screen size']] }],
        extraFields: F.layoutDebug,
        render: ({ data, set, debug }) => (
            <>
                {data['theme.layout_width'] === 'auto' && (
                    <div className="rounded-lg border border-info/40 bg-info/10 p-4 text-sm">
                        <p className="mb-2 font-medium">Screen Size Detection</p>
                        <ul className="list-inside list-disc space-y-1 text-muted">
                            <li><strong>Mobile (&lt;768px):</strong> Full width with padding</li><li><strong>Tablet (768–1024px):</strong> Default width</li>
                            <li><strong>Desktop (1024–1440px):</strong> Default width</li><li><strong>Large screens (&gt;1440px):</strong> Wide layout</li>
                        </ul>
                    </div>
                )}
                {debug && <GridFields data={data} set={set} columns={1} fields={F.layoutDebug} />}
            </>
        ),
    },
    {
        id: 'footer', title: 'Footer Settings',
        fields: [
            { key: 'theme.footer_style', type: 'cards', default: 'default', columns: 3, full: true, options: [['default', 'Default', '4-column layout'], ['minimal', 'Minimal', 'Simple centered'], ['expanded', 'Expanded', 'With newsletter']] },
            toggle('theme.footer_hide_name', 'Hide Store Name'), toggle('theme.footer_show_tagline', 'Show Tagline', true),
            toggle('theme.footer_show_newsletter', 'Show Newsletter', true), toggle('theme.footer_show_social', 'Show Social Links', true),
            text('theme.footer_copyright', 'Copyright Text', '© {year} {store_name}. All rights reserved.', { full: true, hint: 'Use {year} for current year and {store_name} for store name' }),
            color('theme.footer_bg_color', 'Background Color', '#111827'), color('theme.footer_text_color', 'Text Color', '#9ca3af'), color('theme.footer_link_hover', 'Link Hover Color', '#ffffff'),
        ],
    },
    {
        id: 'advanced', title: 'Advanced Settings',
        fields: [
            { key: 'theme.custom_css', label: 'Custom CSS', type: 'textarea', rows: 6, mono: true, full: true, default: '', hint: 'Add custom CSS to override theme styles' },
            { key: 'theme.custom_js', label: 'Custom JavaScript', type: 'textarea', rows: 6, mono: true, full: true, default: '', hint: 'Add custom JavaScript (loaded in footer)' },
            toggle('theme.enable_dark_mode', 'Enable Dark Mode Toggle', false, 'Allow users to switch between light and dark mode'),
            toggle('theme.enable_rtl', 'Enable RTL Support', false, 'Right-to-left layout for Arabic, Hebrew, etc.'),
        ],
    },
];

const SCHEMA = BLOCKS;

export default function Appearance({ values, themes, slides, debug }) {
    const state = useSchemaSettings('theme', SCHEMA, values, { [SLIDES]: slides });
    const { data } = state;

    // load the chosen fonts so the preview shows them (same Google Fonts the storefront uses)
    useEffect(() => {
        const names = [...new Set([data['theme.font_family'], data['theme.heading_font']].filter(Boolean))];
        if (!names.length) return undefined;
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = `https://fonts.googleapis.com/css2?${names.map((n) => `family=${encodeURIComponent(n)}:wght@400;700`).join('&')}&display=swap`;
        document.head.appendChild(link);
        return () => link.remove();
    }, [data['theme.font_family'], data['theme.heading_font']]);

    return (
        <SettingsLayout>
            <Head title="Appearance Settings" />
            <SchemaForm
                blocks={SCHEMA} state={state} ctx={{ themes, debug }} saveLabel="Save Appearance"
                // slides are stored as a cleaned JSON string by the server
                transform={(d) => ({ ...d, [SLIDES]: JSON.stringify(d[SLIDES]) })}
            />
        </SettingsLayout>
    );
}
