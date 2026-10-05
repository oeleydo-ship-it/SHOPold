import { cx } from '../lib/store';

/** Centred card used by the login, register and verify-email pages (theme override: "Auth.Card"). */
export default function AuthCard({ title, subtitle, icon, children }) {
    return (
        <div className="auth-page flex min-h-[70vh] items-center justify-center px-4 py-12">
            <div className="w-full max-w-md rounded-2xl bg-white p-8 shadow-sm">
                <div className="mb-8 text-center">
                    {icon && <span className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-primary-100 text-primary-600">{icon}</span>}
                    <h1 className="text-2xl font-bold text-gray-900">{title}</h1>
                    {subtitle && <p className="mt-2 text-gray-600">{subtitle}</p>}
                </div>
                {children}
            </div>
        </div>
    );
}

export function AuthField({ label, error, ...rest }) {
    return (
        <label className="block">
            <span className="mb-1 block text-sm font-medium text-gray-700">{label}</span>
            <input {...rest} className={cx('w-full rounded-lg border px-4 py-3 focus:border-transparent focus:ring-2 focus:ring-primary-500', error && 'border-red-500')} aria-invalid={!!error} />
            {error && <span className="mt-1 block text-sm text-red-500">{error}</span>}
        </label>
    );
}

export function SubmitButton({ busy, children }) {
    return (
        <button type="submit" disabled={busy} className="sf-btn w-full rounded-lg bg-primary-600 py-3 font-semibold text-white transition hover:bg-primary-700 disabled:opacity-60">
            {children}
        </button>
    );
}
