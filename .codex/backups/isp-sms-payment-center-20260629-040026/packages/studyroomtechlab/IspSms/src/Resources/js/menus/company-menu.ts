import { FileText, MessageSquare, Settings } from 'lucide-react';

declare global {
    function route(name: string): string;
}

export const ispSmsCompanyMenu = (t: (key: string) => string) => ({
    title: t('Communication'),
    icon: MessageSquare,
    parent: 'wifi-billing',
    order: 50,
    children: [
        {
            title: t('SMS Messages'),
            href: route('isp.sms.index'),
            icon: MessageSquare,
            permission: 'view-isp-customers',
            permissions: ['view-isp-customers', 'manage-isp-customers', 'manage-wifi-dashboard'],
            order: 10,
        },
        {
            title: t('SMS Settings'),
            href: route('isp.sms.settings'),
            icon: Settings,
            permission: 'manage-isp-customers',
            permissions: ['manage-isp-customers', 'manage-wifi-dashboard'],
            order: 20,
        },
        {
            title: t('SMS Templates'),
            href: route('isp.sms.templates.index'),
            icon: FileText,
            permission: 'manage-isp-customers',
            permissions: ['manage-isp-customers', 'manage-wifi-dashboard'],
            order: 30,
        },
    ],
});
