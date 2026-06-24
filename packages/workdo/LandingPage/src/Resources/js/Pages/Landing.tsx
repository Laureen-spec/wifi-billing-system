import { Head, usePage } from '@inertiajs/react';
import { getAdminSetting, getImagePath } from '@/utils/helpers';
import CookieConsent from "@/components/cookie-consent";
import Header from './components/Header';
import Hero from './components/Hero';
import Stats from './components/Stats';
import Features from './components/Features';
import Modules from './components/Modules';
import Benefits from './components/Benefits';
import Gallery from './components/Gallery';
import CTA from './components/CTA';
import Footer from './components/Footer';

interface LandingProps {
    settings?: {
        company_name?: string;
        contact_email?: string;
        contact_phone?: string;
        contact_address?: string;
        config_sections?: {
            sections?: { [key: string]: any };
            section_visibility?: { [key: string]: boolean };
            section_order?: string[];
            colors?: { primary: string; secondary: string; accent: string };
        };
    };
}

export default function Landing({ settings }: LandingProps) {
    const { adminAllSetting } = usePage().props as any;
    const favicon = getAdminSetting('favicon');
    const faviconUrl = favicon ? getImagePath(favicon) : null;

    const isSectionVisible = (key: string) =>
        settings?.config_sections?.section_visibility?.[key] !== false;

    const sectionOrder = settings?.config_sections?.section_order ||
        ['header', 'hero', 'stats', 'features', 'modules', 'benefits', 'gallery', 'cta', 'footer'];

    const renderSection = (sectionKey: string) => {
        if (!isSectionVisible(sectionKey)) return null;
        switch (sectionKey) {
            case 'header':   return <Header key={sectionKey} settings={settings} />;
            case 'hero':     return <Hero key={sectionKey} settings={settings} />;
            case 'stats':    return <Stats key={sectionKey} settings={settings} />;
            case 'features': return <Features key={sectionKey} settings={settings} />;
            case 'modules':  return <Modules key={sectionKey} settings={settings} />;
            case 'benefits': return <Benefits key={sectionKey} settings={settings} />;
            case 'gallery':  return <Gallery key={sectionKey} settings={settings} />;
            case 'cta':      return <CTA key={sectionKey} settings={settings} />;
            case 'footer':   return <Footer key={sectionKey} settings={settings} />;
            default:         return null;
        }
    };

    return (
        <div className="min-h-screen bg-white">
            <Head title={`${settings?.company_name || 'WorkDo Dash'} - All-in-One Business Management Solution`}>
                {faviconUrl && <link rel="icon" type="image/x-icon" href={faviconUrl} />}
            </Head>
            
            {/* Render sections in order */}
            {sectionOrder.map(sectionKey => renderSection(sectionKey))}
            
            <CookieConsent settings={adminAllSetting || {}} />
        </div>
    );
}