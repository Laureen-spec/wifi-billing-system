import { CreditCard, FileBarChart2, ListChecks, RadioTower } from 'lucide-react';

declare global {
    function route(name: string): string;
}

const ispReportCompanyMenu = (t: (key: string) => string) => ({
    title: t('ISP Report'),
    icon: FileBarChart2,
    name: 'isp-report',
    menuKey: 'isp-report',
    routeName: 'isp-reports.index',
    order: 66,
    children: [
        {
            title: t('Overview'),
            href: route('isp-reports.index'),
            icon: FileBarChart2,
            name: 'isp-report-overview',
            menuKey: 'isp-report',
            routeName: 'isp-reports.index',
            order: 1,
        },
        {
            title: t('Staff Logs'),
            href: route('isp-reports.staff-logs'),
            icon: ListChecks,
            name: 'isp-report-staff-logs',
            menuKey: 'isp-report',
            routeName: 'isp-reports.staff-logs',
            order: 2,
        },
        {
            title: t('Connection Logs'),
            href: route('isp-reports.connection-logs'),
            icon: RadioTower,
            name: 'isp-report-connection-logs',
            menuKey: 'isp-report',
            routeName: 'isp-reports.connection-logs',
            order: 3,
        },
        {
            title: t('Payment Logs'),
            href: route('isp-reports.payment-logs'),
            icon: CreditCard,
            name: 'isp-report-payment-logs',
            menuKey: 'isp-report',
            routeName: 'isp-reports.payment-logs',
            order: 4,
        },
    ],
});

export default ispReportCompanyMenu;
export { ispReportCompanyMenu };
