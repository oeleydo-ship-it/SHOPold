import { Head, Link } from '@inertiajs/react';
import { CheckCircle2, TriangleAlert } from 'lucide-react';
import SettingsLayout from '../../../Layouts/SettingsLayout';
import { Field, Select } from '../../../Components/ui';
import { Toggle } from '../../../Components/crud';
import { SaveButton, SettingsCard } from '../../../Components/settingsUi';
import { useSettings } from '../../../lib/settings';

const BOOLEANS = ['store.show_language_switcher', 'store.auto_translate'];
const LIST = 'store.available_languages_list';

export default function Localization({ values, languages, available, ai }) {
    const { data, set, submit, processing } = useSettings('store', { ...values, [LIST]: available }, BOOLEANS);
    const selected = data[LIST];
    const def = data['store.default_language'];

    const toggle = (code) => {
        // The default language must always stay available.
        if (code === def) return;
        set(LIST, selected.includes(code) ? selected.filter((c) => c !== code) : [...selected, code]);
    };

    const changeDefault = (code) => {
        set('store.default_language', code);
        if (!selected.includes(code)) set(LIST, [...selected, code]);
    };

    return (
        <SettingsLayout>
            <Head title="Localization Settings" />
            <form onSubmit={(e) => { e.preventDefault(); submit(); }} className="space-y-5">
                <SettingsCard title="Language & Localization" description="Configure languages available for your store and customers.">
                    <div className="grid gap-6 md:grid-cols-2">
                        <Field label="Default Language" hint="The default language for your store">
                            <Select value={def} onChange={(e) => changeDefault(e.target.value)}>
                                {Object.entries(languages).map(([code, name]) => <option key={code} value={code}>{name}</option>)}
                            </Select>
                        </Field>
                        <fieldset>
                            <legend className="mb-1 text-sm font-medium">Available Languages</legend>
                            <div className="max-h-64 overflow-y-auto rounded-lg border border-line bg-surface-2 p-3">
                                {Object.entries(languages).map(([code, name]) => (
                                    <label key={code} className="flex items-center gap-2 rounded px-2 py-1.5 text-sm hover:bg-surface">
                                        <input
                                            type="checkbox"
                                            checked={selected.includes(code)}
                                            disabled={code === def}
                                            onChange={() => toggle(code)}
                                            className="h-4 w-4 accent-[var(--primary)]"
                                        />
                                        {name}{code === def && <span className="text-xs text-muted">(default)</span>}
                                    </label>
                                ))}
                            </div>
                            <p className="mt-1 text-xs text-muted">Select languages available for customers</p>
                        </fieldset>
                    </div>

                    <div className="mt-6">
                        <Toggle label="Show language switcher in header" checked={data['store.show_language_switcher']} onChange={(v) => set('store.show_language_switcher', v)} />
                        <p className="ml-14 mt-1 text-xs text-muted">Allow customers to switch languages from the store header.</p>
                    </div>

                    <div className="mt-6 rounded-lg border border-info/40 bg-info/10 p-4">
                        <h4 className="mb-1 font-medium text-info">AI Auto-Translation</h4>
                        <p className="mb-3 text-sm text-muted">When enabled, content is automatically translated using AI when customers switch languages.</p>
                        <Toggle label="Enable AI auto-translation" checked={data['store.auto_translate']} onChange={(v) => set('store.auto_translate', v)} />
                        {ai.configured ? (
                            <p className="mt-3 flex items-center gap-1 text-xs text-success"><CheckCircle2 size={14} />AI API key configured ({ai.provider})</p>
                        ) : (
                            <p className="mt-3 flex items-center gap-1 text-xs text-warning">
                                <TriangleAlert size={14} />Requires an AI API key. Configure it in <a href="/admin/settings/ai" className="underline">AI Settings</a>.
                            </p>
                        )}
                    </div>
                </SettingsCard>
                <SaveButton processing={processing} />
            </form>
        </SettingsLayout>
    );
}
