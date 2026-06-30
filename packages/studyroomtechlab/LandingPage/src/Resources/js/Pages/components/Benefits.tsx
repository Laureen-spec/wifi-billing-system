import { useState } from 'react';
import { ChevronDown, ShieldCheck } from 'lucide-react';

interface BenefitsProps {
    settings?: any;
}

export default function Benefits({ settings }: BenefitsProps) {
    const sectionData = settings?.config_sections?.sections?.benefits || {};
    const colors = settings?.config_sections?.colors || { primary: '#0f766e', secondary: '#0ea5e9', accent: '#f59e0b' };
    const [openAccordion, setOpenAccordion] = useState(0);

    const title = sectionData.title || 'Why ISPs choose StudyRoom';
    const defaultBenefits = [
        { title: 'Customer pays, router activates', description: 'Customers can buy WiFi packages and the system can trigger provisioning after payment.' },
        { title: 'MikroTik workflows stay clean', description: 'Keep router provisioning, customer billing, and admin operations in one workspace.' },
        { title: 'Add-on friendly', description: 'Enable extra modules as your ISP grows without rebuilding your whole system.' },
    ];
    const benefits = sectionData.benefits?.length > 0 ? sectionData.benefits : defaultBenefits;

    return (
        <section className="bg-white py-16 md:py-24">
            <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <div className="mb-10 text-center">
                    <span className="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-black uppercase tracking-[0.24em] text-slate-500 shadow-sm">
                        <ShieldCheck className="h-4 w-4" style={{ color: colors.primary }} /> Operational confidence
                    </span>
                    <h2 className="mt-5 text-3xl font-black tracking-tight text-slate-950 md:text-5xl">{title}</h2>
                </div>

                <div className="space-y-3">
                    {benefits.map((benefit: any, index: number) => (
                        <div key={`${benefit.title}-${index}`} className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                            <button
                                type="button"
                                onClick={() => setOpenAccordion(openAccordion === index ? -1 : index)}
                                className="flex w-full items-center justify-between gap-4 px-6 py-5 text-left transition hover:bg-slate-50"
                            >
                                <span className="text-lg font-black text-slate-950">{benefit.title}</span>
                                <ChevronDown className={`h-5 w-5 shrink-0 text-slate-500 transition-transform ${openAccordion === index ? 'rotate-180' : ''}`} />
                            </button>
                            {openAccordion === index && (
                                <div className="border-t border-slate-100 px-6 py-5 text-base leading-8 text-slate-600">
                                    {benefit.description}
                                </div>
                            )}
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}
