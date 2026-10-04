import { Head, useForm } from '@inertiajs/react';
import { Building2, CreditCard, Mail } from 'lucide-react';
import VendorLayout from '../../Layouts/VendorLayout';
import { Field, Input } from '../../Components/ui';
import { SaveButton, SettingsCard } from '../../Components/settingsUi';

const METHODS = [
    ['bank_transfer', 'Bank transfer', Building2],
    ['paypal', 'PayPal', Mail],
    ['stripe', 'Stripe', CreditCard],
];

export default function PaymentSettings({ info }) {
    const { data, setData, put, processing, errors } = useForm(info);
    const f = (k) => ({ value: data[k] ?? '', onChange: (e) => setData(k, e.target.value) });
    const submit = (e) => { e.preventDefault(); put('/vendor/payment-settings', { preserveScroll: true }); };

    return (
        <VendorLayout title="Payout details">
            <Head title="Payout details" />
            <form onSubmit={submit} className="max-w-3xl space-y-5">
                <SettingsCard title="How you get paid" description="Payouts you request are sent with this method.">
                    <div className="grid gap-3 sm:grid-cols-3" role="radiogroup" aria-label="Payout method">
                        {METHODS.map(([value, label, Icon]) => (
                            <label key={value} className={`flex cursor-pointer items-center gap-3 rounded-lg border p-3 text-sm ${data.preferred_method === value ? 'border-primary bg-primary/10' : 'border-line hover:bg-surface-2'}`}>
                                <input type="radio" name="preferred_method" value={value} checked={data.preferred_method === value} onChange={() => setData('preferred_method', value)} className="accent-[var(--primary)]" />
                                <Icon size={18} className="text-muted" />{label}
                            </label>
                        ))}
                    </div>
                    {errors.preferred_method && <p className="mt-2 text-xs text-danger">{errors.preferred_method}</p>}
                </SettingsCard>

                {data.preferred_method === 'bank_transfer' && (
                    <SettingsCard title="Bank account">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field label="Bank name *" error={errors.bank_name}><Input maxLength={255} {...f('bank_name')} /></Field>
                            <Field label="Account holder *" error={errors.bank_account_name}><Input maxLength={255} {...f('bank_account_name')} /></Field>
                            <Field label="Account number *" error={errors.bank_account_number}><Input maxLength={50} autoComplete="off" {...f('bank_account_number')} /></Field>
                            <Field label="Routing number" error={errors.bank_routing_number}><Input maxLength={50} autoComplete="off" {...f('bank_routing_number')} /></Field>
                            <Field label="SWIFT / BIC" error={errors.bank_swift_code}><Input maxLength={20} {...f('bank_swift_code')} /></Field>
                        </div>
                    </SettingsCard>
                )}
                {data.preferred_method === 'paypal' && (
                    <SettingsCard title="PayPal">
                        <Field label="PayPal email *" error={errors.paypal_email}><Input type="email" maxLength={255} {...f('paypal_email')} /></Field>
                    </SettingsCard>
                )}
                {data.preferred_method === 'stripe' && (
                    <SettingsCard title="Stripe">
                        <Field label="Stripe account ID *" hint="Starts with acct_" error={errors.stripe_account_id}><Input maxLength={255} {...f('stripe_account_id')} /></Field>
                    </SettingsCard>
                )}

                <SaveButton processing={processing}>Save payout details</SaveButton>
            </form>
        </VendorLayout>
    );
}
