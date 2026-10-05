import { useForm } from '@inertiajs/react';
import AccountLayout from '../../../Storefront/Account/AccountLayout';
import FormField, { SaveButton } from '../../../Storefront/Account/FormField';
import { useT } from '../../../Storefront/lib/store';

export default function Profile({ profile, seo }) {
    const t = useT();
    const form = useForm(profile);
    const submit = (e) => {
        e.preventDefault();
        form.put('/account/profile', { preserveScroll: true });
    };

    return (
        <AccountLayout title={t('profile', 'Profile')} seo={seo}>
            <form onSubmit={submit} className="rounded-xl bg-white p-4 shadow-sm md:p-6">
                <h2 className="mb-4 text-lg font-bold">{t('personal_information', 'Personal Information')}</h2>
                <div className="grid gap-4 md:grid-cols-2">
                    <FormField form={form} name="name" label={`${t('full_name', 'Full Name')} *`} required autoComplete="name" />
                    <FormField form={form} name="email" label={`${t('email', 'Email')} *`} type="email" required autoComplete="email" />
                    <FormField form={form} name="phone" label={t('phone', 'Phone')} type="tel" autoComplete="tel" />
                    <FormField form={form} name="date_of_birth" label={t('date_of_birth', 'Date of Birth')} type="date" autoComplete="bday" />
                    <FormField form={form} name="gender" label={t('gender', 'Gender')} as="select">
                        <option value="">{t('prefer_not_say', 'Prefer not to say')}</option>
                        <option value="male">{t('male', 'Male')}</option>
                        <option value="female">{t('female', 'Female')}</option>
                        <option value="other">{t('other', 'Other')}</option>
                    </FormField>
                    <FormField form={form} name="company" label={t('company', 'Company')} autoComplete="organization" />
                </div>
                <h2 className="mb-4 mt-8 text-lg font-bold">{t('address', 'Address')}</h2>
                <div className="grid gap-4 md:grid-cols-2">
                    <FormField form={form} name="address" label={t('street_address', 'Street Address')} as="textarea" rows={2} className="md:col-span-2" autoComplete="street-address" />
                    <FormField form={form} name="city" label={t('city', 'City')} autoComplete="address-level2" />
                    <FormField form={form} name="state" label={t('state', 'State')} autoComplete="address-level1" />
                    <FormField form={form} name="zip_code" label={t('zip_code', 'ZIP Code')} autoComplete="postal-code" />
                    <FormField form={form} name="country" label={t('country', 'Country')} autoComplete="country-name" />
                </div>
                <div className="mt-6"><SaveButton form={form}>{t('save_changes', 'Save Changes')}</SaveButton></div>
            </form>
        </AccountLayout>
    );
}
