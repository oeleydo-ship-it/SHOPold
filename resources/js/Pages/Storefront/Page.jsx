import { ChevronRight } from 'lucide-react';
import Seo from '../../Storefront/Layout/Seo';
import SfLink from '../../Storefront/lib/SfLink';
import { useT } from '../../Storefront/lib/store';

/** CMS page (About, Contact, custom pages). page.content is sanitised server-side (safe_page_html). */
export default function Page({ page, seo }) {
    const t = useT();
    const body = <div className="prose prose-lg max-w-none" dangerouslySetInnerHTML={{ __html: page.content }} />;

    return (
        <>
            <Seo title={seo.title} description={seo.description} image={seo.image} canonical={seo.canonical} />
            <div className="content-page">
                {page.template === 'full-width' ? (
                    <>
                        {page.image && (
                            <div className="relative h-96 overflow-hidden">
                                <img src={page.image} alt="" className="h-full w-full object-cover" />
                                <div className="absolute inset-0 flex items-center justify-center bg-black/40"><h1 className="px-4 text-center text-4xl font-bold text-white md:text-5xl">{page.title}</h1></div>
                            </div>
                        )}
                        <div className="sf-container py-12">
                            {!page.image && <h1 className="mb-8 text-center text-3xl font-bold text-gray-900 md:text-4xl">{page.title}</h1>}
                            {page.excerpt && <p className="mx-auto mb-8 max-w-3xl text-center text-xl text-gray-600">{page.excerpt}</p>}
                            {body}
                        </div>
                    </>
                ) : (
                    <div className="sf-container py-12">
                        <nav aria-label="Breadcrumb" className="mb-8 flex items-center gap-2 text-sm">
                            <SfLink href="/" className="text-gray-500 hover:text-primary-600">{t('home', 'Home')}</SfLink>
                            <ChevronRight size={12} className="text-gray-400 rtl:rotate-180" />
                            <span className="font-medium text-gray-900" aria-current="page">{page.title}</span>
                        </nav>
                        <div className="overflow-hidden rounded-2xl bg-white shadow-sm">
                            {page.image && <div className="h-64 overflow-hidden md:h-80"><img src={page.image} alt="" className="h-full w-full object-cover" /></div>}
                            <div className="p-8 lg:p-12">
                                <h1 className="mb-6 text-3xl font-bold text-gray-900 md:text-4xl">{page.title}</h1>
                                {page.excerpt && <p className="mb-8 text-xl text-gray-600">{page.excerpt}</p>}
                                {body}
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}
