import { Head, router } from '@inertiajs/react';
import { Check, Star, X } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { Badge, Card, Pagination, Select } from '../../../Components/ui';
import { DataTable, RowActions } from '../../../Components/crud';

function Stars({ n }) {
    return (
        <span className="inline-flex gap-0.5" role="img" aria-label={`${n} out of 5`}>
            {[1, 2, 3, 4, 5].map((i) => (
                <Star key={i} size={14} className={i <= n ? 'text-warning' : 'text-line'} fill="currentColor" />
            ))}
        </span>
    );
}

export default function Index({ reviews, filters }) {
    const act = (r, action) => router.patch(`/admin/reviews/${r.id}/${action}`, {}, { preserveScroll: true });

    const columns = [
        { key: 'product', label: 'Product', render: (r) => <span className="font-medium">{r.product ?? '—'}</span> },
        { key: 'customer', label: 'Customer', render: (r) => <div><p>{r.customer ?? '—'}</p><p className="text-xs text-muted">{r.date}</p></div> },
        { key: 'rating', label: 'Rating', render: (r) => <Stars n={r.rating} /> },
        { key: 'comment', label: 'Comment', render: (r) => <p className="line-clamp-2 max-w-xs text-muted">{r.comment}</p> },
        { key: 'status', label: 'Status', render: (r) => <Badge tone={r.is_approved ? 'success' : 'warning'}>{r.is_approved ? 'Approved' : 'Pending'}</Badge> },
        {
            key: 'actions', label: '', className: 'text-right',
            render: (r) => (
                <RowActions deleteHref={`/admin/reviews/${r.id}`} deleteMessage="Delete this review?">
                    {!r.is_approved && (
                        <button type="button" title="Approve" aria-label="Approve review" onClick={() => act(r, 'approve')} className="text-success hover:opacity-75"><Check size={18} /></button>
                    )}
                    {r.is_approved && (
                        <button type="button" title="Reject" aria-label="Reject review" onClick={() => act(r, 'reject')} className="text-warning hover:opacity-75"><X size={18} /></button>
                    )}
                </RowActions>
            ),
        },
    ];

    return (
        <AdminLayout title="Reviews">
            <Head title="Reviews" />
            <div className="mb-4">
                <Select
                    value={filters.approved ?? ''}
                    onChange={(e) => router.get('/admin/reviews', e.target.value === '' ? {} : { approved: e.target.value }, { preserveState: true })}
                    className="!w-auto"
                    aria-label="Filter by status"
                >
                    <option value="">All Reviews</option>
                    <option value="1">Approved</option>
                    <option value="0">Pending</option>
                </Select>
            </div>
            <Card className="overflow-hidden">
                <DataTable columns={columns} rows={reviews.data} empty="No reviews found." />
                <Pagination links={reviews.links} from={reviews.from} to={reviews.to} total={reviews.total} />
            </Card>
        </AdminLayout>
    );
}
