import { ClipboardList } from 'lucide-react';

declare global {
    function route(name: string, params?: unknown): string;
}

const safeRoute = (name: string, fallback: string) => {
    try {
        return route(name);
    } catch {
        return fallback;
    }
};

const leadsCompanyMenu = (t: (key: string) => string) => ({
    title: t('Lead Desk'),
    href: safeRoute('studyroom-leads.index', '/lead-desk'),
    icon: ClipboardList,
    name: 'studyroom-leads',
    menuKey: 'studyroom-leads',
    routeName: 'studyroom-leads.index',
    permission: 'view-isp-customers',
    permissions: ['view-isp-customers', 'manage-isp-customers'],
    order: 55,
});

export default leadsCompanyMenu;
