import { Building, CreditCard, FileText, MessageSquareText, PanelLeft, SlidersHorizontal, Wifi } from 'lucide-react';

export interface SettingMenuItem {
  order: number;
  title: string;
  href: string;
  icon: any;
  permission: string;
  component: string;
}

export const getCompanySettings = (t: (key: string) => string): SettingMenuItem[] => [
  {
    order: 5,
    title: t('Admin Settings'),
    href: '#admin-settings',
    icon: SlidersHorizontal,
    permission: 'manage-dashboard',
    component: 'admin-module-settings'
  },
  {
    order: 10,
    title: t('Payment Settings'),
    href: '#payment-settings',
    icon: CreditCard,
    permission: 'manage-dashboard',
    component: 'admin-payment-settings'
  },
  {
    order: 20,
    title: t('SMS Settings'),
    href: '#sms-settings',
    icon: MessageSquareText,
    permission: 'manage-dashboard',
    component: 'admin-sms-settings'
  },
  {
    order: 30,
    title: t('SMS Template Settings'),
    href: '#sms-template-settings',
    icon: FileText,
    permission: 'manage-dashboard',
    component: 'admin-sms-template-settings'
  },
  {
    order: 40,
    title: t('Hotspot Template Settings'),
    href: '#hotspot-template-settings',
    icon: Wifi,
    permission: 'manage-dashboard',
    component: 'admin-hotspot-template-settings'
  },
  {
    order: 50,
    title: t('Menu Preferences'),
    href: '#menu-preferences',
    icon: PanelLeft,
    permission: 'manage-settings',
    component: 'menu-preferences'
  },
  {
    order: 60,
    title: t('Company Settings'),
    href: '#company-settings',
    icon: Building,
    permission: 'manage-company-settings',
    component: 'company-settings'
  }
];
