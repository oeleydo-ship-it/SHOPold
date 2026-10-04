import { ShieldCheck } from 'lucide-react';
import { Field, Input, Textarea } from './ui';

/**
 * Write-only input for API keys / passwords. The saved value is never sent to the browser:
 * `stored` says whether one exists. Leave blank to keep it; type to replace it; "Remove" clears it.
 */
export default function SecretField({ label, stored, secret, onChange, onClear, placeholder, hint, multiline = false }) {
    const removing = secret.clear;
    const hidden = stored && !removing ? '•••••••• (saved — leave blank to keep)' : placeholder;
    const Control = multiline ? Textarea : Input;
    return (
        <Field label={label} hint={hint}>
            <Control
                {...(multiline ? { rows: 3 } : { type: 'password' })}
                autoComplete="new-password"
                value={secret.value}
                disabled={removing}
                onChange={(e) => onChange(e.target.value)}
                placeholder={hidden}
            />
            {stored && (
                <span className="mt-1 flex items-center gap-2 text-xs">
                    {removing ? (
                        <>
                            <span className="text-danger">Saved value will be removed when you save.</span>
                            <button type="button" onClick={() => onClear(false)} className="text-primary underline">Undo</button>
                        </>
                    ) : (
                        <>
                            <span className="flex items-center gap-1 text-success"><ShieldCheck size={12} />A value is saved</span>
                            <button type="button" onClick={() => onClear(true)} className="text-danger underline">Remove</button>
                        </>
                    )}
                </span>
            )}
        </Field>
    );
}
