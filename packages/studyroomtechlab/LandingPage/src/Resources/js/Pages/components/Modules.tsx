import { useState } from 'react';
import { ArrowRight, CheckCircle2, Monitor, Router, WalletCards, Wifi } from 'lucide-react';
import { getImagePath } from '@/utils/helpers';

interface ModulesProps {
    settings?: any;
}

export default function Modules({ settings }: ModulesProps) {
    const sectionData = settings?.config_sections?.sections?.modules || {};
    const colors = settings?.config_sections?.colors || { primary: '#0f766e', secondary: '#0ea5e9', accent: '#f59e0b' };
    const [activeTab, setActiveTab] = useState(0);

    const title = sectionData.title || 'Built around the real ISP workflow';
    const subtitle = sectionData.subtitle || 'From public landing page to payment, router command pull, customer activation, and admin reporting.';

    const defaultModules = [
        { key: 'billing', label: 'Core Billing', title: 'WiFi & ISP Billing Core', description: 'Create internet packages, assign routers, manage customers, track billing status, and provisioning status.', image: '' },
        { key: 'agent', label: 'Router Agent', title: 'MikroTik Agent Mode', description: 'RouterOS pulls queued commands from Laravel and provisions hotspot users safely.', image: '' },
        { key: 'wallet', label: 'Wallets', title: 'Wallets & Settlements', description: 'Track ISP revenue, platform commission, wallet ledgers, and payout approvals.', image: '' },
    ];

    const modules = sectionData.modules?.length > 0 ? sectionData.modules : defaultModules;
    const activeModule = modules[Math.min(activeTab, Math.max(modules.length - 1, 0))] || modules[0];

    const moduleImage = activeModule?.image;
    const hasImage = typeof moduleImage === 'string' && moduleImage.trim().length > 0;

    const renderPreview = () => {
        if (hasImage) {
            return (
                <div className="h-full min-h-[330px] overflow-hidden rounded-[1.5rem] border border-slate-200 bg-slate-100 shadow-inner">
                    <img src={moduleImage.startsWith('http') ? moduleImage : getImagePath(moduleImage)} alt={activeModule.title} className="h-full w-full object-cover" />
                </div>
            );
        }

        return (
            <div className="min-h-[330px] rounded-[1.5rem] border border-slate-200 bg-slate-950 p-5 text-white shadow-inner">
                <div className="mb-5 flex items-center justify-between rounded-2xl border border-white/10 bg-white/[0.06] p-4">
                    <div className="flex items-center gap-3">
                        <span className="flex h-10 w-10 items-center justify-center rounded-xl" style={{ backgroundColor: colors.primary }}><Monitor className="h-5 w-5" /></span>
                        <div>
                            <p className="text-sm font-bold">{activeModule?.label}</p>
                            <p className="text-xs text-slate-400">live workspace</p>
                        </div>
                    </div>
                    <span className="rounded-full bg-emerald-400/15 px-3 py-1 text-xs font-bold text-emerald-200">Synced</span>
                </div>
                <div className="grid gap-3 sm:grid-cols-3">
                    {[Wifi, Router, WalletCards].map((Icon, index) => (
                        <div key={index} className="rounded-2xl border border-white/10 bg-white/[0.06] p-4">
                            <Icon className="h-5 w-5 text-white/70" />
                            <div className="mt-10 h-2 rounded-full bg-white/10" />
                            <div className="mt-2 h-2 w-2/3 rounded-full bg-white/10" />
                        </div>
                    ))}
                </div>
                <div className="mt-4 rounded-2xl border border-white/10 bg-white/[0.06] p-4">
                    <div className="mb-3 flex items-center justify-between text-xs text-slate-400"><span>Provisioning queue</span><span>healthy</span></div>
                    <div className="space-y-2">
                        {[78, 56, 88].map((width, index) => (
                            <div key={index} className="h-3 overflow-hidden rounded-full bg-white/10">
                                <div className="h-full rounded-full" style={{ width: `${width}%`, backgroundColor: index === 2 ? colors.secondary : colors.primary }} />
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        );
    };

    return (
        <section id="modules" className="scroll-mt-28 bg-slate-50 py-16 md:py-24">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="mx-auto max-w-3xl text-center">
                    <h2 className="text-3xl font-black tracking-tight text-slate-950 md:text-5xl">{title}</h2>
                    <p className="mt-5 text-lg leading-8 text-slate-600">{subtitle}</p>
                </div>

                <div className="mt-10 flex flex-wrap justify-center gap-2">
                    {modules.map((module: any, index: number) => (
                        <button
                            key={module.key || module.label || index}
                            type="button"
                            onClick={() => setActiveTab(index)}
                            className="rounded-full border px-5 py-2.5 text-sm font-black transition"
                            style={activeTab === index
                                ? { backgroundColor: colors.primary, borderColor: colors.primary, color: '#fff' }
                                : { backgroundColor: '#fff', borderColor: '#e2e8f0', color: '#475569' }}
                        >
                            {module.label || module.title}
                        </button>
                    ))}
                </div>

                <div className="mt-10 grid items-center gap-8 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-xl shadow-slate-900/5 lg:grid-cols-[0.85fr_1.15fr] lg:p-8">
                    <div className="p-2 lg:p-4">
                        <div className="mb-5 inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-black uppercase tracking-[0.24em]" style={{ backgroundColor: `${colors.primary}10`, color: colors.primary }}>
                            <CheckCircle2 className="h-4 w-4" /> Active module
                        </div>
                        <h3 className="text-2xl font-black tracking-tight text-slate-950 md:text-3xl">{activeModule?.title}</h3>
                        <p className="mt-5 text-lg leading-8 text-slate-600">{activeModule?.description}</p>
                        <div className="mt-8 inline-flex items-center gap-2 text-sm font-black" style={{ color: colors.primary }}>
                            View related add-ons <ArrowRight className="h-4 w-4" />
                        </div>
                    </div>
                    {renderPreview()}
                </div>
            </div>
        </section>
    );
}
