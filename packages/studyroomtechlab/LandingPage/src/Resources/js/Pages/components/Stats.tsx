import { Activity, CreditCard, Network, Wifi } from 'lucide-react';

interface StatsProps {
    settings?: any;
}

export default function Stats({ settings }: StatsProps) {
    const sectionData = settings?.config_sections?.sections?.stats || {};
    const colors = settings?.config_sections?.colors || { primary: '#0f766e', secondary: '#0ea5e9', accent: '#f59e0b' };

    const defaultStats = [
        { label: 'MikroTik Router Agent', value: 'Auto' },
        { label: 'M-Pesa STK Ready', value: 'KES' },
        { label: 'Hotspot & PPPoE', value: '2-in-1' },
        { label: 'Multi-ISP SaaS Ready', value: 'Cloud' },
    ];

    const stats = sectionData.stats?.length > 0 ? sectionData.stats : defaultStats;
    const icons = [Activity, CreditCard, Wifi, Network];

    return (
        <section className="relative -mt-10 z-10 pb-10">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="rounded-[1.75rem] border border-slate-200 bg-white/90 p-4 shadow-xl shadow-slate-900/5 backdrop-blur-md">
                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        {stats.map((stat: any, index: number) => {
                            const Icon = icons[index % icons.length];
                            return (
                                <div key={`${stat.label}-${index}`} className="group rounded-2xl border border-slate-100 bg-slate-50/70 p-5 transition hover:-translate-y-0.5 hover:bg-white hover:shadow-lg">
                                    <div className="mb-4 flex items-center justify-between">
                                        <span className="flex h-12 w-12 items-center justify-center rounded-2xl" style={{ backgroundColor: `${colors.primary}12`, color: colors.primary }}>
                                            <Icon className="h-6 w-6" />
                                        </span>
                                        <span className="rounded-full bg-white px-3 py-1 text-xs font-bold text-slate-500 shadow-sm">{index + 1}</span>
                                    </div>
                                    <div className="text-3xl font-black tracking-tight text-slate-950">{stat.value}</div>
                                    <div className="mt-1 text-sm font-semibold text-slate-600">{stat.label}</div>
                                </div>
                            );
                        })}
                    </div>
                </div>
            </div>
        </section>
    );
}
