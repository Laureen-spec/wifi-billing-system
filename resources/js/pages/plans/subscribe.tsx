import { Head, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useFlashMessages } from '@/hooks/useFlashMessages';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { CheckCircle2, Clock, CreditCard, FileUp, Percent, RadioTower, Receipt, ShieldCheck, Users, Wifi } from 'lucide-react';

interface Plan {
    id: number;
    name: string;
    description?: string;
    number_of_users: number;
    custom_plan: boolean;
    status: boolean;
    free_plan: boolean;
    modules: string[];
    package_price_yearly: number;
    package_price_monthly: number;
    trial: boolean;
    trial_days: number;
    hotspot_revenue_fee_percent?: number;
}

interface Module {
    module: string;
    alias: string;
    image: string;
    monthly_price: number;
    yearly_price: number;
}

interface CurrencySettings {
    code?: string;
    symbol?: string;
    symbolPosition?: 'before' | 'after' | string;
    symbolSpace?: boolean;
    decimalPlaces?: number;
    decimalSeparator?: string;
    thousandSeparator?: string;
}

interface Props {
    plan: Plan;
    activeModules: Module[];
    userActiveModules: string[];
    bankTransferEnabled: string | boolean;
    bankTransferInstructions: string;
    mpesaPaymentAvailable?: boolean;
    mpesaMissingMessage?: string;
    planExpireDate?: string;
    planUsage?: {
        pppoeUsers: number;
        routersCount: number;
        hotspotRevenueThisMonth: number;
        hotspotRevenueFeePercent?: number;
        hotspotRevenueFeeThisMonth?: number;
    };
    checkoutInvoice?: {
        hotspotRevenueThisMonth: number;
        hotspotRevenueFeePercent: number;
        hotspotRevenueFeeAmount: number;
        note?: string;
    };
    currencySettings?: CurrencySettings;
}

export default function Subscribe({ plan, activeModules, bankTransferEnabled, bankTransferInstructions, mpesaPaymentAvailable = false, mpesaMissingMessage = 'Payment gateway is not configured. Please contact platform support.', planExpireDate, planUsage, checkoutInvoice, currencySettings }: Props) {
    const { t } = useTranslation();
    const { auth } = usePage().props as any;
    const [pricingPeriod, setPricingPeriod] = useState<'monthly' | 'yearly'>('monthly');
    const [couponCode, setCouponCode] = useState('');
    const [couponDiscount, setCouponDiscount] = useState(0);
    const [couponError, setCouponError] = useState('');
    const [isApplyingCoupon, setIsApplyingCoupon] = useState(false);
    const [selectedPaymentMethod, setSelectedPaymentMethod] = useState<string>(mpesaPaymentAvailable ? 'mpesa' : (bankTransferEnabled === true || bankTransferEnabled === 'on' ? 'bank_transfer' : 'none'));
    const [receiptFile, setReceiptFile] = useState<File | null>(null);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [fileError, setFileError] = useState('');
    const [mpesaPhone, setMpesaPhone] = useState(String(auth?.user?.phone || auth?.user?.phone_number || auth?.user?.mobile || ''));
    const [mpesaStatus, setMpesaStatus] = useState('');
    const [mpesaError, setMpesaError] = useState('');

    useFlashMessages();

    const formatCurrency = (amount: number | string) => {
        const settings = currencySettings || {};
        const num = Number(amount || 0);
        const decimalPlaces = Number.isFinite(Number(settings.decimalPlaces)) ? Number(settings.decimalPlaces) : 0;
        const decimalSeparator = settings.decimalSeparator || '.';
        const thousandSeparator = settings.thousandSeparator ?? ',';
        const symbol = settings.symbol || settings.code || 'KES';
        const symbolPosition = settings.symbolPosition || 'before';
        const space = settings.symbolSpace === false ? '' : ' ';
        const fixed = num.toFixed(decimalPlaces);
        const parts = fixed.split('.');
        if (thousandSeparator) {
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousandSeparator);
        }
        const formatted = parts.join(decimalSeparator);
        return symbolPosition === 'after' ? `${formatted}${space}${symbol}` : `${symbol}${space}${formatted}`;
    };

    const includedModules = useMemo(() => activeModules.filter((module) => plan.modules?.includes(module.module)), [activeModules, plan.modules]);
    const baseAmount = pricingPeriod === 'monthly' ? Number(plan.package_price_monthly || 0) : Number(plan.package_price_yearly || 0);
    const hotspotRevenueAmount = Number(checkoutInvoice?.hotspotRevenueThisMonth ?? planUsage?.hotspotRevenueThisMonth ?? 0);
    const hotspotFeePercent = Number(checkoutInvoice?.hotspotRevenueFeePercent ?? planUsage?.hotspotRevenueFeePercent ?? plan.hotspot_revenue_fee_percent ?? 2.5);
    const hotspotRevenueFeeAmount = Number(checkoutInvoice?.hotspotRevenueFeeAmount ?? planUsage?.hotspotRevenueFeeThisMonth ?? ((hotspotRevenueAmount * hotspotFeePercent) / 100).toFixed(2));
    const subscriptionAfterDiscount = Math.max(0, baseAmount - couponDiscount);
    const totalDue = Math.max(0, subscriptionAfterDiscount + hotspotRevenueFeeAmount);
    const periodLabel = pricingPeriod === 'monthly' ? t('month') : t('year');
    const pppoeLimit = Number(plan.number_of_users || 0);
    const pppoeLabel = pppoeLimit === -1 || pppoeLimit >= 1000 ? t('Unlimited PPPoE users') : pppoeLimit <= 0 ? t('Setup/testing only') : `${pppoeLimit.toLocaleString()} ${t('PPPoE users')}`;
    const bankEnabled = bankTransferEnabled === true || bankTransferEnabled === 'on';
    const mpesaEnabled = Boolean(mpesaPaymentAvailable);
    const noGatewayAvailable = !mpesaEnabled && !bankEnabled && !plan.free_plan;

    const applyCoupon = async () => {
        if (!couponCode.trim()) return;
        setIsApplyingCoupon(true);
        setCouponError('');

        try {
            const response = await fetch(route('plans.apply-coupon'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ coupon_code: couponCode, total_amount: baseAmount }),
            });
            const data = await response.json();
            if (data.success) {
                setCouponDiscount(Number(data.discount_amount || 0));
            } else {
                setCouponDiscount(0);
                setCouponError(data.message || t('Invalid coupon'));
            }
        } catch {
            setCouponDiscount(0);
            setCouponError(t('Failed to apply coupon'));
        } finally {
            setIsApplyingCoupon(false);
        }
    };

    const startTrial = () => {
        router.post(route('plans.start-trial', plan.id), {}, { preserveScroll: true });
    };

    const pollMpesaTransaction = (transactionId: number) => {
        let attempts = 0;
        const maxAttempts = 30;

        const timer = window.setInterval(async () => {
            attempts += 1;

            try {
                const response = await fetch(route('mpesa-payment.plan-subscription.status', transactionId), {
                    headers: {
                        Accept: 'application/json',
                    },
                });
                const data = await response.json();
                const status = data?.transaction?.status;

                if (status === 'paid') {
                    window.clearInterval(timer);
                    setIsSubmitting(false);
                    setMpesaStatus(t('Payment confirmed. Activating your subscription...'));
                    router.visit(route('plans.index'));
                    return;
                }

                if (['failed', 'cancelled', 'expired'].includes(status)) {
                    window.clearInterval(timer);
                    setIsSubmitting(false);
                    setMpesaError(data?.transaction?.result_desc || t('M-Pesa payment was not completed.'));
                    return;
                }

                if (attempts >= maxAttempts) {
                    window.clearInterval(timer);
                    setIsSubmitting(false);
                    setMpesaStatus(t('Still waiting for M-Pesa confirmation. Check your phone or transaction list.'));
                }
            } catch {
                if (attempts >= maxAttempts) {
                    window.clearInterval(timer);
                    setIsSubmitting(false);
                    setMpesaError(t('Could not confirm M-Pesa payment status.'));
                }
            }
        }, 4000);
    };

    const payWithMpesa = async () => {
        setFileError('');
        setMpesaError('');
        setMpesaStatus('');

        if (!mpesaEnabled) {
            setMpesaError(mpesaMissingMessage);
            return;
        }

        if (!mpesaPhone.trim()) {
            setMpesaError(t('Enter admin M-Pesa phone number'));
            return;
        }

        setIsSubmitting(true);
        setMpesaStatus(t('Sending M-Pesa STK Push...'));

        try {
            const response = await fetch(route('mpesa-payment.plan-subscription.stk-push'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    plan_id: plan.id,
                    time_period: pricingPeriod === 'monthly' ? 'Month' : 'Year',
                    phone: mpesaPhone,
                    coupon_code: couponCode || '',
                }),
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || t('Failed to send M-Pesa STK Push.'));
            }

            setMpesaStatus(data.message || t('STK Push sent. Enter your M-Pesa PIN.'));

            if (data?.transaction?.id) {
                pollMpesaTransaction(Number(data.transaction.id));
            } else {
                setIsSubmitting(false);
                setMpesaError(t('M-Pesa transaction ID was not returned.'));
            }
        } catch (error: any) {
            setIsSubmitting(false);
            setMpesaError(error?.message || t('M-Pesa request failed.'));
        }
    };


    const subscribe = () => {
        setFileError('');

        if (plan.free_plan) {
            router.post(route('plans.assign-free', plan.id), { duration: pricingPeriod === 'yearly' ? 'Year' : 'Month' }, { preserveScroll: true });
            return;
        }

        if (selectedPaymentMethod === 'mpesa') {
            payWithMpesa();
            return;
        }

        if (bankEnabled && selectedPaymentMethod === 'bank_transfer') {
            if (!receiptFile) {
                setFileError(t('Please upload payment receipt'));
                return;
            }

            setIsSubmitting(true);
            const formData = new FormData();
            formData.append('plan_id', String(plan.id));
            formData.append('user_counter_input', String(plan.number_of_users || 0));
            formData.append('storage_counter_input', '0');
            formData.append('storage_limit_input', '0');
            formData.append('userprice_input', '0');
            formData.append('storage_price_input', '0');
            formData.append('user_module_price_input', '0');
            formData.append('time_period', pricingPeriod === 'monthly' ? 'Month' : 'Year');
            formData.append('user_module_input', (plan.modules || []).join(','));
            formData.append('payment_receipt', receiptFile);
            if (couponCode) formData.append('coupon_code', couponCode);
            formData.append('hotspot_revenue_amount', String(hotspotRevenueAmount));
            formData.append('hotspot_revenue_fee_percent', String(hotspotFeePercent));
            formData.append('hotspot_revenue_fee_amount', String(hotspotRevenueFeeAmount));
            formData.append('invoice_total_due', String(totalDue));

            router.post(route('payment.bank-transfer.store'), formData, {
                forceFormData: true,
                preserveScroll: true,
                onFinish: () => setIsSubmitting(false),
            });
            return;
        }

        router.post(route('subscriptions.store'), {
            planId: plan.id,
            totalPrice: totalDue,
            hotspot_revenue_amount: hotspotRevenueAmount,
            hotspot_revenue_fee_percent: hotspotFeePercent,
            hotspot_revenue_fee_amount: hotspotRevenueFeeAmount,
            time_period: pricingPeriod === 'monthly' ? 'Month' : 'Year',
            user_module_input: (plan.modules || []).join(','),
            coupon_code: couponCode || '',
        }, { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('Subscription Plans'), url: route('plans.index') },
                { label: `${t('Subscribe to')} ${plan.name}` },
            ]}
            pageTitle={`${t('Subscribe to')} ${plan.name}`}
        >
            <Head title={`${t('Subscribe to')} ${plan.name}`} />

            <div className="space-y-6">
                <div className="rounded-[30px] border border-slate-200 bg-white p-6 shadow-sm">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <div className="mb-3 inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-sm font-semibold text-emerald-700">
                                <Wifi className="h-4 w-4" />
                                {t('ISP subscription checkout')}
                            </div>
                            <h1 className="text-3xl font-bold tracking-tight text-slate-950">{t('Subscribe to')} {plan.name}</h1>
                            <p className="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                                {t('Confirm your ISP billing package before activating. PPPoE billing is a flat platform fee; hotspot revenue fee is added only on successful hotspot payments collected through the system.')}
                            </p>
                        </div>
                        <div className="inline-flex rounded-2xl bg-slate-100 p-1">
                            <button type="button" onClick={() => setPricingPeriod('monthly')} className={`rounded-xl px-5 py-2 text-sm font-semibold ${pricingPeriod === 'monthly' ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-500'}`}>{t('Monthly')}</button>
                            <button type="button" onClick={() => setPricingPeriod('yearly')} className={`rounded-xl px-5 py-2 text-sm font-semibold ${pricingPeriod === 'yearly' ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-500'}`}>{t('Yearly')}</button>
                        </div>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-[1.25fr_0.75fr]">
                    <div className="space-y-6">
                        <Card className="rounded-[28px] border-slate-200 shadow-sm">
                            <CardHeader>
                                <CardTitle className="text-2xl">{t('Plan summary')}</CardTitle>
                                <p className="text-sm text-slate-500">{plan.description || t('ISP billing package for PPPoE, hotspot payments, SMS, and payment operations.')}</p>
                            </CardHeader>
                            <CardContent className="space-y-5">
                                <div className="rounded-[26px] bg-slate-50 p-5">
                                    <div className="flex items-end gap-2">
                                        <div className="text-4xl font-bold text-slate-950">{formatCurrency(baseAmount)}</div>
                                        <div className="pb-1 text-sm text-slate-500">/ {periodLabel}</div>
                                    </div>
                                    <p className="mt-2 text-sm text-slate-500">{pricingPeriod === 'monthly' ? t('Monthly platform subscription') : t('Yearly platform subscription')}</p>
                                </div>

                                <div className="grid gap-3 md:grid-cols-2">
                                    <div className="rounded-2xl border border-slate-100 p-4">
                                        <div className="flex items-center gap-2 text-sm text-slate-500"><Users className="h-4 w-4 text-emerald-600" />{t('PPPoE users')}</div>
                                        <div className="mt-2 font-semibold text-slate-950">{pppoeLabel}</div>
                                    </div>
                                    <div className="rounded-2xl border border-slate-100 p-4">
                                        <div className="flex items-center gap-2 text-sm text-slate-500"><RadioTower className="h-4 w-4 text-blue-600" />{t('Routers')}</div>
                                        <div className="mt-2 font-semibold text-slate-950">{t('Unlimited')}</div>
                                    </div>
                                    <div className="rounded-2xl border border-slate-100 p-4">
                                        <div className="flex items-center gap-2 text-sm text-slate-500"><Percent className="h-4 w-4 text-amber-600" />{t('Hotspot revenue fee')}</div>
                                        <div className="mt-2 font-semibold text-slate-950">{hotspotFeePercent}%</div>
                                    </div>
                                    <div className="rounded-2xl border border-slate-100 p-4">
                                        <div className="flex items-center gap-2 text-sm text-slate-500"><Clock className="h-4 w-4 text-purple-600" />{t('Trial')}</div>
                                        <div className="mt-2 font-semibold text-slate-950">{plan.trial ? `${plan.trial_days || 14} ${t('days')}` : t('Not enabled')}</div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card className="rounded-[28px] border-slate-200 shadow-sm">
                            <CardHeader>
                                <CardTitle>{t('Included modules')}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="grid gap-3 md:grid-cols-2">
                                    {includedModules.length > 0 ? includedModules.map((module) => (
                                        <div key={module.module} className="flex items-center gap-3 rounded-2xl border border-slate-100 p-4">
                                            <CheckCircle2 className="h-5 w-5 text-emerald-600" />
                                            <div>
                                                <div className="font-medium text-slate-950">{module.alias}</div>
                                                <div className="text-xs text-slate-500">{t('Included with this package')}</div>
                                            </div>
                                        </div>
                                    )) : (
                                        <div className="text-sm text-slate-500">{t('No modules are included in this package.')}</div>
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        <div className="rounded-[24px] border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
                            <div className="flex gap-3">
                                <ShieldCheck className="mt-0.5 h-5 w-5 flex-none" />
                                <p>{t('Manual cash payments and PPPoE flat billing are not charged the hotspot revenue percentage. The fee below is calculated from successful hotspot payments collected through the system for the current month.')}</p>
                            </div>
                        </div>
                    </div>

                    <Card className="rounded-[28px] border-slate-200 shadow-sm">
                        <CardHeader>
                            <CardTitle className="text-2xl">{t('Checkout summary')}</CardTitle>
                            <p className="text-sm text-slate-500">{t('Review your billing cycle and payment method.')}</p>
                        </CardHeader>
                        <CardContent className="space-y-5">
                            <div className="space-y-3 rounded-2xl border border-slate-100 p-4 text-sm">
                                <div className="flex justify-between"><span className="text-slate-500">{t('Billing cycle')}</span><span className="font-semibold text-slate-950">{pricingPeriod === 'monthly' ? t('Monthly') : t('Yearly')}</span></div>
                                <div className="flex justify-between"><span className="text-slate-500">{t('Base subscription')}</span><span className="font-semibold text-slate-950">{formatCurrency(baseAmount)}</span></div>
                                {couponDiscount > 0 && <div className="flex justify-between text-emerald-700"><span>{t('Coupon discount')}</span><span>- {formatCurrency(couponDiscount)}</span></div>}
                                <div className="flex justify-between"><span className="text-slate-500">{t('Hotspot revenue generated')}</span><span className="font-semibold text-slate-950">{formatCurrency(hotspotRevenueAmount)}</span></div>
                                <div className="flex justify-between"><span className="text-slate-500">{t('Hotspot revenue fee')} ({hotspotFeePercent}%)</span><span className="font-semibold text-slate-950">{formatCurrency(hotspotRevenueFeeAmount)}</span></div>
                                <div className="border-t pt-3 flex justify-between text-base"><span className="font-semibold text-slate-950">{t('Total due')}</span><span className="font-bold text-slate-950">{formatCurrency(totalDue)}</span></div>
                            </div>

                            <div className="space-y-2">
                                <Label>{t('Coupon Code')}</Label>
                                <div className="flex gap-2">
                                    <Input value={couponCode} onChange={(event) => setCouponCode(event.target.value)} placeholder={t('Enter coupon code')} />
                                    <Button type="button" variant="outline" onClick={applyCoupon} disabled={!couponCode.trim() || isApplyingCoupon}>{isApplyingCoupon ? t('Applying...') : t('Apply')}</Button>
                                </div>
                                {couponError && <p className="text-sm text-red-600">{couponError}</p>}
                            </div>

                            {!plan.free_plan && (mpesaEnabled || bankEnabled) && (
                                <div className="space-y-4">
                                    <Label>{t('Payment Method')}</Label>
                                    <RadioGroup value={selectedPaymentMethod} onValueChange={setSelectedPaymentMethod}>
                                        {mpesaEnabled && (
                                            <div className="flex items-center gap-3 rounded-2xl border border-slate-200 p-4">
                                                <RadioGroupItem value="mpesa" id="mpesa" />
                                                <Label htmlFor="mpesa" className="flex cursor-pointer items-center gap-3">
                                                    <CreditCard className="h-5 w-5 text-emerald-600" />
                                                    <span>{t('M-Pesa STK Push')}</span>
                                                </Label>
                                            </div>
                                        )}
                                        {bankEnabled && (
                                            <div className="flex items-center gap-3 rounded-2xl border border-slate-200 p-4">
                                                <RadioGroupItem value="bank_transfer" id="bank_transfer" />
                                                <Label htmlFor="bank_transfer" className="flex cursor-pointer items-center gap-3">
                                                    <Receipt className="h-5 w-5 text-emerald-600" />
                                                    <span>{t('Bank Transfer')}</span>
                                                </Label>
                                            </div>
                                        )}
                                    </RadioGroup>

                                    {selectedPaymentMethod === 'mpesa' && (
                                        <div className="space-y-2">
                                            <Label>{t('Admin M-Pesa Phone')}</Label>
                                            <Input value={mpesaPhone} onChange={(event) => setMpesaPhone(event.target.value)} placeholder="0712345678" inputMode="tel" />
                                            <p className="text-xs text-slate-500">{t('The STK Push will be sent using the Super Admin platform M-Pesa gateway.')}</p>
                                            {mpesaStatus && <p className="text-sm text-emerald-700">{mpesaStatus}</p>}
                                            {mpesaError && <p className="text-sm text-red-600">{mpesaError}</p>}
                                        </div>
                                    )}

                                    {selectedPaymentMethod === 'bank_transfer' && (
                                        <>
                                            {bankTransferInstructions && <div className="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900" dangerouslySetInnerHTML={{ __html: bankTransferInstructions.replace(/\n/g, '<br/>') }} />}
                                            <div className="space-y-2">
                                                <Label>{t('Upload Payment Receipt')}</Label>
                                                <Input type="file" accept=".png,.jpg,.jpeg,.pdf" onChange={(event) => setReceiptFile(event.target.files?.[0] || null)} />
                                                {receiptFile && <div className="flex items-center gap-2 text-sm text-emerald-700"><FileUp className="h-4 w-4" />{receiptFile.name}</div>}
                                                {fileError && <p className="text-sm text-red-600">{fileError}</p>}
                                            </div>
                                        </>
                                    )}
                                </div>
                            )}

                            {noGatewayAvailable && (
                                <div className="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{mpesaMissingMessage}</div>
                            )}

                            <Button className="w-full" size="lg" onClick={subscribe} disabled={isSubmitting || noGatewayAvailable || (!plan.free_plan && selectedPaymentMethod === 'none')}>
                                <CreditCard className="mr-2 h-4 w-4" />
                                {isSubmitting ? t('Processing...') : plan.free_plan ? t('Activate Free Plan') : selectedPaymentMethod === 'mpesa' ? `${t('Send M-Pesa STK')} - ${formatCurrency(totalDue)}` : `${t('Continue to Payment')} - ${formatCurrency(totalDue)}`}
                            </Button>

                            {plan.trial && (
                                <Button variant="outline" className="w-full bg-white" size="lg" onClick={startTrial}>
                                    <Clock className="mr-2 h-4 w-4" />
                                    {t('Start Trial')} ({plan.trial_days || 14} {t('days')})
                                </Button>
                            )}

                            {noGatewayAvailable && (
                                <p className="text-center text-sm text-slate-500">{t('Payment methods will appear after Super Admin configures a gateway. You can still review this package.')}</p>
                            )}

                            {planExpireDate && <div className="text-center text-xs text-slate-500">{t('Current plan expires')}: {planExpireDate}</div>}
                            {planUsage && <div className="rounded-2xl bg-slate-50 p-4 text-xs text-slate-600">{t('Current usage')}: {Number(planUsage.pppoeUsers || 0).toLocaleString()} {t('PPPoE users')}, {Number(planUsage.routersCount || 0).toLocaleString()} {t('routers')}, {t('hotspot revenue')} {formatCurrency(hotspotRevenueAmount)} → {t('fee')} {formatCurrency(hotspotRevenueFeeAmount)}</div>}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
