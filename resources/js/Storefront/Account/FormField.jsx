import { cx } from '../lib/store';

const input = 'w-full rounded-lg border px-3 py-2.5 text-sm focus:border-primary-500 focus:ring-primary-500 md:px-4 md:text-base';

/** Labelled input bound to an Inertia useForm field. */
export default function FormField({ form, name, label, as = 'input', className, children, ...rest }) {
    const Tag = as;
    const error = form.errors[name];
    return (
        <label className={cx('block', className)}>
            <span className="mb-1 block text-sm font-medium text-gray-700">{label}</span>
            <Tag name={name} value={form.data[name] ?? ''} onChange={(e) => form.setData(name, e.target.value)} aria-invalid={!!error}
                className={cx(input, error && 'border-red-500')} {...rest}>{children}</Tag>
            {error && <span className="mt-1 block text-sm text-red-500">{error}</span>}
        </label>
    );
}

export function SaveButton({ form, children }) {
    return (
        <button type="submit" disabled={form.processing} className="sf-btn rounded-lg bg-primary-600 px-6 py-2.5 font-medium text-white transition hover:bg-primary-700 disabled:opacity-60">
            {children}
        </button>
    );
}
