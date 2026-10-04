import { useState } from 'react';
import { router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { Button, Card, Input, Select } from './ui';

/** GET-filter row: fields = [{ name, placeholder?, options?: [[value, label]] }] (no options = text search). */
export default function FilterBar({ path, filters, fields }) {
    const [values, setValues] = useState(() => Object.fromEntries(fields.map((f) => [f.name, filters[f.name] ?? ''])));
    const active = fields.some((f) => filters[f.name]);
    const submit = (e) => {
        e.preventDefault();
        router.get(path, Object.fromEntries(Object.entries(values).filter(([, v]) => v)), { preserveState: true });
    };
    const clear = () => { setValues(Object.fromEntries(fields.map((f) => [f.name, '']))); router.get(path); };

    return (
        <Card className="mb-4 p-4">
            <form onSubmit={submit} className="flex flex-wrap items-center gap-2">
                {fields.map((f) => f.options ? (
                    <Select key={f.name} aria-label={f.placeholder} className="w-auto" value={values[f.name]} onChange={(e) => setValues((v) => ({ ...v, [f.name]: e.target.value }))}>
                        <option value="">{f.placeholder}</option>{f.options.map(([v, l]) => <option key={v} value={v}>{l}</option>)}
                    </Select>
                ) : (
                    <div key={f.name} className="relative min-w-52 flex-1 sm:max-w-xs">
                        <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
                        <Input className="pl-9" aria-label={f.placeholder} placeholder={f.placeholder} value={values[f.name]} onChange={(e) => setValues((v) => ({ ...v, [f.name]: e.target.value }))} />
                    </div>
                ))}
                <Button type="submit">Filter</Button>
                {active && <Button tone="neutral" onClick={clear}>Clear</Button>}
            </form>
        </Card>
    );
}
