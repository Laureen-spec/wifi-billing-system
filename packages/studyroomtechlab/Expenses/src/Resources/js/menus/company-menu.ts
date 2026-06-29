import { ReceiptText } from 'lucide-react';

declare global {
    function route(name: string): string;
}

const expensesCompanyMenu = (t: (key: string) => string) => ({
    title: t('Expenses'),
    href: route('expenses.index'),
    icon: ReceiptText,
    name: 'expenses',
    menuKey: 'expenses',
    routeName: 'expenses.index',
    order: 58,
});

export default expensesCompanyMenu;
