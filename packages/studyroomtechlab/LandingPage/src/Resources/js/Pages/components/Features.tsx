import { Building2, Calculator, CreditCard, FolderOpen, Package, Router, Users, Wallet, Wifi } from 'lucide-react';

interface FeaturesProps {
    settings?: any;
}

export default function Features({ settings }: FeaturesProps) {
    const sectionData = settings?.config_sections?.sections?.features || {};
    const colors = settings?.config_sections?.colors || { primary: '#0f766e', secondary: '#0ea5e9', accent: '#f59e0b' };

    const title = sectionData.title || 'Powerful features built for ISP growth';
    const subtitle = sectionData.subtitle || 'Everything you need to run and grow your internet business efficiently.';

    const defaultFeatures = [
        { title: 'MikroTik Automation', description: 'Automate hotspot and PPPoE profile creation, queue management, and real-time sync with your routers.', icon: 'Router' },
        { title: 'M-Pesa STK Push', description: 'Real-time payment collection via M-Pesa STK push and instant plan activation.', icon: 'CreditCard' },
        { title: 'Customer Management', description: 'Manage customers, packages, routers, billing status, and connection status from one place.', icon: 'Users' },
    ];

    const features = sectionData.features?.length > 0 ? sectionData.features : defaultFeatures;
    const icons: Record<string, any> = { Building2, Calculator, CreditCard, FolderOpen, Package, Router, Users, Wallet, Wifi };

    return (
        <section id="features" className="scroll-mt-28 bg-white py-16 md:py-24">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="mx-auto max-w-3xl text-center">
                    <div className="mb-4 text-xs font-black uppercase tracking-[0.35em]" style={{ color: colors.primary }}>ISP growth stack</div>
                    <h2 className="text-3xl font-black tracking-tight text-slate-950 md:text-5xl">{title}</h2>
                    <p className="mt-5 text-lg leading-8 text-slate-600">{subtitle}</p>
                </div>

                <div className="mt-14 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                    {features.map((feature: any, index: number) => {
                        const Icon = icons[feature.icon] || Building2;
                        return (
                            <article
                                key={`${feature.title}-${index}`}
                                className="group flex min-h-[285px] flex-col rounded-[1.35rem] border border-slate-200 bg-white p-6 shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-slate-900/5 md:p-7"
                            >
                                <div className="mb-6 flex items-start justify-between gap-4">
                                    <span className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl" style={{ backgroundColor: `${colors.primary}10`, color: colors.primary }}>
                                        <Icon className="h-6 w-6" />
                                    </span>
                                    <span className="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-black text-slate-400">{String(index + 1).padStart(2, '0')}</span>
                                </div>
                                <div className="flex flex-1 flex-col">
                                    <h3 className="text-[1.18rem] font-black leading-snug tracking-tight text-slate-950 md:text-xl">{feature.title}</h3>
                                    <p className="mt-4 flex-1 text-[0.96rem] leading-7 text-slate-600">{feature.description}</p>
                                    <div className="mt-6 h-1 w-12 rounded-full transition-all duration-300 group-hover:w-24" style={{ backgroundColor: colors.primary }} />
                                </div>
                            </article>
                        );
                    })}
                </div>
            </div>
        </section>
    );
}
