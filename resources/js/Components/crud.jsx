import { Link, router } from '@inertiajs/react';
import { Pencil, Trash2 } from 'lucide-react';
import { EmptyState } from './ui';

/** Delete with a native confirm; keeps scroll position and shows the flash toast from the redirect. */
export function confirmDelete(url, message = 'Are you sure? This cannot be undone.') {
    if (window.confirm(message)) router.delete(url, { preserveScroll: true });
}

export function Toggle({ checked, onChange, label }) {
    return (
        <label className="inline-flex cursor-pointer items-center gap-3 text-sm">
            <button
                type="button"
                role="switch"
                aria-checked={!!checked}
                onClick={() => onChange(!checked)}
                className={`relative h-6 w-11 shrink-0 rounded-full transition ${checked ? 'bg-primary' : 'bg-line'}`}
            >
                <span className={`absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition-all ${checked ? 'left-[22px]' : 'left-0.5'}`} />
            </button>
            {label}
        </label>
    );
}

export function ListToolbar({ children }) {
    return <div className="mb-4 flex flex-wrap items-center justify-end gap-3">{children}</div>;
}

/** Responsive table: columns = [{ key, label, render?(row), className? }]. */
export function DataTable({ columns, rows, empty = 'Nothing here yet.' }) {
    if (rows.length === 0) return <EmptyState>{empty}</EmptyState>;
    return (
        <div className="overflow-x-auto">
            <table className="w-full text-sm">
                <thead className="bg-surface-2 text-left text-xs uppercase text-muted">
                    <tr>{columns.map((c) => <th key={c.key} className={`px-4 py-3 font-medium md:px-6 ${c.className ?? ''}`}>{c.label}</th>)}</tr>
                </thead>
                <tbody className="divide-y divide-line">
                    {rows.map((r) => (
                        <tr key={r.id} className="hover:bg-surface-2">
                            {columns.map((c) => <td key={c.key} className={`px-4 py-4 md:px-6 ${c.className ?? ''}`}>{c.render ? c.render(r) : r[c.key]}</td>)}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

export function RowActions({ editHref, deleteHref, deleteMessage, children }) {
    return (
        <div className="flex items-center justify-end gap-3">
            {children}
            {editHref && (
                <Link href={editHref} aria-label="Edit" title="Edit" className="text-primary hover:text-primary-hover"><Pencil size={18} /></Link>
            )}
            {deleteHref && (
                <button type="button" aria-label="Delete" title="Delete" onClick={() => confirmDelete(deleteHref, deleteMessage)} className="text-danger hover:opacity-75">
                    <Trash2 size={18} />
                </button>
            )}
        </div>
    );
}
