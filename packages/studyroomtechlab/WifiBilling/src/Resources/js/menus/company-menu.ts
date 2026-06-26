import { Package, Router, Users, Wifi } from 'lucide-react';

declare global {
    function route(name: string): string;
}

export const wifiBillingCompanyMenu = (t: (key: string) => string) => ({
    title: t('WiFi Billing'),
    icon: Wifi,
    name: 'wifi-billing',
    order: 25,
    children: [
        {
            title: t('Dashboard'),
            href: route('wifi-billing.dashboard'),
            icon: Wifi,
            permission: 'view-wifi-dashboard',
            permissions: ['view-wifi-dashboard', 'manage-wifi-dashboard'],
            order: 10,
        },
        {
            title: t('Internet Packages'),
            href: route('wifi-billing.packages.index'),
            icon: Package,
            permission: 'view-internet-packages',
            permissions: ['view-internet-packages', 'manage-internet-packages'],
            order: 20,
        },
        {
            title: t('Customers'),
            href: route('wifi-billing.customers.index'),
            icon: Users,
            permission: 'view-isp-customers',
            permissions: ['view-isp-customers', 'manage-isp-customers'],
            order: 30,
        },
        {
            title: t('MikroTik Routers'),
            href: route('wifi-billing.routers.index'),
            icon: Router,
            permission: 'view-mikrotik-routers',
            permissions: ['view-mikrotik-routers', 'manage-mikrotik-routers'],
            order: 40,
        },
    ],
});
