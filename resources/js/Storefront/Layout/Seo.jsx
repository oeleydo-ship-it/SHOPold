import { Head } from '@inertiajs/react';
import { useStore } from '../lib/store';

/**
 * Page <title> and meta tags. The HTML template prints the same tags server-side from the page's "seo" prop
 * (head-key matches their inertia="..." attribute), so they are right even without server-side rendering.
 */
export default function Seo({ title, description, image, canonical, jsonLd, robots }) {
    const { store, seo } = useStore();
    const full = title ? `${title} - ${store.name}` : seo.title || store.name;
    const desc = description ?? seo.description ?? '';

    return (
        <Head>
            <title>{full}</title>
            <meta head-key="description" name="description" content={desc} />
            <meta head-key="og:title" property="og:title" content={full} />
            <meta head-key="og:description" property="og:description" content={desc} />
            {image && <meta head-key="og:image" property="og:image" content={image} />}
            {canonical && <link head-key="canonical" rel="canonical" href={canonical} />}
            {robots && <meta head-key="robots" name="robots" content={robots} />}
            {jsonLd && (
                // JSON.stringify escaped so "</script>" inside product text cannot end the tag
                <script head-key="jsonld" type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd).replace(/</g, '\\u003c') }} />
            )}
        </Head>
    );
}
