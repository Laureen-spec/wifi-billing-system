import {
    Bot,
    ClipboardList,
    FileText,
    Inbox,
    LayoutDashboard,
    Megaphone,
    MessageCircle,
    Receipt,
    Settings,
    ShieldCheck,
    Tags,
    WalletCards,
    Activity,
} from 'lucide-react';

declare global {
    function route(name: string): string;
}

export const ispWhatsappCompanyMenu = (t: (key: string) => string) => ({
    title: t('WhatsApp Desk'),
    icon: MessageCircle,
    name: 'isp-whatsapp',
    menuKey: 'isp-whatsapp',
    routeName: 'isp.whatsapp.index',
    order: 63,
    children: [
        { title: t('Overview'), href: route('isp.whatsapp.index'), icon: LayoutDashboard, name: 'whatsapp-overview', menuKey: 'whatsapp-overview', routeName: 'isp.whatsapp.index', order: 1 },
        { title: t('Inbox'), href: route('isp.whatsapp.inbox'), icon: Inbox, name: 'whatsapp-inbox', menuKey: 'whatsapp-inbox', routeName: 'isp.whatsapp.inbox', order: 2 },
        { title: t('Bot Flows'), href: route('isp.whatsapp.bot-flows'), icon: Bot, name: 'whatsapp-bot-flows', menuKey: 'whatsapp-bot-flows', routeName: 'isp.whatsapp.bot-flows', order: 3 },
        { title: t('Payment Requests'), href: route('isp.whatsapp.payment-requests'), icon: WalletCards, name: 'whatsapp-payment-requests', menuKey: 'whatsapp-payment-requests', routeName: 'isp.whatsapp.payment-requests', order: 4 },
        { title: t('Receipts'), href: route('isp.whatsapp.receipts'), icon: Receipt, name: 'whatsapp-receipts', menuKey: 'whatsapp-receipts', routeName: 'isp.whatsapp.receipts', order: 5 },
        { title: t('Support Tickets'), href: route('isp.whatsapp.support-tickets'), icon: ClipboardList, name: 'whatsapp-support-tickets', menuKey: 'whatsapp-support-tickets', routeName: 'isp.whatsapp.support-tickets', order: 6 },
        { title: t('Broadcasts'), href: route('isp.whatsapp.broadcasts'), icon: Megaphone, name: 'whatsapp-broadcasts', menuKey: 'whatsapp-broadcasts', routeName: 'isp.whatsapp.broadcasts', order: 7 },
        { title: t('Templates'), href: route('isp.whatsapp.templates'), icon: Tags, name: 'whatsapp-templates', menuKey: 'whatsapp-templates', routeName: 'isp.whatsapp.templates', order: 8 },
        { title: t('Message Usage'), href: route('isp.whatsapp.usage'), icon: Activity, name: 'whatsapp-usage', menuKey: 'whatsapp-usage', routeName: 'isp.whatsapp.usage', order: 9 },
        { title: t('API Settings'), href: route('isp.whatsapp.api-settings'), icon: ShieldCheck, name: 'whatsapp-api-settings', menuKey: 'whatsapp-api-settings', routeName: 'isp.whatsapp.api-settings', order: 10 },
        { title: t('Logs'), href: route('isp.whatsapp.logs'), icon: FileText, name: 'whatsapp-logs', menuKey: 'whatsapp-logs', routeName: 'isp.whatsapp.logs', order: 11 },
        { title: t('Settings'), href: route('isp.whatsapp.settings'), icon: Settings, name: 'whatsapp-settings', menuKey: 'whatsapp-settings', routeName: 'isp.whatsapp.settings', order: 12 },
    ],
});
