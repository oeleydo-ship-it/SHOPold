{{--
    Starter theme: homepage sections not replaced by a starter template (hero in every style, featured, flash deals,
    top rated, banners, promo, arrivals, blog, newsletter). Flat and square: plain uppercase labels, headings with
    a primary underline, outlines instead of shadows, no hover zoom.
--}}
<style>
    .home-page .rounded, .home-page .rounded-md, .home-page .rounded-lg, .home-page .rounded-xl,
    .home-page .rounded-2xl, .home-page .rounded-3xl { border-radius: 0 !important; }
    .home-page .shadow-sm, .home-page .shadow, .home-page .shadow-md, .home-page .shadow-lg, .home-page .shadow-xl,
    .home-page .shadow-2xl, .home-page [class*="hover:shadow"]:hover { box-shadow: none !important; }
    .home-page article, .home-page .bg-white.shadow-sm, .home-page .bg-white.shadow-lg { border: 1px solid #d1d5db; }
    .home-page [class*="group-hover:scale"] { transform: none !important; }

    /* section labels become plain uppercase text */
    .home-page section span.inline-flex.rounded-full.text-xs {
        background: transparent !important; color: var(--color-primary, #059669) !important; padding-left: 0 !important;
        border: 0 !important; letter-spacing: .12em; text-transform: uppercase;
    }
    .home-page .homepage-heading { display: inline-block; padding-bottom: .35rem; border-bottom: 2px solid var(--color-primary, #059669); }

    /* buttons */
    .home-page a.bg-primary-600, .home-page button.bg-primary-600, .home-page button[type="submit"] {
        border-radius: 0 !important; text-transform: uppercase; letter-spacing: .05em;
    }
    .home-page #newsletterForm > div, .home-page #newsletterForm input { border-radius: 0 !important; }
</style>
