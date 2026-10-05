import { ArrowLeft, Download, File, Link2 } from 'lucide-react';
import AccountLayout from '../../../Storefront/Account/AccountLayout';
import SfLink from '../../../Storefront/lib/SfLink';
import { cx, useT } from '../../../Storefront/lib/store';

export default function Downloads({ order, items, seo }) {
    const t = useT();
    const actions = <SfLink href={order.url} className="flex items-center gap-1 text-primary-600 hover:text-primary-700"><ArrowLeft size={16} />{t('back_to_order', 'Back to order')}</SfLink>;

    return (
        <AccountLayout title={`${t('downloads', 'Downloads')} · ${order.number}`} seo={seo} actions={actions}>
            {items.length === 0 ? (
                <p className="rounded-xl bg-white p-8 text-center text-gray-500 shadow-sm">{t('no_downloads_available', 'No downloads available for this order.')}</p>
            ) : items.map((item) => (
                <section key={item.id} className="overflow-hidden rounded-xl bg-white shadow-sm">
                    <div className="flex items-center gap-4 border-b p-4 md:p-6">
                        {item.image && <img src={item.image} alt="" className="h-16 w-16 rounded-lg object-cover" />}
                        <div>
                            <h2 className="font-semibold">{item.name}</h2>
                            <p className="mt-1 flex flex-wrap gap-3 text-sm text-gray-500">
                                <span>{item.remaining !== null ? `${item.remaining} ${t('downloads_remaining', 'downloads remaining')}` : t('unlimited_downloads', 'Unlimited downloads')}</span>
                                <span className={cx(item.expired && 'text-red-600')}>
                                    {item.expiry ? (item.expired ? t('expired', 'Expired') : `${t('expires', 'Expires')}: ${item.expiry}`) : t('never_expires', 'Never expires')}
                                </span>
                            </p>
                        </div>
                    </div>
                    {item.files.length > 0 ? (
                        <ul className="divide-y">
                            {item.files.map((f) => (
                                <li key={f.id} className="flex items-center justify-between gap-3 p-4 md:px-6">
                                    <div className="flex items-center gap-3">
                                        <span className={cx('flex h-10 w-10 items-center justify-center rounded-lg', f.link ? 'bg-purple-100 text-purple-600' : 'bg-blue-100 text-blue-600')}>{f.link ? <Link2 size={18} /> : <File size={18} />}</span>
                                        <div><p className="font-medium">{f.name}</p>{f.size && <p className="text-xs text-gray-500">{f.size}</p>}</div>
                                    </div>
                                    {f.url ? (
                                        <a href={f.url} className="flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-white hover:bg-primary-700"><Download size={16} />{t('download', 'Download')}</a>
                                    ) : (
                                        <span title={item.reason ?? ''} className="cursor-not-allowed rounded-lg bg-gray-200 px-4 py-2 text-gray-500">{t('unavailable', 'Unavailable')}</span>
                                    )}
                                </li>
                            ))}
                        </ul>
                    ) : <p className="p-4 text-sm text-gray-500 md:px-6">{t('no_files_available', 'No files available yet.')}</p>}
                </section>
            ))}
        </AccountLayout>
    );
}
