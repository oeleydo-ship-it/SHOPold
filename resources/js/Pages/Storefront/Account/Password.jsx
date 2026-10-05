import { useForm } from '@inertiajs/react';
import AccountLayout from '../../../Storefront/Account/AccountLayout';
import FormField, { SaveButton } from '../../../Storefront/Account/FormField';
import { useT } from '../../../Storefront/lib/store';

export default function Password({ seo }) {
    const t = useT();
    const form = useForm({ current_password: '', password: '', password_confirmation: '' });
    const submit = (e) => {
        e.preventDefault();
        form.put('/account/password', { preserveScroll: true, onSuccess: () => form.reset(), onError: () => form.reset('password', 'password_confirmation') });
    };

    return (
        <AccountLayout title={t('change_password', 'Change Password')} seo={seo}>
            <form onSubmit={submit} className="max-w-xl space-y-4 rounded-xl bg-white p-4 shadow-sm md:p-6">
                <FormField form={form} name="current_password" label={t('current_password', 'Current Password')} type="password" required autoComplete="current-password" />
                <FormField form={form} name="password" label={t('new_password', 'New Password')} type="password" required minLength={8} autoComplete="new-password" />
                <FormField form={form} name="password_confirmation" label={t('confirm_new_password', 'Confirm New Password')} type="password" required autoComplete="new-password" />
                <SaveButton form={form}>{t('update_password', 'Update Password')}</SaveButton>
            </form>
        </AccountLayout>
    );
}
