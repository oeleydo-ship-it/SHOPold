{{-- Modern theme: account styles (rounded cards, softer shadows, gradient active navigation, pill buttons). --}}
<style>
    .account-page .rounded-lg { border-radius: 1rem !important; }
    .account-page .rounded-xl { border-radius: 1.5rem !important; }
    .account-page .shadow-sm { box-shadow: 0 12px 40px -20px rgba(17, 24, 39, .25) !important; }
    .account-page nav a.bg-primary-50 {
        background: linear-gradient(90deg, var(--color-primary, #7c3aed), var(--color-accent, #f472b6)) !important;
        color: #fff !important;
    }
    .account-page nav a.bg-primary-50 i { color: #fff !important; }
    .account-page a.bg-primary-600, .account-page button.bg-primary-600 { border-radius: 9999px !important; }
    .account-page input:not([type="checkbox"]):not([type="radio"]), .account-page select, .account-page textarea { border-radius: .9rem !important; }
    .account-page .sticky.top-4 { top: 7rem; }
</style>
