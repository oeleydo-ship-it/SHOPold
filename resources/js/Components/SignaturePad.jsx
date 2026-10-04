import { useEffect, useRef } from 'react';
import { Eraser } from 'lucide-react';

/** Finger/mouse signature on a canvas. Calls onChange with a PNG data URL, or '' when cleared. */
export default function SignaturePad({ onChange, label = 'Customer signature' }) {
    const canvas = useRef(null);
    const drawing = useRef(false);
    const dirty = useRef(false);

    useEffect(() => {
        const c = canvas.current;
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        c.width = c.offsetWidth * ratio;
        c.height = c.offsetHeight * ratio;
        const ctx = c.getContext('2d');
        ctx.scale(ratio, ratio);
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.strokeStyle = '#111827';
    }, []);

    const point = (e) => {
        const r = canvas.current.getBoundingClientRect();
        return [e.clientX - r.left, e.clientY - r.top];
    };
    const down = (e) => {
        e.preventDefault();
        canvas.current.setPointerCapture?.(e.pointerId);
        drawing.current = true;
        const ctx = canvas.current.getContext('2d');
        ctx.beginPath();
        ctx.moveTo(...point(e));
    };
    const move = (e) => {
        if (!drawing.current) return;
        const ctx = canvas.current.getContext('2d');
        ctx.lineTo(...point(e));
        ctx.stroke();
        dirty.current = true;
    };
    const up = () => {
        if (!drawing.current) return;
        drawing.current = false;
        if (dirty.current) onChange(canvas.current.toDataURL('image/png'));
    };
    const clear = () => {
        const c = canvas.current;
        c.getContext('2d').clearRect(0, 0, c.width, c.height);
        dirty.current = false;
        onChange('');
    };

    return (
        <div>
            <div className="mb-1 flex items-center justify-between">
                <span className="text-sm font-medium">{label}</span>
                <button type="button" onClick={clear} className="inline-flex items-center gap-1 text-xs text-muted hover:text-ink"><Eraser size={12} />Clear</button>
            </div>
            {/* white background so the signature stays readable in dark mode */}
            <canvas ref={canvas} aria-label={label} className="h-32 w-full touch-none rounded-lg border border-line bg-white"
                onPointerDown={down} onPointerMove={move} onPointerUp={up} onPointerLeave={up} onPointerCancel={up} />
        </div>
    );
}
