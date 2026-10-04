{{-- Modern theme: login / register / verify email. Rounded inputs, gradient pill submit button, bold heading. --}}
<style>
    .auth-page h1, .auth-page h2 { letter-spacing: -0.02em; }
    .auth-page h1 { font-weight: 800 !important; }
    .auth-page input:not([type="checkbox"]):not([type="radio"]) { border-radius: .9rem !important; }
    .auth-page button[type="submit"]:not(.text-gray-600):not(.underline) {
        border-radius: 9999px !important;
        background: linear-gradient(90deg, var(--color-primary, #7c3aed), var(--color-accent, #f472b6)) !important;
        box-shadow: 0 10px 25px -10px var(--color-primary, #7c3aed);
    }
    .auth-page--verify .rounded-lg, .auth-page--verify .rounded-xl { border-radius: 1.5rem !important; }
</style>
