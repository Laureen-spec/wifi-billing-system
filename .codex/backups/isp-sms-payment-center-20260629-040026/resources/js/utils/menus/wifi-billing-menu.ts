import {
    CreditCard,
    LayoutDashboard,
    MonitorUp,
    Package,
    Router,
    Settings,
    Users,
    Wifi,
} from 'lucide-react';
import { NavItem } from '@/types';

type WifiBillingNavItem = Omit<NavItem, 'children' | 'href'> & {
    href?: string;
    menuKey?: string;
    routeName?: string;
    permissions?: string[];
    children?: WifiBillingNavItem[];
};

const routeUrl = (name: string) => String(route(name));

export const getWifiBillingMenu = (t: (key: string) => string): NavItem[] => ([
    {
        title: t('WiFi Billing'),
        icon: Wifi,
        name: 'wifi-billing',
        menuKey: 'wifi-billing',
        order: 25,
        children: [
            {
                title: t('Dashboard'),
                href: routeUrl('wifi-billing.dashboard'),
                icon: LayoutDashboard,
                permission: 'view-wifi-dashboard',
                permissions: ['view-wifi-dashboard', 'manage-wifi-dashboard'],
                menuKey: 'isp-dashboard',
                routeName: 'wifi-billing.dashboard',
                order: 10,
            },
            {
                title: t('Customers'),
                href: routeUrl('wifi-billing.customers.index'),
                icon: Users,
                permission: 'view-isp-customers',
                permissions: ['view-isp-customers', 'manage-isp-customers'],
                menuKey: 'isp-customers',
                routeName: 'wifi-billing.customers.index',
                order: 20,
            },
            {
                title: t('Routers'),
                href: routeUrl('wifi-billing.routers.index'),
                icon: Router,
                permission: 'view-mikrotik-routers',
                permissions: ['view-mikrotik-routers', 'manage-mikrotik-routers'],
                menuKey: 'isp-routers',
                routeName: 'wifi-billing.routers.index',
                order: 30,
            },
            {
                title: t('Live Sessions'),
                href: routeUrl('wifi-billing.live-sessions.index'),
                icon: MonitorUp,
                permission: 'view-mikrotik-routers',
                permissions: ['view-mikrotik-routers', 'manage-mikrotik-routers'],
                menuKey: 'isp-live-sessions',
                routeName: 'wifi-billing.live-sessions.index',
                order: 40,
            },
            {
                title: t('Plans'),
                href: routeUrl('wifi-billing.packages.index'),
                icon: Package,
                permission: 'view-internet-packages',
                permissions: ['view-internet-packages', 'manage-internet-packages'],
                menuKey: 'isp-plans',
                routeName: 'wifi-billing.packages.index',
                order: 50,
            },
            {
                title: t('Payment Center'),
                href: '/wifi-billing/payment-center',
                icon: CreditCard,
                name: 'isp-payment-center',
                menuKey: 'isp-payment-center',
                routeName: 'isp-payment-center.index',
                order: 55,
            },
            {
                title: t('Settings'),
                href: routeUrl('wifi-billing.settings.index'),
                icon: Settings,
                permission: 'view-wifi-dashboard',
                permissions: ['view-wifi-dashboard', 'manage-wifi-dashboard'],
                menuKey: 'isp-settings',
                routeName: 'wifi-billing.settings.index',
                order: 60,
                children: [
                    {
                        title: t('Hotspot Template'),
                        href: routeUrl('wifi-billing.settings.hotspot-template.edit'),
                        icon: Wifi,
                        permission: 'view-wifi-dashboard',
                        permissions: ['view-wifi-dashboard', 'manage-wifi-dashboard'],
                        menuKey: 'isp-hotspot-template',
                        routeName: 'wifi-billing.settings.hotspot-template.edit',
                        order: 10,
                    },
                ],
            },
        ],
    },
] as WifiBillingNavItem[]) as NavItem[];

