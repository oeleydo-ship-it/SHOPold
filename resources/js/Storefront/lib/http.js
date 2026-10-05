/** JSON POST to a Laravel route with the CSRF token (from the page meta tag or the XSRF cookie). */
export async function postJson(url, data = {}) {
    const meta = typeof document !== 'undefined' ? document.querySelector('meta[name="csrf-token"]')?.content : null;
    const cookie = typeof document !== 'undefined' ? document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] : null;
    const headers = { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
    if (meta) headers['X-CSRF-TOKEN'] = meta;
    else if (cookie) headers['X-XSRF-TOKEN'] = decodeURIComponent(cookie);

    const res = await fetch(url, { method: 'POST', headers, credentials: 'same-origin', body: JSON.stringify(data) });
    let body = null;
    try {
        body = await res.json();
    } catch {
        body = null;
    }
    if (!res.ok || body?.success === false) {
        const message = body?.message || (res.status === 419 ? 'Your session expired. Please reload the page.' : 'Something went wrong. Please try again.');
        throw Object.assign(new Error(message), { status: res.status, body });
    }
    return body ?? {};
}

/** Classic form POST (full page navigation) for endpoints that answer with a redirect to a non-React page. */
export function postForm(url, data = {}) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = url;
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    Object.entries({ ...data, _token: token }).forEach(([name, value]) => {
        if (value === undefined || value === null) return;
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = String(value);
        form.appendChild(input);
    });
    document.body.appendChild(form);
    form.submit();
}
