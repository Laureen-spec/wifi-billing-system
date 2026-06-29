import { Activity, HardDriveUpload, RadioTower, Router, ServerCog, Settings, UploadCloud } from 'lucide-react';

declare global {
    function route(name: string): string;
}

const tr069CompanyMenu = (t: (key: string) => string) => ({
    title: t('TR069'),
    icon: RadioTower,
    name: 'tr069',
    menuKey: 'tr069',
    routeName: 'tr069.index',
    order: 67,
    children: [
        {
            title: t('ACS Dashboard'),
            href: route('tr069.index'),
            icon: ServerCog,
            name: 'tr069-dashboard',
            menuKey: 'tr069',
            routeName: 'tr069.index',
            order: 10,
        },
        {
            title: t('CPE Devices'),
            href: route('tr069.devices.index'),
            icon: Router,
            name: 'tr069-devices',
            menuKey: 'tr069',
            routeName: 'tr069.devices.index',
            order: 20,
        },
        {
            title: t('Provisioning Profiles'),
            href: route('tr069.profiles.index'),
            icon: UploadCloud,
            name: 'tr069-profiles',
            menuKey: 'tr069',
            routeName: 'tr069.profiles.index',
            order: 30,
        },
        {
            title: t('Configuration Jobs'),
            href: route('tr069.jobs.index'),
            icon: ServerCog,
            name: 'tr069-jobs',
            menuKey: 'tr069',
            routeName: 'tr069.jobs.index',
            order: 40,
        },
        {
            title: t('Firmware Updates'),
            href: route('tr069.firmware.index'),
            icon: HardDriveUpload,
            name: 'tr069-firmware',
            menuKey: 'tr069',
            routeName: 'tr069.firmware.index',
            order: 50,
        },
        {
            title: t('Device Logs'),
            href: route('tr069.logs.index'),
            icon: Activity,
            name: 'tr069-logs',
            menuKey: 'tr069',
            routeName: 'tr069.logs.index',
            order: 60,
        },
        {
            title: t('Settings'),
            href: route('tr069.settings'),
            icon: Settings,
            name: 'tr069-settings',
            menuKey: 'tr069',
            routeName: 'tr069.settings',
            order: 70,
        },
    ],
});

export default tr069CompanyMenu;
