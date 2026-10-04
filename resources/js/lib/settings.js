import { useState } from 'react';
import { router } from '@inertiajs/react';

const truthy = (v) => v === true || v === 1 || v === '1' || v === 'true';

/**
 * State + submit helper for a settings tab.
 *
 * The backend (`SettingController@update`) expects PHP-style form keys: the first "." of a setting
 * key is sent as "_" (store.name -> store_name), plus `_group` and the list of boolean keys this
 * form manages. Unmanaged booleans in the same group are left alone, and managed ones are always
 * sent as "1"/"0" so unchecking really saves "0".
 *
 * `values` is { 'store.name': 'Shop', ... } straight from the server; `booleans` lists the keys that
 * are switches. `secretKeys` are write-only fields (API keys, passwords): their stored value is never
 * sent to the browser, so they are only submitted when the user types a new value or asks to remove
 * the saved one; otherwise the key is omitted and the stored value is untouched.
 */
export function useSettings(group, values, booleans = [], secretKeys = []) {
    const [data, setData] = useState(() => {
        const out = {};
        Object.entries(values).forEach(([k, v]) => {
            out[k] = booleans.includes(k) ? truthy(v) : (v ?? '');
        });
        return out;
    });
    const [secrets, setSecrets] = useState(() => Object.fromEntries(secretKeys.map((k) => [k, { value: '', clear: false }])));
    const [processing, setProcessing] = useState(false);

    const set = (key, value) => setData((d) => ({ ...d, [key]: value }));
    const setSecret = (key, value) => setSecrets((s) => ({ ...s, [key]: { value, clear: false } }));
    const clearSecret = (key, clear = true) => setSecrets((s) => ({ ...s, [key]: { value: '', clear } }));

    const submit = (transform = (d) => d, extra = {}) => {
        const payload = { _group: group, _boolean_fields: booleans.join(','), ...extra };
        Object.entries(transform({ ...data })).forEach(([k, v]) => {
            payload[k.replace('.', '_')] = booleans.includes(k) ? (v ? '1' : '0') : v;
        });
        Object.entries(secrets).forEach(([k, s]) => {
            if (s.value !== '') payload[k.replace('.', '_')] = s.value;
            else if (s.clear) payload[k.replace('.', '_')] = '';
        });
        router.post('/admin/settings', payload, {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => {
                setProcessing(false);
                // the server now holds whatever was typed; drop it from memory
                setSecrets(Object.fromEntries(secretKeys.map((k) => [k, { value: '', clear: false }])));
            },
        });
    };

    return { data, set, secrets, setSecret, clearSecret, submit, processing };
}
