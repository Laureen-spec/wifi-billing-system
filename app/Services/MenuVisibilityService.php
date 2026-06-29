<?php

namespace App\Services;

use App\Models\MenuVisibilitySetting;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class MenuVisibilityService
{
    private ?Collection $settingsCache = null;

    public function seedDefaults(?int $userId = null): void
    {
        if (! $this->tableReady()) {
            return;
        }

        foreach ($this->defaultMenuItems() as $item) {
            $setting = MenuVisibilitySetting::query()->firstOrNew([
                'menu_key' => $item['menu_key'],
            ]);

            $setting->fill([
                'label' => $item['label'],
                'menu_group' => $item['menu_group'] ?? null,
                'parent_key' => $item['parent_key'] ?? null,
                'route_name' => $item['route_name'] ?? null,
                'url' => $item['url'] ?? null,
                'aliases' => $this->normalizedAliases($item),
                'sort_order' => $item['sort_order'] ?? 999,
                'is_system' => true,
            ]);

            if (! $setting->exists) {
                $setting->visible_to_superadmin = $item['visible_to_superadmin'] ?? true;
                $setting->visible_to_admin = $item['visible_to_admin'] ?? true;
                $setting->visible_to_isp_admin = $item['visible_to_isp_admin'] ?? true;
                $setting->block_route_access = false;
                $setting->created_by = $userId;
            }

            if (! $setting->exists || $setting->isDirty()) {
                $setting->updated_by = $userId;
                $setting->save();
            }
        }

        $this->settingsCache = null;
    }

    public function registerMenuIfMissing(string $menuKey, string $label, array $metadata = [], ?int $userId = null): ?MenuVisibilitySetting
    {
        if (! $this->tableReady()) {
            return null;
        }

        $menuKey = $this->normalizeKey($menuKey ?: $label);
        if ($menuKey === '') {
            return null;
        }

        $setting = MenuVisibilitySetting::query()->firstOrNew(['menu_key' => $menuKey]);
        if ($setting->exists) {
            return $setting;
        }

        $item = array_merge($metadata, [
            'menu_key' => $menuKey,
            'label' => $label,
        ]);

        $setting->fill([
            'label' => $label,
            'menu_group' => $metadata['menu_group'] ?? null,
            'parent_key' => isset($metadata['parent_key']) ? $this->normalizeKey($metadata['parent_key']) : null,
            'route_name' => $metadata['route_name'] ?? null,
            'url' => $metadata['url'] ?? null,
            'aliases' => $this->normalizedAliases($item),
            'sort_order' => $metadata['sort_order'] ?? 999,
            'visible_to_superadmin' => true,
            'visible_to_admin' => true,
            'visible_to_isp_admin' => true,
            'block_route_access' => false,
            'is_system' => false,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
        $setting->save();

        $this->settingsCache = null;

        return $setting;
    }

    public function settingsForManage(?int $userId = null): array
    {
        $this->seedDefaults($userId);

        return $this->settings()
            ->sortBy([
                ['menu_group', 'asc'],
                ['sort_order', 'asc'],
                ['label', 'asc'],
            ])
            ->values()
            ->map(fn (MenuVisibilitySetting $setting) => $this->settingPayload($setting))
            ->all();
    }

    public function updateSettings(array $items, ?int $userId = null): void
    {
        if (! $this->tableReady()) {
            return;
        }

        $this->seedDefaults($userId);
        $settings = $this->settings()->keyBy('menu_key');

        foreach ($items as $item) {
            $menuKey = $this->normalizeKey($item['menu_key'] ?? $item['key'] ?? '');
            if (! $menuKey || ! $settings->has($menuKey)) {
                continue;
            }

            /** @var MenuVisibilitySetting $setting */
            $setting = $settings->get($menuKey);
            $isMenuControl = $menuKey === 'menu-control';

            $setting->visible_to_superadmin = $isMenuControl ? true : (bool) ($item['visible_to_superadmin'] ?? false);
            $setting->visible_to_admin = (bool) ($item['visible_to_admin'] ?? false);
            $setting->visible_to_isp_admin = (bool) ($item['visible_to_isp_admin'] ?? false);
            $setting->block_route_access = $isMenuControl ? false : (bool) ($item['block_route_access'] ?? false);
            $setting->updated_by = $userId;
            $setting->save();
        }

        $this->settingsCache = null;
    }

    public function visibilityPayload(?Authenticatable $user): array
    {
        $this->seedDefaults();

        $role = $this->roleFor($user);
        $hidden = [];
        $items = [];

        if ($user && $this->tableReady()) {
            foreach ($this->settings() as $setting) {
                $visible = $this->isForcedVisible($setting, $role) || $this->settingVisibleForRole($setting, $role);
                $aliases = $this->aliasesFor($setting);

                foreach ($aliases as $alias) {
                    $items[$alias] = [
                        'menu_key' => $setting->menu_key,
                        'label' => $setting->label,
                        'visible' => $visible,
                        'block_route_access' => (bool) $setting->block_route_access,
                    ];
                }

                if ($this->isForcedVisible($setting, $role)) {
                    continue;
                }

                if (! $visible) {
                    $hidden = array_merge($hidden, $aliases);
                }
            }
        }

        return [
            'role' => $role,
            'hidden' => array_values(array_unique(array_filter($hidden))),
            'items' => $items,
        ];
    }

    public function isVisible(string $menuKey, ?Authenticatable $user = null): bool
    {
        if (! $user || ! $this->tableReady()) {
            return true;
        }

        $role = $this->roleFor($user);
        $setting = $this->settingByKeyOrAlias($menuKey);

        if (! $setting) {
            return true;
        }

        if ($this->isForcedVisible($setting, $role)) {
            return true;
        }

        return $this->settingVisibleForRole($setting, $role);
    }

    public function isVisibleOrRegister(string $menuKey, string $label, ?Authenticatable $user = null, array $metadata = []): bool
    {
        if (! $this->tableReady()) {
            return true;
        }

        $setting = $this->settingByKeyOrAlias($menuKey)
            ?: $this->registerMenuIfMissing($menuKey, $label, $metadata);

        if (! $setting) {
            return true;
        }

        if (! $user) {
            return true;
        }

        $role = $this->roleFor($user);
        if ($this->isForcedVisible($setting, $role)) {
            return true;
        }

        return $this->settingVisibleForRole($setting, $role);
    }

    public function canAccessRoute(Request $request, ?Authenticatable $user): bool
    {
        if (! $user || ! $this->tableReady()) {
            return true;
        }

        $setting = $this->settingForRequest($request);
        if (! $setting || ! $setting->block_route_access) {
            return true;
        }

        return $this->isVisible($setting->menu_key, $user);
    }

    public function isSuperAdmin(?Authenticatable $user): bool
    {
        if (! $user) {
            return false;
        }

        $type = (string) ($user->type ?? '');
        if (in_array($type, ['superadmin', 'super_admin', 'control_isp'], true)) {
            return true;
        }

        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['superadmin', 'super_admin', 'control_isp'])) {
            return true;
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('superadmin')) {
            return true;
        }

        return count(array_intersect($this->userRoleTokens($user), ['superadmin', 'super-admin', 'control-isp'])) > 0;
    }

    public function normalizeKey(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/^https?:\/\/[^\/]+/i', '', $value) ?? $value;
        $value = trim($value, " \t\n\r\0\x0B/#");
        $value = str_replace(['.', '_', '/', '\\'], '-', $value);

        return Str::slug($value);
    }

    private function settingByKeyOrAlias(string $key): ?MenuVisibilitySetting
    {
        $normalized = $this->normalizeKey($key);
        if ($normalized === '') {
            return null;
        }

        return $this->settings()->first(function (MenuVisibilitySetting $setting) use ($normalized) {
            return in_array($normalized, $this->aliasesFor($setting), true);
        });
    }

    private function settingForRequest(Request $request): ?MenuVisibilitySetting
    {
        $requestCandidates = $this->requestCandidates($request);

        return $this->settings()->first(function (MenuVisibilitySetting $setting) use ($request, $requestCandidates) {
            if ($setting->route_name && $request->routeIs($setting->route_name)) {
                return true;
            }

            return count(array_intersect($requestCandidates, $this->aliasesFor($setting))) > 0;
        });
    }

    private function requestCandidates(Request $request): array
    {
        $candidates = [];
        $routeName = optional($request->route())->getName();

        if ($routeName) {
            $candidates[] = $routeName;
            $candidates[] = Str::before($routeName, '.');
        }

        $path = trim($request->path(), '/');
        if ($path !== '') {
            $segments = explode('/', $path);
            $candidates[] = $path;
            $candidates[] = $segments[0] ?? '';

            if (count($segments) >= 2) {
                $candidates[] = $segments[0] . '-' . $segments[1];
            }
        }

        return array_values(array_unique(array_filter(array_map(
            fn ($candidate) => $this->normalizeKey((string) $candidate),
            $candidates
        ))));
    }

    private function settingVisibleForRole(MenuVisibilitySetting $setting, string $role): bool
    {
        return match ($role) {
            'superadmin' => (bool) $setting->visible_to_superadmin,
            'isp_admin' => (bool) $setting->visible_to_isp_admin,
            default => (bool) $setting->visible_to_admin,
        };
    }

    private function isForcedVisible(MenuVisibilitySetting $setting, string $role): bool
    {
        return $role === 'superadmin' && $setting->menu_key === 'menu-control';
    }

    private function roleFor(?Authenticatable $user): string
    {
        if (! $user) {
            return 'guest';
        }

        if ($this->isSuperAdmin($user)) {
            return 'superadmin';
        }

        $tokens = $this->userRoleTokens($user);
        if (count(array_intersect($tokens, ['isp-admin', 'ispadmin', 'isp-administrator']))) {
            return 'isp_admin';
        }

        return 'admin';
    }

    private function userRoleTokens(Authenticatable $user): array
    {
        $roles = [(string) ($user->type ?? '')];

        if (method_exists($user, 'getRoleNames')) {
            $roles = array_merge($roles, $user->getRoleNames()->toArray());
        }

        return array_values(array_unique(array_filter(array_map(
            fn ($role) => Str::slug(strtolower((string) $role)),
            $roles
        ))));
    }

    private function settingPayload(MenuVisibilitySetting $setting): array
    {
        return [
            'id' => $setting->id,
            'menu_key' => $setting->menu_key,
            'label' => $setting->label,
            'menu_group' => $setting->menu_group,
            'parent_key' => $setting->parent_key,
            'route_name' => $setting->route_name,
            'url' => $setting->url,
            'aliases' => $setting->aliases ?: [],
            'sort_order' => $setting->sort_order,
            'visible_to_superadmin' => $setting->visible_to_superadmin,
            'visible_to_admin' => $setting->visible_to_admin,
            'visible_to_isp_admin' => $setting->visible_to_isp_admin,
            'block_route_access' => $setting->block_route_access,
            'is_system' => $setting->is_system,
            'is_locked' => $setting->menu_key === 'menu-control',
        ];
    }

    private function aliasesFor(MenuVisibilitySetting $setting): array
    {
        $routeRoot = $setting->route_name ? Str::before($setting->route_name, '.') : null;

        $aliases = array_merge([
            $setting->menu_key,
            $setting->label,
            $setting->route_name,
            $routeRoot,
            $setting->url,
        ], $setting->aliases ?: []);

        return $this->expandedAliases($aliases);
    }

    private function normalizedAliases(array $item): array
    {
        $aliases = array_merge([
            $item['menu_key'] ?? '',
            $item['label'] ?? '',
            $item['route_name'] ?? '',
            isset($item['route_name']) ? Str::before($item['route_name'], '.') : '',
            $item['url'] ?? '',
        ], $item['aliases'] ?? []);

        return $this->expandedAliases($aliases);
    }

    private function expandedAliases(array $aliases): array
    {
        $expanded = [];

        foreach ($aliases as $alias) {
            $normalized = $this->normalizeKey((string) $alias);
            if ($normalized === '') {
                continue;
            }

            $expanded[] = $normalized;

            if (str_ends_with($normalized, '-invoices')) {
                $expanded[] = Str::beforeLast($normalized, 's');
            }

            if (str_ends_with($normalized, '-invoice')) {
                $expanded[] = $normalized . 's';
            }

            if (str_ends_with($normalized, '-purchases')) {
                $expanded[] = Str::beforeLast($normalized, 's');
            }

            if (str_ends_with($normalized, '-purchase')) {
                $expanded[] = $normalized . 's';
            }

            if (str_ends_with($normalized, 's') && strlen($normalized) > 3) {
                $expanded[] = rtrim($normalized, 's');
            }

            if (! str_ends_with($normalized, 's')) {
                $expanded[] = $normalized . 's';
            }

            if (str_contains($normalized, 'invoice')) {
                $expanded[] = 'invoice';
                $expanded[] = 'invoices';
            }

            if (str_contains($normalized, 'purchase')) {
                $expanded[] = 'purchase';
                $expanded[] = 'purchases';
            }
        }

        return array_values(array_unique(array_filter($expanded)));
    }

    private function settings(): Collection
    {
        if (! $this->tableReady()) {
            return collect();
        }

        if ($this->settingsCache === null) {
            $this->settingsCache = MenuVisibilitySetting::query()->get();
        }

        return $this->settingsCache;
    }

    private function tableReady(): bool
    {
        try {
            return Schema::hasTable('menu_visibility_settings');
        } catch (Throwable) {
            return false;
        }
    }

    private function defaultMenuItems(): array
    {
        return array_merge([
            ['menu_key' => 'dashboard', 'label' => 'Dashboard', 'menu_group' => 'Core', 'route_name' => 'dashboard', 'aliases' => ['billing-dashboard'], 'sort_order' => 1],
            ['menu_key' => 'user-management', 'label' => 'User Management', 'menu_group' => 'Core', 'route_name' => 'users.index', 'aliases' => ['users', 'roles'], 'sort_order' => 10],
            ['menu_key' => 'isps', 'label' => 'ISPs', 'menu_group' => 'Core', 'route_name' => 'isps.index', 'aliases' => ['isp-management'], 'sort_order' => 15],
            ['menu_key' => 'wifi-billing', 'label' => 'WiFi Billing', 'menu_group' => 'Core', 'route_name' => 'wifi-billing.dashboard', 'aliases' => ['isp', 'isp-packages', 'isp-customers', 'isp-routers', 'isp-provisioning'], 'sort_order' => 18],
            ['menu_key' => 'isp-dashboard', 'label' => 'WiFi Billing Dashboard', 'menu_group' => 'WiFi Billing', 'parent_key' => 'wifi-billing', 'route_name' => 'wifi-billing.dashboard', 'aliases' => ['wifi-billing-dashboard'], 'sort_order' => 19],
            ['menu_key' => 'isp-customers', 'label' => 'ISP Customers', 'menu_group' => 'WiFi Billing', 'parent_key' => 'wifi-billing', 'route_name' => 'wifi-billing.customers.index', 'aliases' => ['wifi-billing-customers', 'isp-customers-index'], 'sort_order' => 20],
            ['menu_key' => 'isp-routers', 'label' => 'ISP Routers', 'menu_group' => 'WiFi Billing', 'parent_key' => 'wifi-billing', 'route_name' => 'wifi-billing.routers.index', 'aliases' => ['wifi-billing-routers', 'mikrotik-routers'], 'sort_order' => 21],
            ['menu_key' => 'isp-live-sessions', 'label' => 'ISP Live Sessions', 'menu_group' => 'WiFi Billing', 'parent_key' => 'wifi-billing', 'route_name' => 'wifi-billing.live-sessions.index', 'aliases' => ['wifi-billing-live-sessions', 'live-sessions'], 'sort_order' => 22],
            ['menu_key' => 'isp-plans', 'label' => 'ISP Plans', 'menu_group' => 'WiFi Billing', 'parent_key' => 'wifi-billing', 'route_name' => 'wifi-billing.packages.index', 'aliases' => ['wifi-billing-packages', 'isp-packages', 'internet-packages'], 'sort_order' => 23],
            ['menu_key' => 'isp-settings', 'label' => 'ISP Settings', 'menu_group' => 'WiFi Billing', 'parent_key' => 'wifi-billing', 'route_name' => 'wifi-billing.settings.index', 'aliases' => ['wifi-billing-settings'], 'sort_order' => 24],
            ['menu_key' => 'isp-hotspot-template', 'label' => 'Hotspot Template', 'menu_group' => 'WiFi Billing', 'parent_key' => 'isp-settings', 'route_name' => 'wifi-billing.settings.hotspot-template.edit', 'aliases' => ['wifi-billing-hotspot-template', 'hotspot-template'], 'sort_order' => 25],
            ['menu_key' => 'proposal', 'label' => 'Proposal', 'menu_group' => 'Sales', 'route_name' => 'sales-proposals.index', 'aliases' => ['proposals', 'sales-proposals', 'sales-proposal', 'quotation', 'quotations'], 'sort_order' => 20],
            ['menu_key' => 'sales-invoice', 'label' => 'Sales Invoice', 'menu_group' => 'Sales', 'route_name' => 'sales-invoices.index', 'aliases' => ['sales invoice', 'sales invoices', 'invoice', 'invoices', 'sales-invoices', 'sales-returns', 'sales-return'], 'sort_order' => 35],
            ['menu_key' => 'purchase', 'label' => 'Purchase', 'menu_group' => 'Purchasing', 'route_name' => 'purchase-invoices.index', 'aliases' => ['purchases', 'purchase invoice', 'purchase invoices', 'purchase return', 'purchase returns', 'purchase-invoices', 'purchase-returns', 'warehouses', 'transfers', 'bill', 'bills'], 'sort_order' => 40],
            ['menu_key' => 'media-library', 'label' => 'Media Library', 'menu_group' => 'Content', 'route_name' => 'media-library', 'aliases' => ['media'], 'sort_order' => 2900],
            ['menu_key' => 'messenger', 'label' => 'Messenger', 'menu_group' => 'Communication', 'route_name' => 'messenger.index', 'aliases' => ['messenger-contacts', 'messenger-messages'], 'sort_order' => 2940],
            ['menu_key' => 'ai-agent', 'label' => 'AI Agent', 'menu_group' => 'Communication', 'route_name' => 'ai-agent.chat.page', 'aliases' => ['ai', 'ai-agent-chat', 'ai-agent-chat-page', 'assistant', 'bot'], 'sort_order' => 2945],
            ['menu_key' => 'helpdesk', 'label' => 'Helpdesk', 'menu_group' => 'Support', 'route_name' => 'helpdesk-tickets.index', 'aliases' => ['help desk', 'support', 'ticket', 'tickets', 'helpdesk-ticket', 'helpdesk-tickets', 'helpdesk-categories', 'helpdesk-replies'], 'sort_order' => 2950],
            ['menu_key' => 'subscription', 'label' => 'Subscription', 'menu_group' => 'Platform', 'route_name' => 'plans.index', 'aliases' => ['plans', 'coupons', 'orders', 'bank-transfer'], 'sort_order' => 2800],
            ['menu_key' => 'email-templates', 'label' => 'Email Templates', 'menu_group' => 'Platform', 'route_name' => 'email-templates.index', 'sort_order' => 2850],
            ['menu_key' => 'notification-templates', 'label' => 'Notification Templates', 'menu_group' => 'Platform', 'route_name' => 'notification-templates.index', 'sort_order' => 2900],
            ['menu_key' => 'add-ons-manager', 'label' => 'Add-ons Manager', 'menu_group' => 'Platform', 'route_name' => 'add-ons.index', 'aliases' => ['add-ons', 'add-on'], 'sort_order' => 3000],
            ['menu_key' => 'settings', 'label' => 'Settings', 'menu_group' => 'Platform', 'route_name' => 'settings.index', 'sort_order' => 3050],
            ['menu_key' => 'menu-control', 'label' => 'Menu Control', 'menu_group' => 'Platform', 'route_name' => 'super-admin.menu-control.index', 'visible_to_admin' => false, 'visible_to_isp_admin' => false, 'sort_order' => 3040],
        ], $this->packageMenuDefaults());
    }

    private function packageMenuDefaults(): array
    {
        $items = [];
        $reservedKeys = [
            'dashboard',
            'user-management',
            'isps',
            'wifi-billing',
            'isp-dashboard',
            'isp-customers',
            'isp-routers',
            'isp-live-sessions',
            'isp-plans',
            'isp-settings',
            'isp-hotspot-template',
            'proposal',
            'sales-invoice',
            'purchase',
            'media-library',
            'messenger',
            'ai-agent',
            'helpdesk',
            'subscription',
            'email-templates',
            'notification-templates',
            'add-ons-manager',
            'settings',
            'menu-control',
        ];

        foreach (['workdo', 'studyroomtechlab'] as $vendor) {
            $root = base_path("packages/{$vendor}");
            if (! File::isDirectory($root)) {
                continue;
            }

            foreach (File::directories($root) as $directory) {
                $moduleName = basename($directory);
                $moduleJson = $directory . DIRECTORY_SEPARATOR . 'module.json';
                if (! File::exists($moduleJson)) {
                    continue;
                }

                $data = json_decode((string) File::get($moduleJson), true) ?: [];
                $label = $data['alias'] ?? $data['name'] ?? $moduleName;
                $moduleKey = $this->normalizeKey(Str::snake($moduleName, ' '));

                if (! $moduleKey || in_array($moduleKey, $reservedKeys, true)) {
                    continue;
                }

                $items[] = [
                    'menu_key' => $moduleKey,
                    'label' => $label,
                    'menu_group' => $vendor === 'workdo' ? 'WorkDo Add-ons' : 'StudyRoom Add-ons',
                    'aliases' => [$moduleName, $data['name'] ?? '', $label],
                    'sort_order' => (int) ($data['priority'] ?? 4000),
                ];
            }
        }

        return $items;
    }
}
