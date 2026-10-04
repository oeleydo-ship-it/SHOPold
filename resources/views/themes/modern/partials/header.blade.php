{{--
    Modern theme header: the shared header (all header styles, menus, cart, search, switchers) with a modern skin:
    frosted-glass bar, gradient accent line, pill-shaped navigation and search.
--}}
<style>
    header.sticky, header.shadow-sm {
        backdrop-filter: saturate(180%) blur(14px);
        -webkit-backdrop-filter: saturate(180%) blur(14px);
        box-shadow: 0 8px 30px -12px rgba(17, 24, 39, .18) !important;
    }
    header.shadow-sm:not(.sticky) { position: relative; } /* anchor the accent line without breaking sticky */
    header.sticky::after, header.shadow-sm::after {
        content: ''; position: absolute; left: 0; right: 0; bottom: 0; height: 3px;
        background: linear-gradient(90deg, var(--color-primary, #7c3aed), var(--color-accent, #f472b6));
    }
    header .nav-link { position: relative; padding: .35rem .85rem; border-radius: 9999px; }
    header .nav-link:hover, header .nav-link.active { background: color-mix(in srgb, var(--color-primary, #7c3aed) 12%, transparent); }
    header input[name="search"] { border-radius: 9999px !important; }
    header form:has(input[name="search"]) button[type="submit"] { border-radius: 9999px !important; }
</style>
@include('partials.header.base')
