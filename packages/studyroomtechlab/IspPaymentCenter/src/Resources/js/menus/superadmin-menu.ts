import { CreditCard } from 'lucide-react';

declare global {
    function route(name: string): string;
}

const ispPaymentCenterSuperAdminMenu = (t: (key: string) => string) => ({
    title: t('Payment Center'),
    href: route('isp-payment-center.index'),
    icon: CreditCard,
    name: 'isp-payment-center',
    menuKey: 'isp-payment-center',
    routeName: 'isp-payment-center.index',
    order: 65,
});

export default ispPaymentCenterSuperAdminMenu;
