import { useState } from 'react';
import { ChevronLeft, ChevronRight, LayoutDashboard, Router, WalletCards, Wifi } from 'lucide-react';
import { getImagePath } from '@/utils/helpers';

interface GalleryProps {
    settings?: any;
}

export default function Gallery({ settings }: GalleryProps) {
    const sectionData = settings?.config_sections?.sections?.gallery || {};
    const colors = settings?.config_sections?.colors || { primary: '#0f766e', secondary: '#0ea5e9', accent: '#f59e0b' };
    const [currentImageIndex, setCurrentImageIndex] = useState(0);
    const [failedImages, setFailedImages] = useState<Record<string, boolean>>({});

    const title = sectionData.title || 'A practical dashboard for ISP work';
    const subtitle = sectionData.subtitle || 'Manage routers, packages, hotspot customers, transactions, wallets, and provisioning status in one place.';
    const galleryImages = Array.isArray(sectionData.images)
        ? sectionData.images.filter((img: string) => typeof img === 'string' && img.trim().length > 0)
        : [];

    const currentImage = galleryImages[currentImageIndex] || '';
    const imageSrc = currentImage ? (currentImage.startsWith('http') ? currentImage : getImagePath(currentImage)) : '';
    const canShowImage = Boolean(imageSrc) && !failedImages[imageSrc];
    const hasGalleryImages = galleryImages.length > 0;

    const nextImage = () => {
        if (galleryImages.length <= 1) return;
        setCurrentImageIndex((prev) => (prev + 1) % galleryImages.length);
    };

    const previousImage = () => {
        if (galleryImages.length <= 1) return;
        setCurrentImageIndex((prev) => (prev - 1 + galleryImages.length) % galleryImages.length);
    };

    const renderDashboardPlaceholder = () => (
        <div className="relative overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white p-6 shadow-sm md:p-8">
            <div className="absolute -right-20 -top-20 h-64 w-64 rounded-full blur-3xl" style={{ backgroundColor: `${colors.primary}16` }} />
            <div className="absolute -bottom-20 left-10 h-60 w-60 rounded-full blur-3xl" style={{ backgroundColor: `${colors.secondary}12` }} />
            <div className="relative grid gap-5 md:grid-cols-3">
                {[
                    { label: 'Router dashboard', icon: Router, value: 'Auto' },
                    { label: 'Payment records', icon: WalletCards, value: 'KES' },
                    { label: 'Live sessions', icon: Wifi, value: 'Online' },
                ].map((item) => {
                    const Icon = item.icon;
                    return (
                        <div key={item.label} className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <div className="flex items-center justify-between gap-3">
                                <span className="flex h-11 w-11 items-center justify-center rounded-2xl" style={{ backgroundColor: `${colors.primary}10`, color: colors.primary }}>
                                    <Icon className="h-6 w-6" />
                                </span>
                                <span className="rounded-full bg-slate-50 px-3 py-1 text-xs font-black text-slate-400">Preview</span>
                            </div>
                            <p className="mt-8 text-2xl font-black text-slate-950">{item.value}</p>
                            <p className="mt-1 text-sm font-semibold text-slate-500">{item.label}</p>
                        </div>
                    );
                })}
            </div>
            <div className="relative mt-5 rounded-2xl border border-slate-200 bg-slate-50/80 p-5">
                <div className="mb-4 flex items-center justify-between text-xs font-black uppercase tracking-[0.22em] text-slate-500">
                    <span className="flex items-center gap-2"><LayoutDashboard className="h-4 w-4" style={{ color: colors.primary }} /> Dashboard flow</span>
                    <span>Clean workspace</span>
                </div>
                <div className="grid gap-3 md:grid-cols-3">
                    {[82, 64, 74].map((width, index) => (
                        <div key={index} className="h-3 overflow-hidden rounded-full bg-white">
                            <div className="h-full rounded-full" style={{ width: `${width}%`, backgroundColor: index === 1 ? colors.secondary : colors.primary }} />
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );

    return (
        <section id="gallery" className="scroll-mt-28 bg-white py-16 md:py-24">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="mx-auto max-w-3xl text-center">
                    <h2 className="text-3xl font-black tracking-tight text-slate-950 md:text-5xl">{title}</h2>
                    <p className="mt-5 text-lg leading-8 text-slate-600">{subtitle}</p>
                </div>

                <div className="mt-12 rounded-[2rem] border border-slate-200 bg-slate-50 p-4 shadow-xl shadow-slate-900/5 md:p-6">
                    <div className="relative overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white">
                        {canShowImage ? (
                            <img
                                src={imageSrc}
                                alt={`Gallery image ${currentImageIndex + 1}`}
                                onError={() => setFailedImages((prev) => ({ ...prev, [imageSrc]: true }))}
                                className="h-[420px] w-full object-cover md:h-[560px]"
                            />
                        ) : renderDashboardPlaceholder()}

                        {galleryImages.length > 1 && canShowImage && (
                            <div className="absolute inset-x-4 bottom-4 rounded-2xl border border-white/40 bg-white/90 p-4 shadow-lg backdrop-blur-md">
                                <div className="flex items-center justify-between gap-4">
                                    <div>
                                        <p className="text-xs font-black uppercase tracking-[0.24em] text-slate-500">Dashboard view</p>
                                        <p className="mt-1 text-lg font-black text-slate-950">{currentImageIndex + 1} / {galleryImages.length}</p>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <button type="button" onClick={previousImage} className="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 shadow-sm hover:bg-slate-50"><ChevronLeft className="h-5 w-5" /></button>
                                        <button type="button" onClick={nextImage} className="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 shadow-sm hover:bg-slate-50"><ChevronRight className="h-5 w-5" /></button>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>

                    {hasGalleryImages && (
                        <div className="mt-5 grid grid-cols-2 gap-3 md:grid-cols-4">
                            {galleryImages.slice(0, 4).map((image: any, index: number) => {
                                const src = typeof image === 'string' && image.trim().length > 0
                                    ? (image.startsWith('http') ? image : getImagePath(image))
                                    : '';
                                const showThumb = Boolean(src) && !failedImages[src];

                                if (!showThumb) return null;

                                return (
                                    <button
                                        key={`${src}-${index}`}
                                        type="button"
                                        onClick={() => setCurrentImageIndex(index)}
                                        className="overflow-hidden rounded-2xl border bg-white p-1 transition hover:-translate-y-0.5"
                                        style={{ borderColor: currentImageIndex === index ? colors.primary : '#e2e8f0' }}
                                    >
                                        <img
                                            src={src}
                                            alt={`Gallery thumbnail ${index + 1}`}
                                            onError={() => setFailedImages((prev) => ({ ...prev, [src]: true }))}
                                            className="h-24 w-full rounded-xl object-cover"
                                        />
                                    </button>
                                );
                            })}
                        </div>
                    )}
                </div>
            </div>
        </section>
    );
}
