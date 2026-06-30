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
            'labels' => ['required', 'array'],
            'labels.*' => ['nullable', 'string', 'max:80'],
            'defaults' => ['sometimes', 'array'],
            'defaults.*' => ['nullable', 'string', 'max:120'],
        ]);

        $userId = (int) $request->user()->id;
        $defaults = $validated['defaults'] ?? [];

        foreach ($validated['labels'] as $key => $label) {
            $menuKey = $this->normalizeMenuKey((string) $key);
            if ($menuKey === '') {
                continue;
            }

            $customLabel = trim((string) $label);
            $defaultLabel = trim((string) ($defaults[$key] ?? $defaults[$menuKey] ?? ''));

            if ($customLabel === '' || ($defaultLabel !== '' && Str::lower($customLabel) === Str::lower($defaultLabel))) {
                UserMenuLabelPreference::query()
                    ->where('user_id', $userId)
                    ->where('menu_key', $menuKey)
                    ->delete();
                continue;
            }

            UserMenuLabelPreference::query()->updateOrCreate(
                [
                    'user_id' => $userId,
                    'menu_key' => $menuKey,
                ],
                [
                    'default_label' => $defaultLabel ?: null,
                    'custom_label' => $customLabel,
                ]
            );
        }

        return back()->with('success', __('Menu names updated for your account.'));
    }

    public function reset(Request $request): RedirectResponse
    {
        if ($this->tableReady() && $request->user()) {
            UserMenuLabelPreference::query()
                ->where('user_id', (int) $request->user()->id)
                ->delete();
        }

        return back()->with('success', __('Menu names reset to system defaults.'));
    }

    public function resetOne(Request $request, string $menuKey): RedirectResponse
    {
        if ($this->tableReady() && $request->user()) {
            UserMenuLabelPreference::query()
                ->where('user_id', (int) $request->user()->id)
                ->where('menu_key', $this->normalizeMenuKey($menuKey))
                ->delete();
        }

        return back()->with('success', __('Menu name reset to default.'));
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
