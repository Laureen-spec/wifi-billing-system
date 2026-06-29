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
import { Link, router, usePage } from "@inertiajs/react"
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
              className="h-11 gap-3 rounded-2xl border-border/80 bg-background px-3 py-2 shadow-sm hover:bg-muted/40 data-[state=open]:bg-muted/50"
            >
              <Avatar className="h-9 w-9 rounded-full ring-4 ring-primary/10">
                {(user as any).avatar && <AvatarImage src={getImagePath((user as any).avatar)} alt={user.name} />}
                <AvatarFallback className="rounded-full bg-primary/10 font-semibold text-primary">
                  {initial}
                </AvatarFallback>
              </Avatar>

              <div className="hidden min-w-0 text-left md:block">
                <div className="flex items-center gap-2">
                  <span className="max-w-[170px] truncate text-sm font-semibold text-foreground">
                    {title}
                  </span>
                  <span className="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                    {t("Active")}
                  </span>
                </div>
                <p className="mt-0.5 max-w-[170px] truncate text-xs text-muted-foreground">
                  {subtitle}
                </p>
              </div>

              <ChevronsUpDown className="h-4 w-4 text-muted-foreground" />
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
                  <div className="flex items-center gap-2">
                    <p className="truncate text-sm font-semibold text-foreground">{title}</p>
                    <span className="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                      {t("Active")}
                    </span>
                  </div>
                  <p className="truncate text-xs text-muted-foreground">{subtitle}</p>
                  <p className="truncate text-xs text-muted-foreground">{user.email}</p>
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
