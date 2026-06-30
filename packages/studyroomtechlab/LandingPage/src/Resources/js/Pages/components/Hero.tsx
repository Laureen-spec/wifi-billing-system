import { ArrowRight, CheckCircle2 } from 'lucide-react';
import { useState } from 'react';
import { getImagePath } from '@/utils/helpers';
import { useTranslation } from 'react-i18next';

interface HeroProps {
    settings?: any;
}

export default function Hero({ settings }: HeroProps) {
    const { t } = useTranslation();
    const sectionData = settings?.config_sections?.sections?.hero || {};
    const colors = settings?.config_sections?.colors || { primary: '#0f766e', secondary: '#0ea5e9', accent: '#f59e0b' };

    const title = sectionData.title || 'Run Your ISP with StudyRoomTechLab WiFi Billing';
    const subtitle = sectionData.subtitle || 'A practical WiFi and ISP billing platform for packages, customers, MikroTik provisioning, wallets, and M-Pesa-ready payment workflows.';
    const primaryButtonText = sectionData.primary_button_text || t('Start Free Trial');
    const primaryButtonLink = sectionData.primary_button_link || route('register');
    const secondaryButtonText = sectionData.secondary_button_text || t('Request Demo');
    const secondaryButtonLink = sectionData.secondary_button_link || route('login');
    const highlightText = sectionData.highlight_text;
    const heroImage = sectionData.image;

    const [heroImageFailed, setHeroImageFailed] = useState(false);
    const hasImage = typeof heroImage === 'string' && heroImage.trim().length > 0 && !heroImageFailed;

    const renderTitle = () => {
        if (highlightText && title?.includes(highlightText)) {
            const titleParts = title.split(highlightText);
            return (
                <>
                    {titleParts[0]}
                    <span style={{ color: colors.primary }}>{highlightText}</span>
                    {titleParts[1]}
                </>
            );
        }
        return title;
    };

    const renderVisual = () => {
        if (!hasImage) return null;

        return (
            <div className="relative overflow-hidden rounded-[2rem] border border-slate-200 bg-white p-2 shadow-2xl shadow-slate-900/10">
                <img
                    src={heroImage.startsWith('http') ? heroImage : getImagePath(heroImage)}
                    alt="Hero"
                    onError={() => setHeroImageFailed(true)}
                    className="h-[360px] w-full rounded-[1.5rem] object-cover"
                />
                <div className="absolute inset-x-6 bottom-6 rounded-2xl border border-white/40 bg-white/85 p-4 shadow-xl backdrop-blur-md">
                    <div className="flex items-center justify-between gap-4">
                        <div>
                            <p className="text-xs font-bold uppercase tracking-[0.22em] text-slate-500">{t('Live operations')}</p>
                            <p className="mt-1 text-lg font-black text-slate-950">{t('Router and payment control')}</p>
                        </div>
                        <div className="rounded-full px-3 py-1 text-xs font-bold text-white" style={{ backgroundColor: colors.primary }}>{t('Online')}</div>
                    </div>
                </div>
            </div>
        );
    };

    return (
        <section id="home" className="relative scroll-mt-28 overflow-hidden bg-[radial-gradient(circle_at_top_left,#f8fafc,white_45%,#f8fafc)] py-16 md:py-24">
            <div className="absolute left-0 top-20 h-64 w-64 rounded-full blur-3xl" style={{ backgroundColor: `${colors.primary}14` }} />
            <div className="absolute right-0 top-32 h-72 w-72 rounded-full blur-3xl" style={{ backgroundColor: `${colors.secondary}12` }} />
            <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                {highlightText && (
                    <div className="mb-8 inline-flex max-w-full items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 shadow-sm">
                        <span className="h-2.5 w-2.5 rounded-full" style={{ backgroundColor: colors.primary }} />
                        <span className="truncate">{highlightText}</span>
                    </div>
                )}
                <div className={hasImage ? 'grid items-center gap-12 lg:grid-cols-[0.95fr_1.05fr]' : 'grid items-center gap-12'}>
                    <div className={hasImage ? '' : 'max-w-4xl'}>
                        <h1 className="max-w-3xl text-4xl font-black leading-[1.05] tracking-tight text-slate-950 sm:text-5xl lg:text-6xl">
                            {renderTitle()}
                        </h1>
                        <p className="mt-6 max-w-2xl text-lg leading-8 text-slate-600 md:text-xl">
                            {subtitle}
                        </p>
                        <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                            <button
                                type="button"
                                onClick={() => window.location.href = primaryButtonLink}
                                className="inline-flex items-center justify-center rounded-xl px-6 py-3 text-sm font-black text-white shadow-lg transition hover:-translate-y-0.5"
                                style={{ backgroundColor: colors.primary }}
                            >
                                {primaryButtonText}<ArrowRight className="ms-2 h-5 w-5" />
                            </button>
                            <button
                                type="button"
                                onClick={() => window.location.href = secondaryButtonLink}
                                className="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-6 py-3 text-sm font-black text-slate-800 shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-50"
                            >
                                {secondaryButtonText}
                            </button>
                        </div>
                        <div className="mt-8 grid max-w-xl grid-cols-1 gap-3 text-sm font-semibold text-slate-600 sm:grid-cols-3">
                            {[t('No card required'), t('Fast setup'), t('Cancel anytime')].map((item) => (
                                <div key={item} className="flex items-center gap-2">
                                    <CheckCircle2 className="h-4 w-4" style={{ color: colors.primary }} />
                                    <span>{item}</span>
                                </div>
                            ))}
                        </div>
                    </div>
                    {renderVisual()}
                </div>
            </div>
        </section>
    );
}
