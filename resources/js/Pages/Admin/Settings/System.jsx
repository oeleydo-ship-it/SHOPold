import { Head, router } from '@inertiajs/react';
import { Info, RefreshCw } from 'lucide-react';
import SettingsLayout from '../../../Layouts/SettingsLayout';
import { Button, Field, Select, Textarea } from '../../../Components/ui';
import { SaveButton, SettingsCard, SwitchRow } from '../../../Components/settingsUi';
import { useSettings } from '../../../lib/settings';

const BOOLEANS = ['system.maintenance_mode', 'system.allow_registration', 'system.require_email_verification'];

export default function System({ values, info }) {
    const { data, set, submit, processing } = useSettings('system', values, BOOLEANS);

    return (
        <SettingsLayout>
            <Head title="System Settings" />
            <form onSubmit={(e) => { e.preventDefault(); submit(); }} className="space-y-5">
                <SettingsCard title="System Configuration">
                    <div className="max-w-xs">
                        <Field label="Items per page">
                            <Select value={String(data['system.items_per_page'] || 12)} onChange={(e) => set('system.items_per_page', e.target.value)}>
                                {[8, 12, 16, 24].map((n) => <option key={n} value={n}>{n}</option>)}
                            </Select>
                        </Field>
                    </div>

                    <h3 className="mb-3 mt-8 font-bold">System Options</h3>
                    <div className="space-y-3">
                        <SwitchRow title="Allow Registration" description="Let new customers create accounts" checked={data['system.allow_registration']} onChange={(v) => set('system.allow_registration', v)} />
                        <SwitchRow title="Require Email Verification" description="Customers must verify their email before ordering" checked={data['system.require_email_verification']} onChange={(v) => set('system.require_email_verification', v)} />
                        <div className={`rounded-lg border p-4 ${data['system.maintenance_mode'] ? 'border-danger/50 bg-danger/5' : 'border-line'}`}>
                            <div className="flex items-center justify-between gap-4">
                                <div>
                                    <h4 className="font-semibold">Maintenance Mode</h4>
                                    <p className="text-sm text-muted">Show a maintenance page to visitors (admins can still sign in)</p>
                                </div>
                                <label className="inline-flex cursor-pointer items-center">
                                    <button
                                        type="button" role="switch" aria-label="Maintenance mode" aria-checked={data['system.maintenance_mode']}
                                        onClick={() => set('system.maintenance_mode', !data['system.maintenance_mode'])}
                                        className={`relative h-6 w-11 shrink-0 rounded-full transition ${data['system.maintenance_mode'] ? 'bg-danger' : 'bg-line'}`}
                                    >
                                        <span className={`absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition-all ${data['system.maintenance_mode'] ? 'left-[22px]' : 'left-0.5'}`} />
                                    </button>
                                </label>
                            </div>
                            <div className="mt-3">
                                <Field label="Maintenance Message">
                                    <Textarea rows={2} value={data['system.maintenance_message'] ?? ''} onChange={(e) => set('system.maintenance_message', e.target.value)} />
                                </Field>
                            </div>
                        </div>
                    </div>
                </SettingsCard>
                <SaveButton processing={processing} />
            </form>

            <SettingsCard title="Cache Management">
                <div className="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-line p-4">
                    <div>
                        <h3 className="font-semibold">Clear Application Cache</h3>
                        <p className="text-sm text-muted">Clear all cached data (config, views, settings)</p>
                    </div>
                    <Button onClick={() => router.post('/admin/settings/clear-cache', {}, { preserveScroll: true })}><RefreshCw size={14} />Clear Cache</Button>
                </div>
                <div className="mt-5 rounded-lg bg-info/10 p-4">
                    <h3 className="mb-2 flex items-center gap-2 font-semibold text-info"><Info size={16} />System Information</h3>
                    <dl className="grid gap-3 text-sm md:grid-cols-2">
                        {[['PHP Version', info.php], ['Laravel Version', info.laravel], ['Server', info.server], ['Environment', info.env]].map(([k, v]) => (
                            <div key={k}><dt className="inline text-muted">{k}: </dt><dd className="inline font-medium">{v}</dd></div>
                        ))}
                    </dl>
                </div>
            </SettingsCard>
        </SettingsLayout>
    );
}
