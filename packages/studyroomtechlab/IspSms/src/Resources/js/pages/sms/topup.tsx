import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import { Label } from '@/components/ui/label';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, FileText, MessageSquare, ReceiptText, Wallet } from 'lucide-react';
import { FormEvent, useMemo } from 'react';

type Topup = {
    id: number;
    topup_number: string;
    order_id?: string | null;
    amount: number;
    currency: string;
    sms_units: number;
    status: string;
    created_at?: string | null;
};

type Props = {
    pageTitle: string;
    subtitle: string;
    wallet: {
        balance: number;
        free_sms_remaining: number;
        estimated_cost_per_sms: number;
        low_balance_alert_threshold: number;
    };
    recentTopups: Topup[];
    routes: {
        settings: string;
        store: string;
        messages: string;
    };
};

export default function SmsTopupCheckout({ pageTitle, subtitle, wallet, recentTopups, routes }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        amount: '500',
        payment_method: 'checkout',
    });

    const amount = Number(data.amount || 0);
    const cost = Math.max(Number(wallet.estimated_cost_per_sms || 1), 0.01);
    const estimatedUnits = useMemo(() => Math.floor(amount / cost), [amount, cost]);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(routes.store, { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout breadcrumbs={[{ label: 'ISP SMS', url: routes.messages }, { label: 'SMS Top-up' }]} pageTitle={pageTitle}>
            <Head title={pageTitle} />

            <div className="space-y-5">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p className="text-xs uppercase tracking-[0.2em] text-muted-foreground">Communication — SMS wallet</p>
                        <h1 className="mt-2 text-3xl font-semibold tracking-tight">{pageTitle}</h1>
                        <p className="mt-2 max-w-3xl text-sm text-muted-foreground">{subtitle}</p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href={routes.settings}><ArrowLeft className="h-4 w-4" />Back to SMS settings</Link>
                    </Button>
                </div>

                <div className="grid gap-4 md:grid-cols-4">
                    <Metric label="Current balance" value={wallet.balance.toFixed(2)} hint="system SMS wallet" />
                    <Metric label="Free SMS" value={String(wallet.free_sms_remaining)} hint="starter credits" />
                    <Metric label="Cost per SMS" value={wallet.estimated_cost_per_sms.toFixed(2)} hint="estimated deduction" />
                    <Metric label="Alert below" value={wallet.low_balance_alert_threshold.toFixed(2)} hint="low-balance warning" />
                </div>

                <div className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_420px]">
                    <form onSubmit={submit}>
                        <Card>
                            <CardHeader className="border-b py-4">
                                <CardTitle className="text-base">Generate SMS top-up invoice</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-5 p-5">
                                <div className="rounded-2xl border bg-muted/20 p-4 text-sm text-muted-foreground">
                                    The invoice is created first. Your SMS balance is credited after the top-up payment is approved. This top-up is also recorded as an ISP expense when the Expenses add-on is installed.
                                </div>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="amount">Top-up amount</Label>
                                        <Input id="amount" type="number" min="10" step="1" value={data.amount} onChange={(e) => setData('amount', e.target.value)} />
                                        <InputError message={errors.amount} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="method">Payment method</Label>
                                        <select id="method" value={data.payment_method} onChange={(e) => setData('payment_method', e.target.value)} className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                                            <option value="checkout">Checkout</option>
                                            <option value="mpesa">M-Pesa</option>
                                            <option value="bank_transfer">Bank transfer</option>
                                            <option value="manual">Manual invoice</option>
                                        </select>
                                        <InputError message={errors.payment_method} />
                                    </div>
                                </div>

                                <div className="grid gap-3 rounded-2xl border p-4 md:grid-cols-3">
                                    <Summary label="Invoice amount" value={amount > 0 ? amount.toFixed(2) : '0.00'} />
                                    <Summary label="Estimated SMS" value={String(estimatedUnits)} />
                                    <Summary label="Expense category" value="SMS top-up" />
                                </div>

                                <div className="flex justify-end">
                                    <Button type="submit" disabled={processing || amount <= 0} size="lg">
                                        <ReceiptText className="h-4 w-4" />
                                        Generate invoice
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </form>

                    <div className="space-y-5">
                        <Card>
                            <CardHeader className="border-b py-4">
                                <CardTitle className="text-base">Checkout rule</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 p-5 text-sm text-muted-foreground">
                                <div className="flex gap-3 rounded-xl border p-4">
                                    <Wallet className="mt-0.5 h-5 w-5 text-primary" />
                                    <div>
                                        <p className="font-medium text-foreground">Balance top-up</p>
                                        <p>Admin can top up anytime from this page or when SMS balance is low.</p>
                                    </div>
                                </div>
                                <div className="flex gap-3 rounded-xl border p-4">
                                    <FileText className="mt-0.5 h-5 w-5 text-primary" />
                                    <div>
                                        <p className="font-medium text-foreground">Invoice generated</p>
                                        <p>The top-up creates an invoice/order record and an expense entry when Expenses is installed.</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="border-b py-4">
                                <CardTitle className="text-base">Recent top-ups</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-2 p-5">
                                {recentTopups.length === 0 ? (
                                    <div className="rounded-2xl border border-dashed p-6 text-center text-sm text-muted-foreground">
                                        <MessageSquare className="mx-auto mb-2 h-8 w-8" />
                                        No SMS top-up invoices yet.
                                    </div>
                                ) : (
                                    recentTopups.map((topup) => (
                                        <div key={topup.id} className="rounded-xl border p-3 text-sm">
                                            <div className="flex items-center justify-between gap-3">
                                                <div className="font-medium">{topup.topup_number}</div>
                                                <div className="text-xs uppercase text-muted-foreground">{topup.status}</div>
                                            </div>
                                            <div className="mt-1 text-muted-foreground">{topup.currency} {Number(topup.amount).toFixed(2)} · {topup.sms_units} SMS</div>
                                        </div>
                                    ))
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function Metric({ label, value, hint }: { label: string; value: string; hint: string }) {
    return (
        <Card>
            <CardContent className="p-4">
                <p className="text-sm text-muted-foreground">{label}</p>
                <p className="mt-1 text-2xl font-semibold">{value}</p>
                <p className="mt-1 text-xs text-muted-foreground">{hint}</p>
            </CardContent>
        </Card>
    );
}

function Summary({ label, value }: { label: string; value: string }) {
    return <div><p className="text-xs uppercase tracking-wide text-muted-foreground">{label}</p><p className="mt-1 font-semibold">{value}</p></div>;
}
