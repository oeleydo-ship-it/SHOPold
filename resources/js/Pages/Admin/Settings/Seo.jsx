import { Head } from '@inertiajs/react';
import SettingsLayout from '../../../Layouts/SettingsLayout';
import { Field, Input, Textarea } from '../../../Components/ui';
import { SaveButton, SettingsCard } from '../../../Components/settingsUi';
import { useSettings } from '../../../lib/settings';

export default function Seo({ values }) {
    const { data, set, submit, processing } = useSettings('seo', values);
    const f = (k) => ({ value: data[k] ?? '', onChange: (e) => set(k, e.target.value) });

    return (
        <SettingsLayout>
            <Head title="SEO Settings" />
            <form onSubmit={(e) => { e.preventDefault(); submit(); }} className="space-y-5">
                <SettingsCard title="SEO Defaults">
                    <div className="space-y-4">
                        <Field label="Default Meta Title" hint="Used when pages don't have a custom title."><Input {...f('seo.meta_title')} /></Field>
                        <Field label="Default Meta Description"><Textarea rows={3} {...f('seo.meta_description')} /></Field>
                        <Field label="Meta Keywords" hint="Comma-separated keywords"><Input {...f('seo.meta_keywords')} /></Field>
                    </div>
                </SettingsCard>
                <SettingsCard title="Analytics & Tracking">
                    <div className="space-y-4">
                        <Field label="Google Analytics ID"><Input placeholder="G-XXXXXXXXXX" {...f('seo.google_analytics')} /></Field>
                        <Field label="Facebook Pixel ID"><Input placeholder="XXXXXXXXXXXXXXXX" {...f('seo.facebook_pixel')} /></Field>
                        <Field label="Google Site Verification"><Input {...f('seo.google_site_verification')} /></Field>
                    </div>
                </SettingsCard>
                <SaveButton processing={processing} />
            </form>
        </SettingsLayout>
    );
}
