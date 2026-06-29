import { Gauge, UserCog, Images, WalletCards, TicketCheck, ShoppingBag, MessagesSquare, FileSignature, ReceiptText, Bot } from 'lucide-react';
import { NavItem } from '@/types';

export const getCompanyMenu = (t: (key: string) => string): NavItem[] => [
    {
        title: t('Dashboard'),
        icon: Gauge,
        permission: 'manage-dashboard',
        name: 'dashboard',
        order: 1,
    },
    {
        title: t('User Management'),
        icon: UserCog,
        permission: 'manage-users',
        order: 10,
        children: [
            {
                title: t('Roles'),
                href: route('roles.index'),
                permission: 'manage-roles',
            },
            {
                title: t('Users'),
                href: route('users.index'),
                permission: 'manage-users',
            },
        ],
    },
    {
        title: t('Proposal'),
        href: route('sales-proposals.index'),
        icon: FileSignature,
        permission: 'manage-sales-proposals',
        order: 20,
    },
    {
        title: t('Sales Invoice'),
        icon: ReceiptText,
        permission: 'manage-sales-invoices',
        order: 35,
        children: [
            {
                title: t('Sales Invoice'),
                href: route('sales-invoices.index'),
                permission: 'manage-sales-invoices',
            },
            {
                title: t('Sales Invoice Returns'),
                href: route('sales-returns.index'),
                permission: 'manage-sales-return-invoices',
            },
        ],
    },
    {
        title: t('Purchase'),
        icon: ShoppingBag,
        permission: 'manage-purchase-invoices',
        order: 40,
        children: [
            {
                title: t('Purchase Invoice'),
                href: route('purchase-invoices.index'),
                permission: 'manage-purchase-invoices',
            },
            {
                title: t('Purchase Returns'),
                href: route('purchase-returns.index'),
                permission: 'manage-purchase-return-invoices',
            },
            {
                title: t('Warehouses'),
                href: route('warehouses.index'),
                permission: 'manage-warehouses',
            },
            {
                title: t('Transfers'),
                href: route('transfers.index'),
                permission: 'manage-transfers',
            },
        ],
    },
    {
        title: t('Media Library'),
        href: route('media-library'),
        icon: Images,
        permission: 'manage-media',
        order: 2900,
    },
    {
        title: t('Messenger'),
        href: route('messenger.index'),
        icon: MessagesSquare,
        permission: 'manage-messenger',
        order: 2940,
    },
    {
        title: t('AI Agent'),
        href: route('ai-agent.chat.page'),
        icon: Bot,
        permission: 'manage-ai-agent',
        order: 2945,
    },
    {
        title: t('Tickets'),
        href: route('helpdesk-tickets.index'),
        icon: TicketCheck,
        permission: 'manage-helpdesk-tickets',
        order: 2950,
    },
    {
        title: t('Plan'),
        icon: WalletCards,
        permission: 'manage-plans',
        order: 2980,
        children: [
            {
                title: t('Subscription Plans'),
                href: route('plans.index'),
                permission: 'manage-plans',
            },
            {
                title: t('Bank Transfer Requests'),
                href: route('bank-transfer.index'),
                permission: 'manage-bank-transfer-requests',
            },
            {
                title: t('Orders'),
                href: route('orders.index'),
                permission: 'manage-orders',
            }
        ]
    },
];
