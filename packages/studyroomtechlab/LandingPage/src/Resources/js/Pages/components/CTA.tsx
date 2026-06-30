import { ArrowRight, MessageCircle } from 'lucide-react';
import { useTranslation } from 'react-i18next';

interface CTAProps {
    settings?: any;
}

export default function CTA({ settings }: CTAProps) {
    const { t } = useTranslation();
    const sectionData = settings?.config_sections?.sections?.cta || {};
    const colors = settings?.config_sections?.colors || { primary: '#0f766e', secondary: '#0ea5e9', accent: '#f59e0b' };

    const title = sectionData.title || 'Ready to launch your WiFi billing platform?';
    const subtitle = sectionData.subtitle || 'Use StudyRoom TechLab WiFi Billing to manage packages, customers, MikroTik provisioning, and payment activation from one system.';
    const primaryButton = sectionData.primary_button || t('Create Account');
    const secondaryButton = sectionData.secondary_button || t('Talk to Sales');
    const primaryButtonLink = sectionData.primary_button_link || route('register');
    const secondaryButtonLink = sectionData.secondary_button_link || route('login');

    return (
        <section id="cta" className="scroll-mt-28 bg-slate-50 py-16 md:py-24">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="relative overflow-hidden rounded-[2rem] border border-slate-200 bg-white p-8 shadow-xl shadow-slate-900/5 md:p-12">
                    <div className="absolute -right-20 -top-20 h-64 w-64 rounded-full blur-3xl" style={{ backgroundColor: `${colors.primary}18` }} />
                    <div className="absolute -bottom-24 left-20 h-72 w-72 rounded-full blur-3xl" style={{ backgroundColor: `${colors.secondary}14` }} />
                    <div className="relative grid items-center gap-8 lg:grid-cols-[1fr_auto]">
                        <div>
                            <div className="mb-5 inline-flex items-center gap-2 rounded-full px-4 py-2 text-xs font-black uppercase tracking-[0.24em]" style={{ backgroundColor: `${colors.primary}10`, color: colors.primary }}>
                                <MessageCircle className="h-4 w-4" /> Launch support
                            </div>
                            <h2 className="max-w-3xl text-3xl font-black tracking-tight text-slate-950 md:text-5xl">{title}</h2>
                            <p className="mt-5 max-w-3xl text-lg leading-8 text-slate-600">{subtitle}</p>
                        </div>
                        <div className="flex w-full flex-col items-start gap-3 sm:w-auto sm:flex-row lg:flex-col lg:items-stretch">
                            <button
                                type="button"
                                onClick={() => window.location.href = primaryButtonLink}
                                className="inline-flex w-auto min-w-[180px] items-center justify-center rounded-xl px-6 py-3 text-sm font-black text-white shadow-lg transition hover:-translate-y-0.5 sm:min-w-[190px]"
                                style={{ backgroundColor: colors.primary }}
                            >
                                {primaryButton}<ArrowRight className="ms-2 h-5 w-5" />
                            </button>
                            <button
                                type="button"
                                onClick={() => window.location.href = secondaryButtonLink}
                                className="inline-flex w-auto min-w-[180px] items-center justify-center rounded-xl border border-slate-300 bg-white px-6 py-3 text-sm font-black text-slate-800 shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-50 sm:min-w-[190px]"
                            >
                                {secondaryButton}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}
