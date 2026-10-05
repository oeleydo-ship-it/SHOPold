import { useForm } from '@inertiajs/react';
import AccountLayout from '../../../Storefront/Account/AccountLayout';
import FormField, { SaveButton } from '../../../Storefront/Account/FormField';
import SfLink from '../../../Storefront/lib/SfLink';
import { useT } from '../../../Storefront/lib/store';

export default function Create({ categories, orders, orderId, seo }) {
    const t = useT();
    const form = useForm({ subject: '', category: categories[0]?.value ?? 'other', priority: 'medium', order_id: orderId ?? '', description: '' });
    const submit = (e) => {
        e.preventDefault();
        form.post('/helpdesk');
    };

    return (
        <AccountLayout title={t('new_support_ticket', 'New Support Ticket')} seo={seo}
            actions={<SfLink href="/helpdesk" className="rounded-lg border border-gray-300 px-4 py-2 text-gray-700 hover:bg-gray-50">{t('back_to_tickets', 'Back to Tickets')}</SfLink>}>
            <form onSubmit={submit} className="space-y-6 rounded-xl bg-white p-4 shadow-sm md:p-6">
                <FormField form={form} name="subject" label={`${t('subject', 'Subject')} *`} required maxLength={255} placeholder={t('subject_placeholder', 'Brief summary of your issue')} />
                <div className="grid gap-4 md:grid-cols-2">
                    <FormField form={form} name="category" label={`${t('category', 'Category')} *`} as="select" required>
                        {categories.map((c) => <option key={c.value} value={c.value}>{c.label}</option>)}
                    </FormField>
                    <FormField form={form} name="priority" label={`${t('priority', 'Priority')} *`} as="select" required>
                        <option value="low">{t('low', 'Low')}</option>
                        <option value="medium">{t('medium', 'Medium')}</option>
                        <option value="high">{t('high', 'High')}</option>
                        <option value="urgent">{t('urgent', 'Urgent')}</option>
                    </FormField>
                </div>
                <FormField form={form} name="order_id" label={t('related_order_optional', 'Related Order (optional)')} as="select">
                    <option value="">{t('select_order', '-- Select an order --')}</option>
                    {orders.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
                </FormField>
                <FormField form={form} name="description" label={`${t('description', 'Description')} *`} as="textarea" rows={6} required maxLength={5000}
                    placeholder={t('describe_issue', 'Please describe your issue in detail...')} />
                <div className="flex justify-end gap-3">
                    <SfLink href="/helpdesk" className="rounded-lg border border-gray-300 px-6 py-2 text-gray-700 hover:bg-gray-50">{t('cancel', 'Cancel')}</SfLink>
                    <SaveButton form={form}>{t('submit_ticket', 'Submit Ticket')}</SaveButton>
                </div>
            </form>
        </AccountLayout>
    );
}
