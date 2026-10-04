import { Head } from '@inertiajs/react';
import SettingsLayout from '../../../Layouts/SettingsLayout';
import MediaPicker from '../../../Components/MediaPicker';
import { Field, Input, Select, Textarea } from '../../../Components/ui';
import { Toggle } from '../../../Components/crud';
import { SaveButton, SettingsCard } from '../../../Components/settingsUi';
import { useSettings } from '../../../lib/settings';

const TIMEZONES = ['UTC', 'America/New_York', 'America/Los_Angeles', 'Europe/London', 'Europe/Paris', 'Asia/Dubai', 'Asia/Kolkata'];

export default function General({ values }) {
    const { data, set, submit, processing } = useSettings('store', values, ['store.hide_name_in_header']);
    const f = (k) => ({ value: data[k] ?? '', onChange: (e) => set(k, e.target.value) });
    // Keep a stored timezone that is not in the short list selectable instead of silently resetting it.
    const zones = TIMEZONES.includes(data['store.timezone']) || !data['store.timezone'] ? TIMEZONES : [data['store.timezone'], ...TIMEZONES];

    return (
        <SettingsLayout>
            <Head title="General Settings" />
            <form onSubmit={(e) => { e.preventDefault(); submit(); }} className="space-y-5">
                <SettingsCard title="Store Information">
                    <div className="grid gap-4 md:grid-cols-2">
                        <Field label="Store Name"><Input {...f('store.name')} /></Field>
                        <Field label="Tagline"><Input {...f('store.tagline')} /></Field>
                        <Field label="Email Address"><Input type="email" {...f('store.email')} /></Field>
                        <Field label="Phone Number"><Input {...f('store.phone')} /></Field>
                        <Field label="Address" className="md:col-span-2"><Textarea rows={2} {...f('store.address')} /></Field>
                        <Field label="Timezone">
                            <Select {...f('store.timezone')}>{zones.map((z) => <option key={z} value={z}>{z}</option>)}</Select>
                        </Field>
                    </div>
                </SettingsCard>

                <SettingsCard title="Logo & Favicon">
                    <div className="grid gap-6 md:grid-cols-3">
                        {[
                            ['store.logo', 'Store Logo', 'Displayed on the frontend storefront', 'logos', 'Select Logo'],
                            ['store.admin_logo', 'Admin Logo', 'Displayed in the admin panel sidebar', 'logos', 'Select Admin Logo'],
                            ['store.favicon', 'Favicon', 'Browser tab icon', 'favicons', 'Select Favicon'],
                        ].map(([key, label, hint, collection, button]) => (
                            <div key={key}>
                                <p className="mb-1 text-sm font-medium">{label}</p>
                                <p className="mb-2 text-xs text-muted">{hint}</p>
                                <MediaPicker value={data[key]} onChange={(v) => set(key, v)} collection={collection} label={button} />
                            </div>
                        ))}
                    </div>
                    <div className="mt-5">
                        <Toggle label="Hide store name in header (show only logo)" checked={data['store.hide_name_in_header']} onChange={(v) => set('store.hide_name_in_header', v)} />
                        <p className="ml-14 mt-1 text-xs text-muted">When enabled, only the logo is displayed in the header without the store name text.</p>
                    </div>
                </SettingsCard>

                <SaveButton processing={processing} />
            </form>
        </SettingsLayout>
    );
}
