import { CreditCard } from 'lucide-react';

const ispPaymentCenterCompanyMenu = (t: (key: string) => string) => ({
    title: t('Payment Center'),
    href: '/wifi-billing/payment-center',
    icon: CreditCard,
    parent: 'wifi-billing',
    name: 'isp-payment-center',
    menuKey: 'isp-payment-center',
    routeName: 'isp-payment-center.index',
    order: 55,
});

export default ispPaymentCenterCompanyMenu;
