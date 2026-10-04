import { useState } from 'react';
import { Head } from '@inertiajs/react';
import { Bot, Box, Lightbulb, Loader2, MessageSquare, Plug, Search, Star } from 'lucide-react';
import SettingsLayout from '../../../Layouts/SettingsLayout';
import SecretField from '../../../Components/SecretField';
import { Button, Field, Input, Select, Textarea } from '../../../Components/ui';
import { Toggle } from '../../../Components/crud';
import { SaveButton, SettingsCard } from '../../../Components/settingsUi';
import { useSettings } from '../../../lib/settings';
import { jsonRequest } from '../../../lib/http';

const FEATURES = [
    ['ai.product_generation', 'Product Content Generation', 'Generate product titles, descriptions, and SEO content', Box, 'var(--info)'],
    ['ai.chat_enabled', 'AI Chat Assistant', 'AI-powered customer support chatbot', MessageSquare, 'var(--success)'],
    ['ai.smart_search', 'Smart Search', 'AI-enhanced product search with natural language', Search, 'var(--warning)'],
    ['ai.recommendations', 'AI Recommendations', 'Personalized product recommendations', Lightbulb, 'var(--primary)'],
    ['ai.review_analysis', 'Review Analysis', 'Analyze customer reviews for sentiment and insights', Star, 'var(--danger)'],
];
const BOOLEANS = FEATURES.map(([k]) => k);

const MODELS = {
    OpenAI: [['gpt-4o-mini', 'GPT-4o Mini (Fast & Affordable)'], ['gpt-4o', 'GPT-4o (Most Capable)'], ['gpt-4-turbo', 'GPT-4 Turbo'], ['gpt-3.5-turbo', 'GPT-3.5 Turbo (Budget)']],
    Anthropic: [['claude-3-opus', 'Claude 3 Opus (Most Capable)'], ['claude-3-sonnet', 'Claude 3 Sonnet (Balanced)'], ['claude-3-haiku', 'Claude 3 Haiku (Fast)']],
    Google: [['gemini-pro', 'Gemini Pro'], ['gemini-pro-vision', 'Gemini Pro Vision']],
};

function TestConnection({ unsavedKey }) {
    const [state, setState] = useState({ busy: false, ok: null, text: '' });
    const run = async () => {
        setState({ busy: true, ok: null, text: 'Testing connection…' });
        try {
            const d = await jsonRequest('/admin/ai/test', { method: 'POST' });
            setState({ busy: false, ok: !!d.success, text: d.success ? d.message : d.error });
        } catch (e) {
            setState({ busy: false, ok: false, text: `Connection failed: ${e.message}` });
        }
    };
    return (
        <div className="mt-6 rounded-lg bg-surface-2 p-4">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h4 className="font-semibold">Test API Connection</h4>
                    <p className="text-sm text-muted">Verify your API key is working correctly. Tests the saved settings{unsavedKey ? ' — save your new key first.' : '.'}</p>
                </div>
                <Button onClick={run} disabled={state.busy}>{state.busy ? <Loader2 size={16} className="animate-spin" /> : <Plug size={16} />}Test Connection</Button>
            </div>
            {state.text && <p role="status" className={`mt-3 text-sm ${state.ok === true ? 'text-success' : state.ok === false ? 'text-danger' : 'text-muted'}`}>{state.text}</p>}
        </div>
    );
}

export default function Ai({ values, secrets: stored, usage }) {
    const { data, set, secrets, setSecret, clearSecret, submit, processing } = useSettings('ai', values, BOOLEANS, ['ai.api_key']);
    const f = (k) => ({ value: data[k] ?? '', onChange: (e) => set(k, e.target.value) });

    return (
        <SettingsLayout>
            <Head title="AI Settings" />

            <div className="flex items-center gap-4 rounded-card bg-primary p-5 text-on-primary md:p-6">
                <span className="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-white/20"><Bot size={30} /></span>
                <div><h2 className="text-xl font-bold">AI Integration</h2><p className="text-sm opacity-90">Configure AI-powered features for your store</p></div>
            </div>

            <form onSubmit={(e) => { e.preventDefault(); submit(); }} className="space-y-5">
                <SettingsCard title="AI Provider Configuration">
                    <div className="grid gap-4 md:grid-cols-2">
                        <Field label="AI Provider">
                            <Select {...f('ai.provider')}>
                                <option value="openai">OpenAI (GPT-4, GPT-3.5)</option>
                                <option value="anthropic">Anthropic (Claude)</option>
                                <option value="google">Google (Gemini)</option>
                            </Select>
                        </Field>
                        <SecretField
                            label="API Key" stored={stored['ai.api_key']} secret={secrets['ai.api_key']}
                            onChange={(v) => setSecret('ai.api_key', v)} onClear={(c) => clearSecret('ai.api_key', c)}
                            placeholder="sk-… or your API key"
                        />
                        <Field label="Model">
                            <Select {...f('ai.model')}>
                                {Object.entries(MODELS).map(([group, items]) => (
                                    <optgroup key={group} label={group}>{items.map(([v, l]) => <option key={v} value={v}>{l}</option>)}</optgroup>
                                ))}
                            </Select>
                        </Field>
                        <Field label="Temperature" hint="0 = Focused, 1 = Creative, 2 = Random">
                            <Input type="number" min="0" max="2" step="0.1" {...f('ai.temperature')} />
                        </Field>
                    </div>
                    <TestConnection unsavedKey={secrets['ai.api_key'].value !== ''} />
                </SettingsCard>

                <SettingsCard title="AI Features">
                    <div className="space-y-3">
                        {FEATURES.map(([key, title, desc, Icon, tone]) => (
                            <div key={key} className="flex items-center justify-between gap-4 rounded-lg border border-line p-4 hover:bg-surface-2">
                                <div className="flex items-center gap-3">
                                    <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg" style={{ color: tone, background: `color-mix(in srgb, ${tone} 15%, transparent)` }}><Icon size={18} /></span>
                                    <div><h3 className="font-semibold">{title}</h3><p className="text-sm text-muted">{desc}</p></div>
                                </div>
                                <Toggle checked={data[key]} onChange={(v) => set(key, v)} label={<span className="sr-only">{title}</span>} />
                            </div>
                        ))}
                    </div>
                </SettingsCard>

                <SettingsCard title="AI Chat Configuration">
                    <div className="space-y-4">
                        <Field label="Chat Bot Name"><Input {...f('ai.chat_name')} /></Field>
                        <Field label="Welcome Message"><Textarea rows={2} {...f('ai.chat_welcome')} /></Field>
                        <Field label="System Prompt (Instructions for AI)" hint="This tells the AI how to behave and what information it has access to">
                            <Textarea rows={4} {...f('ai.chat_system_prompt')} />
                        </Field>
                    </div>
                </SettingsCard>

                <SettingsCard title="Usage & Limits">
                    <div className="grid gap-4 md:grid-cols-2">
                        <Field label="Monthly Token Limit" hint="0 = Unlimited. Helps control costs."><Input type="number" min="0" {...f('ai.monthly_limit')} /></Field>
                        <Field label="Max Tokens per Request"><Input type="number" min="100" max="4000" {...f('ai.max_tokens')} /></Field>
                    </div>
                    <div className="mt-6 rounded-lg bg-surface-2 p-4">
                        <h4 className="mb-3 font-semibold">Current Month Usage</h4>
                        <div className="grid grid-cols-3 gap-3 text-center">
                            {[['Tokens Used', usage.tokens.toLocaleString()], ['API Requests', usage.requests.toLocaleString()], ['Est. Cost', `$${usage.cost}`]].map(([k, v]) => (
                                <div key={k}><p className="text-xl font-bold">{v}</p><p className="text-xs text-muted">{k}</p></div>
                            ))}
                        </div>
                        {usage.limit > 0 && (
                            <div className="mt-4">
                                <div className="mb-1 flex justify-between text-xs text-muted"><span>{usage.tokens.toLocaleString()} / {usage.limit.toLocaleString()} tokens</span><span>{usage.percent}%</span></div>
                                <div className="h-2 overflow-hidden rounded-full bg-line" role="progressbar" aria-valuenow={usage.percent} aria-valuemin={0} aria-valuemax={100}>
                                    <div className={`h-2 rounded-full ${usage.percent >= 90 ? 'bg-danger' : 'bg-primary'}`} style={{ width: `${usage.percent}%` }} />
                                </div>
                            </div>
                        )}
                    </div>
                </SettingsCard>

                <SaveButton processing={processing}>Save AI Settings</SaveButton>
            </form>
        </SettingsLayout>
    );
}
