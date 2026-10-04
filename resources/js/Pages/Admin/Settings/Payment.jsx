import { useState } from 'react';
import { Head } from '@inertiajs/react';
import { Banknote, ChevronDown, CreditCard, ExternalLink, Landmark, ScrollText, TriangleAlert } from 'lucide-react';
import SettingsLayout from '../../../Layouts/SettingsLayout';
import SecretField from '../../../Components/SecretField';
import { Badge, Field, Input, Select, Textarea } from '../../../Components/ui';
import { Toggle } from '../../../Components/crud';
import { SaveButton, SettingsCard } from '../../../Components/settingsUi';
import { useSettings } from '../../../lib/settings';

const ICONS = { cod: Banknote, check: ScrollText, bank: Landmark };
const isSecret = (f) => f.type.startsWith('secret');
const ENABLED = 'payment.enabled_currencies';

function Gateway({ g, data, set, secrets, stored, setSecret, clearSecret }) {
    const [open, setOpen] = useState(false);
    const enabled = data[g.enabledKey];
    const Icon = ICONS[g.id] ?? CreditCard;
    const modeField = g.fields.find((f) => f.key.endsWith('_mode'));
    const live = enabled && modeField && data[modeField.key] === 'live';

    return (
        <div className="rounded-lg border border-line">
            <div className="flex items-center justify-between gap-4 p-4">
                <button type="button" onClick={() => setOpen((o) => !o)} aria-expanded={open} className="flex min-w-0 flex-1 items-center gap-3 text-left">
                    <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-soft/50 text-primary"><Icon size={18} /></span>
                    <span className="min-w-0">
                        <span className="flex items-center gap-2"><span className="font-semibold">{g.title}</span>{live && <Badge tone="danger">Live</Badge>}</span>
                        <span className="block text-sm text-muted">{g.description}</span>
                    </span>
                </button>
                <div className="flex shrink-0 items-center gap-3">
                    <button type="button" onClick={() => setOpen((o) => !o)} aria-label={`${open ? 'Hide' : 'Show'} ${g.title} settings`} className="text-muted hover:text-ink">
                        <ChevronDown size={18} className={`transition-transform ${open ? 'rotate-180' : ''}`} />
                    </button>
                    <Toggle checked={enabled} onChange={(v) => set(g.enabledKey, v)} label={<span className="sr-only">Enable {g.title}</span>} />
                </div>
            </div>

            {open && (
                <div className="space-y-4 border-t border-line bg-surface-2/50 p-4">
                    <div className="grid gap-4 md:grid-cols-2">
                        {g.fields.map((f) => {
                            const wide = f.type === 'textarea' || f.type === 'secret_multiline';
                            const cls = wide ? 'md:col-span-2' : '';
                            if (isSecret(f)) {
                                return (
                                    <div key={f.key} className={cls}>
                                        <SecretField
                                            label={f.label} multiline={f.type === 'secret_multiline'} placeholder={f.placeholder ?? undefined}
                                            stored={stored[f.key]} secret={secrets[f.key]}
                                            onChange={(v) => setSecret(f.key, v)} onClear={(c) => clearSecret(f.key, c)}
                                        />
                                    </div>
                                );
                            }
                            return (
                                <Field key={f.key} label={f.label} className={cls}>
                                    {f.type === 'select' ? (
                                        <Select value={data[f.key]} onChange={(e) => set(f.key, e.target.value)}>
                                            {Object.entries(f.options).map(([v, l]) => <option key={v} value={v}>{l}</option>)}
                                        </Select>
                                    ) : f.type === 'textarea' ? (
                                        <Textarea rows={2} value={data[f.key] ?? ''} onChange={(e) => set(f.key, e.target.value)} />
                                    ) : (
                                        <Input type={f.type === 'number' ? 'number' : 'text'} {...(f.type === 'number' ? { step: '0.01', min: '0' } : {})}
                                            placeholder={f.placeholder ?? undefined} value={data[f.key] ?? ''} onChange={(e) => set(f.key, e.target.value)} />
                                    )}
                                </Field>
                            );
                        })}
                    </div>
                    {g.docs && (
                        <p className="flex items-center gap-1 text-sm text-muted">
                            Get your credentials from
                            <a href={g.docs[1]} target="_blank" rel="noreferrer" className="inline-flex items-center gap-1 text-primary hover:underline">{g.docs[0]}<ExternalLink size={12} /></a>
                        </p>
                    )}
                </div>
            )}
        </div>
    );
}

export default function Payment({ values, secrets: stored, gateways, enabledKeys, currencies, enabledCurrencies }) {
    const secretKeys = gateways.flatMap((g) => g.fields.filter(isSecret).map((f) => f.key));
    const { data, set, secrets, setSecret, clearSecret, submit, processing } = useSettings(
        'payment', { ...values, [ENABLED]: enabledCurrencies }, enabledKeys, secretKeys,
    );
    const f = (k) => ({ value: data[k] ?? '', onChange: (e) => set(k, e.target.value) });
    const base = data['payment.currency'];
    const chosen = data[ENABLED];
    const liveMode = data['payment.mode'] === 'live';

    const changeCurrency = (code) => {
        set('payment.currency', code);
        set('payment.currency_symbol', currencies[code]?.symbol ?? code);
        if (!chosen.includes(code)) set(ENABLED, [...chosen, code]); // the base currency is always enabled
    };
    const toggleCurrency = (code) => {
        if (code === base) return;
        set(ENABLED, chosen.includes(code) ? chosen.filter((c) => c !== code) : [...chosen, code]);
    };

    return (
        <SettingsLayout>
            <Head title="Payment Settings" />
            <form onSubmit={(e) => { e.preventDefault(); submit(); }} className="space-y-5">
                <SettingsCard title="General Payment Settings">
                    <div className="grid gap-4 md:grid-cols-2">
                        <Field label="Default Currency">
                            <Select value={base} onChange={(e) => changeCurrency(e.target.value)}>
                                {Object.entries(currencies).map(([code, c]) => <option key={code} value={code}>{code} - {c.name} ({c.symbol})</option>)}
                            </Select>
                        </Field>
                        <Field label="Currency Symbol"><Input {...f('payment.currency_symbol')} /></Field>
                        <fieldset className="md:col-span-2">
                            <legend className="mb-1 text-sm font-medium">Enabled Currencies (Multi-Currency)</legend>
                            <div className="grid max-h-48 grid-cols-1 gap-x-4 overflow-y-auto rounded-lg border border-line bg-surface-2 p-3 sm:grid-cols-2 lg:grid-cols-3">
                                {Object.entries(currencies).map(([code, c]) => (
                                    <label key={code} className="flex items-center gap-2 rounded px-2 py-1 text-sm hover:bg-surface">
                                        <input type="checkbox" checked={chosen.includes(code)} disabled={code === base} onChange={() => toggleCurrency(code)} className="h-4 w-4 accent-[var(--primary)]" />
                                        <span className="truncate">{code} - {c.name}</span>{code === base && <span className="text-xs text-muted">(default)</span>}
                                    </label>
                                ))}
                            </div>
                            <p className="mt-1 text-xs text-muted">Only used when Multi-Currency is enabled in Feature Settings.</p>
                        </fieldset>
                        <Field label="Currency Position">
                            <Select {...f('payment.currency_position')}>
                                <option value="before">Before amount ($100)</option>
                                <option value="after">After amount (100$)</option>
                            </Select>
                        </Field>
                        <Field label="Payment Mode">
                            <Select {...f('payment.mode')}>
                                <option value="sandbox">Sandbox / Test Mode</option>
                                <option value="live">Live / Production Mode</option>
                            </Select>
                        </Field>
                    </div>
                    {liveMode && (
                        <p className="mt-4 flex items-start gap-2 rounded-lg border border-danger/40 bg-danger/10 p-3 text-sm text-danger">
                            <TriangleAlert size={16} className="mt-0.5 shrink-0" />Live mode is on — customers will be charged real money.
                        </p>
                    )}

                    <h3 className="mb-3 mt-8 font-bold">Tax Settings</h3>
                    <div className="grid gap-4 md:grid-cols-2">
                        <Field label="Tax Rate (%)"><Input type="number" step="0.01" min="0" {...f('payment.tax_rate')} /></Field>
                        <Field label="Tax Label"><Input {...f('payment.tax_label')} /></Field>
                    </div>
                </SettingsCard>

                <SettingsCard title="Payment Gateways" description="Switch a gateway on to offer it at checkout. Saved keys are never shown — leave a key field blank to keep it, or type to replace it.">
                    <div className="space-y-3">
                        {gateways.map((g) => (
                            <Gateway key={g.id} g={g} data={data} set={set} secrets={secrets} stored={stored} setSecret={setSecret} clearSecret={clearSecret} />
                        ))}
                    </div>
                </SettingsCard>

                <SaveButton processing={processing} />
            </form>
        </SettingsLayout>
    );
}
