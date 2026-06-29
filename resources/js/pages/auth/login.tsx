import { FormEventHandler, useEffect } from "react";
import AuthLayout from "@/layouts/auth-layout";
import { Head, Link, useForm } from "@inertiajs/react";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import InputError from "@/components/ui/input-error";
import { Checkbox } from "@/components/ui/checkbox";
import { ArrowRight, LockKeyhole, Mail } from "lucide-react";

import { useTranslation } from 'react-i18next';
import { useFormFields } from '@/hooks/useFormFields';
import { usePageButtons } from '@/hooks/usePageButtons';

export default function Login({
    status,
    canResetPassword,
    enableRegistration,
}: {
    status?: string;
    canResetPassword: boolean;
    enableRegistration?: boolean;
}) {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors, reset } = useForm({
        email: "",
        password: "",
        remember: false,
        recaptcha_token: null,
    });

    const formFields = useFormFields('getReCaptchFields', data, setData, errors, 'create', t);
    const loginButtons = usePageButtons('getLoginButtons', { t, isLoading: processing });

    useEffect(() => {
        return () => {
            reset("password");
        };
    }, []);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route("login"));
    };

    return (
        <AuthLayout
            title={t('Welcome back')}
            description={t('Sign in to continue to StudyRoom Connect operations.')}
        >
            <Head title={t('Log in')} />

            {status && (
                <div className="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                    {status}
                </div>
            )}

            <form onSubmit={submit} className="flex flex-col gap-6">
                <div className="grid gap-5">
                    <div className="grid gap-2">
                        <Label htmlFor="email" className="text-sm font-bold text-slate-800 dark:text-slate-100">
                            {t('Email Address')}
                        </Label>
                        <div className="relative">
                            <Mail className="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                required
                                autoFocus
                                tabIndex={1}
                                autoComplete="email"
                                placeholder="admin@company.com"
                                className="h-12 rounded-2xl border-slate-200 bg-white pl-11 text-base shadow-sm transition focus:border-emerald-400 focus:ring-emerald-400/20 dark:border-white/10 dark:bg-white/[0.04]"
                            />
                        </div>
                        <InputError message={errors.email} />
                    </div>

                    <div className="grid gap-2">
                        <div className="flex items-center">
                            <Label htmlFor="password" className="text-sm font-bold text-slate-800 dark:text-slate-100">
                                {t('Password')}
                            </Label>
                            {canResetPassword && (
                                <Link
                                    href={route('password.request')}
                                    className="ml-auto text-sm font-semibold text-emerald-600 hover:text-emerald-700 hover:underline dark:text-emerald-300"
                                    tabIndex={5}
                                >
                                    {t('Forgot password?')}
                                </Link>
                            )}
                        </div>
                        <div className="relative">
                            <LockKeyhole className="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                            <Input
                                id="password"
                                type="password"
                                name="password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                required
                                tabIndex={2}
                                autoComplete="current-password"
                                placeholder={t('Enter your password')}
                                className="h-12 rounded-2xl border-slate-200 bg-white pl-11 text-base shadow-sm transition focus:border-emerald-400 focus:ring-emerald-400/20 dark:border-white/10 dark:bg-white/[0.04]"
                            />
                        </div>
                        <InputError message={errors.password} />
                    </div>

                    <div className="flex items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm dark:border-white/10 dark:bg-white/[0.04]">
                        <div className="flex items-center space-x-3">
                            <Checkbox
                                id="remember"
                                name="remember"
                                checked={data.remember}
                                onCheckedChange={(checked) => setData('remember', !!checked)}
                                tabIndex={3}
                                className="border-emerald-300 data-[state=checked]:bg-emerald-600"
                            />
                            <Label htmlFor="remember" className="text-sm font-semibold text-slate-700 dark:text-slate-200">
                                {t('Remember this device')}
                            </Label>
                        </div>
                        <span className="hidden text-xs font-medium text-slate-400 sm:inline">{t('Secure session')}</span>
                    </div>

                    {formFields.map((field) => (
                        <div key={field.id}>
                            {field.component}
                        </div>
                    ))}

                    <Button
                        type="submit"
                        className="h-12 w-full rounded-2xl text-base font-bold shadow-lg shadow-emerald-500/20 transition hover:-translate-y-0.5"
                        tabIndex={4}
                        disabled={processing}
                        data-test="login-button"
                    >
                        <span>{processing ? t('Checking access...') : t('Log in to workspace')}</span>
                        {!processing && <ArrowRight className="ml-2 h-4 w-4" />}
                    </Button>

                    {loginButtons.length > 0 && (
                        <div className="space-y-3">
                            <div className="relative py-2">
                                <div className="absolute inset-0 flex items-center">
                                    <span className="w-full border-t border-slate-200 dark:border-white/10" />
                                </div>
                                <div className="relative flex justify-center text-xs uppercase tracking-[0.2em]">
                                    <span className="bg-white px-3 text-slate-400 dark:bg-slate-900">{t('Or continue with')}</span>
                                </div>
                            </div>
                            {loginButtons.map((button) => (
                                <div key={button.id}>
                                    {button.component}
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                {enableRegistration && (
                    <div className="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-4 text-center text-sm text-slate-600 dark:border-white/10 dark:bg-white/[0.03] dark:text-slate-300">
                        {t("Don't have an account?")}{' '}
                        <Link href={route('register')} tabIndex={6} className="font-bold text-emerald-600 hover:text-emerald-700 hover:underline dark:text-emerald-300">
                            {t('Create workspace')}
                        </Link>
                    </div>
                )}
            </form>
        </AuthLayout>
    );
}
