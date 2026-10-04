import { Head, Link, useForm } from '@inertiajs/react';
import { Check, Plus, Trash2 } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { Button, Card, CardHeader, Field, Input, Select, Textarea } from '../../../Components/ui';

const money = (n) => n.toFixed(2);

export default function Create({ users, products, taxRate }) {
    const { data, setData, post, processing, errors } = useForm({
        user_id: '',
        products: [{ id: '', quantity: 1 }],
        shipping_name: '', shipping_address: '', shipping_city: '', shipping_state: '', shipping_zip: '',
        shipping_country: 'United States', shipping_phone: '',
        shipping_cost: '0', notes: '',
        status: 'pending', payment_status: 'pending', payment_method: 'manual',
    });

    const byId = Object.fromEntries(products.map((p) => [String(p.id), p]));
    const lineTotal = (l) => (byId[l.id]?.price ?? 0) * (Number(l.quantity) || 0);
    const subtotal = data.products.reduce((s, l) => s + lineTotal(l), 0);
    const tax = subtotal * (taxRate / 100);
    const total = subtotal + (parseFloat(data.shipping_cost) || 0) + tax;

    const setLine = (i, patch) => setData('products', data.products.map((l, n) => (n === i ? { ...l, ...patch } : l)));
    const addLine = () => setData('products', [...data.products, { id: '', quantity: 1 }]);
    const removeLine = (i) => setData('products', data.products.filter((_, n) => n !== i));
    const field = (k) => ({ value: data[k], onChange: (e) => setData(k, e.target.value) });
    const err = (k) => errors[k];

    const submit = (e) => {
        e.preventDefault();
        post('/admin/orders');
    };

    return (
        <AdminLayout title="Create Order">
            <Head title="Create Order" />
            <form onSubmit={submit} className="grid gap-5 lg:grid-cols-3">
                <div className="space-y-5 lg:col-span-2">
                    <Card>
                        <CardHeader title="Customer" />
                        <div className="p-4 md:p-6">
                            <Field label="Customer *" error={err('user_id')}>
                                <Select {...field('user_id')} required>
                                    <option value="">Select customer…</option>
                                    {users.map((u) => <option key={u.id} value={u.id}>{u.name} ({u.email})</option>)}
                                </Select>
                            </Field>
                        </div>
                    </Card>

                    <Card>
                        <CardHeader title="Products">
                            <Button tone="neutral" onClick={addLine}><Plus size={14} />Add product</Button>
                        </CardHeader>
                        <div className="space-y-3 p-4 md:p-6">
                            {err('products') && <p className="text-xs text-danger">{err('products')}</p>}
                            {data.products.map((l, i) => (
                                <div key={i} className="grid grid-cols-12 items-start gap-2">
                                    <div className="col-span-12 sm:col-span-7">
                                        <Select value={l.id} onChange={(e) => setLine(i, { id: e.target.value })} required aria-label="Product">
                                            <option value="">Select product…</option>
                                            {products.map((p) => <option key={p.id} value={p.id}>{p.name} — {money(p.price)} ({p.quantity} in stock)</option>)}
                                        </Select>
                                        {err(`products.${i}.id`) && <p className="mt-1 text-xs text-danger">{err(`products.${i}.id`)}</p>}
                                    </div>
                                    <div className="col-span-5 sm:col-span-2">
                                        <Input type="number" min="1" max={byId[l.id]?.quantity} value={l.quantity} onChange={(e) => setLine(i, { quantity: e.target.value })} required aria-label="Quantity" />
                                    </div>
                                    <p className="col-span-5 self-center text-right text-sm font-medium sm:col-span-2">{money(lineTotal(l))}</p>
                                    <button
                                        type="button"
                                        onClick={() => removeLine(i)}
                                        disabled={data.products.length === 1}
                                        aria-label="Remove product"
                                        className="col-span-2 self-center text-danger disabled:opacity-30 sm:col-span-1"
                                    >
                                        <Trash2 size={16} />
                                    </button>
                                </div>
                            ))}
                        </div>
                    </Card>

                    <Card>
                        <CardHeader title="Shipping Address" />
                        <div className="grid gap-4 p-4 md:grid-cols-2 md:p-6">
                            <Field label="Full Name *" error={err('shipping_name')}><Input {...field('shipping_name')} required /></Field>
                            <Field label="Phone *" error={err('shipping_phone')}><Input {...field('shipping_phone')} required /></Field>
                            <Field label="Address *" error={err('shipping_address')} className="md:col-span-2"><Textarea rows={2} {...field('shipping_address')} required /></Field>
                            <Field label="City *" error={err('shipping_city')}><Input {...field('shipping_city')} required /></Field>
                            <Field label="State/Province" error={err('shipping_state')}><Input {...field('shipping_state')} /></Field>
                            <Field label="ZIP/Postal Code" error={err('shipping_zip')}><Input {...field('shipping_zip')} /></Field>
                            <Field label="Country *" error={err('shipping_country')}><Input {...field('shipping_country')} required /></Field>
                        </div>
                    </Card>

                    <Card>
                        <CardHeader title="Notes" />
                        <div className="p-4 md:p-6">
                            <Textarea rows={3} placeholder="Internal notes about this order…" {...field('notes')} aria-label="Notes" />
                        </div>
                    </Card>
                </div>

                <div className="space-y-5">
                    <Card>
                        <CardHeader title="Summary" />
                        <div className="space-y-2 p-4 text-sm md:p-6">
                            <div className="flex justify-between"><span className="text-muted">Subtotal</span><span>{money(subtotal)}</span></div>
                            <div className="flex items-center justify-between gap-3">
                                <span className="text-muted">Shipping</span>
                                <Input type="number" min="0" step="0.01" {...field('shipping_cost')} className="w-28 text-right" aria-label="Shipping cost" />
                            </div>
                            <div className="flex justify-between"><span className="text-muted">Tax ({taxRate}%)</span><span>{money(tax)}</span></div>
                            <div className="flex justify-between border-t border-line pt-2 text-base font-bold"><span>Total</span><span>{money(total)}</span></div>
                        </div>
                    </Card>

                    <Card>
                        <CardHeader title="Status" />
                        <div className="space-y-4 p-4 md:p-6">
                            <Field label="Order Status">
                                <Select {...field('status')}>
                                    <option value="pending">Pending</option>
                                    <option value="processing">Processing</option>
                                    <option value="shipped">Shipped</option>
                                    <option value="delivered">Delivered</option>
                                </Select>
                            </Field>
                            <Field label="Payment Status">
                                <Select {...field('payment_status')}>
                                    <option value="pending">Pending</option>
                                    <option value="paid">Paid</option>
                                </Select>
                            </Field>
                            <Field label="Payment Method">
                                <Select {...field('payment_method')}>
                                    <option value="manual">Manual / Offline</option>
                                    <option value="cod">Cash on Delivery</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                </Select>
                            </Field>
                        </div>
                    </Card>

                    <Button type="submit" disabled={processing} className="w-full py-3"><Check size={16} />Create Order</Button>
                    <Link href="/admin/orders" className="block text-center text-sm text-muted hover:text-ink">Cancel</Link>
                </div>
            </form>
        </AdminLayout>
    );
}
