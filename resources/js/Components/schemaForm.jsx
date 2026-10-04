import { useState } from 'react';
import { ChevronDown } from 'lucide-react';
import { Field, Input, Select, Textarea } from './ui';
import { Toggle } from './crud';
import MediaPicker from './MediaPicker';
import { SaveButton, SettingsCard } from './settingsUi';
import { useSettings } from '../lib/settings';

/* ------------------------------------------------------------------ field controls */

const HEX6 = /^#[0-9a-f]{6}$/i;
const HEX3 = /^#[0-9a-f]{3}$/i;
const expandHex = (v) => (HEX3.test(v) ? `#${v[1]}${v[1]}${v[2]}${v[2]}${v[3]}${v[3]}` : v);

/** Colour picker + hex text box that stay in sync; invalid text is flagged but never blocks typing. */
export function ColorField({ value, onChange, placeholder, label }) {
    const text = value ?? '';
    const normalised = text && !text.startsWith('#') ? `#${text}` : text;
    const valid = !text || HEX6.test(normalised) || HEX3.test(normalised) || /^#[0-9a-f]{8}$/i.test(normalised);
    return (
        <div className="flex items-center gap-2">
            <input
                type="color"
                aria-label={label ? `${label} picker` : 'Pick colour'}
                value={HEX6.test(expandHex(normalised)) ? expandHex(normalised) : '#000000'}
                onChange={(e) => onChange(e.target.value)}
                className="h-10 w-12 shrink-0 cursor-pointer rounded-lg border border-line bg-surface p-1"
            />
            <Input
                value={text}
                placeholder={placeholder}
                aria-label={label ? `${label} hex value` : 'Hex colour'}
                aria-invalid={!valid}
                onChange={(e) => onChange(e.target.value)}
                onBlur={() => { if (HEX3.test(normalised) || HEX6.test(normalised)) onChange(expandHex(normalised).toLowerCase()); }}
                className={valid ? '' : '!border-danger'}
            />
        </div>
    );
}

/** A grid of selectable cards: options = [[value, title, description]]. */
export function RadioCards({ value, onChange, options, columns = 3, label }) {
    const cols = { 2: 'sm:grid-cols-2', 3: 'sm:grid-cols-3', 4: 'sm:grid-cols-2 lg:grid-cols-4' }[columns] ?? 'sm:grid-cols-3';
    return (
        <div className={`grid grid-cols-1 gap-3 ${cols}`} role="radiogroup" aria-label={label}>
            {options.map(([v, title, desc]) => (
                <button
                    key={v} type="button" role="radio" aria-checked={value === v} onClick={() => onChange(v)}
                    className={`rounded-card border-2 p-4 text-left transition ${value === v ? 'border-primary bg-primary-soft/40' : 'border-line hover:bg-surface-2'}`}
                >
                    <span className="block font-medium">{title}</span>
                    {desc && <span className="block text-xs text-muted">{desc}</span>}
                </button>
            ))}
        </div>
    );
}

/** Renders one schema field. */
export function SchemaField({ f, data, set }) {
    const v = data[f.key];
    const common = { value: v ?? '', onChange: (e) => set(f.key, e.target.value) };

    switch (f.type) {
        case 'toggle':
            return (
                <div>
                    <Toggle checked={!!v} onChange={(x) => set(f.key, x)} label={f.label} />
                    {f.hint && <p className="ml-14 mt-1 text-xs text-muted">{f.hint}</p>}
                </div>
            );
        case 'color':
            return <Field label={f.label} hint={f.hint}><ColorField value={v} onChange={(x) => set(f.key, x)} placeholder={f.placeholder} label={f.label} /></Field>;
        case 'select':
            return (
                <Field label={f.label} hint={f.hint}>
                    <Select {...common}>{f.options.map(([val, text]) => <option key={val} value={val}>{text}</option>)}</Select>
                </Field>
            );
        case 'textarea':
            return <Field label={f.label} hint={f.hint}><Textarea rows={f.rows ?? 3} placeholder={f.placeholder} {...common} className={f.mono ? 'font-mono text-xs' : ''} /></Field>;
        case 'number':
            return (
                <Field label={f.label} hint={f.hint}>
                    <div className="flex items-center gap-2">
                        <Input type="number" min={f.min} max={f.max} step={f.step} placeholder={f.placeholder} {...common} />
                        {f.suffix && <span className="text-sm text-muted">{f.suffix}</span>}
                    </div>
                </Field>
            );
        case 'range':
            return (
                <Field label={`${f.label}: ${v}${f.suffix ?? ''}`} hint={f.hint}>
                    <input type="range" min={f.min} max={f.max} step={f.step ?? 1} value={v ?? 0} onChange={(e) => set(f.key, e.target.value)} className="w-full accent-[var(--primary)]" />
                </Field>
            );
        case 'datetime':
            return <Field label={f.label} hint={f.hint}><Input type="datetime-local" {...common} /></Field>;
        case 'image':
            return (
                <div>
                    <p className="mb-1 text-sm font-medium">{f.label}</p>
                    <MediaPicker value={v} onChange={(x) => set(f.key, x)} collection={f.collection ?? 'pages'} label={f.buttonLabel ?? 'Select from Media Library'} />
                    {f.hint && <p className="mt-1 text-xs text-muted">{f.hint}</p>}
                </div>
            );
        case 'cards':
            return (
                <div>
                    {f.label && <p className="mb-2 text-sm font-medium">{f.label}</p>}
                    <RadioCards value={v} onChange={(x) => set(f.key, x)} options={f.options} columns={f.columns} label={f.label} />
                    {f.hint && <p className="mt-1 text-xs text-muted">{f.hint}</p>}
                </div>
            );
        default:
            return <Field label={f.label} hint={f.hint}><Input type="text" placeholder={f.placeholder} {...common} /></Field>;
    }
}

/* ----------------------------------------------------------------------- schema -> state */

const flatFields = (blocks) => blocks.flatMap((b) => [
    ...(b.fields ?? []),
    ...(b.toggleKey ? [{ key: b.toggleKey, type: 'toggle', default: b.toggleDefault ?? false }] : []),
    ...(b.extraFields ?? []),
]);

/** Builds state from the schema: server values win, then schema defaults. Only schema keys are ever posted back. */
export function useSchemaSettings(group, blocks, serverValues, extraInitial = {}, extraBooleans = []) {
    const fields = flatFields(blocks);
    const booleans = [...new Set([...fields.filter((f) => f.type === 'toggle').map((f) => f.key), ...extraBooleans])];
    const initial = {};
    fields.forEach((f) => {
        const stored = serverValues[f.key];
        initial[f.key] = stored !== undefined && stored !== null ? stored : (f.default ?? (f.type === 'toggle' ? false : ''));
    });
    Object.assign(initial, extraInitial);
    return useSettings(group, initial, booleans);
}

/* -------------------------------------------------------------------------- renderer */

export function GridFields({ fields, data, set, columns = 2 }) {
    const cls = { 1: 'md:grid-cols-1', 2: 'md:grid-cols-2', 3: 'md:grid-cols-3' }[columns] ?? 'md:grid-cols-2';
    const visible = fields.filter((f) => !f.showIf || f.showIf(data));
    if (visible.length === 0) return null;
    return (
        <div className={`grid grid-cols-1 gap-4 ${cls}`}>
            {visible.map((f) => (
                <div key={f.key} className={f.full ? 'md:col-span-full' : ''}><SchemaField f={f} data={data} set={set} /></div>
            ))}
        </div>
    );
}

/** A section that can collapse and (optionally) carry its own on/off switch. */
function CollapsibleBlock({ b, data, set, ctx }) {
    const [open, setOpen] = useState(!!b.startOpen);
    return (
        <div className="rounded-card border border-line bg-surface">
            <div className="flex items-center justify-between gap-3 p-4 md:px-6">
                <button type="button" onClick={() => setOpen((o) => !o)} aria-expanded={open} className="flex min-w-0 flex-1 items-center gap-3 text-left">
                    <ChevronDown size={18} className={`shrink-0 text-muted transition-transform ${open ? 'rotate-180' : ''}`} />
                    <span>
                        <span className="block font-semibold">{b.title}</span>
                        {b.description && <span className="block text-sm text-muted">{b.description}</span>}
                    </span>
                </button>
                {b.toggleKey && <Toggle checked={!!data[b.toggleKey]} onChange={(v) => set(b.toggleKey, v)} label={<span className="sr-only">Show {b.title}</span>} />}
            </div>
            {open && (
                <div className="space-y-5 border-t border-line p-4 md:p-6">
                    {b.fields && <GridFields fields={b.fields} data={data} set={set} columns={b.columns} />}
                    {b.render && b.render(ctx)}
                </div>
            )}
        </div>
    );
}

export function SchemaBlocks({ blocks, data, set, ctx }) {
    return blocks.map((b, i) => {
        if (b.showIf && !b.showIf(data)) return null;
        if (b.collapsible) return <CollapsibleBlock key={b.id ?? i} b={b} data={data} set={set} ctx={ctx} />;
        return (
            <SettingsCard key={b.id ?? i} title={b.title} description={b.description}>
                <div className="space-y-5">
                    {b.fields && <GridFields fields={b.fields} data={data} set={set} columns={b.columns} />}
                    {b.render && b.render(ctx)}
                </div>
            </SettingsCard>
        );
    });
}

/** Sub-heading used inside blocks. */
export function SubHeading({ children }) {
    return <h3 className="border-t border-line pt-5 text-sm font-semibold first:border-0 first:pt-0">{children}</h3>;
}

/** The whole settings form: schema blocks + save button. */
export default function SchemaForm({ blocks, state, ctx, saveLabel, transform }) {
    const { data, set, submit, processing } = state;
    const context = { ...ctx, data, set };
    return (
        <form onSubmit={(e) => { e.preventDefault(); submit(transform); }} className="space-y-5">
            <SchemaBlocks blocks={blocks} data={data} set={set} ctx={context} />
            <SaveButton processing={processing}>{saveLabel}</SaveButton>
        </form>
    );
}
