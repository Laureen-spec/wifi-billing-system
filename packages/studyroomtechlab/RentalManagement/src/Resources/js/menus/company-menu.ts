import { Building2, FileText, Home, Users } from 'lucide-react';

declare global {
    function route(name: string): string;
}

export const rentalManagementCompanyMenu = (t: (key: string) => string) => ({
    title: t('Rental Management'),
    icon: Building2,
    name: 'rental-management',
    order: 3040,
    children: [
        { title: t('Dashboard'), href: route('rental-management.dashboard'), icon: Home, permission: 'manage-add-on', permissions: ['manage-add-on', 'manage-plans', 'manage-settings'], order: 10 },
        { title: t('Properties'), href: route('rental-management.properties.index'), icon: Building2, permission: 'manage-add-on', permissions: ['manage-add-on', 'manage-plans', 'manage-settings'], order: 20 },
        { title: t('Tenants'), href: route('rental-management.tenants.index'), icon: Users, permission: 'manage-add-on', permissions: ['manage-add-on', 'manage-plans', 'manage-settings'], order: 30 },
        { title: t('Invoices'), href: route('rental-management.invoices.index'), icon: FileText, permission: 'manage-add-on', permissions: ['manage-add-on', 'manage-plans', 'manage-settings'], order: 40 },
    ],
});
