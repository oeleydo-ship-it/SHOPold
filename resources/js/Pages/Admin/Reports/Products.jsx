import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, ArrowLeft, ImageIcon, Pencil, XCircle } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { Badge, Card, CardHeader, Pagination } from '../../../Components/ui';
import { DataTable } from '../../../Components/crud';

function StockList({ title, icon: Icon, tone, items, empty, action }) {
    return (
        <Card>
            <CardHeader title={`${title} (${items.length})`}><Icon size={18} style={{ color: `var(--${tone})` }} /></CardHeader>
            {items.length === 0 ? <p className="p-6 text-center text-sm text-muted">{empty}</p> : (
                <ul className="max-h-72 divide-y divide-line overflow-y-auto px-4 md:px-6">
                    {items.map((p) => (
                        <li key={p.id} className="flex items-center justify-between gap-3 py-2.5 text-sm">
                            <span className="truncate">{p.name}</span>
                            {action(p)}
                        </li>
                    ))}
                </ul>
            )}
        </Card>
    );
}

export default function Products({ products, lowStock, outOfStock }) {
    const columns = [
        {
            key: 'name', label: 'Product',
            render: (p) => (
                <div className="flex items-center gap-3">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded bg-surface-2 text-muted">{p.image ? <img src={p.image} alt="" className="h-full w-full object-cover" /> : <ImageIcon size={16} />}</div>
                    <span className="font-medium">{p.name}</span>
                </div>
            ),
        },
        { key: 'sku', label: 'SKU', render: (p) => <span className="text-muted">{p.sku}</span> },
        { key: 'quantity', label: 'Stock', render: (p) => <Badge tone={p.quantity == 0 ? 'danger' : p.quantity <= 5 ? 'warning' : 'success'}>{p.quantity}</Badge> },
        { key: 'sold', label: 'Total Sold' },
        { key: 'revenue', label: 'Revenue', className: 'font-medium' },
        { key: 'actions', label: '', className: 'text-right', render: (p) => <Link href={`/admin/products/${p.id}/edit`} aria-label={`Edit ${p.name}`} className="text-primary"><Pencil size={16} /></Link> },
    ];

    return (
        <AdminLayout title="Products Report">
            <Head title="Products Report" />
            <Link href="/admin/reports" className="mb-5 inline-flex items-center gap-2 text-sm text-muted hover:text-ink"><ArrowLeft size={16} />Back to Reports</Link>

            <div className="mb-5 grid gap-5 md:grid-cols-2">
                <StockList title="Low Stock" icon={AlertTriangle} tone="warning" items={lowStock} empty="No products are running low."
                    action={(p) => <Badge tone="warning">{p.quantity} left</Badge>} />
                <StockList title="Out of Stock" icon={XCircle} tone="danger" items={outOfStock} empty="Nothing is out of stock."
                    action={(p) => <Link href={`/admin/products/${p.id}/edit`} className="text-primary hover:underline">Restock</Link>} />
            </div>

            <Card className="overflow-hidden">
                <CardHeader title="Product Performance" />
                <DataTable columns={columns} rows={products.data} empty="No products yet." />
                <Pagination links={products.links} from={products.from} to={products.to} total={products.total} />
            </Card>
        </AdminLayout>
    );
}
