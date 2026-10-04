import { Head, Link } from "@inertiajs/react";
import { useForm } from '@inertiajs/react';
import { Coins, Gift, History, Users, Wallet } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import StatCard from '../../../Components/StatCard';
import FilterBar from '../../../Components/FilterBar';
import { Button, Card, CardHeader, Field, Input, LinkButton, Pagination, Select } from '../../../Components/ui';
import { DataTable } from '../../../Components/crud';

const fmt = (n) => Number(n).toLocaleString();

export default function Index({ points, stats, users, filters }) {
    const form = useForm({ user_id: '', points: '', description: '' });
    const { data, setData, errors, processing } = form;
    const submit = (e) => { e.preventDefault(); form.post('/admin/loyalty/adjust', { preserveScroll: true, onSuccess: () => form.reset() }); };

    const columns = [
        { key: 'user', label: 'User', render: (p) => <div><p className="font-medium">{p.name ?? 'Unknown'}</p><p className="text-xs text-muted">{p.email}</p></div> },
        { key: 'balance', label: 'Balance', render: (p) => <span className="font-semibold">{fmt(p.balance)}</span> },
        { key: 'lifetime', label: 'Lifetime', render: (p) => fmt(p.lifetime) },
        { key: 'redeemed', label: 'Redeemed', render: (p) => fmt(p.redeemed) },
        { key: 'history', label: '', className: 'text-right', render: (p) => <Link href={`/admin/loyalty/transactions?user_id=${p.user_id}`} className="text-primary hover:underline">History</Link> },
    ];

    return (
        <AdminLayout title="Loyalty Points">
            <Head title="Loyalty Points" />
            <div className="mb-4 flex justify-end"><LinkButton href="/admin/loyalty/transactions" tone="neutral"><History size={16} />All Transactions</LinkButton></div>

            <div className="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
                <StatCard label="Points Balance" value={fmt(stats.total_points)} icon={Wallet} tone="primary" />
                <StatCard label="Total Earned" value={fmt(stats.total_earned)} icon={Coins} tone="success" />
                <StatCard label="Total Redeemed" value={fmt(stats.total_redeemed)} icon={Gift} tone="warning" />
                <StatCard label="Active Users" value={fmt(stats.active_users)} icon={Users} tone="info" />
            </div>

            <div className="grid gap-5 lg:grid-cols-3">
                <Card className="self-start">
                    <CardHeader title="Adjust Points" />
                    <form onSubmit={submit} className="space-y-4 p-4 md:p-6">
                        <Field label="User *" error={errors.user_id}>
                            <Select required value={data.user_id} onChange={(e) => setData('user_id', e.target.value)}><option value="">Select user</option>{users.map((u) => <option key={u.id} value={u.id}>{u.label}</option>)}</Select>
                        </Field>
                        <Field label="Points *" hint="Use a negative number to deduct" error={errors.points}><Input type="number" step="1" required value={data.points} onChange={(e) => setData('points', e.target.value)} /></Field>
                        <Field label="Description *" error={errors.description}><Input required maxLength={255} placeholder="Reason for adjustment" value={data.description} onChange={(e) => setData('description', e.target.value)} /></Field>
                        <Button type="submit" className="w-full" disabled={processing}>Adjust Points</Button>
                    </form>
                </Card>

                <div className="lg:col-span-2">
                    <FilterBar path="/admin/loyalty" filters={filters} fields={[{ name: 'search', placeholder: 'Search users…' }]} />
                    <Card className="overflow-hidden">
                        <DataTable columns={columns} rows={points.data} empty="No loyalty points yet" />
                        <Pagination links={points.links} from={points.from} to={points.to} total={points.total} />
                    </Card>
                </div>
            </div>
        </AdminLayout>
    );
}
