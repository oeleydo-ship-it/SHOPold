import { Head, Link, router } from '@inertiajs/react';
import { Gift, HandCoins, Truck, Wallet } from 'lucide-react';
import DriverLayout from '../../../Layouts/DriverLayout';
import StatCard from '../../../Components/StatCard';
import { Card, CardHeader } from '../../../Components/ui';
import { EarningRow } from './History';

const PERIODS = [['today', 'Today'], ['week', 'This week'], ['month', 'This month'], ['year', 'This year']];

function DailyChart({ data }) {
    if (data.length === 0) return <p className="p-6 text-center text-sm text-muted">No earnings in this period.</p>;
    const max = Math.max(1, ...data.map((d) => d.value));
    return (
        <div className="flex h-44 items-end gap-1.5 overflow-x-auto px-4 pb-4 pt-6" role="img" aria-label="Earnings per day">
            {data.map((d) => (
                <div key={d.date} className="flex min-w-8 flex-1 flex-col items-center gap-1">
                    <div className="w-full rounded-t-md bg-primary/80" style={{ height: `${Math.max(4, (d.value / max) * 120)}px` }} title={`${d.date}: ${d.label}`} />
                    <span className="whitespace-nowrap text-[10px] text-muted">{d.date}</span>
                </div>
            ))}
        </div>
    );
}

export default function Index({ period, summary, daily, transactions }) {
    return (
        <DriverLayout title="Earnings">
            <Head title="Earnings" />
            <div className="mb-4 flex gap-2 overflow-x-auto" role="tablist" aria-label="Period">
                {PERIODS.map(([v, l]) => (
                    <button key={v} type="button" role="tab" aria-selected={period === v} onClick={() => router.get('/driver/earnings', { period: v }, { preserveState: true })}
                        className={`whitespace-nowrap rounded-full px-4 py-2 text-sm ${period === v ? 'bg-primary text-on-primary' : 'border border-line bg-surface text-muted'}`}>{l}</button>
                ))}
            </div>

            <Card className="mb-4 bg-primary p-5 text-on-primary">
                <p className="text-sm opacity-80">Earned {PERIODS.find(([v]) => v === period)?.[1].toLowerCase()}</p>
                <p className="text-3xl font-bold">{summary.total}</p>
                <p className="mt-1 text-sm opacity-80">{summary.deliveries} deliveries · {summary.average} per delivery</p>
            </Card>

            <div className="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
                <StatCard label="Deliveries" value={summary.delivery} icon={Truck} tone="primary" />
                <StatCard label="Tips" value={summary.tips} icon={HandCoins} tone="success" />
                <StatCard label="Bonuses" value={summary.bonus} icon={Gift} tone="info" />
                <StatCard label="All time" value={summary.lifetime} icon={Wallet} tone="warning" />
            </div>

            <div className="grid gap-5 lg:grid-cols-2">
                <Card><CardHeader title="Per day" /><DailyChart data={daily} /></Card>
                <Card>
                    <CardHeader title="Recent"><Link href="/driver/earnings/history" className="text-sm text-primary hover:underline">All</Link></CardHeader>
                    {transactions.length === 0 ? <p className="p-6 text-center text-sm text-muted">Nothing yet.</p> : (
                        <ul className="divide-y divide-line">{transactions.map((t) => <EarningRow key={t.id} t={t} />)}</ul>
                    )}
                </Card>
            </div>
        </DriverLayout>
    );
}
