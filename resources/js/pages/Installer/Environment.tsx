import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import { Database, Globe2, Loader2, Settings } from 'lucide-react';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { InputError } from '@/components/ui/input-error';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useTranslation } from 'react-i18next';
import { InstallerActions, InstallerShell } from './InstallerShell';

export default function Environment({ timezones }: { timezones: string[] }) {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm({
        app_name: 'StudyRoom TechLab Billing',
        app_url: window.location.origin,
        app_timezone: 'Africa/Nairobi',
        db_connection: 'mysql',
        db_host: '127.0.0.1',
        db_port: '3306',
        db_database: 'wifi_billing',
        db_username: 'root',
        db_password: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('installer.environment.store'), {
            forceFormData: true,
            onSuccess: () => {
                window.location.href = route('installer.database');
            },
        });
    };

    return (
        <>
            <Head title={t('Installation - Environment')} />
            <InstallerShell
                step="environment"
                eyebrow={t('System identity')}
                title={t('Environment configuration')}
                description={t('Tell the installer how your application should identify itself and which database it should connect to.')}
                wide
            >
                <form onSubmit={submit} className="space-y-6">
                    {processing && (
                        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 backdrop-blur-sm">
                            <div className="rounded-3xl border border-white/10 bg-white p-8 text-center shadow-2xl">
                                <Loader2 className="mx-auto mb-4 h-10 w-10 animate-spin text-cyan-700" />
                                <p className="font-bold text-slate-950">{t('Configuring environment...')}</p>
                            </div>
                        </div>
                    )}

                    {Object.keys(errors).length > 0 && (
                        <div className="rounded-3xl border border-rose-200 bg-rose-50 p-5">
                            <h3 className="font-black text-rose-900">{t('Validation errors')}</h3>
                            <div className="mt-2 space-y-1 text-sm text-rose-700">
                                {Object.entries(errors).map(([key, message]) => (
                                    <p key={key}><strong>{key}:</strong> {Array.isArray(message) ? message[0] : message}</p>
                                ))}
                            </div>
                        </div>
                    )}

                    <div className="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                        <div className="mb-5 flex items-center gap-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-950 text-cyan-300">
                                <Globe2 className="h-5 w-5" />
                            </div>
                            <h3 className="text-lg font-black text-slate-950">{t('Application')}</h3>
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2 sm:col-span-2">
                                <Label htmlFor="app_name">{t('Application Name')}</Label>
                                <Input id="app_name" type="text" value={data.app_name} onChange={(e) => setData('app_name', e.target.value)} className="h-12 rounded-2xl" required />
                                <InputError message={errors.app_name} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="app_url">{t('Application URL')}</Label>
                                <Input id="app_url" type="url" value={data.app_url} onChange={(e) => setData('app_url', e.target.value)} className="h-12 rounded-2xl" required />
                                <InputError message={errors.app_url} />
                            </div>
                            <div className="space-y-2">
                                <Label>{t('Timezone')}</Label>
                                <Select value={data.app_timezone} onValueChange={(value) => setData('app_timezone', value)}>
                                    <SelectTrigger className="h-12 rounded-2xl">
                                        <SelectValue placeholder="Select timezone" />
                                    </SelectTrigger>
                                    <SelectContent searchable>
                                        {timezones.map((tz) => (
                                            <SelectItem key={tz} value={tz}>{tz}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.app_timezone} />
                            </div>
                        </div>
                    </div>

                    <div className="rounded-3xl border border-cyan-200 bg-cyan-50 p-5">
                        <div className="mb-5 flex items-center gap-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-2xl bg-cyan-700 text-white">
                                <Database className="h-5 w-5" />
                            </div>
                            <h3 className="text-lg font-black text-cyan-950">{t('Database')}</h3>
                        </div>

                        <div className="space-y-4">
                            <div className="space-y-2">
                                <Label>{t('Database Connection')}</Label>
                                <Select value={data.db_connection} onValueChange={(value) => setData('db_connection', value)}>
                                    <SelectTrigger className="h-12 rounded-2xl bg-white">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="mysql">MySQL</SelectItem>
                                        <SelectItem value="pgsql">PostgreSQL</SelectItem>
                                        <SelectItem value="sqlite">SQLite</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            {data.db_connection !== 'sqlite' && (
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="db_host">{t('Database Host')}</Label>
                                        <Input id="db_host" type="text" value={data.db_host} onChange={(e) => setData('db_host', e.target.value)} className="h-12 rounded-2xl bg-white" required />
                                        <InputError message={errors.db_host} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="db_port">{t('Database Port')}</Label>
                                        <Input id="db_port" type="number" value={data.db_port} onChange={(e) => setData('db_port', e.target.value)} className="h-12 rounded-2xl bg-white" required />
                                        <InputError message={errors.db_port} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="db_database">{t('Database Name')}</Label>
                                        <Input id="db_database" type="text" value={data.db_database} onChange={(e) => setData('db_database', e.target.value)} className="h-12 rounded-2xl bg-white" required />
                                        <InputError message={errors.db_database} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="db_username">{t('Database Username')}</Label>
                                        <Input id="db_username" type="text" value={data.db_username} onChange={(e) => setData('db_username', e.target.value)} className="h-12 rounded-2xl bg-white" required />
                                        <InputError message={errors.db_username} />
                                    </div>
                                    <div className="space-y-2 sm:col-span-2">
                                        <Label htmlFor="db_password">{t('Database Password')}</Label>
                                        <Input id="db_password" type="password" value={data.db_password} onChange={(e) => setData('db_password', e.target.value)} className="h-12 rounded-2xl bg-white" placeholder={t('Leave empty for local XAMPP root user')} />
                                        <InputError message={errors.db_password} />
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>

                    <InstallerActions backHref={route('installer.permissions')} type="submit" nextLabel={processing ? t('Saving...') : t('Save & Continue')} disabled={processing} processing={processing} />
                </form>
            </InstallerShell>
        </>
    );
}
