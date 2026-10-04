import { Head } from '@inertiajs/react';
import { Info, Settings2 } from 'lucide-react';
import SettingsLayout from '../../../Layouts/SettingsLayout';
import { Field, Input, LinkButton, Select } from '../../../Components/ui';
import { SaveButton, SettingsCard, SwitchRow } from '../../../Components/settingsUi';
import { useSettings } from '../../../lib/settings';

const BOOLEANS = ['shipping.single_country_mode', 'shipping.store_pickup', 'shipping.international'];
const ALLOWED = 'shipping.allowed_countries';

export default function Shipping({ values, countries, allowed }) {
    const { data, set, submit, processing } = useSettings('shipping', { ...values, [ALLOWED]: allowed }, BOOLEANS);
    const single = data['shipping.single_country_mode'];

    return (
        <SettingsLayout>
            <Head title="Shipping Settings" />

            <div className="flex flex-wrap items-center justify-between gap-4 rounded-card bg-primary p-5 text-on-primary md:p-6">
                <div>
                    <h2 className="text-lg font-bold">Shipping Zones & Methods</h2>
                    <p className="text-sm opacity-90">Configure shipping zones, rates, and delivery methods for different regions.</p>
                </div>
                <a href="/admin/shipping" className="inline-flex items-center gap-2 rounded-lg bg-surface px-5 py-2.5 font-semibold text-primary hover:opacity-90"><Settings2 size={16} />Manage Zones</a>
            </div>

            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    // International shipping is locked off in single-country mode (the old form did not submit it either).
                    submit((d) => ({ ...d, 'shipping.international': single ? false : d['shipping.international'] }));
                }}
                className="space-y-5"
            >
                <SettingsCard title="Shipping Configuration">
                    <div className="grid gap-4 md:grid-cols-2">
                        <Field label="Default Country">
                            <Select value={data['shipping.default_country']} onChange={(e) => set('shipping.default_country', e.target.value)}>
                                {Object.entries(countries).map(([code, name]) => <option key={code} value={code}>{name}</option>)}
                            </Select>
                        </Field>
                        <Field label="Free Shipping Threshold" hint="Orders above this amount get free shipping. Set to 0 to disable.">
                            <Input type="number" step="0.01" min="0" value={data['shipping.free_threshold']} onChange={(e) => set('shipping.free_threshold', e.target.value)} />
                        </Field>
                    </div>

                    <h3 className="mb-3 mt-8 font-bold">Country Restrictions</h3>
                    <div className="space-y-3">
                        <SwitchRow title="Single Country Mode" description="Restrict sales to only one country (domestic only)" checked={single} onChange={(v) => set('shipping.single_country_mode', v)} />
                        {!single ? (
                            <div className="rounded-lg border border-info/40 bg-info/10 p-4">
                                <h4 className="mb-1 font-semibold text-info">Allowed Countries</h4>
                                <p className="mb-3 text-sm text-muted">Select which countries you ship to. Leave empty to allow all countries.</p>
                                <select
                                    multiple size={8} aria-label="Allowed countries"
                                    value={data[ALLOWED]}
                                    onChange={(e) => set(ALLOWED, [...e.target.selectedOptions].map((o) => o.value))}
                                    className="w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm text-ink focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                >
                                    {Object.entries(countries).map(([code, name]) => <option key={code} value={code}>{name}</option>)}
                                </select>
                                <p className="mt-2 text-xs text-muted">Hold Ctrl/Cmd to select multiple countries · {data[ALLOWED].length} selected</p>
                            </div>
                        ) : (
                            <p className="flex items-start gap-2 rounded-lg border border-warning/40 bg-warning/10 p-4 text-sm text-warning">
                                <Info size={16} className="mt-0.5 shrink-0" />When Single Country Mode is enabled, customers can only checkout with the default country selected above. The country field will be locked.
                            </p>
                        )}
                    </div>

                    <h3 className="mb-3 mt-8 font-bold">Shipping Options</h3>
                    <div className="space-y-3">
                        <SwitchRow title="Store Pickup" description="Allow customers to pick up orders from store" checked={data['shipping.store_pickup']} onChange={(v) => set('shipping.store_pickup', v)} />
                        <SwitchRow title="International Shipping" description="Enable shipping to international destinations" checked={single ? false : data['shipping.international']} disabled={single} onChange={(v) => set('shipping.international', v)} />
                    </div>
                </SettingsCard>
                <SaveButton processing={processing} />
            </form>
        </SettingsLayout>
    );
}
