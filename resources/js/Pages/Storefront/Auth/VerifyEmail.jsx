import { router } from '@inertiajs/react';
import { useState } from 'react';
import { Mail } from 'lucide-react';
import Seo from '../../../Storefront/Layout/Seo';
import AuthCard from '../../../Storefront/Auth/AuthCard';
import { logout } from '../../../Storefront/Header/parts';
import { useT } from '../../../Storefront/lib/store';
import { useThemed } from '../../../Storefront/theme/registry';

export default function VerifyEmail({ email, seo }) {
    const t = useT();
    const Card = useThemed('Auth.Card', AuthCard);
    const [busy, setBusy] = useState(false);
    const resend = () => router.post('/email/verification-notification', {}, { preserveScroll: true, onStart: () => setBusy(true), onFinish: () => setBusy(false) });

    return (
        <>
            <Seo title={seo.title} robots={seo.robots} />
            <Card icon={<Mail size={26} />} title={t('verify_your_email', 'Verify Your Email')}
                subtitle={<>{t('verification_sent_to', 'We sent a verification link to')} <strong>{email}</strong></>}>
                <p className="mb-6 text-sm text-gray-600">{t('verify_email_text', "Click the link in the email to activate your account. If you didn't receive it, we can send another.")}</p>
                <button type="button" onClick={resend} disabled={busy}
                    className="sf-btn w-full rounded-lg bg-primary-600 py-3 font-semibold text-white transition hover:bg-primary-700 disabled:opacity-60">
                    {busy ? t('sending', 'Sending...') : t('resend_verification', 'Resend Verification Email')}
                </button>
                <p className="mt-4 text-center">
                    <button type="button" onClick={logout} className="text-sm text-gray-500 hover:text-gray-700">{t('logout', 'Logout')}</button>
                </p>
            </Card>
        </>
    );
}
