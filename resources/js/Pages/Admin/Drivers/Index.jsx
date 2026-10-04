import { Head, Link } from '@inertiajs/react';
import { Eye, PackageCheck, Pencil, Plus, Truck, UserCheck, Users } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import StatCard from '../../../Components/StatCard';
import FilterBar from '../../../Components/FilterBar';
import { Badge, Card, LinkButton, Pagination, titleCase } from '../../../Components/ui';
import { DataTable } from '../../../Components/crud';

export const DRIVER_TONE = { available: 'success', busy: 'warning', offline: 'muted' };

export default function Index({ drivers, stats, filters }) {
    const columns = [
        { key: 'driver', label: 'Driver', render: (d) => <div className="flex items-center gap-3"><span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10 font-semibold text-primary">{d.name?.[0]?.toUpperCase()}</span><div className="min-w-0"><p className="font-medium">{d.name}</p><p className="text-xs text-muted">{d.email}</p></div></div> },
        { key: 'phone', label: 'Contact', render: (d) => d.phone ?? '-' },
        { key: 'vehicle', label: 'Vehicle', render: (d) => d.vehicle ?? '-' },
        { key: 'status', label: 'Status', render: (d) => <Badge tone={DRIVER_TONE[d.status] ?? 'muted'}>{titleCase(d.status ?? 'offline')}</Badge> },
        { key: 'active', label: 'Active Orders' },
        { key: 'completed', label: 'Completed' },
        {
            key: 'actions', label: '', className: 'text-right',
            render: (d) => (
                <div className="flex items-center justify-end gap-3">
                    <Link href={`/admin/drivers/${d.id}`} title="View" aria-label={`View ${d.name}`} className="text-muted hover:text-ink"><Eye size={18} /></Link>
                    <Link href={`/admin/users/${d.id}/edit`} title="Edit" aria-label={`Edit ${d.name}`} className="text-primary"><Pencil size={18} /></Link>
                </div>
            ),
        },
    ];

    return (
        <AdminLayout title="Drivers">
            <Head title="Drivers" />
            <div className="mb-4 flex justify-end"><LinkButton href="/admin/users/create"><Plus size={16} />Add Driver</LinkButton></div>
            <div className="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
                <StatCard label="Total Drivers" value={stats.total} icon={Users} tone="primary" />
                <StatCard label="Available" value={stats.available} icon={UserCheck} tone="success" />
                <StatCard label="Busy" value={stats.busy} icon={Truck} tone="warning" />
                <StatCard label="Pending Deliveries" value={stats.pending} icon={PackageCheck} tone="info" />
            </div>
            <FilterBar path="/admin/drivers" filters={filters} fields={[{ name: 'search', placeholder: 'Name, email, phone…' }, { name: 'status', placeholder: 'All status', options: [['available', 'Available'], ['busy', 'Busy'], ['offline', 'Offline']] }]} />
            <Card className="overflow-hidden">
                <DataTable columns={columns} rows={drivers.data} empty="No drivers found. Create a user and mark them as a driver." />
                <Pagination links={drivers.links} from={drivers.from} to={drivers.to} total={drivers.total} />
            </Card>
        </AdminLayout>
    );
}
