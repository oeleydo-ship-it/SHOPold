import { Head, useForm } from '@inertiajs/react';
import { ExternalLink } from 'lucide-react';
import VendorLayout from '../../../Layouts/VendorLayout';
import VendorStoreFields from '../../../Components/VendorStoreFields';
import { Card, CardHeader, Field, Input } from '../../../Components/ui';
import { SaveButton, SettingsCard } from '../../../Components/settingsUi';

const SOCIAL = [['facebook', 'Facebook'], ['twitter', 'X / Twitter'], ['instagram', 'Instagram']];

export default function Store({ store, summary }) {
    const form = useForm({
        store_name: store.store_name ?? '', description: store.description ?? '', phone: store.phone ?? '', email: store.email ?? '',
        address: store.address ?? '', city: store.city ?? '', state: store.state ?? '', country: store.country ?? '', zip_code: store.zip_code ?? '',
        social_links: store.social_links, logo: null, banner: null,
    });
    const { data, setData, post, processing, errors, transform } = form;

    transform((d) => ({ ...d, _method: 'put' }));
    const submit = (e) => {
        e.preventDefault();
        post('/vendor/settings', { forceFormData: true, preserveScroll: true, onSuccess: () => { setData('logo', null); setData('banner', null); } });
    };

    return (
        <VendorLayout title="Store settings" actions={<a href={store.url} target="_blank" rel="noreferrer" className="inline-flex items-center gap-1 text-sm text-primary hover:underline"><ExternalLink size={14} />View store</a>}>
            <Head title="Store settings" />
            <form onSubmit={submit} className="grid gap-5 xl:grid-cols-3">
                <div className="space-y-5 xl:col-span-2">
                    <SettingsCard title="Store profile" description="Shown to customers on your store page.">
                        <VendorStoreFields form={form} current={store} withEmail />
                    </SettingsCard>
                    <SettingsCard title="Social links">
                        <div className="grid gap-4 sm:grid-cols-3">
                            {SOCIAL.map(([k, label]) => (
                                <Field key={k} label={label} error={errors[`social_links.${k}`]}>
                                    <Input type="url" placeholder="https://" value={data.social_links[k] ?? ''} onChange={(e) => setData('social_links', { ...data.social_links, [k]: e.target.value })} />
                                </Field>
                            ))}
                        </div>
                    </SettingsCard>
                    <SaveButton processing={processing}>Save store settings</SaveButton>
                </div>

                <Card className="h-fit">
                    <CardHeader title="Account" />
                    <dl className="space-y-2 p-4 text-sm md:px-6">
                        <div className="flex justify-between"><dt className="text-muted">Status</dt><dd className="capitalize">{store.status}</dd></div>
                        <div className="flex justify-between"><dt className="text-muted">Store commission</dt><dd>{store.commission_rate}%</dd></div>
                        <div className="flex justify-between"><dt className="text-muted">Member since</dt><dd>{store.joined}</dd></div>
                        <div className="flex justify-between border-t border-line pt-2"><dt className="text-muted">Total sales</dt><dd>{summary.sales}</dd></div>
                        <div className="flex justify-between"><dt className="text-muted">You earned</dt><dd>{summary.earnings}</dd></div>
                        <div className="flex justify-between font-semibold"><dt>Balance</dt><dd className="text-success">{summary.balance}</dd></div>
                    </dl>
                </Card>
            </form>
        </VendorLayout>
    );
}
