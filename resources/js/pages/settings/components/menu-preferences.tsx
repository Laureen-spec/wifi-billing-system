import { useEffect, useMemo, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import { RotateCcw, Save, Search, PanelLeft, Undo2 } from 'lucide-react';
import { allMenuItems } from '@/utils/menu';
import { NavItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';

interface MenuPreferenceRow {
  key: string;
  defaultLabel: string;
  currentLabel: string;
  level: number;
  path: string;
}

declare function route(name: string, params?: string | number | Record<string, unknown>): string;

const flattenMenuItems = (items: NavItem[], level = 0, parentPath = ''): MenuPreferenceRow[] => {
  return items.flatMap((item) => {
    const key = item.menuLabelKey || item.name || item.href || item.title;
    const defaultLabel = item.defaultTitle || item.title;
    const currentLabel = item.customTitle || '';
    const path = parentPath ? `${parentPath} / ${defaultLabel}` : defaultLabel;

    const row: MenuPreferenceRow = {
      key,
      defaultLabel,
      currentLabel,
      level,
      path,
    };

    return [
      row,
      ...(item.children ? flattenMenuItems(item.children, level + 1, path) : []),
    ];
  });
};

export default function MenuPreferences() {
  const { t } = useTranslation();
  const { auth } = usePage().props as any;
  const menuItems = allMenuItems();
  const rows = useMemo(() => flattenMenuItems(menuItems), [menuItems]);
  const [search, setSearch] = useState('');
  const [labels, setLabels] = useState<Record<string, string>>({});
  const [saving, setSaving] = useState(false);
  const canEdit = auth?.user?.permissions?.includes('manage-settings') || auth?.user?.type === 'superadmin';
  const rowSignature = rows.map((row) => `${row.key}:${row.currentLabel}:${row.defaultLabel}`).join('|');

  useEffect(() => {
    const nextLabels = rows.reduce<Record<string, string>>((carry, row) => {
      carry[row.key] = row.currentLabel || '';
      return carry;
    }, {});

    setLabels(nextLabels);
  }, [rowSignature]);

  const visibleRows = rows.filter((row) => {
    const query = search.trim().toLowerCase();
    if (!query) return true;

    return row.defaultLabel.toLowerCase().includes(query)
      || row.path.toLowerCase().includes(query)
      || (labels[row.key] || '').toLowerCase().includes(query);
  });

  const changedCount = rows.filter((row) => {
    const value = (labels[row.key] || '').trim();
    return value !== '' && value.toLowerCase() !== row.defaultLabel.toLowerCase();
  }).length;

  const updateLabel = (key: string, value: string) => {
    setLabels((current) => ({ ...current, [key]: value }));
  };

  const resetLocalLabel = (key: string) => {
    setLabels((current) => ({ ...current, [key]: '' }));
  };

  const saveLabels = () => {
    if (!canEdit) return;
    setSaving(true);

    const defaults = rows.reduce<Record<string, string>>((carry, row) => {
      carry[row.key] = row.defaultLabel;
      return carry;
    }, {});

    router.put(route('settings.menu-preferences.update'), {
      labels,
      defaults,
    }, {
      preserveScroll: true,
      onSuccess: (page) => {
        setSaving(false);
        toast.success((page.props.flash as any)?.success || t('Menu names updated.'));
        router.reload({ only: ['auth'] });
      },
      onError: (errors) => {
        setSaving(false);
        toast.error(Object.values(errors).join(', ') || t('Failed to save menu names.'));
      },
    });
  };

  const resetAll = () => {
    if (!canEdit) return;
    setSaving(true);

    router.delete(route('settings.menu-preferences.reset'), {
      preserveScroll: true,
      onSuccess: (page) => {
        setSaving(false);
        setLabels({});
        toast.success((page.props.flash as any)?.success || t('Menu names reset.'));
        router.reload({ only: ['auth'] });
      },
      onError: () => {
        setSaving(false);
        toast.error(t('Failed to reset menu names.'));
      },
    });
  };

  return (
    <div className="space-y-5">
      <Card>
        <CardHeader className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <CardTitle className="flex items-center gap-2 text-lg">
              <PanelLeft className="h-5 w-5 text-primary" />
              {t('Menu Preferences')}
            </CardTitle>
            <p className="mt-1 max-w-2xl text-sm text-muted-foreground">
              {t('Rename sidebar menu labels for your own account only. Other admins and system defaults are not affected.')}
            </p>
          </div>
          <div className="flex flex-wrap items-center gap-2">
            <Badge variant="outline" className="rounded-full px-3 py-1">
              {changedCount} {t('custom names')}
            </Badge>
            <Button variant="outline" size="sm" onClick={resetAll} disabled={!canEdit || saving || changedCount === 0}>
              <RotateCcw className="mr-2 h-4 w-4" />
              {t('Reset all')}
            </Button>
            <Button size="sm" onClick={saveLabels} disabled={!canEdit || saving}>
              <Save className="mr-2 h-4 w-4" />
              {saving ? t('Saving...') : t('Save names')}
            </Button>
          </div>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid gap-3 rounded-2xl border bg-muted/20 p-4 md:grid-cols-3">
            <div>
              <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{t('Scope')}</p>
              <p className="mt-1 text-sm font-medium text-foreground">{t('Only my account')}</p>
            </div>
            <div>
              <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{t('System default')}</p>
              <p className="mt-1 text-sm font-medium text-foreground">{t('Kept safely')}</p>
            </div>
            <div>
              <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{t('Reset')}</p>
              <p className="mt-1 text-sm font-medium text-foreground">{t('Restore original labels anytime')}</p>
            </div>
          </div>

          <div className="relative">
            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <Input
              value={search}
              onChange={(event) => setSearch(event.target.value)}
              className="h-11 rounded-2xl pl-9"
              placeholder={t('Search menu name...')}
            />
          </div>

          <div className="overflow-hidden rounded-2xl border">
            <div className="grid grid-cols-[minmax(0,1fr)_minmax(220px,360px)_auto] gap-3 border-b bg-muted/30 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
              <div>{t('Default menu')}</div>
              <div>{t('My custom name')}</div>
              <div className="text-right">{t('Action')}</div>
            </div>

            <div className="divide-y">
              {visibleRows.map((row) => {
                const value = labels[row.key] || '';
                const isCustom = value.trim() !== '' && value.trim().toLowerCase() !== row.defaultLabel.toLowerCase();

                return (
                  <div key={row.key} className="grid grid-cols-[minmax(0,1fr)_minmax(220px,360px)_auto] items-center gap-3 px-4 py-3">
                    <div className="min-w-0" style={{ paddingLeft: `${row.level * 18}px` }}>
                      <div className="flex items-center gap-2">
                        <span className="truncate text-sm font-medium text-foreground">{row.defaultLabel}</span>
                        {isCustom && <Badge className="rounded-full bg-primary/10 text-primary hover:bg-primary/10">{t('Renamed')}</Badge>}
                      </div>
                      <p className="mt-1 truncate text-xs text-muted-foreground">{row.path}</p>
                    </div>

                    <Input
                      value={value}
                      onChange={(event) => updateLabel(row.key, event.target.value)}
                      placeholder={row.defaultLabel}
                      disabled={!canEdit}
                      className="h-10 rounded-xl"
                      maxLength={80}
                    />

                    <div className="flex justify-end">
                      <Button
                        type="button"
                        size="sm"
                        variant="ghost"
                        onClick={() => resetLocalLabel(row.key)}
                        disabled={!canEdit || !value}
                        className="rounded-xl"
                      >
                        <Undo2 className="mr-2 h-4 w-4" />
                        {t('Default')}
                      </Button>
                    </div>
                  </div>
                );
              })}

              {visibleRows.length === 0 && (
                <div className="px-4 py-10 text-center text-sm text-muted-foreground">
                  {t('No menu items match your search.')}
                </div>
              )}
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
