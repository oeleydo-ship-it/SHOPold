import { useState } from 'react';
import { Head } from '@inertiajs/react';
import { ExternalLink, Info, Loader2, Send, Terminal } from 'lucide-react';
import SettingsLayout from '../../../Layouts/SettingsLayout';
import SecretField from '../../../Components/SecretField';
import { Badge, Button, Field, Input, Select } from '../../../Components/ui';
import { SaveButton, SettingsCard, SwitchRow } from '../../../Components/settingsUi';
import { useSettings } from '../../../lib/settings';
import { jsonRequest } from '../../../lib/http';
import QueueStatus from '../../../Components/QueueStatus';

const NOTIFICATIONS = [
    ['email.order_confirmation', 'Order Confirmation', 'Send confirmation email when order is placed'],
    ['email.order_shipped', 'Order Shipped', 'Notify customer when order is shipped'],
    ['email.order_delivered', 'Order Delivered', 'Notify customer when order is delivered'],
    ['email.order_status_updates', 'Other Status Updates', 'Notify customer about other status changes (processing, ready for pickup, cancelled...)'],
    ['email.admin_new_order', 'Admin: New Order Alert', 'Notify admin when new order is placed'],
    ['email.welcome_email', 'Welcome Email', 'Send welcome email to new customers'],
    ['email.password_reset', 'Password Reset', 'Send password reset emails'],
    ['email.vendor_new_order', 'Vendor: New Order', 'Email each vendor their items when an order includes their products'],
    ['email.vendor_status', 'Vendor: Store Approved / Rejected', 'Email vendors when their store is approved, rejected or suspended'],
    ['email.vendor_payout', 'Vendor: Payout Sent / Failed', 'Email vendors when a payout is completed or fails'],
    ['email.driver_assigned', 'Driver: New Delivery', 'Email drivers when a delivery is assigned to them'],
];
const BOOLEANS = [...NOTIFICATIONS.map(([k]) => k), 'email.queue_enabled'];
const SECRETS = ['email.smtp_password', 'email.resend_api_key'];

const PRESETS = {
    gmail: ['Gmail', { host: 'smtp.gmail.com', port: '587', encryption: 'tls' }],
    outlook: ['Outlook', { host: 'smtp.office365.com', port: '587', encryption: 'tls' }],
    mailgun: ['Mailgun', { host: 'smtp.mailgun.org', port: '587', encryption: 'tls' }],
    sendgrid: ['SendGrid', { host: 'smtp.sendgrid.net', port: '587', encryption: 'tls' }],
    resend: ['Resend', { host: 'smtp.resend.com', port: '465', encryption: 'ssl' }],
};

function TestEmail() {
    const [email, setEmail] = useState('');
    const [state, setState] = useState({ busy: false, ok: null, text: '' });

    const send = async () => {
        if (!email) return setState({ busy: false, ok: false, text: 'Please enter an email address' });
        setState({ busy: true, ok: null, text: 'Sending test email… This may take up to 30 seconds.' });
        const controller = new AbortController();
        const timer = setTimeout(() => controller.abort(), 30000);
        try {
            const data = await jsonRequest('/admin/settings/test-email', { method: 'POST', body: { email }, signal: controller.signal });
            setState({ busy: false, ok: !!data.success, text: data.message });
        } catch (e) {
            setState({
                busy: false, ok: false,
                text: e.name === 'AbortError' ? 'Request timed out. Please check your SMTP settings (host, port, encryption) and try again.' : (e.message || 'Failed to send test email. Please check your settings.'),
            });
        } finally {
            clearTimeout(timer);
        }
    };

    return (
        <SettingsCard title="Test Email Configuration" description="Save your settings first, then send a test email to verify they work.">
            <div className="flex flex-col gap-3 sm:flex-row">
                <Input type="email" value={email} onChange={(e) => setEmail(e.target.value)} placeholder="Enter test email address" aria-label="Test email address" />
                <Button onClick={send} disabled={state.busy}>{state.busy ? <Loader2 size={16} className="animate-spin" /> : <Send size={16} />}Send Test</Button>
            </div>
            {state.text && (
                <p role="status" className={`mt-3 text-sm ${state.ok === true ? 'text-success' : state.ok === false ? 'text-danger' : 'text-muted'}`}>{state.text}</p>
            )}
        </SettingsCard>
    );
}

export default function Email({ values, secrets: stored, queue }) {
    const { data, set, secrets, setSecret, clearSecret, submit, processing } = useSettings('email', values, BOOLEANS, SECRETS);
    const f = (k) => ({ value: data[k] ?? '', onChange: (e) => set(k, e.target.value) });
    const resend = data['email.driver'] === 'resend';

    const applyPreset = (id) => {
        const [, p] = PRESETS[id];
        set('email.smtp_host', p.host);
        set('email.smtp_port', p.port);
        set('email.smtp_encryption', p.encryption);
    };

    return (
        <SettingsLayout>
            <Head title="Email Settings" />
            <form onSubmit={(e) => { e.preventDefault(); submit(); }} className="space-y-5">
                <SettingsCard
                    title="Mail Delivery"
                    description="Mail settings are stored in the database — no need to edit the .env file."
                >
                    <div className="max-w-xs">
                        <Field label="Mail Driver">
                            <Select {...f('email.driver')}>
                                <option value="smtp">SMTP</option>
                                <option value="resend">Resend</option>
                                <option value="sendmail">Sendmail</option>
                                <option value="mailgun">Mailgun</option>
                                <option value="ses">Amazon SES</option>
                                <option value="postmark">Postmark</option>
                            </Select>
                        </Field>
                    </div>

                    {!resend && (
                        <>
                            <div className="mt-6 grid gap-4 md:grid-cols-2">
                                <Field label="SMTP Host"><Input placeholder="smtp.gmail.com" {...f('email.smtp_host')} /></Field>
                                <Field label="SMTP Port">
                                    <Select value={String(data['email.smtp_port'] || '587')} onChange={(e) => set('email.smtp_port', e.target.value)}>
                                        <option value="25">25 (Standard)</option>
                                        <option value="465">465 (SSL)</option>
                                        <option value="587">587 (TLS)</option>
                                        <option value="2525">2525 (Alternative)</option>
                                    </Select>
                                </Field>
                                <Field label="Encryption">
                                    <Select {...f('email.smtp_encryption')}>
                                        <option value="tls">TLS</option>
                                        <option value="ssl">SSL</option>
                                        <option value="">None</option>
                                    </Select>
                                </Field>
                                <Field label="SMTP Username"><Input placeholder="your@email.com" autoComplete="off" {...f('email.smtp_username')} /></Field>
                                <SecretField
                                    label="SMTP Password" stored={stored['email.smtp_password']} secret={secrets['email.smtp_password']}
                                    onChange={(v) => setSecret('email.smtp_password', v)} onClear={(c) => clearSecret('email.smtp_password', c)}
                                    hint="Encrypted and stored securely"
                                />
                            </div>
                            <div className="mt-6 rounded-lg bg-surface-2 p-4">
                                <h4 className="mb-3 text-sm font-medium">Quick Setup Presets</h4>
                                <div className="flex flex-wrap gap-2">
                                    {Object.entries(PRESETS).map(([id, [label]]) => <Button key={id} tone="neutral" onClick={() => applyPreset(id)}>{label}</Button>)}
                                </div>
                            </div>
                        </>
                    )}
                </SettingsCard>

                {resend && (
                    <SettingsCard title="Resend Configuration">
                        <div className="mb-4 flex justify-end"><a href="https://resend.com/api-keys" target="_blank" rel="noreferrer" className="inline-flex items-center gap-1 text-sm text-primary hover:underline"><ExternalLink size={14} />Get API Key</a></div>
                        <p className="mb-4 flex items-start gap-2 rounded-lg border border-info/40 bg-info/10 p-3 text-sm text-info"><Info size={16} className="mt-0.5 shrink-0" />Resend is a modern email API for developers. Simple, reliable, and scalable.</p>
                        <SecretField
                            label="Resend API Key" stored={stored['email.resend_api_key']} secret={secrets['email.resend_api_key']}
                            onChange={(v) => setSecret('email.resend_api_key', v)} onClear={(c) => clearSecret('email.resend_api_key', c)}
                            placeholder="re_xxxxxxxxxx" hint='Your Resend API key starting with "re_"'
                        />
                        <div className="mt-4 rounded-lg border border-warning/40 bg-warning/10 p-4">
                            <h4 className="mb-2 flex items-center gap-2 font-medium text-warning"><Terminal size={16} />Package Installation Required</h4>
                            <p className="mb-2 text-sm text-muted">To use the Resend API, install the package via Composer:</p>
                            <code className="block rounded bg-black/80 p-2 text-sm text-green-400">composer require resend/resend-laravel</code>
                            <p className="mt-2 text-xs text-muted">Alternatively, choose the SMTP driver and use the Resend preset — no package needed.</p>
                        </div>
                    </SettingsCard>
                )}

                <SettingsCard title="Sender Information">
                    <div className="grid gap-4 md:grid-cols-2">
                        <Field label="From Name"><Input placeholder="Your Store Name" {...f('email.from_name')} /></Field>
                        <Field label="From Email Address"><Input type="email" placeholder="noreply@yourstore.com" {...f('email.from_address')} /></Field>
                        <Field label="Reply-To Email"><Input type="email" placeholder="support@yourstore.com" {...f('email.reply_to')} /></Field>
                        <Field label="Admin Email (for notifications)"><Input type="email" placeholder="admin@yourstore.com" {...f('email.admin_email')} /></Field>
                    </div>
                </SettingsCard>

                <SettingsCard title="Background Sending" description="Send emails from a background worker so checkout never waits for the mail server, and failed emails are retried.">
                    <div className="space-y-3">
                        <SwitchRow title="Send emails in the background" description="Needs the scheduler running every minute (scriptsun-scheduler.bat, see the README). Leave off until it is set up."
                            checked={data['email.queue_enabled']} onChange={(v) => set('email.queue_enabled', v)} />
                        <QueueStatus queue={queue} showLink />
                    </div>
                </SettingsCard>

                <SettingsCard title="Email Notifications">
                    <div className="space-y-3">
                        {NOTIFICATIONS.map(([key, title, desc]) => (
                            <SwitchRow key={key} title={title} description={desc} checked={data[key]} onChange={(v) => set(key, v)} />
                        ))}
                    </div>
                </SettingsCard>

                <SaveButton processing={processing} />
            </form>

            <TestEmail />
        </SettingsLayout>
    );
}
