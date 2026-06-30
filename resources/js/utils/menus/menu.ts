import { NavItem } from '@/types';
import { usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { getSuperAdminMenu } from './superadmin-menu';
import { getCompanyMenu } from './company-menu';
import { getWifiBillingMenu } from './wifi-billing-menu';
import * as LucideIcons from 'lucide-react';

const WORKSPACE_PARENT_ADDON_PACKAGES = [
    'IspSms',
    'IspWhatsapp',
    'IspReport',
    'tr069',
    'loyalty',
    'IspPaymentCenter',
    'Expenses',
];

const normalizePackageToken = (value: string): string => String(value || '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '')
    .trim();


// Get role-based core menu items
const getCoreMenuItems = (userRoles: string[], t: (key: string) => string): NavItem[] => {
    const installedParentAddonMenus = getWorkspaceParentAddonMenuItems(userRoles, t);

    if (userRoles.includes('superadmin')) {
        return [...getSuperAdminMenu(t), ...installedParentAddonMenus];
    }

    return [...getCompanyMenu(t), ...getWifiBillingMenu(t), ...installedParentAddonMenus];
};

const packageNameCandidates = (packageName: string): string[] => {
    const raw = String(packageName || '').trim();
    const normalized = raw.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    const compact = raw.toLowerCase().replace(/[^a-z0-9]/g, '');
    const pascalFromSeparators = raw
        .split(/[^a-zA-Z0-9]+/)
        .filter(Boolean)
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join('');

    const knownAliases: Record<string, string> = {
        'isp-payment-center': 'IspPaymentCenter',
        'isppaymentcenter': 'IspPaymentCenter',
        'payment-center': 'IspPaymentCenter',
        'isp-sms': 'IspSms',
        'ispsms': 'IspSms',
        'sms': 'IspSms',
        'isp-whatsapp': 'IspWhatsapp',
        'ispwhatsapp': 'IspWhatsapp',
        'whatsapp': 'IspWhatsapp',
        'whatsapp-desk': 'IspWhatsapp',
        'isp-report': 'IspReport',
        'ispreport': 'IspReport',
        'reports': 'IspReport',
        'isp-reports': 'IspReport',
        'tr069': 'tr069',
        'tr-069': 'tr069',
        'loyalty': 'loyalty',
    };

    return Array.from(new Set([
        raw,
        normalized,
        knownAliases[normalized],
        knownAliases[compact],
        pascalFromSeparators,
    ].filter(Boolean) as string[]));
};

const packageExportToMenuItems = (module: Record<string, any>, t: (key: string) => string): NavItem[] => {
    const menuItems: NavItem[] = [];

    Array.from(new Set(Object.values(module))).forEach((item: any) => {
        const result = typeof item === 'function' ? item(t) : item;
        const items = Array.isArray(result) ? result : [result];
        menuItems.push(...items.filter(Boolean));
    });

    return menuItems;
};

const workspaceParentAddonPackageAliases = (): Set<string> => {
    return new Set(
        WORKSPACE_PARENT_ADDON_PACKAGES
            .flatMap((packageName) => packageNameCandidates(packageName))
            .map(normalizePackageToken)
    );
};

const isWorkspaceParentAddonPackage = (packageName: string): boolean => {
    const parentAliases = workspaceParentAddonPackageAliases();

    return packageNameCandidates(packageName).some((candidate) => parentAliases.has(normalizePackageToken(candidate)));
};

const loadPackageMenuItems = (userRoles: string[], packageNames: string[], t: (key: string) => string): NavItem[] => {
    const menuType = userRoles.includes('superadmin') ? 'superadmin-menu' : 'company-menu';
    const packageRoots = ['studyroomtechlab', 'workdo'];
    const allModules = {
        ...import.meta.glob('../../../packages/studyroomtechlab/*/src/Resources/js/menus/*.ts', { eager: true }),
        ...import.meta.glob('../../../packages/workdo/*/src/Resources/js/menus/*.ts', { eager: true }),
    };

    return Array.from(new Set(packageNames.filter(Boolean)))
        .filter((packageName) => !['wifibilling', 'wifi-billing'].includes(String(packageName).toLowerCase()))
        .flatMap((packageName) => {
            const module = packageNameCandidates(String(packageName))
                .flatMap((candidate) => packageRoots.map((packageRoot) => allModules[`../../../packages/${packageRoot}/${candidate}/src/Resources/js/menus/${menuType}.ts`] as any))
                .find(Boolean);

            return module ? packageExportToMenuItems(module, t) : [];
        });
};

// Installed StudyRoom workspace add-ons are parent menus, same pattern as WiFi Billing.
// They are loaded from their package menu files, not forced as child items.
const getWorkspaceParentAddonMenuItems = (userRoles: string[], t: (key: string) => string): NavItem[] => {
    return loadPackageMenuItems(userRoles, WORKSPACE_PARENT_ADDON_PACKAGES, t);
};

// Auto-load package menus based on activated packages, excluding workspace parent add-ons
// already loaded above to avoid duplicates.
const getPackageMenuItems = (userRoles: string[], activatedPackages: string[], t: (key: string) => string): NavItem[] => {
    const packageList = Array.isArray(activatedPackages) ? activatedPackages : [];
    const childOrExternalPackages = packageList.filter((packageName) => !isWorkspaceParentAddonPackage(String(packageName)));

    return loadPackageMenuItems(userRoles, childOrExternalPackages, t);
};

// Get custom menu items from database
const getCustomMenuItems = (userRoles: string[], t: (key: string) => string): NavItem[] => {
    const { auth } = usePage().props as any;
    const customMenus = auth?.customMenus || [];
    
    return customMenus.map((menu: any) => {
        // Convert string icon to Lucide icon component
        let iconComponent = null;
        if (menu.icon && typeof menu.icon === 'string') {
            const IconComponent = (LucideIcons as any)[menu.icon];
            if (IconComponent) {
                iconComponent = IconComponent;
            }
        }
        
        return {
            ...menu,
            icon: iconComponent,
        };
    });
};

// Group menu items by parent
const groupMenusByParent = (menuItems: NavItem[], packageMenuItems: NavItem[]): NavItem[] => {
    const groupedItems = [...menuItems];

    packageMenuItems.forEach(packageItem => {
        if (packageItem.parent) {
            const parentMenu = groupedItems.find(item =>
                item.name === packageItem.parent
            );

            if (parentMenu) {
                if (!parentMenu.children) {
                    parentMenu.children = [];
                }
                parentMenu.children.push({
                    ...packageItem,
                    parent: undefined
                });

                // Sort children by order
                if (parentMenu.children) {
                    parentMenu.children.sort((a, b) => (a.order || 999) - (b.order || 999));
                }
            } else {
                groupedItems.push(packageItem);
            }
        } else {
            groupedItems.push(packageItem);
        }
    });

    return groupedItems;
};

const normalizeVisibilityKey = (value?: string | null): string => {
    if (!value) {
        return '';
    }

    let normalized = String(value).trim();

    try {
        const parsedUrl = new URL(normalized, window.location.origin);
        normalized = parsedUrl.pathname;
    } catch {
        // Keep the original value when it is not URL-like.
    }

    normalized = normalized.replace(/^https?:\/\/[^/]+/i, '');
    normalized = normalized.replace(/[?#].*$/, '');
    normalized = normalized.replace(/^\/+|\/+$/g, '');
    normalized = normalized.replace(/[._/\\]+/g, '-');
    normalized = normalized.replace(/([a-z0-9])([A-Z])/g, '$1-$2');

    return normalized
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
};

const expandedVisibilityKeys = (values: Array<string | undefined | null>): string[] => {
    const expanded: string[] = [];

    values.forEach((value) => {
        const normalized = normalizeVisibilityKey(value);
        if (!normalized) {
            return;
        }

        expanded.push(normalized);

        if (normalized.endsWith('-invoices')) {
            expanded.push(normalized.slice(0, -1));
        }

        if (normalized.endsWith('-invoice')) {
            expanded.push(`${normalized}s`);
        }

        if (normalized.endsWith('-purchases')) {
            expanded.push(normalized.slice(0, -1));
        }

        if (normalized.endsWith('-purchase')) {
            expanded.push(`${normalized}s`);
        }

        if (normalized.endsWith('s') && normalized.length > 3) {
            expanded.push(normalized.replace(/s+$/, ''));
        }

        if (!normalized.endsWith('s')) {
            expanded.push(`${normalized}s`);
        }

        if (normalized.includes('invoice')) {
            expanded.push('invoice', 'invoices');
        }

        if (normalized.includes('purchase')) {
            expanded.push('purchase', 'purchases');
        }
    });

    return Array.from(new Set(expanded.filter(Boolean)));
};

const visibilityCandidates = (item: NavItem): string[] => {
    const menuItem = item as NavItem & { menuKey?: string; routeName?: string };
    const candidates = [
        menuItem.menuKey,
        menuItem.routeName,
        item.name,
        item.title,
        item.href,
    ];

    if (item.href) {
        try {
            const path = new URL(item.href, window.location.origin).pathname.replace(/^\/+|\/+$/g, '');
            const segments = path.split('/').filter(Boolean);
            candidates.push(path);
            candidates.push(segments[0]);
            if (segments.length >= 2) {
                candidates.push(`${segments[0]}-${segments[1]}`);
            }
            if (segments.length >= 1) {
                candidates.push(segments.join('-'));
            }
        } catch {
            // The raw href candidate above is enough when URL parsing is unavailable.
        }
    }

    return expandedVisibilityKeys(candidates);
};

const filterByVisibility = (items: NavItem[], hiddenKeys: string[], visibilityItems: Record<string, { visible: boolean }> = {}): NavItem[] => {
    const hidden = new Set(expandedVisibilityKeys(hiddenKeys));
    const visibilityByAlias = Object.entries(visibilityItems).reduce<Record<string, { visible: boolean }>>((aliases, [key, value]) => {
        expandedVisibilityKeys([key]).forEach((alias) => {
            aliases[alias] = value;
        });
        return aliases;
    }, {});

    if (!hidden.size && Object.keys(visibilityByAlias).length === 0) {
        return items;
    }

    return items.reduce<NavItem[]>((visibleItems, item) => {
        const candidates = visibilityCandidates(item);
        const isMenuControl = candidates.includes('menu-control');
        const isHidden = candidates.some(candidate => hidden.has(candidate) || visibilityByAlias[candidate]?.visible === false);

        if (isHidden && !isMenuControl) {
            return visibleItems;
        }

        const nextItem: NavItem = { ...item };
        if (item.children) {
            nextItem.children = filterByVisibility(item.children, hiddenKeys, visibilityItems);
            if (!nextItem.href && nextItem.children.length === 0) {
                return visibleItems;
            }
        }

        visibleItems.push(nextItem);
        return visibleItems;
    }, []);
};

// Filter menu items based on permissions
const filterByPermission = (items: NavItem[], userPermissions: string[]): NavItem[] => {
    return items.filter(item => {
        const menuItem = item as NavItem & { permissions?: string[] };
        const allowedPermissions = Array.isArray(menuItem.permissions) && menuItem.permissions.length > 0
            ? menuItem.permissions
            : (item.permission ? [item.permission] : []);

        if (allowedPermissions.length === 0) {
            if (item.children) {
                item.children = filterByPermission(item.children, userPermissions);
                return item.children.length > 0;
            }
            return true;
        }

        if (!allowedPermissions.some(permission => userPermissions.includes(permission))) {
            return false;
        }

        if (item.children) {
            item.children = filterByPermission(item.children, userPermissions);
            return item.children.length > 0;
        }

        return true;
    });
};


const firstMenuLabelKey = (item: NavItem): string => {
    return visibilityCandidates(item)[0] || normalizeVisibilityKey(item.title);
};

const applyMenuLabelPreferences = (
    items: NavItem[],
    labels: Record<string, string> = {},
    storedDefaults: Record<string, string> = {}
): NavItem[] => {
    const normalizedLabels = Object.entries(labels || {}).reduce<Record<string, string>>((carry, [key, label]) => {
        const normalizedKey = normalizeVisibilityKey(key);
        const cleanLabel = String(label || '').trim();

        if (normalizedKey && cleanLabel) {
            carry[normalizedKey] = cleanLabel;
        }

        return carry;
    }, {});

    return items.map((item) => {
        const candidates = visibilityCandidates(item);
        const menuLabelKey = candidates.find((candidate) => normalizedLabels[candidate]) || firstMenuLabelKey(item);
        const defaultTitle = storedDefaults[menuLabelKey] || item.title;
        const customTitle = normalizedLabels[menuLabelKey];

        return {
            ...item,
            title: customTitle || item.title,
            defaultTitle,
            customTitle,
            menuLabelKey,
            children: item.children
                ? applyMenuLabelPreferences(item.children, normalizedLabels, storedDefaults)
                : item.children,
        } as NavItem;
    });
};

// Main function to get filtered menu items
export const allMenuItems = (): NavItem[] => {
    const { auth } = usePage().props as any;
    const { t } = useTranslation();
    const userPermissions = auth?.user?.permissions || [];
    const userRoles = auth?.user?.roles || [];
    const activatedPackages = auth?.user?.activatedPackages || [];
    const hiddenMenuKeys = auth?.menuVisibility?.hidden || [];
    const visibilityItems = auth?.menuVisibility?.items || {};
    const menuLabelPreferences = auth?.menuLabelPreferences || {};
    const customMenuLabels = menuLabelPreferences?.labels || {};
    const storedMenuDefaults = menuLabelPreferences?.defaults || {};

    const coreMenuItems = getCoreMenuItems(userRoles, t);

    const packageMenuItems = getPackageMenuItems(userRoles, activatedPackages, t);
    
    const customMenuItems = getCustomMenuItems(userRoles, t);
    
    // Separate custom menus into parents and children
    const customParentMenus = customMenuItems.filter(menu => !menu.parent);
    const customChildMenus = customMenuItems.filter(menu => menu.parent);
    
    // First add custom parent menus to core menus
    const coreWithCustomParents = [...coreMenuItems, ...customParentMenus];
    
    // Then group all children (package + custom children) with their parents
    const allChildMenus = [...packageMenuItems, ...customChildMenus];
    const finalGroupedMenuItems = groupMenusByParent(coreWithCustomParents, allChildMenus);

    const sortedMenuItems = finalGroupedMenuItems.sort((a, b) => (a.order || 999) - (b.order || 999));

    const visibilityFilteredItems = filterByVisibility(sortedMenuItems, hiddenMenuKeys, visibilityItems);

    const finalMenuItems = filterByPermission(visibilityFilteredItems, userPermissions);

    return applyMenuLabelPreferences(finalMenuItems, customMenuLabels, storedMenuDefaults);
};
