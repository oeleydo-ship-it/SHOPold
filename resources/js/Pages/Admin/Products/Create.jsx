import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import ProductForm from '../../../Components/Product/ProductForm';

export default function Create(props) {
    return (
        <AdminLayout title="Add Product">
            <Head title="Add Product" />
            <div className="mb-5">
                <Link href="/admin/products" className="inline-flex items-center gap-2 text-sm text-muted hover:text-ink"><ArrowLeft size={16} />Back to Products</Link>
            </div>
            <ProductForm mode="create" {...props} />
        </AdminLayout>
    );
}
