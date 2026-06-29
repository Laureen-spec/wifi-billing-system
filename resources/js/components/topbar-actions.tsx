"use client"

import React from "react"
import { Link } from "@inertiajs/react"
import {
  Bolt,
  CreditCard,
  LifeBuoy,
  ReceiptText,
  UserPlus,
  Users,
} from "lucide-react"
import { useTranslation } from "react-i18next"

import { Button } from "@/components/ui/button"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuGroup,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import { cn } from "@/lib/utils"

type OnlineUser = {
  id: number
  name: string
  is_online?: boolean
}

const routeOr = (name: string, fallback: string) => {
  try {
    return route(name)
  } catch (error) {
    return fallback
  }
}

const fetchJson = async <T,>(url: string): Promise<T | null> => {
  try {
    const response = await fetch(url, {
      method: "GET",
      credentials: "same-origin",
      headers: {
        Accept: "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
    })

    if (!response.ok) {
      return null
    }

    return (await response.json()) as T
  } catch (error) {
    return null
  }
}

function TopbarPill({
  icon: Icon,
  label,
  value,
  tone = "neutral",
  className,
}: {
  icon: React.ElementType
  label: string
  value?: React.ReactNode
  tone?: "neutral" | "success"
  className?: string
}) {
  return (
    <div
      className={cn(
        "hidden h-11 items-center gap-3 rounded-2xl border border-border/80 bg-background px-3 shadow-sm transition-colors hover:bg-muted/40 lg:flex",
        className,
      )}
    >
      <span
        className={cn(
          "relative flex h-8 w-8 items-center justify-center rounded-full",
          tone === "success" ? "bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300" : "bg-muted text-muted-foreground",
        )}
      >
        {tone === "success" && (
          <span className="absolute right-1 top-1 h-2 w-2 rounded-full bg-emerald-500 ring-2 ring-background" />
        )}
        <Icon className="h-4 w-4" />
      </span>

      <span className="grid text-left leading-tight">
        <span className="text-[11px] font-medium text-muted-foreground">{label}</span>
        <span className="text-sm font-semibold text-foreground">{value}</span>
      </span>
    </div>
  )
}

export function TopbarActions() {
  const { t } = useTranslation()
  const [onlineUsers, setOnlineUsers] = React.useState<OnlineUser[]>([])
  const onlineUrl = React.useMemo(() => routeOr("messenger.online-users", "/messenger/online-users"), [])

  const refreshPresence = React.useCallback(async () => {
    const users = await fetchJson<OnlineUser[]>(onlineUrl)
    if (Array.isArray(users)) {
      setOnlineUsers(users.filter((user) => user.is_online))
    }
  }, [onlineUrl])


  React.useEffect(() => {
    refreshPresence()

    const timer = window.setInterval(() => {
      refreshPresence()
    }, 30000)

    return () => window.clearInterval(timer)
  }, [refreshPresence])

  const quickActions = [
    {
      label: t("Record payment"),
      href: routeOr("isp-payment-center.index", "/wifi-billing/payment-center"),
      icon: CreditCard,
    },
    {
      label: t("Add customer"),
      href: routeOr("isp.customers.create", "/isp/customers/create"),
      icon: UserPlus,
    },
    {
      label: t("Payment ledger"),
      href: routeOr("isp-payment-center.index", "/wifi-billing/payment-center"),
      icon: ReceiptText,
    },
    {
      label: t("Support tickets"),
      href: routeOr("helpdesk-tickets.index", "/helpdesk-tickets"),
      icon: LifeBuoy,
    },
  ]

  return (
    <div className="flex items-center gap-2">
      <TopbarPill
        icon={Users}
        label={t("Live users")}
        value={onlineUsers.length.toLocaleString()}
        tone="success"
      />

      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <Button
            variant="outline"
            className="hidden h-11 gap-2 rounded-2xl border-border/80 bg-background px-4 shadow-sm hover:bg-muted/40 xl:inline-flex"
          >
            <Bolt className="h-4 w-4" />
            <span className="text-sm font-semibold">{t("Quick actions")}</span>
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" sideOffset={10} className="w-64 rounded-2xl border-border/80 p-2 shadow-xl">
          <DropdownMenuLabel className="px-3 py-2 text-sm font-semibold">
            {t("Operations")}
          </DropdownMenuLabel>
          <DropdownMenuSeparator className="my-1" />
          <DropdownMenuGroup>
            {quickActions.map((item) => (
              <DropdownMenuItem key={item.label} asChild className="cursor-pointer rounded-xl px-3 py-2.5">
                <Link href={item.href}>
                  <item.icon className="mr-2 h-4 w-4" />
                  {item.label}
                </Link>
              </DropdownMenuItem>
            ))}
          </DropdownMenuGroup>
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
  )
}
