import { Head } from '@inertiajs/react';
import SettingsLayout from '../../../Layouts/SettingsLayout';
import { Field, Input } from '../../../Components/ui';
import { SaveButton, SettingsCard } from '../../../Components/settingsUi';
import { useSettings } from '../../../lib/settings';

const NETWORKS = [
    ['social.facebook', 'Facebook', 'https://facebook.com/yourpage'],
    ['social.twitter', 'Twitter / X', 'https://twitter.com/yourhandle'],
    ['social.instagram', 'Instagram', 'https://instagram.com/yourhandle'],
    ['social.youtube', 'YouTube', 'https://youtube.com/@yourchannel'],
    ['social.linkedin', 'LinkedIn', 'https://linkedin.com/company/yourcompany'],
    ['social.tiktok', 'TikTok', 'https://tiktok.com/@yourhandle'],
];

export default function Social({ values }) {
    const { data, set, submit, processing } = useSettings('social', values);

    return (
        <SettingsLayout>
            <Head title="Social Media Settings" />
            <form onSubmit={(e) => { e.preventDefault(); submit(); }} className="space-y-5">
                <SettingsCard title="Social Media Links" description="Links shown in the storefront footer and header.">
                    <div className="grid gap-4 md:grid-cols-2">
                        {NETWORKS.map(([key, label, placeholder]) => (
                            <Field key={key} label={label}>
                                <Input type="url" placeholder={placeholder} value={data[key] ?? ''} onChange={(e) => set(key, e.target.value)} />
                            </Field>
                        ))}
                    </div>
                </SettingsCard>
                <SaveButton processing={processing} />
            </form>
        </SettingsLayout>
    );
}
