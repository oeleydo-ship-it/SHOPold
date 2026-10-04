import { Head, Link } from '@inertiajs/react';
import { Globe, Pencil, Plus, Settings, Trash2, Truck } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { Badge, Card, LinkButton, titleCase } from '../../../Components/ui';
import { confirmDelete } from '../../../Components/crud';

export const methodCost = (m) => {
    if (m.type === 'free') return `Free${m.min_label ? ` (min ${m.min_label})` : ''}`;
    return `${m.cost_label}${m.type === 'weight_based' && m.per_kg_label ? ` + ${m.per_kg_label}/kg` : ''}`;
};

export default function Index({ zones }) {
    return (
        <AdminLayout title="Shipping Zones">
            <Head title="Shipping Zones" />
            <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                <Link href="/admin/settings/shipping" className="inline-flex items-center gap-2 text-sm text-muted hover:text-ink"><Settings size={16} />Shipping settings</Link>
                <LinkButton href="/admin/shipping/create"><Plus size={16} />Add Zone</LinkButton>
            </div>

            {zones.length === 0 ? (
                <Card className="p-10 text-center">
                    <Globe className="mx-auto mb-3 text-muted" size={36} />
                    <h3 className="font-semibold">No Shipping Zones</h3>
                    <p className="mb-4 text-sm text-muted">Create shipping zones to define where you ship and how much it costs.</p>
                    <LinkButton href="/admin/shipping/create"><Plus size={16} />Create First Zone</LinkButton>
                </Card>
            ) : (
                <div className="space-y-4">
                    {zones.map((z) => (
                        <Card key={z.id} className="overflow-hidden">
                            <div className="flex flex-wrap items-center justify-between gap-3 p-4 md:px-6">
                                <div className="flex items-center gap-3">
                                    <span className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary"><Truck size={18} /></span>
                                    <div>
                                        <h3 className="font-semibold">{z.name}</h3>
                                        <p className="text-xs text-muted">{z.all_countries ? 'All countries' : `${z.countries_count} countries`} · {z.methods.length} shipping method{z.methods.length === 1 ? '' : 's'}</p>
                                    </div>
                                </div>
                                <div className="flex items-center gap-3">
                                    <Badge tone={z.is_active ? 'success' : 'muted'}>{z.is_active ? 'Active' : 'Inactive'}</Badge>
                                    <Link href={`/admin/shipping/${z.id}/edit`} aria-label={`Edit ${z.name}`} className="text-primary"><Pencil size={18} /></Link>
                                    <button type="button" aria-label={`Delete ${z.name}`} onClick={() => confirmDelete(`/admin/shipping/${z.id}`, `Delete shipping zone "${z.name}" and its methods?`)} className="text-danger"><Trash2 size={18} /></button>
                                </div>
                            </div>
                            {z.methods.length > 0 && (
                                <div className="overflow-x-auto border-t border-line">
                                    <table className="w-full text-sm">
                                        <thead className="bg-surface-2 text-left text-xs uppercase text-muted"><tr><th className="px-6 py-2">Method</th><th className="px-3 py-2">Type</th><th className="px-3 py-2">Cost</th><th className="px-3 py-2">Delivery time</th><th className="px-3 py-2">Status</th></tr></thead>
                                        <tbody className="divide-y divide-line">
                                            {z.methods.map((m) => (
                                                <tr key={m.id}>
                                                    <td className="px-6 py-2 font-medium">{m.name}</td><td className="px-3 py-2">{titleCase(m.type)}</td><td className="px-3 py-2">{methodCost(m)}</td>
                                                    <td className="px-3 py-2">{m.delivery_time || '-'}</td><td className="px-3 py-2"><Badge tone={m.is_active ? 'success' : 'muted'}>{m.is_active ? 'Active' : 'Inactive'}</Badge></td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </Card>
                    ))}
                </div>
            )}
        </AdminLayout>
    );
}
