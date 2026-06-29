import { CreditCard } from 'lucide-react';

const ispPaymentCenterSuperAdminMenu = (t: (key: string) => string) => ({
    title: t('Payment Center'),
    href: '/wifi-billing/payment-center',
    icon: CreditCard,
    name: 'isp-payment-center',
    menuKey: 'isp-payment-center',
    routeName: 'isp-payment-center.index',
    order: 2820,
});

export default ispPaymentCenterSuperAdminMenu;
