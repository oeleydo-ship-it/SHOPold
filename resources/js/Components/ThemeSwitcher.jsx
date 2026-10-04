import { useEffect, useRef, useState } from 'react';
import { Palette, Check } from 'lucide-react';
import { themes, getTheme, applyTheme } from '../lib/themes';

export default function ThemeSwitcher({ fallback }) {
    const [open, setOpen] = useState(false);
    const [current, setCurrent] = useState(() => getTheme(fallback));
    const ref = useRef(null);

    useEffect(() => {
        const close = (e) => ref.current && !ref.current.contains(e.target) && setOpen(false);
        document.addEventListener('mousedown', close);
        return () => document.removeEventListener('mousedown', close);
    }, []);

    const choose = (id) => {
        applyTheme(id);
        setCurrent(id);
        setOpen(false);
    };

    return (
        <div className="relative" ref={ref}>
            <button
                type="button"
                onClick={() => setOpen((o) => !o)}
                aria-label="Change theme"
                aria-expanded={open}
                className="flex h-9 w-9 items-center justify-center rounded-lg text-muted hover:bg-surface-2 hover:text-ink"
            >
                <Palette size={18} />
            </button>
            {open && (
                <div className="absolute right-0 z-50 mt-2 w-48 rounded-card border border-line bg-surface p-1 shadow-lg">
                    {themes.map((t) => (
                        <button
                            key={t.id}
                            type="button"
                            onClick={() => choose(t.id)}
                            className="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm text-ink hover:bg-surface-2"
                        >
                            <span className="flex -space-x-1">
                                {t.swatch.map((c) => (
                                    <span key={c} className="h-4 w-4 rounded-full border border-line" style={{ background: c }} />
                                ))}
                            </span>
                            <span className="flex-1 text-left">{t.label}</span>
                            {current === t.id && <Check size={14} className="text-primary" />}
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}
