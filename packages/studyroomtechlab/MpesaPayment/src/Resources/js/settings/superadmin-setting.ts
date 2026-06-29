import { CreditCard } from 'lucide-react';

export const mpesaPaymentGatewaySetting = (t: (key: string) => string) => ({
    order: 1,
    title: t('Payment Settings'),
    href: '#payment-gateway-settings',
    icon: CreditCard,
    permission: 'manage-settings',
    component: 'payment-gateway-settings',
});

export default mpesaPaymentGatewaySetting;
