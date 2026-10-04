import { Head, useForm } from '@inertiajs/react';
import { BadgeCheck, Store } from 'lucide-react';
import VendorLayout from '../../Layouts/VendorLayout';
import VendorStoreFields from '../../Components/VendorStoreFields';
import { Button } from '../../Components/ui';
import { SettingsCard } from '../../Components/settingsUi';

export default function Register({ commission, email }) {
    const form = useForm({ store_name: '', description: '', phone: '', address: '', city: '', state: '', country: '', zip_code: '', logo: null, banner: null });
    const submit = (e) => { e.preventDefault(); form.post('/vendor/register', { forceFormData: true }); };

    return (
        <VendorLayout title="Become a vendor">
            <Head title="Become a vendor" />
            <div className="mx-auto max-w-3xl space-y-5">
                <div className="flex items-start gap-4 rounded-card border border-primary/30 bg-primary/10 p-4 text-sm">
                    <Store size={22} className="mt-0.5 shrink-0 text-primary" />
                    <div className="space-y-1">
                        <p className="font-semibold">Sell your products in our store</p>
                        <p className="text-muted">Tell us about your store. Once an administrator approves it you can add products and receive orders.</p>
                        <p className="flex items-center gap-1 text-muted"><BadgeCheck size={14} />Store commission: {commission}% of each sale. We will contact you at {email}.</p>
                    </div>
                </div>
                <form onSubmit={submit}>
                    <SettingsCard title="Your store">
                        <VendorStoreFields form={form} />
                        <div className="mt-6"><Button type="submit" disabled={form.processing}>{form.processing ? 'Sending…' : 'Submit application'}</Button></div>
                    </SettingsCard>
                </form>
            </div>
        </VendorLayout>
    );
}
