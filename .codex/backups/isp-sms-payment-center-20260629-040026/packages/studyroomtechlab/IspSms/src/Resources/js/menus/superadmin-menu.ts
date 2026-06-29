import { FileText, MessageSquare, Settings } from 'lucide-react';

declare global {
    function route(name: string): string;
}

export const ispSmsSuperAdminMenu = (t: (key: string) => string) => ({
    title: t('ISP SMS'),
    icon: MessageSquare,
    name: 'isp-sms',
    order: 3010,
    children: [
        {
            title: t('SMS Messages'),
            href: route('isp.sms.index'),
            icon: MessageSquare,
            permission: 'manage-add-on',
            permissions: ['manage-add-on', 'manage-settings', 'manage-plans'],
            order: 10,
        },
        {
            title: t('SMS Settings'),
            href: route('isp.sms.settings'),
            icon: Settings,
            permission: 'manage-add-on',
            permissions: ['manage-add-on', 'manage-settings', 'manage-plans'],
            order: 20,
        },
        {
            title: t('SMS Templates'),
            href: route('isp.sms.templates.index'),
            icon: FileText,
            permission: 'manage-add-on',
            permissions: ['manage-add-on', 'manage-settings', 'manage-plans'],
            order: 30,
        },
    ],
});
