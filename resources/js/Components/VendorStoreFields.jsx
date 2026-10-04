import { useEffect, useState } from 'react';
import { ImagePlus } from 'lucide-react';
import { Field, Input, Textarea } from './ui';

/** Image picker with a live preview; `current` is the saved image URL. */
function ImageInput({ label, hint, current, file, onChange, error, wide }) {
    const [preview, setPreview] = useState(null);
    useEffect(() => {
        if (!file) { setPreview(null); return undefined; }
        const url = URL.createObjectURL(file);
        setPreview(url);
        return () => URL.revokeObjectURL(url);
    }, [file]);
    const src = preview ?? current;

    return (
        <Field label={label} hint={hint} error={error}>
            <span className={`relative flex cursor-pointer items-center justify-center overflow-hidden rounded-lg border border-dashed border-line bg-surface-2 text-muted hover:border-primary ${wide ? 'h-32 w-full' : 'h-24 w-24'}`}>
                {src ? <img src={src} alt="" className="h-full w-full object-cover" /> : <ImagePlus size={22} />}
                <input type="file" accept="image/*" className="absolute inset-0 cursor-pointer opacity-0" onChange={(e) => onChange(e.target.files[0] ?? null)} />
            </span>
        </Field>
    );
}

/** Store profile fields shared by vendor registration and store settings (works with an Inertia useForm). */
export default function VendorStoreFields({ form, current = {}, withEmail = false }) {
    const { data, setData, errors } = form;
    const f = (k) => ({ value: data[k] ?? '', onChange: (e) => setData(k, e.target.value) });

    return (
        <div className="space-y-4">
            <Field label="Store name *" error={errors.store_name}><Input required maxLength={255} {...f('store_name')} /></Field>
            <Field label="About your store" error={errors.description}><Textarea rows={4} maxLength={5000} {...f('description')} /></Field>
            <div className="grid gap-4 sm:grid-cols-2">
                <Field label="Phone *" error={errors.phone}><Input required type="tel" maxLength={20} {...f('phone')} /></Field>
                {withEmail && <Field label="Store email *" error={errors.email}><Input required type="email" {...f('email')} /></Field>}
            </div>
            <Field label="Address *" error={errors.address}><Input required maxLength={500} {...f('address')} /></Field>
            <div className="grid gap-4 sm:grid-cols-2">
                <Field label="City *" error={errors.city}><Input required maxLength={255} {...f('city')} /></Field>
                <Field label="State / region" error={errors.state}><Input maxLength={255} {...f('state')} /></Field>
                <Field label="Country *" error={errors.country}><Input required maxLength={255} {...f('country')} /></Field>
                <Field label="Postcode" error={errors.zip_code}><Input maxLength={20} {...f('zip_code')} /></Field>
            </div>
            <div className="flex flex-wrap gap-4">
                <ImageInput label="Logo" hint="Square, up to 2 MB" current={current.logo} file={data.logo} onChange={(v) => setData('logo', v)} error={errors.logo} />
                <div className="min-w-56 flex-1">
                    <ImageInput wide label="Banner" hint="Wide image, up to 4 MB" current={current.banner} file={data.banner} onChange={(v) => setData('banner', v)} error={errors.banner} />
                </div>
            </div>
        </div>
    );
}
