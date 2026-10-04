{{-- Modern theme: about, contact and page templates. Gradient hero bands with rounded bottoms, rounded cards, pill buttons. --}}
<style>
    .content-page > .bg-gradient-to-r:first-child {
        background: linear-gradient(135deg, var(--color-primary, #7c3aed), var(--color-accent, #f472b6)) !important;
        border-radius: 0 0 2.5rem 2.5rem;
    }
    .content-page h1 { font-weight: 800; letter-spacing: -0.02em; }
    .content-page .rounded-lg { border-radius: 1rem !important; }
    .content-page .rounded-xl, .content-page .rounded-2xl { border-radius: 1.75rem !important; }
    .content-page .shadow-sm { box-shadow: 0 12px 40px -20px rgba(17, 24, 39, .25) !important; }
    .content-page input:not([type="checkbox"]):not([type="radio"]), .content-page textarea, .content-page select { border-radius: .9rem !important; }
    .content-page button[type="submit"], .content-page a.bg-primary-600 { border-radius: 9999px !important; }
</style>
