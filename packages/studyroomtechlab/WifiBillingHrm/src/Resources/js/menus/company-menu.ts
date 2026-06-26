import { ClipboardList } from 'lucide-react';

declare global {
    function route(name: string): string;
}

export const wifiBillingHrmCompanyMenu = (t: (key: string) => string) => ({
    title: t('HRM & Field Team'),
    href: route('wifi-billing-hrm.dashboard'),
    icon: ClipboardList,
    parent: 'wifi-billing',
    order: 50,
});
