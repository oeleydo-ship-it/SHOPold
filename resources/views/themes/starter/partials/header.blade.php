{{--
    Starter theme header: the shared header (all header styles, menus, cart, search, switchers) with a flat skin:
    no shadow, a solid primary rule underneath, uppercase navigation and square controls.
--}}
<style>
    header.sticky, header.shadow-sm { box-shadow: none !important; border-bottom: 2px solid var(--color-primary, #059669); }
    header .nav-link { text-transform: uppercase; letter-spacing: .06em; font-size: .8rem; font-weight: 600; }
    header .nav-link.active { text-decoration: underline; text-underline-offset: 6px; text-decoration-thickness: 2px; }
    header input[name="search"],
    header form:has(input[name="search"]) button,
    header form:has(input[name="search"]) select { border-radius: 0 !important; }
</style>
@include('partials.header.base')
