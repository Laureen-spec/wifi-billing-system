import { FileText, MessageSquare, PlusCircle, Settings } from 'lucide-react';

declare global {
    function route(name: string): string;
}

export const ispSmsCompanyMenu = (t: (key: string) => string) => ({
    title: t('ISP SMS'),
    icon: MessageSquare,
    parent: 'wifi-billing',
    name: 'isp-sms',
    menuKey: 'isp-sms',
    routeName: 'isp.sms.index',
    order: 50,
    children: [
        {
            title: t('SMS Messages'),
            href: route('isp.sms.index'),
            icon: MessageSquare,
            menuKey: 'isp-sms-messages',
            routeName: 'isp.sms.index',
            permission: 'view-isp-customers',
            permissions: ['view-isp-customers', 'manage-isp-customers', 'manage-wifi-dashboard'],
            order: 10,
        },
        {
            title: t('New Message'),
            href: route('isp.sms.new-message'),
            icon: PlusCircle,
            menuKey: 'isp-sms-new-message',
            routeName: 'isp.sms.new-message',
            permission: 'manage-isp-customers',
            permissions: ['manage-isp-customers', 'manage-wifi-dashboard'],
            order: 15,
        },
        {
            title: t('SMS Settings'),
            href: route('isp.sms.settings'),
            icon: Settings,
            menuKey: 'isp-sms-settings',
            routeName: 'isp.sms.settings',
            permission: 'manage-isp-customers',
            permissions: ['manage-isp-customers', 'manage-wifi-dashboard'],
            order: 20,
        },
        {
            title: t('SMS Templates'),
            href: route('isp.sms.templates.index'),
            icon: FileText,
            menuKey: 'isp-sms-templates',
            routeName: 'isp.sms.templates.index',
            permission: 'manage-isp-customers',
            permissions: ['manage-isp-customers', 'manage-wifi-dashboard'],
            order: 30,
        },
    ],
});
