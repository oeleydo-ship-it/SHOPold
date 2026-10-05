import Seo from '../../Storefront/Layout/Seo';
import { CartActions, CartCrossSell, CartEmpty, CartItems, CartSummary } from '../../Storefront/Cart/Blocks';
import { useT } from '../../Storefront/lib/store';
import { useThemed } from '../../Storefront/theme/registry';

/** Cart page. Blocks can be replaced per theme as "Cart.<Block>". */
export default function Cart({ items, coupon, totals, crossSell, guestCheckout, seo }) {
    const t = useT();
    const B = {
        Items: useThemed('Cart.Items', CartItems),
        Actions: useThemed('Cart.Actions', CartActions),
        CrossSell: useThemed('Cart.CrossSell', CartCrossSell),
        Summary: useThemed('Cart.Summary', CartSummary),
        Empty: useThemed('Cart.Empty', CartEmpty),
    };

    return (
        <>
            <Seo title={seo.title} robots={seo.robots} />
            <div className="sf-container py-8">
                <h1 className="mb-8 text-3xl font-bold">{t('your_cart', 'Your Cart')}</h1>
                {items.length > 0 ? (
                    <div className="grid gap-8 lg:grid-cols-3">
                        <div className="lg:col-span-2">
                            <B.Items items={items} />
                            <B.Actions />
                            <B.CrossSell products={crossSell} />
                        </div>
                        <div className="lg:col-span-1">
                            <B.Summary coupon={coupon} totals={totals} guestCheckout={guestCheckout} />
                        </div>
                    </div>
                ) : <B.Empty />}
            </div>
        </>
    );
}
