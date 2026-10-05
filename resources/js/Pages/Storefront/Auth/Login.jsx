import { useForm } from '@inertiajs/react';
import Seo from '../../../Storefront/Layout/Seo';
import AuthCard, { AuthField, SubmitButton } from '../../../Storefront/Auth/AuthCard';
import SfLink from '../../../Storefront/lib/SfLink';
import { useT } from '../../../Storefront/lib/store';
import { useThemed } from '../../../Storefront/theme/registry';

export default function Login({ allowRegistration, seo }) {
    const t = useT();
    const Card = useThemed('Auth.Card', AuthCard);
    const form = useForm({ email: '', password: '', remember: false });
    const submit = (e) => {
        e.preventDefault();
        form.post('/login', { onFinish: () => form.reset('password') });
    };

    return (
        <>
            <Seo title={seo.title} robots={seo.robots} />
            <Card title={t('welcome_back', 'Welcome Back')} subtitle={t('sign_in_to_account', 'Sign in to your account')}>
                <form onSubmit={submit} className="space-y-4">
                    <AuthField label={t('email_address', 'Email Address')} type="email" name="email" autoComplete="email" required autoFocus
                        value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} error={form.errors.email} />
                    <AuthField label={t('password', 'Password')} type="password" name="password" autoComplete="current-password" required
                        value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} error={form.errors.password} />
                    <label className="flex items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" checked={form.data.remember} onChange={(e) => form.setData('remember', e.target.checked)} className="rounded text-primary-600 focus:ring-primary-500" />
                        {t('remember_me', 'Remember me')}
                    </label>
                    <SubmitButton busy={form.processing}>{t('sign_in', 'Sign In')}</SubmitButton>
                </form>
                {allowRegistration && (
                    <p className="mt-6 text-center text-gray-600">
                        {t('no_account', "Don't have an account?")}{' '}
                        <SfLink href="/register" className="font-medium text-primary-600 hover:underline">{t('create_one', 'Create one')}</SfLink>
                    </p>
                )}
            </Card>
        </>
    );
}
