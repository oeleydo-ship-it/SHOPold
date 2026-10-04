import { Head, Link, router } from '@inertiajs/react';
import { Check, Eye, Store, X } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import FilterBar from '../../../Components/FilterBar';
import { Badge, Card, Pagination } from '../../../Components/ui';
import { DataTable } from '../../../Components/crud';

export const VENDOR_TONE = { approved: 'success', pending: 'warning', suspended: 'danger', rejected: 'muted' };
const STATUSES = [['pending', 'Pending'], ['approved', 'Approved'], ['suspended', 'Suspended'], ['rejected', 'Rejected']];

export function VendorLogo({ src, size = 'h-10 w-10' }) {
    return <div className={`flex ${size} shrink-0 items-center justify-center overflow-hidden rounded-lg bg-surface-2 text-muted`}>{src ? <img src={src} alt="" className="h-full w-full object-cover" /> : <Store size={18} />}</div>;
}

export default function Index({ vendors, filters }) {
    const setStatus = (v, status) => {
        if (window.confirm(`${status === 'approved' ? 'Approve' : 'Reject'} "${v.store_name}"?`)) router.patch(`/admin/vendors/${v.id}/status`, { status }, { preserveScroll: true });
    };

    const columns = [
        { key: 'vendor', label: 'Vendor', render: (v) => <div className="flex items-center gap-3"><VendorLogo src={v.logo} /><div className="min-w-0"><p className="font-medium">{v.store_name}</p><p className="text-xs text-muted">{v.email}</p></div></div> },
        { key: 'owner', label: 'Owner', render: (v) => v.owner ?? 'N/A' },
        { key: 'products', label: 'Products', render: (v) => v.products_count },
        { key: 'sales', label: 'Sales' },
        { key: 'balance', label: 'Balance' },
        { key: 'status', label: 'Status', render: (v) => <Badge tone={VENDOR_TONE[v.status] ?? 'muted'}>{v.status[0].toUpperCase() + v.status.slice(1)}</Badge> },
        {
            key: 'actions', label: '', className: 'text-right',
            render: (v) => (
                <div className="flex items-center justify-end gap-3">
                    <Link href={`/admin/vendors/${v.id}`} title="View" aria-label={`View ${v.store_name}`} className="text-muted hover:text-ink"><Eye size={18} /></Link>
                    {v.status === 'pending' && <>
                        <button type="button" title="Approve" aria-label={`Approve ${v.store_name}`} onClick={() => setStatus(v, 'approved')} className="text-success"><Check size={18} /></button>
                        <button type="button" title="Reject" aria-label={`Reject ${v.store_name}`} onClick={() => setStatus(v, 'rejected')} className="text-danger"><X size={18} /></button>
                    </>}
                </div>
            ),
        },
    ];

    return (
        <AdminLayout title="Vendors">
            <Head title="Vendors" />
            <FilterBar path="/admin/vendors" filters={filters} fields={[{ name: 'search', placeholder: 'Search vendors…' }, { name: 'status', placeholder: 'All status', options: STATUSES }]} />
            <Card className="overflow-hidden">
                <DataTable columns={columns} rows={vendors.data} empty="No vendors registered yet" />
                <Pagination links={vendors.links} from={vendors.from} to={vendors.to} total={vendors.total} />
            </Card>
        </AdminLayout>
    );
}
