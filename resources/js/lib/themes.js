// Keep in sync with the [data-theme] blocks in resources/css/app.css.
export const themes = [
    { id: 'light', label: 'Light', swatch: ['#ffffff', '#2563eb', '#111827'] },
    { id: 'dark', label: 'Dark', swatch: ['#111827', '#3b82f6', '#070d18'] },
    { id: 'violet', label: 'Violet', swatch: ['#ffffff', '#7c3aed', '#2e1065'] },
    { id: 'emerald', label: 'Emerald', swatch: ['#ffffff', '#059669', '#064e3b'] },
    { id: 'sunset', label: 'Sunset', swatch: ['#ffffff', '#ea580c', '#431407'] },
];

const KEY = 'admin-theme';

export function getTheme(fallback = 'light') {
    try {
        const t = localStorage.getItem(KEY);
        return themes.some((x) => x.id === t) ? t : fallback;
    } catch {
        return fallback;
    }
}

export function applyTheme(id) {
    document.documentElement.setAttribute('data-theme', id);
    try { localStorage.setItem(KEY, id); } catch { /* storage unavailable */ }
}
