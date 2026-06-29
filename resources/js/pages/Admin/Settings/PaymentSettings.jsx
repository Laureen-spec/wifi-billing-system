import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import {
    CreditCard,
    Landmark,
    Smartphone,
    ReceiptText,
    ShieldCheck,
    Wallet,
} from 'lucide-react';

export default function PaymentSettings({ settings, platformFee = 2.5, paymentLogs = [] }) {
    const { data, setData, put, processing, errors, recentlySuccessful } = useForm({
        mode: settings?.mode || 'system_payment',
        gateway: settings?.gateway || 'mpesa',
        method_type: settings?.method_type || 'till',
        till_number: settings?.till_number || '',
        paybill_number: settings?.paybill_number || '',
        account_number: settings?.account_number || '',
        phone_number: settings?.phone_number || '',
        fee_handling: settings?.fee_handling || 'add_to_checkout',
    });

    const submit = (event) => {
        event.preventDefault();
        put(route('admin.settings.payment.update'), {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Payment Settings" />

            <div className="space-y-6">
                <div className="flex flex-col gap-1">
                    <p className="text-sm font-semibold uppercase tracking-wide text-emerald-600">
                        Settings
                    </p>
                    <h1 className="text-2xl font-bold text-slate-900">
                        Payment Settings
                    </h1>
                    <p className="text-sm text-slate-500">
                        Configure how subscriber payments are collected for this admin account.
                    </p>
                </div>

                {recentlySuccessful && (
                    <div className="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                        Payment settings updated successfully.
                    </div>
                )}

                <form onSubmit={submit} className="space-y-6">
                    <section className="rounded-[22px] border border-slate-200 bg-white p-5 shadow-sm">
                        <SectionHeader
                            icon={Wallet}
                            title="Revenue Collection Method"
                            subtitle="Choose how subscriber money should be collected."
                        />

                        <div className="grid gap-4 md:grid-cols-2">
                            <ChoiceCard
                                selected={data.mode === 'system_payment'}
                                icon={ShieldCheck}
                                title="Use System Payment"
                                description="Subscriber pays through the platform gateway. Commission is calculated automatically."
                                onClick={() => setData('mode', 'system_payment')}
                            />

                            <ChoiceCard
                                selected={data.mode === 'own_payment'}
                                icon={CreditCard}
                                title="Use My Own M-Pesa"
                                description="Subscriber pays directly to your M-Pesa Till, Paybill, or Phone number."
                                onClick={() => setData('mode', 'own_payment')}
                            />
                        </div>

                        <FieldError error={errors.mode} />
                    </section>

                    {data.mode === 'own_payment' && (
                        <section className="rounded-[22px] border border-slate-200 bg-white p-5 shadow-sm">
                            <SectionHeader
                                icon={Landmark}
                                title="M-Pesa Till / Paybill / Phone"
                                subtitle="Add the M-Pesa account where your subscriber payments should go."
                            />

                            <div className="mb-5 grid gap-3 md:grid-cols-3">
                                <MethodButton
                                    selected={data.method_type === 'till'}
                                    icon={CreditCard}
                                    label="Till Number"
                                    onClick={() => setData('method_type', 'till')}
                                />
                                <MethodButton
                                    selected={data.method_type === 'paybill'}
                                    icon={Landmark}
                                    label="Paybill"
                                    onClick={() => setData('method_type', 'paybill')}
                                />
                                <MethodButton
                                    selected={data.method_type === 'phone'}
                                    icon={Smartphone}
                                    label="Phone Number"
                                    onClick={() => setData('method_type', 'phone')}
                                />
                            </div>

                            {data.method_type === 'till' && (
                                <Input
                                    label="Till Number"
                                    value={data.till_number}
                                    onChange={(event) => setData('till_number', event.target.value)}
                                    error={errors.till_number}
                                    placeholder="Enter M-Pesa Till number"
                                />
                            )}

                            {data.method_type === 'paybill' && (
                                <div className="grid gap-4 md:grid-cols-2">
                                    <Input
                                        label="Paybill Number"
                                        value={data.paybill_number}
                                        onChange={(event) => setData('paybill_number', event.target.value)}
                                        error={errors.paybill_number}
                                        placeholder="Enter Paybill number"
                                    />
                                    <Input
                                        label="Account Number"
                                        value={data.account_number}
                                        onChange={(event) => setData('account_number', event.target.value)}
                                        error={errors.account_number}
                                        placeholder="Username, phone, or package code"
                                    />
                                </div>
                            )}

                            {data.method_type === 'phone' && (
                                <Input
                                    label="Phone Number"
                                    value={data.phone_number}
                                    onChange={(event) => setData('phone_number', event.target.value)}
                                    error={errors.phone_number}
                                    placeholder="07XXXXXXXX"
                                />
                            )}

                            <FieldError error={errors.method_type} />
                        </section>
                    )}

                    <section className="rounded-[22px] border border-slate-200 bg-white p-5 shadow-sm">
                        <SectionHeader
                            icon={ReceiptText}
                            title="Checkout Fee Settings"
                            subtitle="Platform fee is controlled by Super Admin."
                        />

                        <div className="mb-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p className="text-sm text-slate-500">Current Platform Fee</p>
                            <p className="text-2xl font-bold text-slate-900">{platformFee}%</p>
                            <p className="mt-1 text-xs text-slate-500">
                                This fee can be added on checkout or deducted from admin revenue.
                            </p>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2">
                            <ChoiceCard
                                selected={data.fee_handling === 'add_to_checkout'}
                                title="Add fee on top of checkout"
                                description="Subscriber pays the package price plus platform fee."
                                onClick={() => setData('fee_handling', 'add_to_checkout')}
                            />
                            <ChoiceCard
                                selected={data.fee_handling === 'deduct_from_revenue'}
                                title="Deduct fee from admin revenue"
                                description="Subscriber pays normal price, then the platform fee is deducted from your revenue."
                                onClick={() => setData('fee_handling', 'deduct_from_revenue')}
                            />
                        </div>

                        <FieldError error={errors.fee_handling} />
                    </section>

                    <section className="rounded-[22px] border border-slate-200 bg-white p-5 shadow-sm">
                        <div className="mb-4">
                            <h2 className="text-lg font-semibold text-slate-900">Payment Logs</h2>
                            <p className="mt-1 text-sm text-slate-500">
                                Recent payment setting changes.
                            </p>
                        </div>

                        <div className="overflow-hidden rounded-2xl border border-slate-200">
                            {paymentLogs.length === 0 ? (
                                <div className="bg-slate-50 p-6 text-center text-sm text-slate-500">
                                    No payment setting logs yet.
                                </div>
                            ) : (
                                <table className="w-full text-left text-sm">
                                    <thead className="bg-slate-50 text-slate-500">
                                        <tr>
                                            <th className="p-3">Date</th>
                                            <th className="p-3">Action</th>
                                            <th className="p-3">Method</th>
                                            <th className="p-3">Updated By</th>
                                            <th className="p-3">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {paymentLogs.map((log) => (
                                            <tr key={log.id} className="border-t border-slate-100">
                                                <td className="p-3">{log.date}</td>
                                                <td className="p-3">{log.action}</td>
                                                <td className="p-3">{log.method}</td>
                                                <td className="p-3">{log.updated_by}</td>
                                                <td className="p-3">{log.status}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            )}
                        </div>
                    </section>

                    <div className="flex justify-end">
                        <button
                            type="submit"
                            disabled={processing}
                            className="rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:opacity-60"
                        >
                            {processing ? 'Saving...' : 'Save Payment Settings'}
                        </button>
                    </div>
                </form>
            </div>
        </>
    );
}

function SectionHeader({ icon: Icon, title, subtitle }) {
    return (
        <div className="mb-4 flex items-center gap-3">
            <div className="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                <Icon size={20} />
            </div>
            <div>
                <h2 className="text-lg font-semibold text-slate-900">{title}</h2>
                <p className="text-sm text-slate-500">{subtitle}</p>
            </div>
        </div>
    );
}

function ChoiceCard({ selected, icon: Icon, title, description, onClick }) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={`rounded-2xl border p-4 text-left transition ${
                selected
                    ? 'border-emerald-500 bg-emerald-50 shadow-sm'
                    : 'border-slate-200 bg-white hover:bg-slate-50'
            }`}
        >
            <div className="flex gap-3">
                {Icon && <Icon className="mt-0.5 text-emerald-600" size={22} />}
                <div>
                    <h3 className="font-semibold text-slate-900">{title}</h3>
                    <p className="mt-1 text-sm leading-5 text-slate-500">{description}</p>
                </div>
            </div>
        </button>
    );
}

function MethodButton({ selected, icon: Icon, label, onClick }) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={`flex items-center gap-3 rounded-2xl border p-3 text-left transition ${
                selected
                    ? 'border-emerald-500 bg-emerald-50'
                    : 'border-slate-200 bg-white hover:bg-slate-50'
            }`}
        >
            <Icon size={18} className="text-emerald-600" />
            <span className="text-sm font-semibold text-slate-800">{label}</span>
        </button>
    );
}

function Input({ label, value, onChange, error, placeholder }) {
    return (
        <div>
            <label className="mb-1 block text-sm font-medium text-slate-700">
                {label}
            </label>
            <input
                value={value}
                onChange={onChange}
                placeholder={placeholder}
                className="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
            />
            <FieldError error={error} />
        </div>
    );
}

function FieldError({ error }) {
    if (!error) return null;

    return <p className="mt-1 text-sm text-red-600">{error}</p>;
}
