{{--
    Modern theme: homepage sections not replaced by a modern template (hero in every style, featured, flash deals,
    top rated, banners, promo, arrivals, blog, newsletter). Gradient badges, extra-bold headings, rounded media,
    pill buttons with a gradient, cards that lift on hover, and a floating rounded newsletter panel.
--}}
<style>
    /* section labels ("Featured", "Just in"...) */
    .home-page section span.inline-flex.rounded-full.text-xs {
        background: linear-gradient(90deg, var(--color-primary, #7c3aed), var(--color-accent, #f472b6)) !important;
        color: #fff !important; border: 0 !important;
    }
    .home-page section span.inline-flex.rounded-full.text-xs i { color: #fff !important; }
    .home-page .homepage-heading { font-weight: 800 !important; letter-spacing: -0.025em; }

    /* buttons and links styled as buttons */
    .home-page a.bg-primary-600, .home-page button.bg-primary-600, .home-page a.bg-white.rounded-lg, .home-page a.bg-white.rounded-xl {
        border-radius: 9999px !important;
    }
    .home-page a.bg-primary-600, .home-page button.bg-primary-600 {
        background: linear-gradient(90deg, var(--color-primary, #7c3aed), var(--color-accent, #f472b6)) !important;
        box-shadow: 0 10px 25px -12px var(--color-primary, #7c3aed);
    }

    /* hero: rounded image/slide frames and softer corners everywhere in the section */
    .home-page > section:first-child .rounded-lg, .home-page > section:first-child .rounded-xl,
    .home-page > section:first-child .rounded-2xl { border-radius: 1.75rem !important; }

    /* cards and media */
    .home-page .rounded-xl, .home-page .rounded-2xl { border-radius: 1.5rem !important; }
    .home-page article, .home-page .group.rounded-3xl { transition: transform .3s, box-shadow .3s; }
    .home-page article:hover { transform: translateY(-4px); }
    .home-page .shadow-sm { box-shadow: 0 12px 40px -20px rgba(17, 24, 39, .25) !important; }

    /* newsletter as a floating panel */
    .home-page section:has(#newsletterForm) { max-width: 80rem; margin: 2rem auto 3rem; border-radius: 2rem; }
    @media (max-width: 80rem) { .home-page section:has(#newsletterForm) { margin-left: 1rem; margin-right: 1rem; } }
</style>
