import { useForm } from '@inertiajs/react';
import { CalendarDays, Eye, Mail, UserRound } from 'lucide-react';
import Seo from '../../../Storefront/Layout/Seo';
import PostCard from '../../../Storefront/Blog/PostCard';
import { SocialIcon } from '../../../Storefront/Footer/Footer';
import SfLink from '../../../Storefront/lib/SfLink';
import { useStore, useT } from '../../../Storefront/lib/store';
import { useThemed } from '../../../Storefront/theme/registry';

function Share({ post }) {
    const t = useT();
    const u = encodeURIComponent(post.url);
    const n = encodeURIComponent(post.title);
    const links = [
        ['facebook', `https://www.facebook.com/sharer/sharer.php?u=${u}`, 'bg-blue-600 hover:bg-blue-700'],
        ['twitter', `https://twitter.com/intent/tweet?url=${u}&text=${n}`, 'bg-black hover:bg-gray-800'],
        ['linkedin', `https://www.linkedin.com/shareArticle?mini=true&url=${u}&title=${n}`, 'bg-blue-700 hover:bg-blue-800'],
        ['whatsapp', `https://wa.me/?text=${encodeURIComponent(`${post.title} ${post.url}`)}`, 'bg-green-500 hover:bg-green-600'],
    ];
    return (
        <div className="mt-12 border-t pt-8">
            <h2 className="mb-4 font-semibold">{t('share_article', 'Share this article')}</h2>
            <div className="flex gap-3">
                {links.map(([k, href, tone]) => (
                    <a key={k} href={href} target="_blank" rel="noopener noreferrer" aria-label={`Share on ${k}`} className={`flex h-10 w-10 items-center justify-center rounded-full text-white ${tone}`}><SocialIcon network={k} size={16} /></a>
                ))}
                <a href={`mailto:?subject=${n}&body=${u}`} aria-label="Share by email" className="flex h-10 w-10 items-center justify-center rounded-full bg-gray-600 text-white hover:bg-gray-700"><Mail size={16} /></a>
            </div>
        </div>
    );
}

function Comments({ post, comments }) {
    const t = useT();
    const { customer } = useStore();
    const form = useForm({ author_name: '', author_email: '', content: '', website: '' });
    if (!comments.open) return null;
    const submit = (e) => {
        e.preventDefault();
        form.post(`/blog/${post.slug}/comments`, { preserveScroll: true, onSuccess: () => form.reset('content') });
    };
    const errors = ['author_name', 'author_email', 'content'].map((k) => form.errors[k]).filter(Boolean);

    return (
        <section id="comments" className="mt-12 rounded-2xl bg-white p-8 shadow-sm">
            <h2 className="mb-6 text-2xl font-bold">{t('comments', 'Comments')} ({comments.list.length})</h2>
            {comments.list.length > 0 && (
                <ul className="mb-8 space-y-6">
                    {comments.list.map((c) => (
                        <li key={c.id} className="flex gap-4">
                            <span className="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-gray-200 text-gray-400"><UserRound size={18} /></span>
                            <div className="flex-1">
                                <p className="mb-2 flex items-center gap-3"><span className="font-semibold">{c.author}</span><span className="text-sm text-gray-500">{c.ago}</span></p>
                                <p className="whitespace-pre-line text-gray-600">{c.content}</p>
                            </div>
                        </li>
                    ))}
                </ul>
            )}
            <div className="border-t pt-8">
                <h3 className="mb-4 font-bold">{t('leave_comment', 'Leave a Comment')}</h3>
                <p className="mb-4 text-sm text-gray-500">{t('comments_moderated', 'Comments are moderated and will appear after approval.')}</p>
                <form onSubmit={submit} className="space-y-4">
                    {/* spam trap: hidden from people, bots tend to fill it in */}
                    <div className="hidden" aria-hidden="true"><label>Website <input type="text" name="website" tabIndex={-1} autoComplete="off" value={form.data.website} onChange={(e) => form.setData('website', e.target.value)} /></label></div>
                    {customer ? <p className="text-sm text-gray-600">{t('commenting_as', 'Commenting as')} <strong>{customer.name}</strong></p> : (
                        <div className="grid gap-4 md:grid-cols-2">
                            <input type="text" required maxLength={100} value={form.data.author_name} onChange={(e) => form.setData('author_name', e.target.value)} placeholder={`${t('your_name', 'Your Name')} *`} aria-label={t('your_name', 'Your Name')} className="rounded-lg border px-4 py-3 focus:ring-2 focus:ring-primary-500" />
                            <input type="email" required maxLength={255} value={form.data.author_email} onChange={(e) => form.setData('author_email', e.target.value)} placeholder={`${t('your_email', 'Your Email')} *`} aria-label={t('your_email', 'Your Email')} className="rounded-lg border px-4 py-3 focus:ring-2 focus:ring-primary-500" />
                        </div>
                    )}
                    {errors.length > 0 && <ul className="space-y-1 text-sm text-red-600">{errors.map((m) => <li key={m}>{m}</li>)}</ul>}
                    <textarea rows={4} required maxLength={2000} value={form.data.content} onChange={(e) => form.setData('content', e.target.value)} placeholder={`${t('your_comment', 'Your comment')} *`} aria-label={t('your_comment', 'Your comment')}
                        className="w-full rounded-lg border px-4 py-3 focus:ring-2 focus:ring-primary-500" />
                    <button type="submit" disabled={form.processing} className="sf-btn rounded-lg bg-primary-600 px-6 py-3 font-semibold text-white hover:bg-primary-700 disabled:opacity-60">{t('post_comment', 'Post Comment')}</button>
                </form>
            </div>
        </section>
    );
}

export default function BlogShow({ post, related, comments, seo }) {
    const t = useT();
    const { store } = useStore();
    const Card = useThemed('Blog.PostCard', PostCard);
    const jsonLd = {
        '@context': 'https://schema.org', '@type': 'BlogPosting', headline: post.title, image: post.image || undefined,
        datePublished: post.publishedIso, author: { '@type': 'Person', name: post.author }, mainEntityOfPage: post.url,
    };

    return (
        <>
            <Seo title={seo.title} description={seo.description} image={seo.image} canonical={seo.canonical} jsonLd={jsonLd} />
            <article className="bg-gray-50 py-12">
                <div className="sf-container">
                    <div className="mx-auto max-w-4xl">
                        <nav aria-label="Breadcrumb" className="mb-8 flex flex-wrap items-center gap-2 text-sm">
                            <SfLink href="/" className="text-gray-500 hover:text-primary-600">{t('home', 'Home')}</SfLink><span className="text-gray-400">/</span>
                            <SfLink href="/blog" className="text-gray-500 hover:text-primary-600">{t('blog', 'Blog')}</SfLink>
                            {post.category && <><span className="text-gray-400">/</span><SfLink href={`/blog/category/${post.category.slug}`} className="text-gray-500 hover:text-primary-600">{post.category.name}</SfLink></>}
                            <span className="text-gray-400">/</span><span className="line-clamp-1 text-gray-900" aria-current="page">{post.title}</span>
                        </nav>

                        <header className="mb-8">
                            {post.category && <SfLink href={`/blog/category/${post.category.slug}`} className="mb-4 inline-block rounded-full bg-primary-100 px-3 py-1 text-sm font-semibold text-primary-600 hover:bg-primary-200">{post.category.name}</SfLink>}
                            <h1 className="mb-6 text-4xl font-bold text-gray-900 md:text-5xl">{post.title}</h1>
                            <div className="flex flex-wrap items-center gap-6 text-gray-500">
                                <span className="flex items-center gap-3"><span className="flex h-10 w-10 items-center justify-center rounded-full bg-gray-200 text-gray-400"><UserRound size={16} /></span><span><span className="block font-medium text-gray-900">{post.author}</span><span className="text-sm">{t('author', 'Author')}</span></span></span>
                                <span className="flex items-center gap-2"><CalendarDays size={16} /><time dateTime={post.publishedIso}>{post.longDate}</time></span>
                                <span className="flex items-center gap-2"><Eye size={16} />{post.views} {t('views', 'views')}</span>
                            </div>
                        </header>

                        {post.image && <div className="mb-8 overflow-hidden rounded-2xl shadow-lg"><img src={post.image} alt={post.title} className="h-auto w-full" /></div>}

                        <div className="mb-8 rounded-2xl bg-white p-8 shadow-sm md:p-12">
                            {/* content is sanitised server-side (safe_html) */}
                            <div className="prose prose-lg max-w-none" dangerouslySetInnerHTML={{ __html: post.content }} />
                            <Share post={post} />
                        </div>

                        <div className="mb-8 flex items-start gap-6 rounded-2xl bg-white p-8 shadow-sm">
                            <span className="flex h-20 w-20 shrink-0 items-center justify-center rounded-full bg-gray-200 text-gray-400"><UserRound size={28} /></span>
                            <div><h2 className="text-lg font-bold">{post.author}</h2><p className="mt-2 text-gray-600">{t('author_at', 'Author at')} {store.name}</p></div>
                        </div>

                        {related.length > 0 && (
                            <section className="mt-12">
                                <h2 className="mb-6 text-2xl font-bold">{t('related_articles', 'Related Articles')}</h2>
                                <div className="grid gap-6 md:grid-cols-3">{related.map((p) => <Card key={p.id} post={p} compact />)}</div>
                            </section>
                        )}

                        <Comments post={post} comments={comments} />
                    </div>
                </div>
            </article>
        </>
    );
}
