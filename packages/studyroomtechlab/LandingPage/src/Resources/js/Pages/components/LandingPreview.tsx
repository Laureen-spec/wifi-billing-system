import { useTranslation } from 'react-i18next';

interface LandingPreviewProps {
  settings?: any;
}

export function LandingPreview({ settings }: LandingPreviewProps) {
  const { t } = useTranslation();

  const colors = settings?.config_sections?.colors || {
    primary: '#0f766e',
    secondary: '#0ea5e9',
    accent: '#f59e0b',
  };

  const getSectionData = (key: string) => settings?.config_sections?.sections?.[key] || {};
  const isSectionVisible = (key: string) => settings?.config_sections?.section_visibility?.[key] !== false;
  const sectionOrder = settings?.config_sections?.section_order || ['header', 'hero', 'stats', 'features', 'modules', 'benefits', 'gallery', 'cta', 'footer'];

  const renderMiniSection = (sectionKey: string) => {
    if (!isSectionVisible(sectionKey)) return null;
    const sectionData = getSectionData(sectionKey);

    switch (sectionKey) {
      case 'header': {
        const navItems = Array.isArray(sectionData.navigation_items) ? sectionData.navigation_items : [];
        return (
          <div key={sectionKey} className="border-b border-slate-100 bg-white p-3">
            <div className="mb-3 flex items-center justify-between">
              <div className="truncate text-sm font-black text-slate-950">{sectionData.company_name || settings?.company_name || 'StudyRoomTechLab WiFi Billing'}</div>
              <div className="rounded-full px-3 py-1 text-[10px] font-black text-white" style={{ backgroundColor: colors.primary }}>{sectionData.cta_text || t('Get Started')}</div>
            </div>
            <div className="flex gap-1 overflow-hidden">
              {navItems.slice(0, 4).map((item: any, i: number) => <span key={i} className="rounded-full bg-slate-100 px-2 py-1 text-[9px] font-bold text-slate-500">{item.text}</span>)}
            </div>
          </div>
        );
      }

      case 'hero':
        return (
          <div key={sectionKey} className="bg-slate-50 p-4">
            <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
              {sectionData.highlight_text && <div className="mb-2 truncate rounded-full bg-slate-50 px-2 py-1 text-[9px] font-bold text-slate-500">{sectionData.highlight_text}</div>}
              <div className="text-sm font-black leading-tight text-slate-950">{sectionData.title || t('Landing hero title')}</div>
              <div className="mt-2 line-clamp-2 text-[11px] leading-5 text-slate-600">{sectionData.subtitle || t('Landing hero subtitle')}</div>
              <div className="mt-3 flex gap-2">
                <span className="rounded-lg px-2 py-1 text-[10px] font-black text-white" style={{ backgroundColor: colors.primary }}>{sectionData.primary_button_text || t('Start')}</span>
                <span className="rounded-lg border border-slate-200 px-2 py-1 text-[10px] font-black text-slate-600">{sectionData.secondary_button_text || t('Login')}</span>
              </div>
            </div>
          </div>
        );

      case 'stats': {
        const stats = sectionData.stats?.length > 0 ? sectionData.stats : [{ value: '10K+', label: 'Businesses' }, { value: '99.9%', label: 'Uptime' }];
        return (
          <div key={sectionKey} className="bg-white p-3">
            <div className="grid grid-cols-2 gap-2">
              {stats.slice(0, 4).map((stat: any, i: number) => (
                <div key={i} className="rounded-xl border border-slate-100 bg-slate-50 p-2">
                  <div className="text-sm font-black text-slate-950">{stat.value}</div>
                  <div className="truncate text-[10px] text-slate-500">{stat.label}</div>
                </div>
              ))}
            </div>
          </div>
        );
      }

      case 'features': {
        const features = sectionData.features?.length > 0 ? sectionData.features : [{ title: 'Feature' }, { title: 'Feature' }, { title: 'Feature' }];
        return (
          <div key={sectionKey} className="bg-white p-4">
            <div className="text-center text-xs font-black text-slate-950">{sectionData.title || t('Features')}</div>
            <div className="mt-3 grid grid-cols-2 gap-2">
              {features.slice(0, 4).map((feature: any, i: number) => (
                <div key={i} className="rounded-xl border border-slate-100 bg-white p-2 shadow-sm">
                  <div className="mb-2 h-6 w-6 rounded-lg" style={{ backgroundColor: `${colors.primary}20` }} />
                  <div className="truncate text-[10px] font-bold text-slate-700">{feature.title}</div>
                </div>
              ))}
            </div>
          </div>
        );
      }

      case 'modules':
        return (
          <div key={sectionKey} className="bg-slate-50 p-4">
            <div className="text-center text-xs font-black text-slate-950">{sectionData.title || t('Modules')}</div>
            <div className="mt-3 flex gap-1 overflow-hidden">
              {(sectionData.modules || []).slice(0, 4).map((module: any, i: number) => <span key={i} className="rounded-full bg-white px-2 py-1 text-[9px] font-bold text-slate-500 shadow-sm">{module.label}</span>)}
            </div>
            <div className="mt-3 h-20 rounded-xl bg-slate-900" />
          </div>
        );

      case 'benefits':
        return (
          <div key={sectionKey} className="bg-white p-4">
            <div className="text-center text-xs font-black text-slate-950">{sectionData.title || t('Benefits')}</div>
            <div className="mt-3 space-y-2">
              {(sectionData.benefits || [{ title: 'Benefit' }, { title: 'Benefit' }, { title: 'Benefit' }]).slice(0, 3).map((benefit: any, i: number) => <div key={i} className="rounded-xl border border-slate-100 p-2 text-[10px] font-bold text-slate-600">{benefit.title}</div>)}
            </div>
          </div>
        );

      case 'gallery':
        return (
          <div key={sectionKey} className="bg-slate-50 p-4">
            <div className="text-center text-xs font-black text-slate-950">{sectionData.title || t('Gallery')}</div>
            <div className="mt-3 rounded-xl border border-slate-200 bg-white p-2">
              <div className="h-24 rounded-lg bg-slate-200" />
            </div>
          </div>
        );

      case 'cta':
        return (
          <div key={sectionKey} className="bg-slate-50 p-4">
            <div className="rounded-2xl border border-slate-200 bg-white p-4 text-center">
              <div className="text-xs font-black text-slate-950">{sectionData.title || t('Ready to start?')}</div>
              <div className="mt-3 inline-flex rounded-lg px-3 py-1 text-[10px] font-black text-white" style={{ backgroundColor: colors.primary }}>{sectionData.primary_button || t('Start')}</div>
            </div>
          </div>
        );

      case 'footer':
        return (
          <div key={sectionKey} className="bg-slate-950 p-4 text-white">
            <div className="text-xs font-black">{settings?.company_name || 'StudyRoomTechLab WiFi Billing'}</div>
            <div className="mt-2 line-clamp-2 text-[10px] leading-4 text-slate-400">{sectionData.description || t('Footer description')}</div>
          </div>
        );

      default:
        return null;
    }
  };

  return (
    <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg">
      <div className="flex items-center justify-between border-b border-slate-100 bg-slate-50 px-3 py-2">
        <div className="flex items-center gap-2 text-xs font-black text-slate-700"><span className="h-2 w-2 rounded-full" style={{ backgroundColor: colors.primary }} />{t('Live Preview')}</div>
        <div className="text-[10px] font-bold text-slate-400">{t('Mobile')}</div>
      </div>
      <div className="max-h-[520px] overflow-y-auto">
        {sectionOrder.map((sectionKey: string) => renderMiniSection(sectionKey))}
      </div>
      <div className="border-t border-slate-100 bg-white px-3 py-2 text-[10px] font-bold text-slate-500">
        {sectionOrder.filter((key: string) => isSectionVisible(key)).length} {t('sections active')}
      </div>
    </div>
  );
}
