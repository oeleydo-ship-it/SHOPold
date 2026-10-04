import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, ExternalLink } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import ProductForm from '../../../Components/Product/ProductForm';
import { LinkButton } from '../../../Components/ui';

export default function Edit(props) {
    const { product } = props;
    return (
        <AdminLayout title="Edit Product">
            <Head title={`Edit ${product.name}`} />
            <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
                <Link href="/admin/products" className="inline-flex items-center gap-2 text-sm text-muted hover:text-ink"><ArrowLeft size={16} />Back to Products</Link>
                <LinkButton external tone="success" href={`/shop/${product.slug}`} target="_blank" rel="noreferrer"><ExternalLink size={16} />View Product</LinkButton>
            </div>
            <ProductForm key={product.id} mode="edit" {...props} />
        </AdminLayout>
    );
}
