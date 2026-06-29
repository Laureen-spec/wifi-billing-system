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

    const currentPath = page.url.split('?')[0];

    const getItemPath = (href?: string): string => {
        if (!href) return '';

        try {
            return new URL(href, window.location.origin).pathname;
        } catch {
            return href.startsWith('/') ? href : '';
        }
    };

    const pathMatchesCurrent = (itemPath: string): boolean => {
        return !!itemPath && (currentPath === itemPath || currentPath.startsWith(`${itemPath}/`));
    };

    const collectPaths = (menuItems: NavItem[]): string[] => {
        return menuItems.flatMap((menuItem) => {
            const paths: string[] = [];
            const path = getItemPath(menuItem.href);

            if (path) {
                paths.push(path);
            }

            if (menuItem.children?.length) {
                paths.push(...collectPaths(menuItem.children));
            }

            return paths;
        });
    };

    // Use the longest matching menu path so a parent/index item such as /isp/sms
    // does not stay active when a more specific child such as /isp/sms/new-message is open.
    const activePath = collectPaths(items)
        .filter(pathMatchesCurrent)
        .sort((a, b) => b.length - a.length)[0] || '';

    const isPathActive = (itemPath: string): boolean => activePath === itemPath;

    const isChildActive = (children: NavItem[]): boolean => {
        return children.some((child) => {
            const childPath = getItemPath(child.href);

            if (childPath && isPathActive(childPath)) {
                return true;
            }

            if (child.children?.length) {
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
                        ? 'border-slate-300 bg-white text-slate-700 shadow-sm ring-1 ring-slate-200 group-hover/menu-item:border-emerald-300 group-hover/menu-item:bg-emerald-50 group-hover/menu-item:text-emerald-800'
                        : 'border-slate-300 bg-white text-slate-600 shadow-sm group-hover/menu-item:border-emerald-300 group-hover/menu-item:bg-emerald-50 group-hover/menu-item:text-emerald-800'
                } ${className}`}
            >
                <Icon className="h-4 w-4" />
            </span>
        );
    };

    return (
        <SidebarGroup className="px-0 py-0">
            <SidebarGroupLabel className="mb-2 px-2 text-[10px] font-bold uppercase tracking-[0.24em] text-slate-500">
                Navigation
            </SidebarGroupLabel>
            <SidebarMenu className="gap-1.5">
                {filteredItems.map((item) => {
                  const itemPath = getItemPath(item.href);
                  const isActive = !!(itemPath && isPathActive(itemPath));

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
                                                className="group/menu-item h-11 rounded-2xl px-2.5 text-[14px] font-semibold"
                                            >
                                                {renderIcon(item, shouldBeActive)}
                                                <span className="min-w-0 flex-1 truncate">{item.title}</span>
                                                <ChevronDown className="ml-auto h-4 w-4 text-slate-500 transition-transform group-data-[state=open]/collapsible:rotate-180" />
                                            </SidebarMenuButton>
                                        </CollapsibleTrigger>
                                        <CollapsibleContent className="data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0">
                                            <SidebarMenuSub>
                                                {item.children.map((subItem) => {
                                                    const subItemPath = getItemPath(subItem.href);
                                                    const subItemActive = !!(subItemPath && isPathActive(subItemPath));
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
                                                                                <ChevronDown className="ml-auto h-3 w-3 text-slate-500 transition-transform group-data-[state=open]/subcollapsible:rotate-180" />
                                                                            </SidebarMenuSubButton>
                                                                        </CollapsibleTrigger>
                                                                        <CollapsibleContent>
                                                                            <SidebarMenuSub>
                                                                                {subItem.children.map((subSubItem) => (
                                                                                    <SidebarMenuSubItem key={subSubItem.title}>
                                                                                        <SidebarMenuSubButton
                                                                                            asChild
                                                                                            isActive={!!(getItemPath(subSubItem.href) && isPathActive(getItemPath(subSubItem.href)))}
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
                                                className="group/menu-item rounded-xl font-semibold"
                                            >
                                                {item.icon && <item.icon className="h-4 w-4" />}
                                                <span>{item.title}</span>
                                            </SidebarMenuButton>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent side="right" align="start" className="w-56 rounded-2xl border-slate-300 bg-white p-2 shadow-xl">
                                            {item.children.map((subItem) => {
                                                if (subItem.children && subItem.children.length > 0) {
                                                    return (
                                                        <DropdownMenu key={subItem.title}>
                                                            <DropdownMenuTrigger asChild>
                                                                <DropdownMenuItem className="flex cursor-pointer items-center gap-2 rounded-xl px-3 py-2.5 text-sm">
                                                                    {subItem.icon && <subItem.icon className="h-4 w-4 text-slate-600" />}
                                                                    <span>{subItem.title}</span>
                                                                    <ChevronDown className="ml-auto h-3 w-3" />
                                                                </DropdownMenuItem>
                                                            </DropdownMenuTrigger>
                                                            <DropdownMenuContent side="right" align="start" className="w-48 rounded-2xl border-slate-300 bg-white p-2 shadow-xl">
                                                                {subItem.children.map((subSubItem) => (
                                                                    <DropdownMenuItem key={subSubItem.title} asChild className="rounded-xl px-3 py-2.5">
                                                                        <Link href={subSubItem.href!} className="flex items-center gap-2">
                                                                            {subSubItem.icon && <subSubItem.icon className="h-3 w-3 text-slate-600" />}
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
                                                            {subItem.icon && <subItem.icon className="h-4 w-4 text-slate-600" />}
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
                                className="group/menu-item h-11 rounded-2xl px-2.5 text-[14px] font-semibold"
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
