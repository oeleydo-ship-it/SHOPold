import { Save } from 'lucide-react';
import { Button, Card } from './ui';
import { Toggle } from './crud';

export function SettingsCard({ title, description, children }) {
    return (
        <Card className="p-4 md:p-6">
            {title && <h2 className="text-lg font-bold">{title}</h2>}
            {description && <p className="mt-1 text-sm text-muted">{description}</p>}
            <div className={title || description ? 'mt-5' : ''}>{children}</div>
        </Card>
    );
}

/** A bordered row with a title, helper text and a switch on the right. */
export function SwitchRow({ title, description, checked, onChange, disabled }) {
    return (
        <div className={`flex items-center justify-between gap-4 rounded-lg border border-line p-4 ${disabled ? 'opacity-60' : 'hover:bg-surface-2'}`}>
            <div>
                <h4 className="font-semibold">{title}</h4>
                {description && <p className="text-sm text-muted">{description}</p>}
            </div>
            <Toggle checked={checked} onChange={(v) => !disabled && onChange(v)} label={<span className="sr-only">{title}</span>} />
        </div>
    );
}

export function SaveButton({ processing, children = 'Save Settings' }) {
    return <Button type="submit" disabled={processing}><Save size={16} />{processing ? 'Saving…' : children}</Button>;
}
