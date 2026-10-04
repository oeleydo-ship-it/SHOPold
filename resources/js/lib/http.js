// Small fetch wrapper for JSON endpoints that are not Inertia pages (media picker, AI, search…).
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

export async function jsonRequest(url, { method = 'GET', body, formData, signal } = {}) {
    const headers = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
    if (method !== 'GET') headers['X-CSRF-TOKEN'] = csrf();
    if (body) headers['Content-Type'] = 'application/json';

    const res = await fetch(url, {
        method,
        headers,
        body: formData ?? (body ? JSON.stringify(body) : undefined),
        credentials: 'same-origin',
        signal,
    });

    const isJson = res.headers.get('content-type')?.includes('application/json');
    const data = isJson ? await res.json() : null;
    if (!res.ok) {
        const msg = data?.message
            || (data?.errors && Object.values(data.errors).flat().join(', '))
            || data?.error
            || `Request failed (${res.status})`;
        throw new Error(msg);
    }
    return data;
}

// Paths stored by the media library are relative to the public disk.
export function storageUrl(img) {
    if (!img) return '';
    if (typeof img === 'object') return img.url ?? '';
    if (img.startsWith('http') || img.startsWith('/storage/')) return img;
    return `/storage/${img}`;
}
