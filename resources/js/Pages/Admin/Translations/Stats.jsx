import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Languages } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { Card, CardHeader, LinkButton } from '../../../Components/ui';

const TYPES = ['products', 'categories', 'brands', 'pages'];
const pct = (done, total) => (total > 0 ? Math.round((done / total) * 100) : 0);

export default function Stats({ stats }) {
    return (
        <AdminLayout title="Translation Statistics">
            <Head title="Translation Statistics" />
            <Link href="/admin/translations" className="mb-5 inline-flex items-center gap-2 text-sm text-muted hover:text-ink"><ArrowLeft size={16} />Back to Translations</Link>

            {stats.length === 0 ? (
                <Card className="p-10 text-center">
                    <Languages className="mx-auto mb-3 text-muted" size={36} />
                    <h3 className="font-semibold">No additional languages configured</h3>
                    <p className="mb-4 text-sm text-muted">Add languages in the store settings to see translation progress.</p>
                    <LinkButton href="/admin/settings">Open Settings</LinkButton>
                </Card>
            ) : (
                <div className="grid gap-5 lg:grid-cols-2">
                    {stats.map((s) => {
                        const total = TYPES.reduce((n, t) => n + s[t].total, 0);
                        const done = TYPES.reduce((n, t) => n + s[t].translated, 0);
                        return (
                            <Card key={s.code}>
                                <CardHeader title={s.name}><span className="text-2xl font-bold">{pct(done, total)}%</span></CardHeader>
                                <div className="space-y-4 p-4 md:p-6">
                                    <p className="text-xs uppercase text-muted">Language code: {s.code}</p>
                                    {TYPES.map((t) => {
                                        const p = pct(s[t].translated, s[t].total);
                                        const remaining = s[t].total - s[t].translated;
                                        return (
                                            <div key={t}>
                                                <div className="mb-1 flex justify-between text-sm"><span className="font-medium capitalize">{t} <span className="font-normal text-muted">({s[t].total} total)</span></span><span>{p}%</span></div>
                                                <div className="h-2 overflow-hidden rounded-full bg-surface-2" role="progressbar" aria-valuenow={p} aria-valuemin={0} aria-valuemax={100} aria-label={`${s.name} ${t}`}><div className="h-full rounded-full bg-primary" style={{ width: `${p}%` }} /></div>
                                                <div className="mt-1 flex justify-between text-xs text-muted">
                                                    <span>{s[t].translated} done</span>
                                                    {remaining > 0 && <Link href={`/admin/translations?type=${t}&locale=${s.code}`} className="text-primary hover:underline">{remaining} pending — translate</Link>}
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            </Card>
                        );
                    })}
                </div>
            )}
        </AdminLayout>
    );
}
