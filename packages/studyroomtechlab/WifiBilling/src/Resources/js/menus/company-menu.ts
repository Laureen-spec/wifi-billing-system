import { Wifi } from 'lucide-react';

declare global {
    function route(name: string): string;
}

export const wifiBillingCompanyMenu = (t: (key: string) => string) => ({
    title: t('WiFi Billing'),
    href: route('wifi-billing.dashboard'),
    icon: Wifi,
    permission: 'manage-dashboard',
    order: 25,
});
