import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, ImageIcon } from 'lucide-react';
import VendorLayout from '../../../Layouts/VendorLayout';
import { Badge, Card, Pagination } from '../../../Components/ui';
import { DataTable } from '../../../Components/crud';

const TONE = { add: 'success', remove: 'danger', sale: 'danger', adjust: 'info' };

export default function History({ product, logs }) {
    const stockTone = product.quantity <= 0 ? 'text-danger' : product.quantity <= 5 ? 'text-warning' : 'text-success';
    const columns = [
        { key: 'date', label: 'Date', render: (l) => <span className="whitespace-nowrap text-muted">{l.date}</span> },
        { key: 'user', label: 'User' },
        { key: 'type', label: 'Type', render: (l) => <Badge tone={TONE[l.type] ?? 'muted'}>{l.type[0].toUpperCase() + l.type.slice(1)}</Badge> },
        { key: 'variation', label: 'Variation', render: (l) => <span className="text-muted">{l.variation ?? '-'}</span> },
        { key: 'before', label: 'Before', className: 'text-right' },
        { key: 'change', label: 'Change', className: 'text-right', render: (l) => <span className={`font-medium ${l.change >= 0 ? 'text-success' : 'text-danger'}`}>{l.change >= 0 ? '+' : ''}{l.change}</span> },
        { key: 'after', label: 'After', className: 'text-right font-medium' },
        { key: 'note', label: 'Note', render: (l) => <span className="text-muted">{l.note || '-'}</span> },
    ];

    return (
        <VendorLayout title="Stock History">
            <Head title={`Stock History: ${product.name}`} />
            <Link href="/vendor/stock" className="mb-5 inline-flex items-center gap-2 text-sm text-muted hover:text-ink"><ArrowLeft size={16} />Back to Stock</Link>

            <Card className="mb-5 flex items-center gap-4 p-4 md:p-6">
                <div className="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-surface-2 text-muted">
                    {product.image ? <img src={product.image} alt="" className="h-full w-full object-cover" /> : <ImageIcon size={24} />}
                </div>
                <div>
                    <h2 className="text-lg font-medium">{product.name}</h2>
                    <p className="text-sm text-muted">SKU: {product.sku || 'N/A'}</p>
                    <p className="text-sm">Current stock: <span className={`font-medium ${stockTone}`}>{product.quantity}</span></p>
                </div>
            </Card>

            <Card className="overflow-hidden">
                <DataTable columns={columns} rows={logs.data} empty="No inventory history yet." />
                <Pagination links={logs.links} from={logs.from} to={logs.to} total={logs.total} />
            </Card>
        </VendorLayout>
    );
}
