<?php

namespace App\Http\Controllers;

use App\Models\UserMenuLabelPreference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class MenuPreferenceController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        if (! $this->tableReady() || ! $request->user()) {
            return back()->with('error', __('Menu preferences are not ready. Please run migrations.'));
        }

        $validated = $request->validate([
            'labels' => ['sometimes', 'array'],
            'labels.*' => ['nullable', 'string', 'max:80'],
            'positions' => ['sometimes', 'array'],
            'positions.*' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'defaults' => ['sometimes', 'array'],
            'defaults.*' => ['nullable', 'string', 'max:120'],
        ]);

        $userId = (int) $request->user()->id;
        $labels = $validated['labels'] ?? [];
        $positions = $validated['positions'] ?? [];
        $defaults = $validated['defaults'] ?? [];
        $keys = collect(array_keys($labels))
            ->merge(array_keys($positions))
            ->unique()
            ->values();
        $hasSortOrderColumn = Schema::hasColumn('user_menu_label_preferences', 'sort_order');

        foreach ($keys as $key) {
            $menuKey = $this->normalizeMenuKey((string) $key);
            if ($menuKey === '') {
                continue;
            }

            $customLabel = trim((string) ($labels[$key] ?? $labels[$menuKey] ?? ''));
            $defaultLabel = trim((string) ($defaults[$key] ?? $defaults[$menuKey] ?? ''));
            $sortOrder = $hasSortOrderColumn && isset($positions[$key]) && is_numeric($positions[$key])
                ? max(1, min(9999, (int) $positions[$key]))
                : null;

            $hasCustomLabel = $customLabel !== '' && ($defaultLabel === '' || Str::lower($customLabel) !== Str::lower($defaultLabel));
            $hasCustomPosition = $sortOrder !== null;

            if (! $hasCustomLabel && ! $hasCustomPosition) {
                UserMenuLabelPreference::query()
                    ->where('user_id', $userId)
                    ->where('menu_key', $menuKey)
                    ->delete();
                continue;
            }

            $payload = [
                'default_label' => $defaultLabel ?: null,
                'custom_label' => $hasCustomLabel ? $customLabel : '',
            ];

            if ($hasSortOrderColumn) {
                $payload['sort_order'] = $sortOrder;
            }

            UserMenuLabelPreference::query()->updateOrCreate(
                [
                    'user_id' => $userId,
                    'menu_key' => $menuKey,
                ],
                $payload
            );
        }

        return back()->with('success', __('Menu preferences updated for your account.'));
    }

    public function reset(Request $request): RedirectResponse
    {
        if ($this->tableReady() && $request->user()) {
            UserMenuLabelPreference::query()
                ->where('user_id', (int) $request->user()->id)
                ->delete();
        }

        return back()->with('success', __('Menu preferences reset to system defaults.'));
    }

    public function resetOne(Request $request, string $menuKey): RedirectResponse
    {
        if ($this->tableReady() && $request->user()) {
            UserMenuLabelPreference::query()
                ->where('user_id', (int) $request->user()->id)
                ->where('menu_key', $this->normalizeMenuKey($menuKey))
                ->delete();
        }

        return back()->with('success', __('Menu preference reset to default.'));
    }

    private function normalizeMenuKey(string $value): string
    {
        return Str::slug(trim($value));
    }

    private function tableReady(): bool
    {
        try {
            return Schema::hasTable('user_menu_label_preferences');
        } catch (Throwable) {
            return false;
        }
    }
}
