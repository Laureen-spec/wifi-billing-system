import { Activity, Gift, LayoutDashboard, Settings, Star, Ticket, Trophy } from 'lucide-react';

declare global {
    function route(name: string): string;
}

const loyaltySuperAdminMenu = (t: (key: string) => string) => ({
    title: t('Loyalty'),
    icon: Gift,
    name: 'loyalty',
    menuKey: 'loyalty',
    routeName: 'loyalty.index',
    order: 66,
    children: [
        {
            title: t('Dashboard'),
            href: route('loyalty.index'),
            icon: LayoutDashboard,
            name: 'loyalty-dashboard',
            menuKey: 'loyalty-dashboard',
            routeName: 'loyalty.index',
            order: 10,
        },
        {
            title: t('Customer Points'),
            href: route('loyalty.customers'),
            icon: Star,
            name: 'loyalty-customer-points',
            menuKey: 'loyalty-customer-points',
            routeName: 'loyalty.customers',
            order: 20,
        },
        {
            title: t('Reward Rules'),
            href: route('loyalty.rules.index'),
            icon: Trophy,
            name: 'loyalty-reward-rules',
            menuKey: 'loyalty-reward-rules',
            routeName: 'loyalty.rules.index',
            order: 30,
        },
        {
            title: t('Vouchers'),
            href: route('loyalty.vouchers.index'),
            icon: Ticket,
            name: 'loyalty-vouchers',
            menuKey: 'loyalty-vouchers',
            routeName: 'loyalty.vouchers.index',
            order: 40,
        },
        {
            title: t('Activity Logs'),
            href: route('loyalty.logs.index'),
            icon: Activity,
            name: 'loyalty-activity-logs',
            menuKey: 'loyalty-activity-logs',
            routeName: 'loyalty.logs.index',
            order: 50,
        },
        {
            title: t('Settings'),
            href: route('loyalty.settings'),
            icon: Settings,
            name: 'loyalty-settings',
            menuKey: 'loyalty-settings',
            routeName: 'loyalty.settings',
            order: 60,
        },
    ],
});

export default loyaltySuperAdminMenu;
