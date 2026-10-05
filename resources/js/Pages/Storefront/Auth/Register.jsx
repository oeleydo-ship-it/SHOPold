import { useForm } from '@inertiajs/react';
import Seo from '../../../Storefront/Layout/Seo';
import AuthCard, { AuthField, SubmitButton } from '../../../Storefront/Auth/AuthCard';
import SfLink from '../../../Storefront/lib/SfLink';
import { useT } from '../../../Storefront/lib/store';
import { useThemed } from '../../../Storefront/theme/registry';

export default function Register({ seo }) {
    const t = useT();
    const Card = useThemed('Auth.Card', AuthCard);
    const form = useForm({ name: '', email: '', password: '', password_confirmation: '' });
    const bind = (name) => ({ name, value: form.data[name], onChange: (e) => form.setData(name, e.target.value), error: form.errors[name] });
    const submit = (e) => {
        e.preventDefault();
        form.post('/register', { onFinish: () => form.reset('password', 'password_confirmation') });
    };

    return (
        <>
            <Seo title={seo.title} robots={seo.robots} />
            <Card title={t('create_account_title', 'Create Account')} subtitle={t('join_us', 'Join us and start shopping')}>
                <form onSubmit={submit} className="space-y-4">
                    <AuthField label={t('full_name', 'Full Name')} type="text" autoComplete="name" required autoFocus {...bind('name')} />
                    <AuthField label={t('email_address', 'Email Address')} type="email" autoComplete="email" required {...bind('email')} />
                    <AuthField label={t('password', 'Password')} type="password" autoComplete="new-password" required minLength={8} {...bind('password')} />
                    <AuthField label={t('confirm_password', 'Confirm Password')} type="password" autoComplete="new-password" required {...bind('password_confirmation')} />
                    <SubmitButton busy={form.processing}>{t('create_account_button', 'Create Account')}</SubmitButton>
                </form>
                <p className="mt-6 text-center text-gray-600">
                    {t('have_account', 'Already have an account?')}{' '}
                    <SfLink href="/login" className="font-medium text-primary-600 hover:underline">{t('sign_in', 'Sign In')}</SfLink>
                </p>
            </Card>
        </>
    );
}
