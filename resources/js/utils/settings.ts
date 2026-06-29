import { SettingMenuItem } from './menus/superadmin-setting';
import { getSuperAdminSettings } from './menus/superadmin-setting';
import { getCompanySettings } from './menus/company-setting';

let packageSettingsCache: Record<string, SettingMenuItem[]> = {};


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
        'mpesa-payment': 'MpesaPayment',
        'mpesapayment': 'MpesaPayment',
        'm-pesa-payment': 'MpesaPayment',
        'isp-payment-center': 'IspPaymentCenter',
        'isppaymentcenter': 'IspPaymentCenter',
        'isp-sms': 'IspSms',
        'ispsms': 'IspSms',
    };

    return Array.from(new Set([
        raw,
        normalized,
        knownAliases[normalized],
        knownAliases[compact],
        pascalFromSeparators,
    ].filter(Boolean) as string[]));
};

// Get role-based core settings items
const getCoreSettingsItems = (userRoles: string[], t: (key: string) => string): SettingMenuItem[] => {
    if (userRoles.includes('superadmin')) {
        return getSuperAdminSettings(t);
    }
    return getCompanySettings(t);
};

// Auto-load package settings based on activated packages
const getPackageSettingsItems = (userRoles: string[], activatedPackages: string[], t: (key: string) => string): SettingMenuItem[] => {
    const cacheKey = `${userRoles.join(',')}-${[...activatedPackages].sort().join(',')}`;
    
    if (packageSettingsCache[cacheKey]) {
        return packageSettingsCache[cacheKey];
    }

    const menuItems: SettingMenuItem[] = [];
    const settingType = userRoles.includes('superadmin') ? 'superadmin-setting' : 'company-setting';
    const packageRoots = ['studyroomtechlab', 'workdo'];

    const allModules = userRoles.includes('superadmin')
        ? {
            ...import.meta.glob('../../../packages/studyroomtechlab/*/src/Resources/js/settings/superadmin-setting.ts', { eager: true }),
            ...import.meta.glob('../../../packages/workdo/*/src/Resources/js/settings/superadmin-setting.ts', { eager: true }),
        }
        : {
            ...import.meta.glob('../../../packages/studyroomtechlab/*/src/Resources/js/settings/company-setting.ts', { eager: true }),
            ...import.meta.glob('../../../packages/workdo/*/src/Resources/js/settings/company-setting.ts', { eager: true }),
        };

    (Array.isArray(activatedPackages) ? activatedPackages : []).forEach(packageName => {
        const module = packageNameCandidates(String(packageName))
            .flatMap((candidate) => packageRoots.map((packageRoot) => allModules[`../../../packages/${packageRoot}/${candidate}/src/Resources/js/settings/${settingType}.ts`] as any))
            .find(Boolean);

        if (module) {
            Object.values(module).forEach((item: any) => {
                const result = typeof item === 'function' ? item(t) : item;
                const items = Array.isArray(result) ? result : [result];
                menuItems.push(...items);
            });
        }
    });

    packageSettingsCache[cacheKey] = menuItems;
    return menuItems;
};

const normaliseSettingKey = (value: string): string =>
    String(value || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');

const dedupeSettingsItems = (items: SettingMenuItem[]): SettingMenuItem[] => {
    const seenStrict = new Set<string>();
    const seenTitles = new Set<string>();
    const deduped: SettingMenuItem[] = [];

    items.forEach((item) => {
        const strictKey = `${normaliseSettingKey(item.href)}:${normaliseSettingKey(item.component)}`;
        const titleKey = normaliseSettingKey(item.title);

        if (seenStrict.has(strictKey)) {
            return;
        }

        // Prevent duplicated package registrations from showing two identical sidebar labels,
        // for example two separate "Payment Gateway" entries when the same addon is loaded twice.
        if (seenTitles.has(titleKey)) {
            return;
        }

        seenStrict.add(strictKey);
        seenTitles.add(titleKey);
        deduped.push(item);
    });

    return deduped;
};

// Main function to get filtered settings items
export const allSettingsItems = (userPermissions: string[], userRoles: string[], activatedPackages: string[], t: (key: string) => string): SettingMenuItem[] => {
    const coreSettingsItems = getCoreSettingsItems(userRoles, t);
    const packageSettingsItems = getPackageSettingsItems(userRoles, activatedPackages, t);

    const allItems = [...coreSettingsItems, ...packageSettingsItems];

    // Sort by order, filter by permission, and remove duplicated addon menu entries.
    return dedupeSettingsItems(
        allItems
            .sort((a, b) => (a.order || 999) - (b.order || 999))
            .filter(item => userPermissions.includes(item.permission))
    );
};
