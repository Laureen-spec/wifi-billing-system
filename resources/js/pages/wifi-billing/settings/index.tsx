import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, Link } from '@inertiajs/react';
import { Palette, Wifi } from 'lucide-react';
import { PageHeader, StatusBadge } from '../components';

declare function route(name: string, params?: string | number | Record<string, unknown>): string;

type Props = {
    isp: {
        id: number;
        name: string;
    };
    hotspotTemplate: {
        template_key?: string | null;
        template_name?: string | null;
        primary_color?: string | null;
        secondary_color?: string | null;
        accent_color?: string | null;
    };
};

export default function WifiBillingSettings({ isp, hotspotTemplate }: Props) {
    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: 'WiFi Billing', url: route('wifi-billing.dashboard') },
                { label: 'Settings' },
            ]}
            pageTitle="WiFi Billing Settings"
        >
            <Head title="WiFi Billing Settings" />

            <div className="space-y-5">
                <PageHeader
                    title="WiFi Billing Settings"
                    description={`Manage admin-facing WiFi Billing settings for ${isp.name}.`}
                />

                <div className="grid gap-4 lg:grid-cols-3">
                    <Card>
                        <CardHeader className="border-b py-4">
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Palette className="h-4 w-4" />
                                Hotspot Template
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4 p-4">
                            <div className="space-y-2">
                                <div className="flex items-center justify-between gap-3">
                                    <span className="text-sm text-muted-foreground">Template</span>
                                    <StatusBadge value={hotspotTemplate.template_key || 'modern'} />
                                </div>
                                <div className="text-sm font-medium">{hotspotTemplate.template_name || 'Modern Hotspot'}</div>
                            </div>
                            <div className="flex items-center gap-2">
                                {[hotspotTemplate.primary_color, hotspotTemplate.secondary_color, hotspotTemplate.accent_color].map((color, index) => (
                                    <span
                                        key={`${color || 'empty'}-${index}`}
                                        className="h-8 w-8 rounded-md border"
                                        style={{ backgroundColor: color || '#ffffff' }}
                                    />
                                ))}
                            </div>
                            <Button asChild>
                                <Link href={route('wifi-billing.settings.hotspot-template.edit')}>
                                    <Wifi className="h-4 w-4" />
                                    Open Template
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
