import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { Button, Card, Field, Input, Select } from '../../../Components/ui';
import { Toggle } from '../../../Components/crud';

export default function Form({ coupon }) {
    const edit = !!coupon;
    const s = (v) => (v === null || v === undefined ? '' : String(v));
    const form = useForm({
        code: s(coupon?.code),
        type: coupon?.type ?? 'percent',
        value: s(coupon?.value),
        minimum_amount: s(coupon?.minimum_amount),
        max_uses: s(coupon?.max_uses),
        starts_at: s(coupon?.starts_at),
        expires_at: s(coupon?.expires_at),
        is_active: coupon?.is_active ?? true,
    });
    const { data, setData, errors, processing } = form;
    const f = (k) => ({ value: data[k], onChange: (e) => setData(k, e.target.value) });

    const submit = (e) => {
        e.preventDefault();
        if (edit) form.put(`/admin/coupons/${coupon.id}`);
        else form.post('/admin/coupons');
    };

    const title = edit ? 'Edit Coupon' : 'Add Coupon';
    return (
        <AdminLayout title={title}>
            <Head title={title} />
            <Link href="/admin/coupons" className="mb-5 inline-flex items-center gap-2 text-sm text-muted hover:text-ink"><ArrowLeft size={16} />Back to Coupons</Link>
            <form onSubmit={submit} className="max-w-2xl">
                <Card className="space-y-4 p-4 md:p-6">
                    <Field label="Coupon Code *" error={errors.code}>
                        <Input value={data.code} onChange={(e) => setData('code', e.target.value.toUpperCase())} required maxLength={50} className="font-mono uppercase" />
                    </Field>
                    <div className="grid gap-4 md:grid-cols-2">
                        <Field label="Type *" error={errors.type}>
                            <Select {...f('type')} required>
                                <option value="percent">Percentage</option>
                                <option value="fixed">Fixed Amount</option>
                            </Select>
                        </Field>
                        <Field label={data.type === 'percent' ? 'Value (%) *' : 'Value *'} error={errors.value}>
                            <Input type="number" step="0.01" min="0" max={data.type === 'percent' ? 100 : undefined} {...f('value')} required />
                        </Field>
                        <Field label="Minimum Order Amount" error={errors.minimum_amount}><Input type="number" step="0.01" min="0" {...f('minimum_amount')} /></Field>
                        <Field label="Max Uses" hint="Leave empty for unlimited" error={errors.max_uses}><Input type="number" min="1" {...f('max_uses')} /></Field>
                        <Field label="Start Date" error={errors.starts_at}><Input type="date" {...f('starts_at')} /></Field>
                        <Field label="Expiry Date" error={errors.expires_at}><Input type="date" {...f('expires_at')} /></Field>
                    </div>
                    <Toggle label="Active" checked={data.is_active} onChange={(v) => setData('is_active', v)} />
                    <Button type="submit" className="w-full py-3" disabled={processing}>{edit ? 'Update Coupon' : 'Create Coupon'}</Button>
                </Card>
            </form>
        </AdminLayout>
    );
}
