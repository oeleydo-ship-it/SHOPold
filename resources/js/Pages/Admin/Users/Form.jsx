import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { Button, Card, CardHeader, Field, Input, Select, Textarea } from '../../../Components/ui';
import { Toggle } from '../../../Components/crud';

const s = (v) => (v === null || v === undefined ? '' : String(v));

export default function Form({ user, options }) {
    const edit = !!user;
    const self = !!user?.is_self;
    const form = useForm({
        name: s(user?.name), email: s(user?.email), phone: s(user?.phone),
        date_of_birth: s(user?.date_of_birth), gender: s(user?.gender), company: s(user?.company), website: s(user?.website),
        address: s(user?.address), city: s(user?.city), state: s(user?.state), zip_code: s(user?.zip_code), country: s(user?.country),
        password: '', password_confirmation: '',
        is_active: user?.is_active ?? true,
        is_admin: user?.is_admin ?? false,
        admin_role: user?.admin_role ?? 'manager',
        is_driver: user?.is_driver ?? false,
        driver_phone: s(user?.driver_phone), driver_vehicle: s(user?.driver_vehicle), driver_license: s(user?.driver_license),
        driver_status: user?.driver_status ?? 'offline',
        notes: s(user?.notes),
    });
    const { data, setData, errors, processing } = form;
    const f = (k) => ({ value: data[k], onChange: (e) => setData(k, e.target.value) });

    const submit = (e) => {
        e.preventDefault();
        if (edit) form.put(`/admin/users/${user.id}`);
        else form.post('/admin/users');
    };

    const title = edit ? `Edit User: ${user.name}` : 'Add User';
    // A super admin cannot demote themselves; the server also enforces this.
    const roleLocked = self && user?.admin_role === 'super_admin';

    return (
        <AdminLayout title={edit ? 'Edit User' : 'Add User'}>
            <Head title={title} />
            <Link href="/admin/users" className="mb-5 inline-flex items-center gap-2 text-sm text-muted hover:text-ink"><ArrowLeft size={16} />Back to Users</Link>

            <form onSubmit={submit} className="grid gap-5 lg:grid-cols-3">
                <div className="space-y-5 lg:col-span-2">
                    <Card>
                        <CardHeader title="Basic Information" />
                        <div className="grid gap-4 p-4 md:grid-cols-2 md:p-6">
                            <Field label="Full Name *" error={errors.name}><Input {...f('name')} required /></Field>
                            <Field label="Email Address *" error={errors.email}><Input type="email" {...f('email')} required /></Field>
                            <Field label="Phone Number" error={errors.phone}><Input {...f('phone')} /></Field>
                            <Field label="Date of Birth" error={errors.date_of_birth}><Input type="date" {...f('date_of_birth')} /></Field>
                            <Field label="Gender" error={errors.gender}>
                                <Select {...f('gender')}>
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </Select>
                            </Field>
                            <Field label="Company" error={errors.company}><Input {...f('company')} /></Field>
                            <Field label="Website" error={errors.website} className="md:col-span-2"><Input type="url" placeholder="https://" {...f('website')} /></Field>
                        </div>
                    </Card>

                    <Card>
                        <CardHeader title="Address Information" />
                        <div className="grid gap-4 p-4 md:grid-cols-2 md:p-6">
                            <Field label="Street Address" error={errors.address} className="md:col-span-2"><Textarea rows={2} {...f('address')} /></Field>
                            <Field label="City" error={errors.city}><Input {...f('city')} /></Field>
                            <Field label="State / Province" error={errors.state}><Input {...f('state')} /></Field>
                            <Field label="ZIP / Postal Code" error={errors.zip_code}><Input {...f('zip_code')} /></Field>
                            <Field label="Country" error={errors.country}><Input {...f('country')} /></Field>
                        </div>
                    </Card>

                    <Card>
                        <CardHeader title={edit ? 'Change Password' : 'Password'} />
                        <div className="grid gap-4 p-4 md:grid-cols-2 md:p-6">
                            <Field label={edit ? 'New Password' : 'Password *'} hint={edit ? 'Leave blank to keep the current password' : 'At least 8 characters'} error={errors.password}>
                                <Input type="password" autoComplete="new-password" minLength={8} required={!edit} {...f('password')} />
                            </Field>
                            <Field label="Confirm Password" error={errors.password_confirmation}>
                                <Input type="password" autoComplete="new-password" required={!edit} {...f('password_confirmation')} />
                            </Field>
                        </div>
                    </Card>
                </div>

                <div className="space-y-5">
                    <Card>
                        <CardHeader title="Status & Role" />
                        <div className="space-y-4 p-4 md:p-6">
                            <Toggle label="Active" checked={data.is_active} onChange={(v) => setData('is_active', v)} />
                            <div>
                                <Toggle label="Administrator" checked={data.is_admin} onChange={(v) => !self && setData('is_admin', v)} />
                                {self && <p className="mt-1 text-xs text-muted">You cannot remove your own admin access.</p>}
                                {data.is_admin && (
                                    <div className="mt-3 rounded-lg bg-surface-2 p-3">
                                        <Field label="Admin Role" error={errors.admin_role}>
                                            <Select {...f('admin_role')} disabled={roleLocked}>
                                                {options.canAssignSuper && <option value="super_admin">Super Admin - Full access</option>}
                                                <option value="manager">Manager - Orders, Products, Users</option>
                                                <option value="editor">Editor - Products &amp; Content only</option>
                                            </Select>
                                        </Field>
                                    </div>
                                )}
                            </div>
                            {options.driverPortal && <Toggle label="Delivery driver" checked={data.is_driver} onChange={(v) => setData('is_driver', v)} />}
                        </div>
                    </Card>

                    {options.driverPortal && data.is_driver && (
                        <Card>
                            <CardHeader title="Driver Information" />
                            <div className="space-y-4 p-4 md:p-6">
                                <Field label="Driver Phone" error={errors.driver_phone}><Input {...f('driver_phone')} /></Field>
                                <Field label="Vehicle Type" error={errors.driver_vehicle}><Input {...f('driver_vehicle')} /></Field>
                                <Field label="License Number" error={errors.driver_license}><Input {...f('driver_license')} /></Field>
                                <Field label="Status" error={errors.driver_status}>
                                    <Select {...f('driver_status')}>
                                        <option value="offline">Offline</option>
                                        <option value="available">Available</option>
                                        <option value="busy">Busy</option>
                                    </Select>
                                </Field>
                                {edit && user.stats.assigned_deliveries !== null && (
                                    <div className="border-t border-line pt-3 text-sm text-muted">
                                        <p>Assigned deliveries: <span className="font-medium text-ink">{user.stats.assigned_deliveries}</span></p>
                                        <p>Completed deliveries: <span className="font-medium text-ink">{user.stats.completed_deliveries}</span></p>
                                    </div>
                                )}
                            </div>
                        </Card>
                    )}

                    <Card>
                        <CardHeader title="Admin Notes" />
                        <div className="p-4 md:p-6">
                            <p className="mb-2 text-xs text-muted">Internal notes (not visible to the customer)</p>
                            <Textarea rows={4} placeholder="Add notes about this customer…" {...f('notes')} aria-label="Admin notes" />
                        </div>
                    </Card>

                    {edit && (
                        <Card>
                            <CardHeader title="Statistics" />
                            <dl className="space-y-3 p-4 text-sm md:p-6">
                                {[['Total Orders', user.stats.orders], ['Total Spent', user.stats.spent], ['Member Since', user.stats.since], ['Last Login', user.stats.last_login ?? 'Never']].map(([k, v]) => (
                                    <div key={k} className="flex justify-between"><dt className="text-muted">{k}</dt><dd className="font-medium">{v}</dd></div>
                                ))}
                            </dl>
                        </Card>
                    )}

                    <Button type="submit" className="w-full py-3" disabled={processing}><Save size={16} />{edit ? 'Update User' : 'Create User'}</Button>
                </div>
            </form>
        </AdminLayout>
    );
}
