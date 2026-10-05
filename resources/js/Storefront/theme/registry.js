import { useStore } from '../lib/store';

/**
 * Theme component overrides. A theme replaces any storefront component by registering it under the same name:
 *
 *   overrides.modern = { ProductCard: ModernProductCard, 'Home.Hero': ModernHero }
 *
 * Components ask for their themed version with useThemed('ProductCard', ProductCard). Themes without an entry
 * (or names a theme does not override) use the shared component, styled through the theme's colours and options.
 * Modern and starter mostly restyle the shared components (resources/css/storefront-themes.css); add entries here
 * when a theme needs different markup.
 */
export const overrides = {
    default: {},
    modern: {},
    starter: {},
};

export function useThemed(name, Base) {
    const { theme } = useStore();
    return overrides[theme?.slug]?.[name] ?? Base;
}
