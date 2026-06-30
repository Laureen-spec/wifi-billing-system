"use client"

import React from "react"
import {
  BadgeCheck,
  ChevronsUpDown,
  Languages,
  LogOut,
  Monitor,
  Moon,
  Settings as SettingsIcon,
  Sun,
} from "lucide-react"
import { useTranslation } from "react-i18next"
import { LanguageSwitcher } from "./language-switcher"

import {
  Avatar,
  AvatarFallback,
  AvatarImage,
} from "@/components/ui/avatar"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuGroup,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import {
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  useSidebar,
} from "@/components/ui/sidebar"
import { Button } from "@/components/ui/button"
import { User, PageProps } from "@/types"
import { Link, usePage } from "@inertiajs/react"
import { useTheme } from "next-themes"
import { getImagePath } from "@/utils/helpers"

const normaliseAccountType = (value?: string | null) => {
  const type = String(value || "company").replace(/[_-]+/g, " ").trim()
  return type ? type.charAt(0).toUpperCase() + type.slice(1) : "Company"
}

const workspaceTitle = (user: User) => {
  const type = String((user as any).type || "").toLowerCase()
  const name = user.name || "Company"

  if (type === "superadmin" || name.toLowerCase().includes("super admin")) {
    return "Super Admin Workspace"
  }

  if (type === "company") {
    return `${name} Workspace`
  }

  return `${name} Workspace`
}

const workspaceSubtitle = (user: User) => {
  const type = String((user as any).type || "company").toLowerCase()

  if (type === "superadmin") {
    return "platform account"
  }

  return `${normaliseAccountType(type).toLowerCase()} account`
}

export function NavUser({
  user,
  inHeader = false,
}: {
  user: User
  inHeader?: boolean
}) {
  const { isMobile } = useSidebar()
  const { setTheme } = useTheme()
  const { i18n, t } = useTranslation()
  const { auth } = usePage<PageProps>().props

  const roles = ((auth.user as any)?.roles || []) as any[]
  const permissions = auth.user?.permissions || []
  const isSuperAdmin = roles.some((role: any) => role?.name === "superadmin" || role === "superadmin")
  const canManageProfile = permissions.includes("manage-profile")
  const canManageSettings = permissions.includes("manage-settings")
  const canManageLanguages = isSuperAdmin || permissions.includes("manage-languages") || permissions.includes("create-languages")

  const initial = (user.name || "U").charAt(0).toUpperCase()
  const title = workspaceTitle(user)
  const subtitle = workspaceSubtitle(user)
  const languageSettingsHref = canManageLanguages ? route("languages.manage") : route("settings.index")

  React.useEffect(() => {
    if (user.lang) {
      i18n.changeLanguage(user.lang)
    }
  }, [user.lang, i18n])

  if (inHeader) {
    return (
      <div className="flex items-center gap-3">
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button
              variant="outline"
              className="h-12 w-12 max-w-[390px] items-center justify-start overflow-hidden rounded-2xl border border-slate-200 bg-white px-2.5 py-2 shadow-sm transition hover:border-emerald-200 hover:bg-slate-50 data-[state=open]:border-emerald-200 data-[state=open]:bg-emerald-50/40 dark:border-slate-800 dark:bg-slate-950 dark:hover:border-slate-700 md:w-[360px] xl:w-[390px]"
            >
              <div className="flex min-w-0 flex-1 items-center gap-3">
                <Avatar className="h-10 w-10 shrink-0 rounded-full ring-4 ring-primary/10">
                  {(user as any).avatar && <AvatarImage src={getImagePath((user as any).avatar)} alt={user.name} />}
                  <AvatarFallback className="rounded-full bg-primary/10 font-semibold text-primary">
                    {initial}
                  </AvatarFallback>
                </Avatar>

                <div className="hidden min-w-0 flex-1 text-left md:block">
                  <span className="block truncate text-sm font-semibold text-foreground">
                    {title}
                  </span>
                  <p className="mt-0.5 truncate text-xs text-muted-foreground">
                    {subtitle}
                  </p>
                </div>
              </div>

              <div className="ml-auto hidden shrink-0 items-center gap-2 md:flex">
                <span className="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-300">
                  {t("Active")}
                </span>
                <ChevronsUpDown className="h-4 w-4 text-muted-foreground" />
              </div>

              <ChevronsUpDown className="h-4 w-4 shrink-0 text-muted-foreground md:hidden" />
            </Button>
          </DropdownMenuTrigger>

          <DropdownMenuContent
            align="end"
            sideOffset={10}
            className="w-80 rounded-2xl border-border/80 p-2 shadow-xl"
          >
            <DropdownMenuLabel className="p-0 font-normal">
              <div className="flex items-center gap-3 rounded-xl bg-muted/60 p-3 text-left">
                <Avatar className="h-12 w-12 rounded-full ring-4 ring-primary/10">
                  {(user as any).avatar && <AvatarImage src={getImagePath((user as any).avatar)} alt={user.name} />}
                  <AvatarFallback className="rounded-full bg-primary/10 text-base font-semibold text-primary">
                    {initial}
                  </AvatarFallback>
                </Avatar>

                <div className="min-w-0 flex-1">
                  <div className="flex items-start justify-between gap-2">
                    <div className="min-w-0">
                      <p className="truncate text-sm font-semibold text-foreground">{title}</p>
                      <p className="truncate text-xs text-muted-foreground">{subtitle}</p>
                    </div>
                    <span className="shrink-0 rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/40 dark:text-emerald-300">
                      {t("Active")}
                    </span>
                  </div>
                  <p className="mt-1 truncate text-xs text-muted-foreground">{user.email}</p>
                </div>
              </div>
            </DropdownMenuLabel>

            <DropdownMenuSeparator className="my-2" />

            <DropdownMenuGroup>
              {canManageProfile && (
                <DropdownMenuItem asChild className="cursor-pointer rounded-xl px-3 py-2.5">
                  <Link href={route("profile.edit")}>
                    <BadgeCheck className="mr-2 h-4 w-4" />
                    {t("Edit Profile")}
                  </Link>
                </DropdownMenuItem>
              )}

              <DropdownMenuItem asChild className="cursor-pointer rounded-xl px-3 py-2.5">
                <Link href={languageSettingsHref}>
                  <Languages className="mr-2 h-4 w-4" />
                  {t("Language Settings")}
                </Link>
              </DropdownMenuItem>

              {canManageSettings && (
                <DropdownMenuItem asChild className="cursor-pointer rounded-xl px-3 py-2.5">
                  <Link href={route("settings.index") + "#payment-gateway-settings"}>
                    <SettingsIcon className="mr-2 h-4 w-4" />
                    {t("Settings")}
                  </Link>
                </DropdownMenuItem>
              )}
            </DropdownMenuGroup>

            <DropdownMenuSeparator className="my-2" />

            <DropdownMenuItem asChild className="cursor-pointer rounded-xl px-3 py-2.5 text-red-600 focus:bg-red-50 focus:text-red-600 dark:focus:bg-red-950/20">
              <Link className="w-full" href={route("logout")} method="post" as="button">
                <LogOut className="mr-2 h-4 w-4" />
                {t("Log out")}
              </Link>
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    )
  }

  return (
    <SidebarMenu>
      <SidebarMenuItem>
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <SidebarMenuButton
              size="lg"
              className="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
            >
              <Avatar className="h-8 w-8 rounded-md">
                {(user as any).avatar && <AvatarImage src={getImagePath((user as any).avatar)} alt={user.name} />}
                <AvatarFallback className="rounded-md">{initial}</AvatarFallback>
              </Avatar>
              <div className="grid flex-1 text-left text-sm leading-tight">
                <span className="truncate font-semibold">{user.name}</span>
                <span className="truncate text-xs">{user.email}</span>
              </div>
              <ChevronsUpDown className="ml-auto size-4" />
            </SidebarMenuButton>
          </DropdownMenuTrigger>
          <DropdownMenuContent
            className="w-[--radix-dropdown-menu-trigger-width] min-w-56 rounded-lg"
            side={isMobile ? "bottom" : "right"}
            align="end"
            sideOffset={4}
          >
            <DropdownMenuLabel className="p-0 font-normal">
              <div className="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                <Avatar className="h-8 w-8 rounded-md">
                  {(user as any).avatar && <AvatarImage src={getImagePath((user as any).avatar)} alt={user.name} />}
                  <AvatarFallback className="rounded-md">{initial}</AvatarFallback>
                </Avatar>
                <div className="grid flex-1 text-left text-sm leading-tight">
                  <span className="truncate font-semibold">{user.name}</span>
                  <span className="truncate text-xs">{user.email}</span>
                </div>
              </div>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuGroup>
              {canManageProfile && (
                <DropdownMenuItem asChild>
                  <Link href={route("profile.edit")}>
                    <BadgeCheck className="mr-2 h-4 w-4" />
                    {t("Edit Profile")}
                  </Link>
                </DropdownMenuItem>
              )}
            </DropdownMenuGroup>
            <DropdownMenuSeparator />
            <DropdownMenuGroup>
              <DropdownMenuItem onClick={() => setTheme("light")}>
                <Sun className="mr-2 h-4 w-4" />
                {t("Light")}
              </DropdownMenuItem>
              <DropdownMenuItem onClick={() => setTheme("dark")}>
                <Moon className="mr-2 h-4 w-4" />
                {t("Dark")}
              </DropdownMenuItem>
              <DropdownMenuItem onClick={() => setTheme("system")}>
                <Monitor className="mr-2 h-4 w-4" />
                {t("System")}
              </DropdownMenuItem>
            </DropdownMenuGroup>
            <DropdownMenuSeparator />
            <DropdownMenuGroup>
              <DropdownMenuLabel className="px-2 py-1.5 text-sm font-semibold">{t("Language")}</DropdownMenuLabel>
              <div className="px-2">
                <LanguageSwitcher />
              </div>
            </DropdownMenuGroup>
            <DropdownMenuSeparator />

            <DropdownMenuItem asChild>
              <Link className="w-full" href={route("logout")} method="post" as="button">
                <LogOut className="mr-2 h-4 w-4" />
                {t("Log out")}
              </Link>
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </SidebarMenuItem>
    </SidebarMenu>
  )
}
