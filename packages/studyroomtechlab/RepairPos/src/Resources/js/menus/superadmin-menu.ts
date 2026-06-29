import { ClipboardList, Package, ShoppingCart, Users, Wrench } from 'lucide-react';

declare global {
    function route(name: string): string;
}

export const repairPosSuperAdminMenu = (t: (key: string) => string) => ({
    title: t('Repair POS'),
    icon: Wrench,
    name: 'repair-pos',
    order: 3050,
    children: [
        { title: t('Dashboard'), href: route('repair-pos.dashboard'), icon: Wrench, permission: 'manage-add-on', permissions: ['manage-add-on', 'manage-plans', 'manage-settings'], order: 10 },
        { title: t('Customers'), href: route('repair-pos.customers.index'), icon: Users, permission: 'manage-add-on', permissions: ['manage-add-on', 'manage-plans', 'manage-settings'], order: 20 },
        { title: t('Repair Jobs'), href: route('repair-pos.jobs.index'), icon: ClipboardList, permission: 'manage-add-on', permissions: ['manage-add-on', 'manage-plans', 'manage-settings'], order: 30 },
        { title: t('Inventory'), href: route('repair-pos.products.index'), icon: Package, permission: 'manage-add-on', permissions: ['manage-add-on', 'manage-plans', 'manage-settings'], order: 40 },
        { title: t('Sales'), href: route('repair-pos.sales.index'), icon: ShoppingCart, permission: 'manage-add-on', permissions: ['manage-add-on', 'manage-plans', 'manage-settings'], order: 50 },
    ],
});
