import { Link } from '@inertiajs/react';

export function Card({ children, className = '' }) {
    return <section className={`rounded-card border border-line bg-surface ${className}`}>{children}</section>;
}

export function CardHeader({ title, children }) {
    return (
        <div className="flex items-center justify-between gap-3 border-b border-line p-4 md:px-6">
            <h3 className="text-sm font-semibold md:text-base">{title}</h3>
            {children}
        </div>
    );
}

const BUTTON_TONES = {
    primary: 'bg-primary text-on-primary hover:bg-primary-hover',
    neutral: 'border border-line bg-surface text-ink hover:bg-surface-2',
    danger: 'bg-danger text-white hover:opacity-90',
    success: 'bg-success text-white hover:opacity-90',
};

export function buttonClass(tone = 'primary', extra = '') {
    return `inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition disabled:opacity-50 ${BUTTON_TONES[tone]} ${extra}`;
}

export function Button({ tone = 'primary', className = '', ...props }) {
    return <button type="button" {...props} className={buttonClass(tone, className)} />;
}

export function LinkButton({ tone = 'primary', className = '', external, ...props }) {
    const Tag = external ? 'a' : Link;
    return <Tag {...props} className={buttonClass(tone, className)} />;
}

const inputClass =
    'w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm text-ink placeholder:text-muted focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30';

export function Field({ label, error, hint, children, className = '' }) {
    return (
        <label className={`block ${className}`}>
            {label && <span className="mb-1 block text-sm font-medium">{label}</span>}
            {children}
            {hint && !error && <span className="mt-1 block text-xs text-muted">{hint}</span>}
            {error && <span className="mt-1 block text-xs text-danger">{error}</span>}
        </label>
    );
}

export function Input(props) {
    return <input {...props} className={`${inputClass} ${props.className ?? ''}`} />;
}

export function Textarea(props) {
    return <textarea {...props} className={`${inputClass} ${props.className ?? ''}`} />;
}

export function Select({ children, ...props }) {
    return <select {...props} className={`${inputClass} ${props.className ?? ''}`}>{children}</select>;
}

const TONES = {
    success: 'var(--success)', warning: 'var(--warning)', danger: 'var(--danger)',
    info: 'var(--info)', primary: 'var(--primary)', muted: 'var(--muted)',
};

export function Badge({ tone = 'muted', children }) {
    const c = TONES[tone] ?? tone;
    return (
        <span
            className="inline-block whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-medium"
            style={{ color: c, background: `color-mix(in srgb, ${c} 15%, transparent)` }}
        >
            {children}
        </span>
    );
}

export const titleCase = (s) => String(s ?? '').replace(/_/g, ' ').replace(/^\w/, (c) => c.toUpperCase());

const ORDER_TONE = {
    pending: 'warning', processing: 'info', ready_for_pickup: 'primary', shipped: 'primary',
    delivered: 'success', cancelled: 'danger',
};
const PAYMENT_TONE = { pending: 'warning', paid: 'success', failed: 'danger', refunded: 'info' };

export function OrderStatusBadge({ status }) {
    return <Badge tone={ORDER_TONE[status] ?? 'muted'}>{titleCase(status)}</Badge>;
}

export function PaymentStatusBadge({ status }) {
    return <Badge tone={PAYMENT_TONE[status] ?? 'muted'}>{titleCase(status)}</Badge>;
}

export function Pagination({ links, from, to, total }) {
    if (!links || links.length <= 3) return null;
    return (
        <div className="flex flex-col items-center justify-between gap-3 border-t border-line p-4 sm:flex-row">
            <p className="text-xs text-muted">Showing {from ?? 0}–{to ?? 0} of {total}</p>
            <nav className="flex flex-wrap gap-1" aria-label="Pagination">
                {links.map((l, i) => {
                    const label = l.label.replace('&laquo;', '‹').replace('&raquo;', '›').replace(/Previous|Next/g, '').trim();
                    const cls = `min-w-9 rounded-lg px-3 py-1.5 text-center text-sm ${l.active ? 'bg-primary text-on-primary' : 'text-ink hover:bg-surface-2'} ${!l.url ? 'pointer-events-none opacity-40' : ''}`;
                    return l.url ? (
                        <Link key={i} href={l.url} preserveScroll className={cls}>{label}</Link>
                    ) : (
                        <span key={i} className={cls}>{label}</span>
                    );
                })}
            </nav>
        </div>
    );
}

export function EmptyState({ children }) {
    return <p className="p-10 text-center text-sm text-muted">{children}</p>;
}
