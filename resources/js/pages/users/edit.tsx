import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm, usePage } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import InputError from "@/components/ui/input-error";
import { PhoneInputComponent } from "@/components/ui/phone-input";
import { EditUserProps, EditUserFormData } from './types';

export default function Edit({ user, onSuccess, roles = {} }: EditUserProps) {
    const { t } = useTranslation();
    const { auth } = usePage().props as any;
    const { data, setData, put, processing, errors } = useForm<EditUserFormData>({
        name: user.name,
        email: user.email,
        mobile_no: user.mobile_no,
        role_id: user.role_id ? String(user.role_id) : '',
        is_enable_login: user.is_enable_login,
    });
    const canAssignRole = auth.user?.type !== 'superadmin' && Object.keys(roles).length > 0;

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('users.update', user.id), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Edit User')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="edit_name">{t('Name')}</Label>
                    <Input
                        id="edit_name"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        placeholder={t('Enter full name')}
                        required
                    />
                    <InputError message={errors.name} />
                </div>
                <div>
                    <Label htmlFor="edit_email">{t('Email')}</Label>
                    <Input
                        id="edit_email"
                        type="email"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        placeholder={t('Enter email address')}
                        required
                    />
                    <InputError message={errors.email} />
                </div>
                <div>
                    <PhoneInputComponent
                        label={t('Mobile Number')}
                        value={data.mobile_no}
                        onChange={(value) => setData('mobile_no', value)}
                        placeholder="+1234567890"
                        error={errors.mobile_no}
                    />
                </div>

                {canAssignRole && (
                    <div>
                        <Label htmlFor="edit_role_id" required>{t('Role/Profile')}</Label>
                        <p className="text-xs text-gray-500 mb-2">
                            {t('Select a role/profile for this staff member. Permissions can be managed from Roles.')}
                        </p>
                        <Select value={data.role_id} onValueChange={(value) => setData('role_id', value)}>
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {Object.entries(roles).map(([id, label]) => (
                                    <SelectItem key={id} value={id}>
                                        {label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.role_id} />
                    </div>
                )}

                <div>
                    <Label htmlFor="edit_is_enable_login">{t('Login Status')}</Label>
                    <Select value={data.is_enable_login ? "1" : "0"} onValueChange={(value) => setData('is_enable_login', value === "1")}>
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="1">{t('Enabled')}</SelectItem>
                            <SelectItem value="0">{t('Disabled')}</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={errors.is_enable_login} />
                </div>
                <div className="flex justify-end gap-2">
                    <Button type="button" variant="outline" onClick={onSuccess}>
                        {t('Cancel')}
                    </Button>
                    <Button type="submit" disabled={processing}>
                        {processing ? t('Updating...') : t('Update')}
                    </Button>
                </div>
            </form>
        </DialogContent>
    );
}
