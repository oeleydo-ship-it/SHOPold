import { router } from '@inertiajs/react';
import { postJson } from './http';
import { useShop, useT } from './store';

/**
 * Add-to-cart for product cards. Variable products need a choice of options, so they open the product page.
 * Uses the existing /cart/add endpoint (JSON answer) and updates the header badge without reloading.
 */
export function useAddToCart() {
    const { setCartCount, toast } = useShop();
    const t = useT();

    return async (product, quantity = 1, variationId = null) => {
        if (product.variable && !variationId) {
            router.visit(product.url);
            return false;
        }
        try {
            const res = await postJson('/cart/add', { product_id: product.id, quantity, variation_id: variationId ?? undefined });
            if (typeof res.cart_count === 'number') setCartCount(res.cart_count);
            toast('success', res.message || t('added_to_cart', 'Added to cart'));
            // On the cart page itself ("You may also like"), show the new line straight away.
            if (window.location.pathname === '/cart') router.reload();
            return true;
        } catch (e) {
            toast('error', e.message);
            return false;
        }
    };
}

export function useAddToWishlist() {
    const { toast } = useShop();
    const t = useT();

    return async (product) => {
        try {
            const res = await postJson('/account/wishlist', { product_id: product.id });
            toast('success', res.message || t('added_to_wishlist', 'Added to wishlist'));
        } catch (e) {
            toast('error', e.status === 401 ? t('login_required', 'Please log in first.') : e.message);
        }
    };
}
