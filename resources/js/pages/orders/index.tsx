import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Pagination } from "@/components/ui/pagination";
import { Head, Link, router, usePage } from "@inertiajs/react";
import {
  Download,
  FileText,
  ReceiptText,
  Search,
  ShoppingCart,
  WalletCards,
} from "lucide-react";
import { FormEvent, ReactNode, useMemo, useState } from "react";
import { useTranslation } from "react-i18next";
import { formatAdminCurrency, formatDate } from "@/utils/helpers";

type Order = {
  id: number | string;
  order_id: string;
  invoice_number?: string;
  invoice_type?: string;
  source_type?: string;
  source_label?: string;
  name: string;
  email: string | null;
  plan_name: string;
  price: string | number;
  currency: string;
  payment_status: string;
  payment_type: string;
  txn_id?: string | null;
  receipt?: string | null;
  created_at: string;
  original_price?: string | null;
  total_coupon_used?: {
    coupon_detail?: {
      code: string;
      name: string;
    };
  } | null;
  user?: {
    name: string;
    email: string;
  } | null;
};

type Paginated<T> = {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number;
  to: number;
  links: unknown;
  meta: unknown;
};

type Stats = {
  total: number;
  paid: number;
  pending: number;
  failed: number;
  trial: number;
  subscription: number;
  sms_topup: number;
  free_plan: number;
  collected: number | string;
};

type Props = {
  orders: Paginated<Order>;
  viewMode?: "orders" | "invoices";
  stats?: Stats;
  filters?: {
    search?: string;
    source?: string;
    status?: string;
  };
};

const sourceChips = [
  { key: "", label: "All records", stat: "total" },
  { key: "subscription", label: "Subscriptions", stat: "subscription" },
  { key: "trial", label: "Free trials", stat: "trial" },
  { key: "sms_topup", label: "SMS top-ups", stat: "sms_topup" },
  { key: "free_plan", label: "Free plans", stat: "free_plan" },
];

const statusChips = [
  { key: "", label: "Any status", stat: "total" },
  { key: "paid", label: "Paid", stat: "paid" },
  { key: "pending", label: "Pending", stat: "pending" },
  { key: "failed", label: "Failed", stat: "failed" },
];

export default function OrdersIndex({
  orders,
  viewMode = "orders",
  stats,
  filters,
}: Props) {
  const { t } = useTranslation();
  const pageProps = usePage().props as any;
  const urlParams = useMemo(
    () => new URLSearchParams(window.location.search),
    [],
  );
  const isInvoices =
    viewMode === "invoices" || urlParams.get("view") === "invoices";
  const [search, setSearch] = useState(
    filters?.search || urlParams.get("search") || "",
  );
  const activeSource = filters?.source || urlParams.get("source") || "";
  const activeStatus = filters?.status || urlParams.get("status") || "";

  const go = (next: Record<string, string | undefined>) => {
    router.get(
      route("orders.index"),
      {
        view: isInvoices ? "invoices" : undefined,
        search: search || undefined,
        source: activeSource || undefined,
        status: activeStatus || undefined,
        ...next,
      },
      { preserveState: true, replace: true },
    );
  };

  const submit = (event: FormEvent) => {
    event.preventDefault();
    go({ search: search || undefined });
  };

  const clear = () => {
    setSearch("");
    router.get(
      route("orders.index"),
      { view: isInvoices ? "invoices" : undefined },
      { preserveState: true, replace: true },
    );
  };

  const statusBadge = (status: string) => {
    const value = String(status || "").toLowerCase();
    const group = statusGroup(value);
    const className =
      group === "paid"
        ? "border-emerald-200 bg-emerald-50 text-emerald-700"
        : group === "pending"
          ? "border-amber-200 bg-amber-50 text-amber-700"
          : group === "failed"
            ? "border-rose-200 bg-rose-50 text-rose-700"
            : "border-slate-200 bg-slate-50 text-slate-700";

    return (
      <Badge variant="outline" className={className}>
        {statusLabel(value)}
      </Badge>
    );
  };

  return (
    <AuthenticatedLayout
      breadcrumbs={[
        { label: t("Plan") },
        { label: isInvoices ? t("Invoices") : t("Orders") },
      ]}
      pageTitle={isInvoices ? t("System Invoices") : t("System Orders")}
    >
      <Head title={isInvoices ? t("System Invoices") : t("System Orders")} />

      <div className="space-y-6">
        <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
          <div>
            <p className="text-xs font-semibold uppercase tracking-[0.22em] text-muted-foreground">
              {isInvoices ? t("Plan — Invoices") : t("Plan — Orders")}
            </p>
            <h1 className="mt-2 text-3xl font-semibold tracking-tight text-foreground">
              {isInvoices ? t("System invoices") : t("System billing records")}
            </h1>
            <p className="mt-2 max-w-3xl text-sm text-muted-foreground">
              {isInvoices
                ? t(
                    "One invoice view for admin subscriptions, free trials, free plans, and system SMS top-ups.",
                  )
                : t(
                    "Orders and invoices now share the same system billing records for subscription and SMS package activity.",
                  )}
            </p>
          </div>
          <div className="flex flex-wrap gap-2">
            <Button variant={isInvoices ? "outline" : "default"} asChild>
              <Link href={route("orders.index")}>
                <ShoppingCart className="h-4 w-4" />
                {t("Orders")}
              </Link>
            </Button>
            <Button variant={isInvoices ? "default" : "outline"} asChild>
              <Link href={`${route("orders.index")}?view=invoices`}>
                <ReceiptText className="h-4 w-4" />
                {t("Invoices")}
              </Link>
            </Button>
          </div>
        </div>

        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
          <Metric
            title={t("All records")}
            value={String(stats?.total ?? orders.total ?? 0)}
            subtitle={t("subscriptions, trials, SMS")}
          />
          <Metric
            title={t("Subscriptions")}
            value={String(stats?.subscription ?? 0)}
            subtitle={t("paid plan documents")}
          />
          <Metric
            title={t("Free trials")}
            value={String(stats?.trial ?? 0)}
            subtitle={t("trial invoices issued")}
          />
          <Metric
            title={t("SMS top-ups")}
            value={String(stats?.sms_topup ?? 0)}
            subtitle={t("system SMS wallet")}
          />
          <Metric
            title={t("Collected")}
            value={formatAdminCurrency(stats?.collected ?? 0, pageProps)}
            subtitle={t("confirmed payments only")}
          />
        </div>

        <Card className="overflow-hidden shadow-sm">
          <CardContent className="space-y-4 border-b bg-muted/10 p-4">
            <form
              onSubmit={submit}
              className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between"
            >
              <div className="relative w-full lg:max-w-xl">
                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                  value={search}
                  onChange={(event) => setSearch(event.target.value)}
                  placeholder={
                    isInvoices
                      ? t("Search invoice, admin, plan, SMS top-up...")
                      : t("Search order, admin, plan, SMS top-up...")
                  }
                  className="pl-9"
                />
              </div>
              <div className="flex gap-2">
                <Button type="button" variant="outline" onClick={clear}>
                  {t("Reset")}
                </Button>
                <Button type="submit">{t("Search")}</Button>
              </div>
            </form>

            <div className="space-y-3">
              <ChipGroup
                label={t("Source")}
                chips={sourceChips}
                active={activeSource}
                stats={stats}
                onChange={(value) =>
                  go({
                    source: value || undefined,
                    status: activeStatus || undefined,
                  })
                }
              />
              <ChipGroup
                label={t("Status")}
                chips={statusChips}
                active={activeStatus}
                stats={stats}
                onChange={(value) =>
                  go({
                    status: value || undefined,
                    source: activeSource || undefined,
                  })
                }
              />
            </div>
          </CardContent>

          <CardContent className="p-0">
            {orders.data.length === 0 ? (
              <div className="flex flex-col items-center justify-center px-6 py-16 text-center">
                <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                  {isInvoices ? (
                    <ReceiptText className="h-7 w-7" />
                  ) : (
                    <WalletCards className="h-7 w-7" />
                  )}
                </div>
                <h2 className="mt-4 text-lg font-semibold text-foreground">
                  {isInvoices
                    ? t("No invoices found")
                    : t("No billing records found")}
                </h2>
                <p className="mt-1 max-w-md text-sm text-muted-foreground">
                  {t(
                    "Subscription payments, free trials, free plans, and SMS top-up records will appear here when they are created.",
                  )}
                </p>
              </div>
            ) : isInvoices ? (
              <InvoiceTable
                records={orders.data}
                pageProps={pageProps}
                statusBadge={statusBadge}
              />
            ) : (
              <OrderTable
                records={orders.data}
                pageProps={pageProps}
                statusBadge={statusBadge}
              />
            )}
          </CardContent>

          <CardContent className="border-t bg-muted/10 px-4 py-3">
            <Pagination
              data={orders}
              routeName="orders.index"
              filters={{
                search,
                view: isInvoices ? "invoices" : undefined,
                source: activeSource || undefined,
                status: activeStatus || undefined,
              }}
            />
          </CardContent>
        </Card>
      </div>
    </AuthenticatedLayout>
  );
}

function Metric({
  title,
  value,
  subtitle,
}: {
  title: string;
  value: string;
  subtitle: string;
}) {
  return (
    <Card className="shadow-sm">
      <CardContent className="p-5">
        <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
          {title}
        </div>
        <div className="mt-3 text-2xl font-semibold text-foreground">
          {value}
        </div>
        <div className="mt-1 text-sm text-muted-foreground">{subtitle}</div>
      </CardContent>
    </Card>
  );
}

function ChipGroup({
  label,
  chips,
  active,
  stats,
  onChange,
}: {
  label: string;
  chips: typeof sourceChips;
  active: string;
  stats?: Stats;
  onChange: (value: string) => void;
}) {
  return (
    <div className="flex flex-col gap-2 md:flex-row md:items-center">
      <span className="min-w-16 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
        {label}
      </span>
      <div className="flex flex-wrap gap-2">
        {chips.map((chip) => {
          const count = stats?.[chip.stat as keyof Stats] ?? 0;
          const selected = active === chip.key;

          return (
            <button
              key={chip.key || chip.label}
              type="button"
              onClick={() => onChange(chip.key)}
              className={`inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-sm font-medium transition ${selected ? "border-primary/40 bg-primary/10 text-primary shadow-sm" : "border-border bg-background text-muted-foreground hover:bg-muted/50 hover:text-foreground"}`}
            >
              {chip.label}
              <span
                className={`rounded-full px-2 py-0.5 text-xs ${selected ? "bg-primary/15 text-primary" : "bg-muted text-muted-foreground"}`}
              >
                {count}
              </span>
            </button>
          );
        })}
      </div>
    </div>
  );
}

function InvoiceTable({
  records,
  pageProps,
  statusBadge,
}: {
  records: Order[];
  pageProps: any;
  statusBadge: (status: string) => ReactNode;
}) {
  return (
    <div className="overflow-x-auto">
      <table className="min-w-full divide-y divide-border text-sm">
        <thead className="bg-muted/40 text-left text-xs uppercase tracking-wide text-muted-foreground">
          <tr>
            <th className="px-5 py-4">Invoice</th>
            <th className="px-5 py-4">Admin</th>
            <th className="px-5 py-4">Source</th>
            <th className="px-5 py-4">Amount</th>
            <th className="px-5 py-4">Status</th>
            <th className="px-5 py-4">Issued</th>
            <th className="px-5 py-4 text-right">Download</th>
          </tr>
        </thead>
        <tbody className="divide-y divide-border">
          {records.map((record) => (
            <tr key={record.id} className="hover:bg-muted/30">
              <td className="px-5 py-4">
                <div className="flex items-start gap-3">
                  <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                    <FileText className="h-5 w-5" />
                  </div>
                  <div>
                    <div className="font-semibold text-foreground">
                      {record.invoice_number}
                    </div>
                    <div className="text-xs text-muted-foreground">
                      Ref {record.order_id || "-"}
                    </div>
                  </div>
                </div>
              </td>
              <td className="px-5 py-4">
                <div className="font-medium text-foreground">
                  {record.name || record.user?.name || "-"}
                </div>
                <div className="text-xs text-muted-foreground">
                  {record.email || record.user?.email || "-"}
                </div>
              </td>
              <td className="px-5 py-4">
                <SourceBadge
                  source={record.source_type || ""}
                  label={
                    record.source_label || record.invoice_type || "Subscription"
                  }
                />
                <div className="mt-1 text-xs text-muted-foreground">
                  {record.plan_name || "-"}
                </div>
              </td>
              <td className="px-5 py-4 font-semibold text-foreground">
                {formatAdminCurrency(record.price, pageProps)}
              </td>
              <td className="px-5 py-4">
                {statusBadge(record.payment_status)}
              </td>
              <td className="px-5 py-4 text-muted-foreground">
                {formatDate(record.created_at, pageProps)}
              </td>
              <td className="px-5 py-4 text-right">
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  onClick={() => downloadInvoice(record, pageProps)}
                  className="gap-2"
                >
                  <Download className="h-4 w-4" />
                  Download
                </Button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

function OrderTable({
  records,
  pageProps,
  statusBadge,
}: {
  records: Order[];
  pageProps: any;
  statusBadge: (status: string) => ReactNode;
}) {
  return (
    <div className="overflow-x-auto">
      <table className="min-w-full divide-y divide-border text-sm">
        <thead className="bg-muted/40 text-left text-xs uppercase tracking-wide text-muted-foreground">
          <tr>
            <th className="px-5 py-4">Record</th>
            <th className="px-5 py-4">Admin</th>
            <th className="px-5 py-4">Source</th>
            <th className="px-5 py-4">Amount</th>
            <th className="px-5 py-4">Payment</th>
            <th className="px-5 py-4">Status</th>
            <th className="px-5 py-4">Date</th>
          </tr>
        </thead>
        <tbody className="divide-y divide-border">
          {records.map((record) => (
            <tr key={record.id} className="hover:bg-muted/30">
              <td className="px-5 py-4">
                <div className="font-semibold text-foreground">
                  {record.order_id}
                </div>
                <div className="text-xs text-muted-foreground">
                  {record.invoice_number}
                </div>
              </td>
              <td className="px-5 py-4">
                <div className="font-medium text-foreground">
                  {record.name || record.user?.name || "-"}
                </div>
                <div className="text-xs text-muted-foreground">
                  {record.email || record.user?.email || "-"}
                </div>
              </td>
              <td className="px-5 py-4">
                <SourceBadge
                  source={record.source_type || ""}
                  label={record.source_label || "Subscription"}
                />
                <div className="mt-1 text-xs text-muted-foreground">
                  {record.plan_name || "-"}
                </div>
              </td>
              <td className="px-5 py-4 font-semibold text-foreground">
                {formatAdminCurrency(record.price, pageProps)}
              </td>
              <td className="px-5 py-4 text-muted-foreground">
                {record.payment_type || "-"}
              </td>
              <td className="px-5 py-4">
                {statusBadge(record.payment_status)}
              </td>
              <td className="px-5 py-4 text-muted-foreground">
                {formatDate(record.created_at, pageProps)}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

function SourceBadge({ source, label }: { source: string; label: string }) {
  const value = String(source || "").toLowerCase();
  const className =
    value === "sms_topup"
      ? "border-sky-200 bg-sky-50 text-sky-700"
      : value === "trial"
        ? "border-violet-200 bg-violet-50 text-violet-700"
        : value === "free_plan"
          ? "border-slate-200 bg-slate-50 text-slate-700"
          : "border-emerald-200 bg-emerald-50 text-emerald-700";

  return (
    <Badge variant="outline" className={className}>
      {label}
    </Badge>
  );
}

function downloadInvoice(record: Order, pageProps: any) {
  const invoiceNumber = record.invoice_number || record.order_id || "invoice";
  const fileName = `${safeFileName(invoiceNumber)}.html`;
  const issuedDate = formatDate(record.created_at, pageProps);
  const amount = formatAdminCurrency(record.price, pageProps);
  const status = statusLabel(String(record.payment_status || "").toLowerCase());
  const customerName = record.name || record.user?.name || "-";
  const customerEmail = record.email || record.user?.email || "-";
  const source = record.source_label || record.invoice_type || "Subscription";
  const reference = record.order_id || "-";
  const paymentMethod = record.payment_type || "-";
  const receipt = record.txn_id || record.receipt || "-";

  const html = `<!doctype html>
<html>
<head>
    <meta charset="utf-8" />
    <title>${escapeHtml(invoiceNumber)}</title>
    <style>
        body { margin: 0; background: #f8fafc; color: #0f172a; font-family: Inter, Arial, sans-serif; }
        .page { max-width: 860px; margin: 32px auto; background: #fff; border: 1px solid #e2e8f0; border-radius: 18px; overflow: hidden; }
        .top { padding: 34px 38px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; gap: 24px; }
        .brand { font-size: 13px; color: #64748b; text-transform: uppercase; letter-spacing: .16em; font-weight: 700; }
        h1 { margin: 10px 0 0; font-size: 30px; }
        .badge { display: inline-block; padding: 8px 12px; border-radius: 999px; background: #ecfdf5; color: #047857; font-size: 13px; font-weight: 700; }
        .section { padding: 28px 38px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        .box { border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px; background: #f8fafc; }
        .label { color: #64748b; font-size: 12px; text-transform: uppercase; letter-spacing: .08em; font-weight: 700; }
        .value { margin-top: 8px; font-size: 15px; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { text-align: left; color: #64748b; font-size: 12px; text-transform: uppercase; letter-spacing: .08em; border-bottom: 1px solid #e2e8f0; padding: 13px 0; }
        td { padding: 16px 0; border-bottom: 1px solid #e2e8f0; }
        .right { text-align: right; }
        .total { font-size: 22px; font-weight: 800; }
        .footer { padding: 20px 38px 30px; color: #64748b; font-size: 13px; }
        @media print { body { background: #fff; } .page { margin: 0; border: 0; border-radius: 0; } }
    </style>
</head>
<body>
    <div class="page">
        <div class="top">
            <div>
                <div class="brand">StudyRoom Connect</div>
                <h1>Invoice</h1>
            </div>
            <div style="text-align:right">
                <div class="badge">${escapeHtml(status)}</div>
                <div style="margin-top:12px;color:#64748b;font-size:13px">${escapeHtml(issuedDate)}</div>
            </div>
        </div>
        <div class="section grid">
            <div class="box">
                <div class="label">Invoice number</div>
                <div class="value">${escapeHtml(invoiceNumber)}</div>
            </div>
            <div class="box">
                <div class="label">Reference</div>
                <div class="value">${escapeHtml(reference)}</div>
            </div>
            <div class="box">
                <div class="label">Bill to</div>
                <div class="value">${escapeHtml(customerName)}</div>
                <div style="margin-top:4px;color:#64748b;font-size:13px">${escapeHtml(customerEmail)}</div>
            </div>
            <div class="box">
                <div class="label">Source</div>
                <div class="value">${escapeHtml(source)}</div>
                <div style="margin-top:4px;color:#64748b;font-size:13px">${escapeHtml(paymentMethod)}</div>
            </div>
        </div>
        <div class="section" style="padding-top:0">
            <table>
                <thead>
                    <tr><th>Description</th><th class="right">Amount</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <strong>${escapeHtml(record.plan_name || source)}</strong>
                            <div style="margin-top:5px;color:#64748b;font-size:13px">Receipt: ${escapeHtml(receipt)}</div>
                        </td>
                        <td class="right">${escapeHtml(amount)}</td>
                    </tr>
                    <tr>
                        <td class="total">Total</td>
                        <td class="right total">${escapeHtml(amount)}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="footer">
            This invoice was generated from system billing records for admin subscriptions, free trials, free plans, and SMS top-ups.
        </div>
    </div>
</body>
</html>`;

  const blob = new Blob([html], { type: "text/html;charset=utf-8" });
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.href = url;
  link.download = fileName;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);
}

function escapeHtml(value: unknown) {
  return String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

function safeFileName(value: string) {
  return (
    value
      .replace(/[^a-z0-9-_]+/gi, "-")
      .replace(/^-+|-+$/g, "")
      .toLowerCase() || "invoice"
  );
}

function statusGroup(status: string) {
  if (["succeeded", "paid", "success", "approved"].includes(status))
    return "paid";
  if (
    ["pending", "processing", "pending_mpesa", "pending_approval"].includes(
      status,
    )
  )
    return "pending";
  if (["failed", "cancelled", "canceled"].includes(status)) return "failed";
  return "other";
}

function statusLabel(status: string) {
  if (statusGroup(status) === "paid") return "Paid";
  if (statusGroup(status) === "pending") return "Pending";
  if (statusGroup(status) === "failed")
    return status === "cancelled" || status === "canceled"
      ? "Cancelled"
      : "Failed";
  return status ? status.charAt(0).toUpperCase() + status.slice(1) : "Unknown";
}
