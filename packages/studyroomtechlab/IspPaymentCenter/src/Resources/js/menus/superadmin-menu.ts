import { CreditCard, LayoutDashboard, ShoppingCart } from 'lucide-react';

declare global {
    function route(name: string): string;
}

const ispPaymentCenterSuperAdminMenu = (t: (key: string) => string) => ({
    title: t('Payment Center'),
    icon: CreditCard,
    name: 'isp-payment-center',
    menuKey: 'isp-payment-center',
    order: 65,
    children: [
        {
            title: t('Overview'),
            href: route('isp-payment-center.index'),
            icon: LayoutDashboard,
            name: 'isp-payment-center-overview',
            menuKey: 'isp-payment-center-overview',
            routeName: 'isp-payment-center.index',
            order: 10,
        },
        {
            title: t('Orders'),
            href: route('orders.index'),
            icon: ShoppingCart,
            name: 'payment-center-orders',
            menuKey: 'payment-center-orders',
            routeName: 'orders.index',
            permission: 'manage-orders',
            order: 20,
        },
    ],
});

export default ispPaymentCenterSuperAdminMenu;
