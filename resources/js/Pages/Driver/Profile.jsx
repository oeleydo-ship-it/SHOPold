import { useEffect, useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Star, Truck, User, Wallet } from 'lucide-react';
import DriverLayout from '../../Layouts/DriverLayout';
import { Field, Input } from '../../Components/ui';
import { Toggle } from '../../Components/crud';
import { SaveButton, SettingsCard } from '../../Components/settingsUi';

const DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

function Details({ profile }) {
    const { data, setData, post, processing, errors, transform } = useForm({ ...profile, avatar: null });
    const [preview, setPreview] = useState(null);
    useEffect(() => {
        if (!data.avatar) { setPreview(null); return undefined; }
        const url = URL.createObjectURL(data.avatar);
        setPreview(url);
        return () => URL.revokeObjectURL(url);
    }, [data.avatar]);
    const f = (k) => ({ value: data[k] ?? '', onChange: (e) => setData(k, e.target.value) });

    transform((d) => ({ ...d, _method: 'put' }));
    const submit = (e) => { e.preventDefault(); post('/driver/profile', { forceFormData: true, preserveScroll: true, onSuccess: () => setData('avatar', null) }); };
    const photo = preview ?? profile.avatar;

    return (
        <SettingsCard title="Your details">
            <form onSubmit={submit} className="space-y-4">
                <label className="flex cursor-pointer items-center gap-4">
                    <span className="flex h-16 w-16 items-center justify-center overflow-hidden rounded-full bg-surface-2 text-muted">{photo ? <img src={photo} alt="" className="h-full w-full object-cover" /> : <User size={24} />}</span>
                    <span className="text-sm text-primary">Change photo</span>
                    <input type="file" accept="image/*" className="sr-only" onChange={(e) => setData('avatar', e.target.files[0] ?? null)} />
                </label>
                {errors.avatar && <p className="text-xs text-danger">{errors.avatar}</p>}
                <div className="grid gap-4 sm:grid-cols-2">
                    <Field label="Name *" error={errors.name}><Input required maxLength={255} autoComplete="name" {...f('name')} /></Field>
                    <Field label="Email *" error={errors.email}><Input required type="email" autoComplete="email" {...f('email')} /></Field>
                    <Field label="Phone *" error={errors.driver_phone}><Input required type="tel" maxLength={20} autoComplete="tel" {...f('driver_phone')} /></Field>
                    <Field label="Vehicle" error={errors.driver_vehicle}><Input maxLength={100} placeholder="e.g. Scooter, blue" {...f('driver_vehicle')} /></Field>
                    <Field label="Licence number" error={errors.driver_license}><Input maxLength={50} {...f('driver_license')} /></Field>
                </div>
                <SaveButton processing={processing}>Save details</SaveButton>
            </form>
        </SettingsCard>
    );
}

function Schedule({ schedule }) {
    const { data, setData, put, processing, errors } = useForm({ schedule });
    const set = (day, k, v) => setData('schedule', { ...data.schedule, [day]: { ...data.schedule[day], [k]: v } });
    const submit = (e) => { e.preventDefault(); put('/driver/profile/schedule', { preserveScroll: true }); };

    return (
        <SettingsCard title="When you work" description="The store uses this to decide who to assign deliveries to.">
            <form onSubmit={submit} className="space-y-2">
                {DAYS.map((day) => {
                    const d = data.schedule[day];
                    return (
                        <div key={day} className="flex flex-wrap items-center gap-3 rounded-lg border border-line p-2">
                            <div className="w-32"><Toggle label={day[0].toUpperCase() + day.slice(1)} checked={d.enabled} onChange={(v) => set(day, 'enabled', v)} /></div>
                            <div className={`flex items-center gap-2 ${d.enabled ? '' : 'opacity-40'}`}>
                                <Input type="time" disabled={!d.enabled} aria-label={`${day} start`} className="!w-28" value={d.start ?? ''} onChange={(e) => set(day, 'start', e.target.value)} />
                                <span className="text-muted">–</span>
                                <Input type="time" disabled={!d.enabled} aria-label={`${day} end`} className="!w-28" value={d.end ?? ''} onChange={(e) => set(day, 'end', e.target.value)} />
                            </div>
                        </div>
                    );
                })}
                {Object.values(errors)[0] && <p className="text-xs text-danger">{Object.values(errors)[0]}</p>}
                <div className="pt-2"><SaveButton processing={processing}>Save schedule</SaveButton></div>
            </form>
        </SettingsCard>
    );
}

function Password() {
    const { data, setData, put, processing, errors, reset } = useForm({ current_password: '', password: '', password_confirmation: '' });
    const submit = (e) => { e.preventDefault(); put('/driver/profile/password', { preserveScroll: true, onFinish: () => reset() }); };
    return (
        <SettingsCard title="Password">
            <form onSubmit={submit} className="space-y-4">
                <Field label="Current password" error={errors.current_password}><Input type="password" required autoComplete="current-password" value={data.current_password} onChange={(e) => setData('current_password', e.target.value)} /></Field>
                <div className="grid gap-4 sm:grid-cols-2">
                    <Field label="New password" hint="At least 8 characters" error={errors.password}><Input type="password" required minLength={8} autoComplete="new-password" value={data.password} onChange={(e) => setData('password', e.target.value)} /></Field>
                    <Field label="Repeat new password"><Input type="password" required autoComplete="new-password" value={data.password_confirmation} onChange={(e) => setData('password_confirmation', e.target.value)} /></Field>
                </div>
                <SaveButton processing={processing}>Change password</SaveButton>
            </form>
        </SettingsCard>
    );
}

export default function Profile({ profile, stats, schedule }) {
    return (
        <DriverLayout title="Profile">
            <Head title="Profile" />
            <div className="mb-5 grid grid-cols-3 gap-2 text-center">
                <div className="rounded-card border border-line bg-surface p-3"><Truck size={18} className="mx-auto text-primary" /><p className="mt-1 font-semibold">{stats.deliveries}</p><p className="text-xs text-muted">Deliveries</p></div>
                <div className="rounded-card border border-line bg-surface p-3"><Star size={18} className="mx-auto text-warning" /><p className="mt-1 font-semibold">{stats.rating ?? '–'}</p><p className="text-xs text-muted">{stats.ratings} ratings</p></div>
                <div className="rounded-card border border-line bg-surface p-3"><Wallet size={18} className="mx-auto text-success" /><p className="mt-1 font-semibold">{stats.earnings}</p><p className="text-xs text-muted">{stats.commission}% of delivery fees</p></div>
            </div>
            <div className="grid max-w-5xl gap-5 xl:grid-cols-2">
                <div className="space-y-5"><Details profile={profile} /><Password /></div>
                <Schedule schedule={schedule} />
            </div>
        </DriverLayout>
    );
}
