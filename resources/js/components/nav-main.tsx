import { Link, usePage } from '@inertiajs/react';
import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuItem, SidebarMenuButton, SidebarMenuSub, SidebarMenuSubItem, SidebarMenuSubButton } from '@/components/ui/sidebar';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { ChevronDown } from 'lucide-react';
import { NavItem } from '@/types';

export function NavMain({ items = [], searchQuery = "" }: { items: NavItem[], searchQuery?: string }) {
    const page = usePage();

    // Filter items based on search query
    const filterItems = (items: NavItem[], query: string): NavItem[] => {
        if (!query) return items;

        return items.reduce((acc, item) => {
            const matchesTitle = item.title.toLowerCase().includes(query.toLowerCase());
            const filteredChildren = item.children ? filterItems(item.children, query) : [];

            if (matchesTitle || filteredChildren.length > 0) {
                acc.push({
                    ...item,
                    children: filteredChildren.length > 0 ? filteredChildren : item.children
                });
            }
            return acc;
        }, [] as NavItem[]);
    };

    const filteredItems = filterItems(items, searchQuery);

    // Helper function to check if URL matches (exact or starts with for detail pages)
    const isUrlActive = (itemPath: string): boolean => {
        const currentPath = page.url.split('?')[0];
        return currentPath === itemPath || currentPath.startsWith(itemPath + '/');
    };

    // Helper function to check if any child is active (recursive for nested children)
    const isChildActive = (children: NavItem[]): boolean => {
        return children.some(child => {
            if (child.href) {
                const childPath = new URL(child.href, window.location.origin).pathname;
                return isUrlActive(childPath);
            }
            if (child.children) {
                return isChildActive(child.children);
            }
            return false;
        });
    };

    const renderIcon = (item: NavItem, isActive = false, className = '') => {
        if (!item.icon) {
            return null;
        }

        const Icon = item.icon;
        return (
            <span
                className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border transition-all ${
                    isActive
                        ? 'border-emerald-200 bg-emerald-50 text-emerald-700 shadow-sm'
                        : 'border-slate-200 bg-slate-50/80 text-slate-500 group-hover/menu-item:border-emerald-100 group-hover/menu-item:bg-emerald-50/70 group-hover/menu-item:text-emerald-700'
                } ${className}`}
            >
                <Icon className="h-4 w-4" />
            </span>
        );
    };

    return (
        <SidebarGroup className="px-0 py-0">
            <SidebarGroupLabel className="mb-2 px-2 text-[10px] font-semibold uppercase tracking-[0.24em] text-slate-400">
                Navigation
            </SidebarGroupLabel>
            <SidebarMenu className="gap-1.5">
                {filteredItems.map((item) => {
                  const itemPath = item.href ? new URL(item.href, window.location.origin).pathname : '';
                  const isActive = !!(itemPath && isUrlActive(itemPath));

                  // Check if any child is active for parent menus
                  const hasActiveChild = item.children ? isChildActive(item.children) : false;
                  const shouldBeActive = isActive || hasActiveChild;
                    if (item.children && item.children.length > 0) {
                        return (
                            <SidebarMenuItem key={item.title}>
                                {/* Expanded sidebar - use collapsible */}
                                <Collapsible asChild defaultOpen={shouldBeActive} className="group/collapsible group-data-[collapsible=icon]:hidden">
                                    <div>
                                        <CollapsibleTrigger asChild>
                                            <SidebarMenuButton
                                                tooltip={item.title}
                                                isActive={shouldBeActive}
                                                className="group/menu-item h-11 rounded-2xl px-2.5 text-[14px] font-medium"
                                            >
                                                {renderIcon(item, shouldBeActive)}
                                                <span className="min-w-0 flex-1 truncate">{item.title}</span>
                                                <ChevronDown className="ml-auto h-4 w-4 text-slate-400 transition-transform group-data-[state=open]/collapsible:rotate-180" />
                                            </SidebarMenuButton>
                                        </CollapsibleTrigger>
                                        <CollapsibleContent className="data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0">
                                            <SidebarMenuSub>
                                                {item.children.map((subItem) => {
                                                    const subItemActive = !!(subItem.href && isUrlActive(new URL(subItem.href, window.location.origin).pathname));
                                                    const hasActiveSubChild = subItem.children ? isChildActive(subItem.children) : false;
                                                    const subItemShouldBeActive = subItemActive || hasActiveSubChild;

                                                    if (subItem.children && subItem.children.length > 0) {
                                                        return (
                                                            <SidebarMenuSubItem key={subItem.title}>
                                                                <Collapsible asChild defaultOpen={subItemShouldBeActive} className="group/subcollapsible">
                                                                    <div>
                                                                        <CollapsibleTrigger asChild>
                                                                            <SidebarMenuSubButton isActive={subItemShouldBeActive} className="h-9 rounded-xl px-3">
                                                                                {subItem.icon && <subItem.icon className="h-4 w-4" />}
                                                                                <span>{subItem.title}</span>
                                                                                <ChevronDown className="ml-auto h-3 w-3 text-slate-400 transition-transform group-data-[state=open]/subcollapsible:rotate-180" />
                                                                            </SidebarMenuSubButton>
                                                                        </CollapsibleTrigger>
                                                                        <CollapsibleContent>
                                                                            <SidebarMenuSub>
                                                                                {subItem.children.map((subSubItem) => (
                                                                                    <SidebarMenuSubItem key={subSubItem.title}>
                                                                                        <SidebarMenuSubButton
                                                                                            asChild
                                                                                            isActive={!!(subSubItem.href && isUrlActive(new URL(subSubItem.href, window.location.origin).pathname))}
                                                                                            className="h-8 rounded-lg text-sm"
                                                                                        >
                                                                                            <Link href={subSubItem.href!}>
                                                                                                {subSubItem.icon && <subSubItem.icon className="h-3 w-3" />}
                                                                                                <span>{subSubItem.title}</span>
                                                                                            </Link>
                                                                                        </SidebarMenuSubButton>
                                                                                    </SidebarMenuSubItem>
                                                                                ))}
                                                                            </SidebarMenuSub>
                                                                        </CollapsibleContent>
                                                                    </div>
                                                                </Collapsible>
                                                            </SidebarMenuSubItem>
                                                        );
                                                    }

                                                    return (
                                                        <SidebarMenuSubItem key={subItem.title}>
                                                            <SidebarMenuSubButton
                                                                asChild
                                                                isActive={subItemActive}
                                                                className="h-9 rounded-xl px-3"
                                                            >
                                                                <Link href={subItem.href!}>
                                                                    {subItem.icon && <subItem.icon className="h-4 w-4" />}
                                                                    <span>{subItem.title}</span>
                                                                </Link>
                                                            </SidebarMenuSubButton>
                                                        </SidebarMenuSubItem>
                                                    );
                                                })}
                                            </SidebarMenuSub>
                                        </CollapsibleContent>
                                    </div>
                                </Collapsible>

                                {/* Collapsed sidebar - use dropdown */}
                                <div className="hidden group-data-[collapsible=icon]:block" onMouseEnter={(e) => e.stopPropagation()} onMouseLeave={(e) => e.stopPropagation()}>
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <SidebarMenuButton
                                                tooltip={item.title}
                                                isActive={shouldBeActive}
                                                className="group/menu-item rounded-xl"
                                            >
                                                {item.icon && <item.icon className="h-4 w-4" />}
                                                <span>{item.title}</span>
                                            </SidebarMenuButton>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent side="right" align="start" className="w-56 rounded-2xl border-slate-200/80 bg-white/95 p-2 shadow-xl backdrop-blur">
                                            {item.children.map((subItem) => {
                                                if (subItem.children && subItem.children.length > 0) {
                                                    return (
                                                        <DropdownMenu key={subItem.title}>
                                                            <DropdownMenuTrigger asChild>
                                                                <DropdownMenuItem className="flex cursor-pointer items-center gap-2 rounded-xl px-3 py-2.5 text-sm">
                                                                    {subItem.icon && <subItem.icon className="h-4 w-4 text-slate-500" />}
                                                                    <span>{subItem.title}</span>
                                                                    <ChevronDown className="ml-auto h-3 w-3" />
                                                                </DropdownMenuItem>
                                                            </DropdownMenuTrigger>
                                                            <DropdownMenuContent side="right" align="start" className="w-48 rounded-2xl border-slate-200/80 bg-white/95 p-2 shadow-xl backdrop-blur">
                                                                {subItem.children.map((subSubItem) => (
                                                                    <DropdownMenuItem key={subSubItem.title} asChild className="rounded-xl px-3 py-2.5">
                                                                        <Link href={subSubItem.href!} className="flex items-center gap-2">
                                                                            {subSubItem.icon && <subSubItem.icon className="h-3 w-3 text-slate-500" />}
                                                                            <span className="text-sm">{subSubItem.title}</span>
                                                                        </Link>
                                                                    </DropdownMenuItem>
                                                                ))}
                                                            </DropdownMenuContent>
                                                        </DropdownMenu>
                                                    );
                                                }

                                                return (
                                                    <DropdownMenuItem key={subItem.title} asChild className="rounded-xl px-3 py-2.5">
                                                        <Link href={subItem.href!} className="flex items-center gap-2">
                                                            {subItem.icon && <subItem.icon className="h-4 w-4 text-slate-500" />}
                                                            <span>{subItem.title}</span>
                                                        </Link>
                                                    </DropdownMenuItem>
                                                );
                                            })}
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            </SidebarMenuItem>
                        );
                    }

                    return (
                        <SidebarMenuItem key={item.title}>
                            <SidebarMenuButton
                                asChild
                                isActive={shouldBeActive}
                                tooltip={item.title}
                                className="group/menu-item h-11 rounded-2xl px-2.5 text-[14px] font-medium"
                            >
                                <Link href={item.href!}>
                                    {renderIcon(item, shouldBeActive)}
                                    <span className="min-w-0 flex-1 truncate">{item.title}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    );
                })}
            </SidebarMenu>
        </SidebarGroup>
    );
}
