import { FormEvent, useMemo, useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useFlashMessages } from '@/hooks/useFlashMessages';
import { Eye, EyeOff, LockKeyhole, RotateCcw, Save, Search, SlidersHorizontal } from 'lucide-react';

interface MenuVisibilitySetting {
    id: number;
    menu_key: string;
    label: string;
    menu_group?: string | null;
    route_name?: string | null;
    aliases?: string[];
    sort_order: number;
    visible_to_superadmin: boolean;
    visible_to_admin: boolean;
    visible_to_isp_admin: boolean;
    block_route_access: boolean;
    is_locked?: boolean;
}

interface Props {
    menus: MenuVisibilitySetting[];
    roleLabels: Record<string, string>;
}

type VisibilityField = 'visible_to_superadmin' | 'visible_to_admin' | 'visible_to_isp_admin';

const roleFields: VisibilityField[] = [
    'visible_to_superadmin',
    'visible_to_admin',
    'visible_to_isp_admin',
];

export default function MenuControl({ menus, roleLabels }: Props) {
    const { t } = useTranslation();
    const [search, setSearch] = useState('');

    const { data, setData, put, processing, isDirty } = useForm({
        menus: menus.map((menu) => ({
            ...menu,
            visible_to_superadmin: Boolean(menu.visible_to_superadmin),
            visible_to_admin: Boolean(menu.visible_to_admin),
            visible_to_isp_admin: Boolean(menu.visible_to_isp_admin),
            block_route_access: Boolean(menu.block_route_access),
        })),
    });

    useFlashMessages();

    const filteredMenus = useMemo(() => {
        const term = search.trim().toLowerCase();
        if (!term) {
            return data.menus;
        }

        return data.menus.filter((menu) => [
            menu.label,
            menu.menu_key,
            menu.menu_group,
            menu.route_name,
            ...(menu.aliases || []),
        ].filter(Boolean).some((value) => String(value).toLowerCase().includes(term)));
    }, [data.menus, search]);

    const groups = useMemo(() => {
        return Array.from(new Set(data.menus.map((menu) => menu.menu_group || t('Ungrouped')))).length;
    }, [data.menus, t]);

    const hiddenMenus = useMemo(() => {
        return data.menus.filter((menu) => (
            !menu.visible_to_superadmin ||
            !menu.visible_to_admin ||
            !menu.visible_to_isp_admin
        )).length;
    }, [data.menus]);

    const blockedRoutes = useMemo(() => {
        return data.menus.filter((menu) => menu.block_route_access).length;
    }, [data.menus]);

    const updateMenu = (menuKey: string, field: VisibilityField | 'block_route_access', value: boolean) => {
        setData('menus', data.menus.map((menu) => {
            if (menu.menu_key !== menuKey) {
                return menu;
            }

            if (menu.is_locked) {
                return {
                    ...menu,
                    visible_to_superadmin: true,
                    visible_to_admin: false,
                    visible_to_isp_admin: false,
                    block_route_access: false,
                };
            }

            return { ...menu, [field]: value };
        }));
    };

    const restoreMenus = () => {
        setData('menus', data.menus.map((menu) => ({
            ...menu,
            visible_to_superadmin: true,
            visible_to_admin: menu.is_locked ? false : true,
            visible_to_isp_admin: menu.is_locked ? false : true,
            block_route_access: false,
        })));
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        put(route('super-admin.menu-control.update'), {
            preserveScroll: true,
        });
    };

    const visibleForAll = (menu: MenuVisibilitySetting) => (
        menu.visible_to_superadmin &&
        menu.visible_to_admin &&
        menu.visible_to_isp_admin
    );

    const roleLabel = (field: VisibilityField) => roleLabels?.[field] || field;

    return (
        <AuthenticatedLayout
            breadcrumbs={[{ label: t('Menu Control') }]}
            pageTitle={t('Menu Control')}
            pageActions={
                <div className="flex items-center gap-2">
                    <Button type="button" variant="outline" size="sm" onClick={restoreMenus}>
                        <RotateCcw className="mr-2 h-4 w-4" />
                        {t('Restore')}
                    </Button>
                    <Button type="submit" form="menu-control-form" size="sm" disabled={processing || !isDirty}>
                        <Save className="mr-2 h-4 w-4" />
                        {processing ? t('Saving...') : t('Save')}
                    </Button>
                </div>
            }
        >
            <Head title={t('Menu Control')} />

            <form id="menu-control-form" onSubmit={submit} className="space-y-5">
                <div className="grid gap-4 md:grid-cols-4">
                    <div className="rounded-lg border bg-white p-4 shadow-sm">
                        <SlidersHorizontal className="mb-3 h-5 w-5 text-blue-600" />
                        <div className="text-2xl font-semibold text-slate-950">{data.menus.length}</div>
                        <div className="text-xs text-slate-500">{t('Menus')}</div>
                    </div>
                    <div className="rounded-lg border bg-white p-4 shadow-sm">
                        <Eye className="mb-3 h-5 w-5 text-emerald-600" />
                        <div className="text-2xl font-semibold text-slate-950">{data.menus.length - hiddenMenus}</div>
                        <div className="text-xs text-slate-500">{t('Fully visible')}</div>
                    </div>
                    <div className="rounded-lg border bg-white p-4 shadow-sm">
                        <EyeOff className="mb-3 h-5 w-5 text-amber-600" />
                        <div className="text-2xl font-semibold text-slate-950">{hiddenMenus}</div>
                        <div className="text-xs text-slate-500">{t('Hidden by role')}</div>
                    </div>
                    <div className="rounded-lg border bg-white p-4 shadow-sm">
                        <LockKeyhole className="mb-3 h-5 w-5 text-red-600" />
                        <div className="text-2xl font-semibold text-slate-950">{blockedRoutes}</div>
                        <div className="text-xs text-slate-500">{t('Route blocks')}</div>
                    </div>
                </div>

                <Card className="shadow-sm">
                    <CardContent className="border-b bg-gray-50/50 p-4">
                        <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div className="relative w-full md:max-w-sm">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <Input
                                    value={search}
                                    onChange={(event) => setSearch(event.target.value)}
                                    placeholder={t('Search menus...')}
                                    className="pl-9"
                                />
                            </div>
                            <div className="flex flex-wrap items-center gap-2">
                                <Badge variant="outline">{groups} {t('groups')}</Badge>
                                <Badge variant="outline">{filteredMenus.length} {t('shown')}</Badge>
                            </div>
                        </div>
                    </CardContent>

                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="min-w-[240px]">{t('Menu')}</TableHead>
                                    {roleFields.map((field) => (
                                        <TableHead key={field} className="w-[150px] text-center">{roleLabel(field)}</TableHead>
                                    ))}
                                    <TableHead className="w-[150px] text-center">{t('Block Route')}</TableHead>
                                    <TableHead className="w-[130px] text-center">{t('Status')}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {filteredMenus.map((menu) => (
                                    <TableRow key={menu.menu_key}>
                                        <TableCell>
                                            <div className="flex flex-col gap-1">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <span className="font-medium text-slate-950">{t(menu.label)}</span>
                                                    {menu.is_locked && (
                                                        <Badge variant="outline" className="text-blue-700">
                                                            <LockKeyhole className="mr-1 h-3 w-3" />
                                                            {t('Locked')}
                                                        </Badge>
                                                    )}
                                                </div>
                                                <div className="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                                    <span>{menu.menu_key}</span>
                                                    {menu.menu_group && <span>{menu.menu_group}</span>}
                                                    {menu.route_name && <span>{menu.route_name}</span>}
                                                </div>
                                            </div>
                                        </TableCell>
                                        {roleFields.map((field) => (
                                            <TableCell key={`${menu.menu_key}-${field}`} className="text-center">
                                                <Switch
                                                    checked={menu.is_locked ? field === 'visible_to_superadmin' : Boolean(menu[field])}
                                                    disabled={Boolean(menu.is_locked)}
                                                    onCheckedChange={(checked) => updateMenu(menu.menu_key, field, checked)}
                                                    aria-label={`${menu.label} ${roleLabel(field)}`}
                                                />
                                            </TableCell>
                                        ))}
                                        <TableCell className="text-center">
                                            <Switch
                                                checked={menu.is_locked ? false : Boolean(menu.block_route_access)}
                                                disabled={Boolean(menu.is_locked)}
                                                onCheckedChange={(checked) => updateMenu(menu.menu_key, 'block_route_access', checked)}
                                                aria-label={`${menu.label} ${t('Block Route')}`}
                                            />
                                        </TableCell>
                                        <TableCell className="text-center">
                                            {visibleForAll(menu) ? (
                                                <Badge className="bg-emerald-100 text-emerald-700 hover:bg-emerald-100">{t('Visible')}</Badge>
                                            ) : (
                                                <Badge className="bg-amber-100 text-amber-700 hover:bg-amber-100">{t('Limited')}</Badge>
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </form>
        </AuthenticatedLayout>
    );
}
