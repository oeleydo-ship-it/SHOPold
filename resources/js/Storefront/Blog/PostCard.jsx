import { Newspaper, UserRound } from 'lucide-react';
import SfLink from '../lib/SfLink';

/** Blog post card for the blog list and "related articles" (theme override: "Blog.PostCard"). */
export default function PostCard({ post, compact = false }) {
    return (
        <article className="group overflow-hidden rounded-xl bg-white shadow-sm transition hover:shadow-lg">
            <SfLink href={`/blog/${post.slug}`} className="block aspect-video overflow-hidden" tabIndex={-1} aria-hidden="true">
                {post.image
                    ? <img src={post.image} alt="" loading="lazy" className="h-full w-full object-cover transition duration-300 group-hover:scale-105" />
                    : <span className="flex h-full w-full items-center justify-center bg-gradient-to-br from-primary-500 to-primary-700 text-white/50"><Newspaper size={compact ? 32 : 40} /></span>}
            </SfLink>
            <div className={compact ? 'p-4' : 'p-6'}>
                {!compact && post.category && (
                    <SfLink href={`/blog/category/${post.category.slug}`} className="text-xs font-semibold uppercase tracking-wide text-primary-600 hover:underline">{post.category.name}</SfLink>
                )}
                <h2 className={compact ? 'line-clamp-2 font-bold text-gray-900 group-hover:text-primary-600' : 'mt-2 text-xl font-bold text-gray-900 transition group-hover:text-primary-600'}>
                    <SfLink href={`/blog/${post.slug}`}>{post.title}</SfLink>
                </h2>
                {compact ? <p className="mt-2 text-sm text-gray-500">{post.date}</p> : (
                    <>
                        <p className="mt-3 line-clamp-3 text-gray-600">{post.excerpt}</p>
                        <div className="mt-4 flex items-center justify-between text-sm text-gray-500">
                            <span className="flex items-center gap-2"><span className="flex h-8 w-8 items-center justify-center rounded-full bg-gray-200 text-gray-400"><UserRound size={14} /></span>{post.author}</span>
                            <span>{post.date}</span>
                        </div>
                    </>
                )}
            </div>
        </article>
    );
}
