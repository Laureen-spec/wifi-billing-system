import { FileText, MessageSquare, Send, Settings } from 'lucide-react';

declare global {
    function route(name: string): string;
}

export const ispSmsCompanyMenu = (t: (key: string) => string) => ({
    title: t('ISP SMS'),
    icon: MessageSquare,
    name: 'isp-sms',
    menuKey: 'isp-sms',
    routeName: 'isp.sms.index',
    order: 64,
    children: [
        {
            title: t('SMS Messages'),
            href: route('isp.sms.index'),
            icon: MessageSquare,
            name: 'isp-sms-messages',
            menuKey: 'isp-sms',
            routeName: 'isp.sms.index',
            order: 1,
        },
        {
            title: t('New Message'),
            href: route('isp.sms.new-message'),
            icon: Send,
            name: 'isp-sms-new-message',
            menuKey: 'isp-sms',
            routeName: 'isp.sms.new-message',
            order: 2,
        },
        {
            title: t('SMS Settings'),
            href: route('isp.sms.settings'),
            icon: Settings,
            name: 'isp-sms-settings',
            menuKey: 'isp-sms',
            routeName: 'isp.sms.settings',
            order: 3,
        },
        {
            title: t('SMS Templates'),
            href: route('isp.sms.templates.index'),
            icon: FileText,
            name: 'isp-sms-templates',
            menuKey: 'isp-sms',
            routeName: 'isp.sms.templates.index',
            order: 4,
        },
    ],
});
