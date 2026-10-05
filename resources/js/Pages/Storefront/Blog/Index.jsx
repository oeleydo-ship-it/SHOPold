import { useState } from 'react';
import { router } from '@inertiajs/react';
import { Newspaper, Search } from 'lucide-react';
import Seo from '../../../Storefront/Layout/Seo';
import PostCard from '../../../Storefront/Blog/PostCard';
import { Pagination } from '../../../Storefront/Shop/Listing';
import { postJson } from '../../../Storefront/lib/http';
import SfLink from '../../../Storefront/lib/SfLink';
import { cx, useShop, useT } from '../../../Storefront/lib/store';
import { useThemed } from '../../../Storefront/theme/registry';

function SidebarNewsletter() {
    const t = useT();
    const { toast } = useShop();
    const [email, setEmail] = useState('');
    const [busy, setBusy] = useState(false);
    const submit = async (e) => {
        e.preventDefault();
        setBusy(true);
        try {
            const res = await postJson('/newsletter/subscribe', { email });
            toast('success', res.message || t('newsletter_success', 'Thank you for subscribing!'));
            setEmail('');
        } catch (err) {
            toast('error', err.message);
        }
        setBusy(false);
    };
    return (
        <div className="rounded-xl bg-gradient-to-br from-primary-600 to-primary-800 p-6 text-white shadow-sm">
            <h3 className="mb-2 text-lg font-bold">{t('subscribe_newsletter', 'Subscribe to Newsletter')}</h3>
            <p className="mb-4 text-sm text-primary-100">{t('blog_newsletter_text', 'Get the latest posts delivered to your inbox')}</p>
            <form onSubmit={submit}>
                <input type="email" required value={email} onChange={(e) => setEmail(e.target.value)} placeholder={t('your_email', 'Your email')} aria-label={t('your_email', 'Your email')}
                    className="mb-3 w-full rounded-lg bg-white px-4 py-2 text-gray-900" />
                <button type="submit" disabled={busy} className="w-full rounded-lg bg-white py-2 font-semibold text-primary-600 hover:bg-gray-100 disabled:opacity-60">{t('subscribe', 'Subscribe')}</button>
            </form>
        </div>
    );
}

export function BlogSidebar({ categories, category, total, newsletter }) {
    const t = useT();
    const item = (active) => cx('flex items-center justify-between rounded-lg px-3 py-2', active ? 'bg-primary-50 text-primary-600' : 'hover:bg-gray-50');
    return (
        <aside className="space-y-6 lg:w-1/3">
            <div className="rounded-xl bg-white p-6 shadow-sm">
                <h3 className="mb-4 text-lg font-bold">{t('categories', 'Categories')}</h3>
                <ul className="space-y-2">
                    <li><SfLink href="/blog" className={item(!category)}><span>{t('all_posts', 'All Posts')}</span>{!category && <span className="text-sm text-gray-500">{total}</span>}</SfLink></li>
                    {categories.map((c) => (
                        <li key={c.id}><SfLink href={`/blog/category/${c.slug}`} className={item(category?.id === c.id)}><span>{c.name}</span><span className="text-sm text-gray-500">{c.count}</span></SfLink></li>
                    ))}
                </ul>
            </div>
            {newsletter && <SidebarNewsletter />}
        </aside>
    );
}

export default function BlogIndex({ posts, categories, category, search, newsletter, seo }) {
    const t = useT();
    const [q, setQ] = useState(search);
    const Card = useThemed('Blog.PostCard', PostCard);
    const Sidebar = useThemed('Blog.Sidebar', BlogSidebar);
    const submit = (e) => {
        e.preventDefault();
        router.get('/blog', q ? { search: q } : {}, { preserveState: true });
    };

    return (
        <>
            <Seo title={seo.title} description={seo.description} canonical={seo.canonical} />
            <div className="bg-gray-50 py-12">
                <div className="sf-container">
                    <header className="mb-12 text-center">
                        <h1 className="mb-4 text-4xl font-bold text-gray-900">{category ? category.name : t('our_blog', 'Our Blog')}</h1>
                        <p className="mx-auto max-w-2xl text-gray-600">{category?.description || t('blog_intro', 'Stay updated with the latest news, tips, and insights')}</p>
                    </header>
                    <div className="flex flex-col gap-8 lg:flex-row">
                        <div className="lg:w-2/3">
                            <form onSubmit={submit} role="search" className="mb-8 flex gap-2">
                                <input type="search" value={q} onChange={(e) => setQ(e.target.value)} placeholder={t('search_articles', 'Search articles...')} aria-label={t('search_articles', 'Search articles...')}
                                    className="flex-1 rounded-lg border bg-white px-4 py-3 focus:ring-2 focus:ring-primary-500" />
                                <button type="submit" aria-label={t('search', 'Search')} className="rounded-lg bg-primary-600 px-6 py-3 text-white hover:bg-primary-700"><Search size={18} /></button>
                            </form>
                            {posts.data.length > 0 ? (
                                <>
                                    <div className="grid gap-6 md:grid-cols-2">{posts.data.map((p) => <Card key={p.id} post={p} />)}</div>
                                    <Pagination products={posts} />
                                </>
                            ) : (
                                <div className="rounded-xl bg-white py-12 text-center">
                                    <Newspaper size={56} className="mx-auto mb-4 text-gray-300" />
                                    <h2 className="text-xl font-semibold text-gray-700">{t('no_posts_found', 'No posts found')}</h2>
                                    <p className="mt-2 text-gray-500">{t('check_back_later', 'Check back later for new content')}</p>
                                </div>
                            )}
                        </div>
                        <Sidebar categories={categories} category={category} total={posts.total} newsletter={newsletter} />
                    </div>
                </div>
            </div>
        </>
    );
}
