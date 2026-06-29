"use client"

import * as React from "react"
import { Search, Wifi, ShieldCheck } from "lucide-react"

import { NavMain } from "@/components/nav-main"
import {
  Sidebar,
  SidebarContent,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarInput,
} from "@/components/ui/sidebar"
import { Link } from "@inertiajs/react";
import { allMenuItems } from "@/utils/menu";
import { useBrand } from "@/contexts/brand-context";
import { cn } from "@/lib/utils";



export function AppSidebar({ ...props }: React.ComponentProps<typeof Sidebar>) {
    const { settings, getCompleteSidebarProps, getLogoSrc, getPreviewUrl } = useBrand();
    const [searchQuery, setSearchQuery] = React.useState("");
    const [logoFailed, setLogoFailed] = React.useState(false);
    const [faviconFailed, setFaviconFailed] = React.useState(false);
    const scrollRef = React.useRef<HTMLDivElement>(null);
    const logoSrc = getLogoSrc();
    const logoUrl = logoSrc ? getPreviewUrl(logoSrc) : '';
    const faviconUrl = settings.favicon ? getPreviewUrl(settings.favicon) : '';

    // Preserve scroll position on navigation
    React.useEffect(() => {
        const savedScroll = sessionStorage.getItem('sidebar-scroll');
        if (savedScroll && scrollRef.current) {
            scrollRef.current.scrollTop = parseInt(savedScroll);
        }
    }, []);

    const handleScroll = () => {
        if (scrollRef.current) {
            sessionStorage.setItem('sidebar-scroll', scrollRef.current.scrollTop.toString());
        }
    };

    React.useEffect(() => {
        setLogoFailed(false);
    }, [logoUrl]);

    React.useEffect(() => {
        setFaviconFailed(false);
    }, [faviconUrl]);

    const sidebarProps = getCompleteSidebarProps();

    return (
    <Sidebar
        variant={settings.sidebarVariant as any}
        side={settings.layoutDirection === 'rtl' ? 'right' : 'left'}
        collapsible="icon"
        className={cn(
            sidebarProps.className,
            "border-r border-slate-200/80 bg-[linear-gradient(180deg,#ffffff_0%,#fbfdfc_45%,#f6faf8_100%)] shadow-[12px_0_30px_rgba(15,23,42,0.035)]"
        )}
        style={sidebarProps.style}
        {...props}
    >
      <SidebarHeader className="gap-4 border-b border-slate-200/70 px-4 pb-4 pt-5 group-data-[collapsible=icon]:items-center group-data-[collapsible=icon]:px-2">
        <SidebarMenu>
          <SidebarMenuItem>
            <SidebarMenuButton
              size="lg"
              asChild
              className="h-auto rounded-2xl border border-slate-200/80 bg-white/80 px-3 py-3 shadow-[0_12px_30px_rgba(15,23,42,0.055)] hover:bg-white hover:text-slate-950 group-data-[collapsible=icon]:h-10 group-data-[collapsible=icon]:w-10 group-data-[collapsible=icon]:rounded-xl group-data-[collapsible=icon]:border-0 group-data-[collapsible=icon]:bg-transparent group-data-[collapsible=icon]:p-0 group-data-[collapsible=icon]:shadow-none"
            >
              <Link href={route('dashboard')} className="flex w-full items-center gap-3">
                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100 group-data-[collapsible=icon]:hidden">
                  {faviconUrl && !faviconFailed ? (
                      <img
                        src={faviconUrl}
                        alt="Icon"
                        className="h-7 w-7 rounded-lg object-contain transition-all duration-200"
                        onError={() => setFaviconFailed(true)}
                      />
                    ) : (
                      <Wifi className="h-5 w-5" />
                    )}
                </div>

                {/* Logo and brand text for expanded sidebar */}
                <div className="min-w-0 flex-1 group-data-[collapsible=icon]:hidden">
                  {logoUrl && !logoFailed ? (
                      <img
                        src={logoUrl}
                        alt="Logo"
                        className="max-h-8 w-auto max-w-36 object-contain transition-all duration-200"
                        onError={() => setLogoFailed(true)}
                      />
                    ) : (
                      <div className="truncate text-[15px] font-semibold tracking-tight text-slate-900">
                        {settings.titleText || 'StudyRoom Connect'}
                      </div>
                    )}
                  <div className="mt-1 flex items-center gap-1.5 text-[11px] font-medium uppercase tracking-[0.18em] text-slate-400">
                    <ShieldCheck className="h-3 w-3 text-emerald-600" />
                    Control center
                  </div>
                </div>

                {/* Icon for collapsed sidebar */}
                <div className="hidden h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100 group-data-[collapsible=icon]:flex">
                  {faviconUrl && !faviconFailed ? (
                      <img
                        src={faviconUrl}
                        alt="Icon"
                        className="h-7 w-7 rounded-lg object-contain transition-all duration-200"
                        onError={() => setFaviconFailed(true)}
                      />
                    ) : (
                      <Wifi className="h-5 w-5" />
                    )}
                </div>
              </Link>
            </SidebarMenuButton>
          </SidebarMenuItem>
        </SidebarMenu>
        <div className="group-data-[collapsible=icon]:hidden">
          <div className="relative">
            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <SidebarInput
              placeholder="Search navigation..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="h-11 rounded-xl border-slate-200 bg-white/85 pl-9 pr-3 text-[14px] shadow-sm placeholder:text-slate-400 focus-visible:border-emerald-300 focus-visible:ring-2 focus-visible:ring-emerald-100"
            />
          </div>
        </div>
      </SidebarHeader>
      <SidebarContent ref={scrollRef} onScroll={handleScroll} className="px-3 py-4 group-data-[collapsible=icon]:px-2">
        <NavMain items={allMenuItems()} searchQuery={searchQuery} />
      </SidebarContent>
    </Sidebar>
  )
}
