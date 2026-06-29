import {
    AlertTriangle,
    BadgeDollarSign,
    ClipboardList,
    CreditCard,
    FileText,
    KeyRound,
    LayoutDashboard,
    MapPin,
    Network,
    Package,
    Receipt,
    Router,
    Ticket,
    UserCheck,
    Users,
    Wifi,
} from 'lucide-react';

declare global {
    function route(name: string): string;
}

const withQuery = (url: string, query: string) => `${url}?${query}`;

export const wifiBillingCompanyMenu = (t: (key: string) => string) => ({
    title: t('WiFi Billing'),
    icon: Wifi,
    name: 'wifi-billing',
    order: 25,
    children: [
        {
            title: t('Overview'),
            icon: LayoutDashboard,
            order: 10,
            children: [
                {
                    title: t('Dashboard'),
                    href: route('wifi-billing.dashboard'),
                    icon: LayoutDashboard,
                    permission: 'view-wifi-dashboard',
                    permissions: ['view-wifi-dashboard', 'manage-wifi-dashboard'],
                    order: 10,
                },
            ],
        },
        {
            title: t('Customers'),
            icon: Users,
            order: 20,
            children: [
                {
                    title: t('All Customers'),
                    href: route('wifi-billing.customers.index'),
                    icon: Users,
                    permission: 'view-isp-customers',
                    permissions: ['view-isp-customers', 'manage-isp-customers'],
                    order: 10,
                },
                {
                    title: t('Subscriptions'),
                    href: withQuery(route('wifi-billing.customers.index'), 'view=subscriptions'),
                    icon: UserCheck,
                    permission: 'view-isp-customers',
                    permissions: ['view-isp-customers', 'manage-isp-customers'],
                    order: 20,
                },
                {
                    title: t('PPPoE Users'),
                    href: withQuery(route('wifi-billing.customers.index'), 'access_type=pppoe'),
                    icon: Users,
                    permission: 'view-isp-customers',
                    permissions: ['view-isp-customers', 'manage-isp-customers'],
                    order: 30,
                },
                {
                    title: t('Hotspot Users'),
                    href: withQuery(route('wifi-billing.customers.index'), 'access_type=hotspot'),
                    icon: Wifi,
                    permission: 'view-isp-customers',
                    permissions: ['view-isp-customers', 'manage-isp-customers'],
                    order: 40,
                },
                {
                    title: t('Leads'),
                    href: route('isp.leads.index'),
                    icon: UserCheck,
                    permission: 'view-isp-customers',
                    permissions: ['view-isp-customers', 'manage-isp-customers'],
                    order: 50,
                },
            ],
        },
        {
            title: t('Packages'),
            icon: Package,
            order: 30,
            children: [
                {
                    title: t('Internet Packages'),
                    href: route('wifi-billing.packages.index'),
                    icon: Package,
                    permission: 'view-internet-packages',
                    permissions: ['view-internet-packages', 'manage-internet-packages'],
                    order: 10,
                },
                {
                    title: t('Access Vouchers'),
                    href: route('isp.vouchers.index'),
                    icon: KeyRound,
                    permission: 'view-internet-packages',
                    permissions: ['view-internet-packages', 'manage-internet-packages', 'manage-isp-customers'],
                    order: 20,
                },
            ],
        },
        {
            title: t('Billing'),
            icon: BadgeDollarSign,
            order: 40,
            children: [
                {
                    title: t('Invoices'),
                    href: route('isp.invoices.index'),
                    icon: FileText,
                    permission: 'view-isp-customers',
                    permissions: ['view-isp-customers', 'manage-isp-customers'],
                    order: 10,
                },
                {
                    title: t('Payments'),
                    href: route('isp.payments.index'),
                    icon: CreditCard,
                    permission: 'view-isp-customers',
                    permissions: ['view-isp-customers', 'manage-isp-customers'],
                    order: 20,
                },
                {
                    title: t('Receipts'),
                    href: route('isp.receipts.index'),
                    icon: Receipt,
                    permission: 'view-isp-customers',
                    permissions: ['view-isp-customers', 'manage-isp-customers'],
                    order: 30,
                },
                {
                    title: t('Overdue Accounts'),
                    href: route('isp.overdue.index'),
                    icon: AlertTriangle,
                    permission: 'view-isp-customers',
                    permissions: ['view-isp-customers', 'manage-isp-customers'],
                    order: 40,
                },
            ],
        },
        {
            title: t('Network'),
            icon: Network,
            order: 50,
            children: [
                {
                    title: t('MikroTik Routers'),
                    href: route('wifi-billing.routers.index'),
                    icon: Router,
                    permission: 'view-mikrotik-routers',
                    permissions: ['view-mikrotik-routers', 'manage-mikrotik-routers'],
                    order: 10,
                },
            ],
        },
        {
            title: t('Support'),
            icon: Ticket,
            order: 60,
            children: [
                {
                    title: t('Tickets'),
                    href: route('isp.tickets.index'),
                    icon: Ticket,
                    permission: 'view-isp-customers',
                    permissions: ['view-isp-customers', 'manage-isp-customers'],
                    order: 10,
                },
                {
                    title: t('Field Visits'),
                    href: route('isp.field-visits.index'),
                    icon: MapPin,
                    permission: 'view-isp-customers',
                    permissions: ['view-isp-customers', 'manage-isp-customers'],
                    order: 20,
                },
                {
                    title: t('Installations'),
                    href: withQuery(route('wifi-billing.customers.index'), 'view=installations'),
                    icon: ClipboardList,
                    permission: 'view-isp-customers',
                    permissions: ['view-isp-customers', 'manage-isp-customers'],
                    order: 30,
                },
            ],
        },
    ],
});
