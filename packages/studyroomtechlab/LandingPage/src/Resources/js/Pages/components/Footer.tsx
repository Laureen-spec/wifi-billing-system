import { Mail, MapPin, Phone } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { getAdminSetting, getImagePath } from '@/utils/helpers';
import { FormEvent, useState } from 'react';
import { toast } from 'sonner';

interface FooterProps {
    settings?: any;
}

export default function Footer({ settings }: FooterProps) {
    const [emailInput, setEmailInput] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    const sectionData = settings?.config_sections?.sections?.footer || {};
    const colors = settings?.config_sections?.colors || { primary: '#0f766e', secondary: '#0ea5e9', accent: '#f59e0b' };

    const companyName = settings?.company_name || 'StudyRoomTechLab WiFi Billing';
    const description = sectionData.description || 'A MikroTik-ready ISP billing platform for hotspot packages, customer subscriptions, M-Pesa payments, wallets, and router provisioning.';
    const contactEmail = sectionData.email || settings?.contact_email;
    const phone = sectionData.phone || settings?.contact_phone;
    const contactAddress = settings?.contact_address;
    const newsletterTitle = sectionData.newsletter_title || 'Get ISP system updates';
    const newsletterDescription = sectionData.newsletter_description || 'Subscribe for WiFi billing, MikroTik, M-Pesa, and product updates.';
    const newsletterButtonText = sectionData.newsletter_button_text || 'Subscribe';
    const copyrightText = sectionData.copyright_text || `© ${new Date().getFullYear()} ${companyName}. All rights reserved.`;
    const navigationSections = Array.isArray(sectionData.navigation_sections) ? sectionData.navigation_sections : [];

    const logoPath = getAdminSetting('logo_light') || getAdminSetting('logo_dark');
    const logoUrl = logoPath ? getImagePath(logoPath) : null;

    const normalizeHref = (href?: string) => {
        if (!href) return '#';
        return href.startsWith('/page/') ? route('custom-page.show', href.replace('/page/', '')) : href;
    };

    const handleNewsletterSubmit = async (e: FormEvent) => {
        e.preventDefault();

        if (!emailInput.trim()) {
            toast.error('Please enter your email address');
            return;
        }

        setIsSubmitting(true);

        try {
            const response = await fetch(route('newsletter.subscribe'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ email: emailInput.trim() }),
            });

            const data = await response.json();

            if (data.success) {
                toast.success(data.message);
                setEmailInput('');
            } else {
                toast.error(data.message);
            }
        } catch (error) {
            toast.error('An error occurred. Please try again.');
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <footer className="bg-slate-950 text-white">
            <div className="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
                <div className="grid gap-10 lg:grid-cols-[1.05fr_0.9fr_minmax(300px,0.95fr)]">
                    <div>
                        <Link href={route('landing.page')} className="inline-flex items-center gap-3">
                            {logoUrl ? (
                                <img src={logoUrl} alt={companyName} className="h-10 w-auto max-w-[180px] object-contain" />
                            ) : (
                                <>
                                    <span className="flex h-11 w-11 items-center justify-center rounded-2xl" style={{ backgroundColor: colors.primary }}>
                                        <span className="h-3 w-3 rounded-full bg-white" />
                                    </span>
                                    <span className="text-lg font-black tracking-tight">{companyName}</span>
                                </>
                            )}
                        </Link>
                        <p className="mt-5 max-w-md text-sm leading-7 text-slate-300">{description}</p>
                        <div className="mt-6 space-y-3 text-sm text-slate-300">
                            {contactEmail && <div className="flex items-center gap-3"><Mail className="h-4 w-4" style={{ color: colors.accent }} /> <span>{contactEmail}</span></div>}
                            {phone && <div className="flex items-center gap-3"><Phone className="h-4 w-4" style={{ color: colors.accent }} /> <span>{phone}</span></div>}
                            {contactAddress && <div className="flex items-center gap-3"><MapPin className="h-4 w-4" style={{ color: colors.accent }} /> <span>{contactAddress}</span></div>}
                        </div>
                    </div>

                    <div className="grid gap-8 sm:grid-cols-2">
                        {navigationSections.slice(0, 2).map((section: any, index: number) => (
                            <div key={`${section.title}-${index}`}>
                                <h3 className="text-sm font-black uppercase tracking-[0.22em] text-slate-400">{section.title}</h3>
                                <div className="mt-4 grid gap-3">
                                    {section.links?.map((link: any, linkIndex: number) => (
                                        <a key={`${link.text}-${linkIndex}`} href={normalizeHref(link.href)} target={link.target || undefined} className="text-sm font-semibold text-slate-300 transition hover:text-white">
                                            {link.text}
                                        </a>
                                    ))}
                                </div>
                            </div>
                        ))}
                    </div>

                    <div className="w-full max-w-xl rounded-[1.5rem] border border-white/10 bg-white/[0.04] p-5 lg:max-w-md lg:justify-self-end">
                        <h3 className="text-lg font-black">{newsletterTitle}</h3>
                        <p className="mt-2 text-sm leading-6 text-slate-300">{newsletterDescription}</p>
                        <form onSubmit={handleNewsletterSubmit} className="mt-5 flex max-w-md flex-col gap-2 sm:flex-row">
                            <input
                                type="email"
                                value={emailInput}
                                onChange={(e) => setEmailInput(e.target.value)}
                                placeholder="Enter email"
                                className="min-w-0 flex-1 rounded-xl border border-white/10 bg-white/10 px-4 py-3 text-sm text-white outline-none placeholder:text-slate-400 focus:border-white/30"
                            />
                            <button type="submit" disabled={isSubmitting} className="rounded-xl px-5 py-3 text-sm font-black text-white disabled:opacity-60 sm:w-auto" style={{ backgroundColor: colors.primary }}>
                                {isSubmitting ? '...' : newsletterButtonText}
                            </button>
                        </form>
                    </div>
                </div>

                <div className="mt-12 flex flex-col gap-4 border-t border-white/10 pt-6 text-sm text-slate-400 md:flex-row md:items-center md:justify-between">
                    <p>{copyrightText}</p>
                    <div className="flex flex-wrap gap-4">
                        {navigationSections.slice(2).flatMap((section: any) => section.links || []).slice(0, 4).map((link: any, index: number) => (
                            <a key={`${link.text}-${index}`} href={normalizeHref(link.href)} className="hover:text-white">{link.text}</a>
                        ))}
                    </div>
                </div>
            </div>
        </footer>
    );
}
