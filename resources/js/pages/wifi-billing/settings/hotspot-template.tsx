import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { InputError } from "@/components/ui/input-error";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import { PageHeader } from "../components";
import { Head, useForm } from "@inertiajs/react";
import {
  BadgeCheck,
  Clock,
  Globe2,
  ImageIcon,
  ListChecks,
  Minus,
  Palette,
  Phone,
  Plus,
  Save,
  ShieldCheck,
  Upload,
  Wifi,
} from "lucide-react";
import { FormEvent, type ReactNode } from "react";

declare function route(
  name: string,
  params?: string | number | Record<string, unknown>,
): string;

type Option = {
  value: string;
  label: string;
};

type Setting = {
  template_key?: string | null;
  template_name?: string | null;
  logo_url?: string | null;
  background_url?: string | null;
  primary_color?: string | null;
  secondary_color?: string | null;
  accent_color?: string | null;
  welcome_text?: string | null;
  footer_text?: string | null;
  care_phone?: string | null;
  redirect_url?: string | null;
  language?: string | null;
  purchase_instructions?: string[] | null;
  custom_css?: string | null;
  enable_datalan_free_access?: boolean | number | null;
  free_access_duration_minutes?: number | string | null;
  free_access_cooldown_hours?: number | string | null;
  free_access_package_id?: number | string | null;
  free_access_speed_limit?: string | null;
  free_access_identity_mode?: string | null;
  free_access_requires_phone?: boolean | number | null;
  free_access_requires_name?: boolean | number | null;
  free_access_button_text?: string | null;
  free_access_cooldown_message?: string | null;
  free_access_success_message?: string | null;
};

type FormData = {
  template_key: string;
  template_name: string;
  logo: File | null;
  background: File | null;
  primary_color: string;
  secondary_color: string;
  accent_color: string;
  welcome_text: string;
  footer_text: string;
  care_phone: string;
  redirect_url: string;
  language: string;
  purchase_instructions: string[];
  custom_css: string;
  enable_datalan_free_access: boolean;
  free_access_duration_minutes: number;
  free_access_cooldown_hours: number;
  free_access_package_id: string;
  free_access_speed_limit: string;
  free_access_identity_mode: string;
  free_access_requires_phone: boolean;
  free_access_requires_name: boolean;
  free_access_button_text: string;
  free_access_cooldown_message: string;
  free_access_success_message: string;
};

type Props = {
  isp: {
    id: number;
    name: string;
  };
  setting: Setting;
  templateOptions: Option[];
  languageOptions: Option[];
};

const defaultInstructions = [
  "Choose a package",
  "Enter your M-Pesa phone number",
  "Confirm the STK prompt",
  "Internet activates after payment confirmation",
];

function SectionHeading({
  icon,
  title,
  description,
}: {
  icon: ReactNode;
  title: string;
  description: string;
}) {
  return (
    <CardHeader className="border-b bg-muted/20 py-4">
      <div className="flex items-start gap-3">
        <div className="mt-0.5 flex h-9 w-9 items-center justify-center rounded-lg border bg-background text-primary">
          {icon}
        </div>
        <div>
          <CardTitle className="text-base">{title}</CardTitle>
          <p className="mt-1 text-sm text-muted-foreground">{description}</p>
        </div>
      </div>
    </CardHeader>
  );
}

function PortalChip({ children }: { children: ReactNode }) {
  return (
    <span className="inline-flex items-center rounded-full border bg-background px-3 py-1 text-xs font-medium text-muted-foreground">
      {children}
    </span>
  );
}

function ToggleRow({
  checked,
  onChange,
  title,
  description,
}: {
  checked: boolean;
  onChange: (checked: boolean) => void;
  title: string;
  description: string;
}) {
  return (
    <button
      type="button"
      onClick={() => onChange(!checked)}
      className="flex w-full items-center justify-between gap-4 rounded-xl border bg-card p-4 text-left transition hover:bg-muted/30"
    >
      <span>
        <span className="block text-sm font-semibold text-foreground">
          {title}
        </span>
        <span className="mt-1 block text-xs text-muted-foreground">
          {description}
        </span>
      </span>
      <span
        className={`relative h-6 w-11 shrink-0 rounded-full border transition ${
          checked ? "border-primary bg-primary" : "border-border bg-muted"
        }`}
      >
        <span
          className={`absolute top-1 h-4 w-4 rounded-full bg-background shadow-sm transition ${
            checked ? "left-6" : "left-1"
          }`}
        />
      </span>
    </button>
  );
}

export default function HotspotTemplateSettings({
  isp,
  setting,
  templateOptions,
  languageOptions,
}: Props) {
  const {
    data,
    setData,
    post,
    processing,
    errors,
    recentlySuccessful,
    transform,
  } = useForm<FormData>({
    template_key: setting.template_key || "modern",
    template_name: setting.template_name || "Modern Hotspot",
    logo: null,
    background: null,
    primary_color: setting.primary_color || "#0f766e",
    secondary_color: setting.secondary_color || "#475569",
    accent_color: setting.accent_color || "#d97706",
    welcome_text: setting.welcome_text || "",
    footer_text: setting.footer_text || "",
    care_phone: setting.care_phone || "",
    redirect_url: setting.redirect_url || "",
    language: setting.language || "en",
    purchase_instructions: setting.purchase_instructions?.length
      ? setting.purchase_instructions
      : defaultInstructions,
    custom_css: setting.custom_css || "",
    enable_datalan_free_access: Boolean(setting.enable_datalan_free_access),
    free_access_duration_minutes: Number(
      setting.free_access_duration_minutes || 60,
    ),
    free_access_cooldown_hours: Number(
      setting.free_access_cooldown_hours || 24,
    ),
    free_access_package_id: setting.free_access_package_id
      ? String(setting.free_access_package_id)
      : "",
    free_access_speed_limit: setting.free_access_speed_limit || "",
    free_access_identity_mode: setting.free_access_identity_mode || "mac",
    free_access_requires_phone: Boolean(setting.free_access_requires_phone),
    free_access_requires_name: Boolean(setting.free_access_requires_name),
    free_access_button_text:
      setting.free_access_button_text || "Get 1 hour free access",
    free_access_cooldown_message:
      setting.free_access_cooldown_message ||
      "You already used free access. Come back after @time_remaining.",
    free_access_success_message:
      setting.free_access_success_message ||
      "Free access is active for @duration minutes.",
  });

  const validationErrors = errors as Record<string, string | undefined>;

  const submit = (event: FormEvent) => {
    event.preventDefault();
    transform((formData) => ({
      ...formData,
      enable_datalan_free_access: formData.enable_datalan_free_access
        ? "1"
        : "0",
      free_access_requires_phone: formData.free_access_requires_phone
        ? "1"
        : "0",
      free_access_requires_name: formData.free_access_requires_name ? "1" : "0",
    }));
    post(route("wifi-billing.settings.hotspot-template.update"), {
      preserveScroll: true,
      forceFormData: true,
    });
  };

  const updateInstruction = (index: number, value: string) => {
    const instructions = [...data.purchase_instructions];
    instructions[index] = value;
    setData("purchase_instructions", instructions);
  };

  const addInstruction = () => {
    setData("purchase_instructions", [...data.purchase_instructions, ""]);
  };

  const removeInstruction = (index: number) => {
    setData(
      "purchase_instructions",
      data.purchase_instructions.filter((_, itemIndex) => itemIndex !== index),
    );
  };

  return (
    <AuthenticatedLayout
      breadcrumbs={[
        { label: "WiFi Billing", url: route("wifi-billing.dashboard") },
        { label: "Settings", url: route("wifi-billing.settings.index") },
        { label: "Hotspot Template" },
      ]}
      pageTitle="Hotspot Template"
    >
      <Head title="Hotspot Template" />

      <div className="space-y-5">
        <PageHeader
          title="Hotspot Template"
          description={`Design the customer-facing hotspot portal for ${isp.name}.`}
          actions={
            <Button
              form="hotspot-template-form"
              type="submit"
              disabled={processing}
            >
              <Save className="h-4 w-4" />
              Save template
            </Button>
          }
        />

        {recentlySuccessful && (
          <div className="flex items-center gap-3 rounded-xl border border-primary/20 bg-primary/5 px-4 py-3 text-sm text-primary">
            <BadgeCheck className="h-4 w-4" />
            Hotspot template settings saved successfully.
          </div>
        )}

        <div className="rounded-2xl border bg-card p-4 shadow-sm">
          <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
              <div className="flex flex-wrap items-center gap-2">
                <PortalChip>Customer portal</PortalChip>
                <PortalChip>M-Pesa checkout</PortalChip>
                <PortalChip>
                  {data.enable_datalan_free_access
                    ? "Free access enabled"
                    : "Free access disabled"}
                </PortalChip>
              </div>
              <h2 className="mt-3 text-xl font-semibold text-foreground">
                Portal experience setup
              </h2>
              <p className="mt-1 max-w-2xl text-sm text-muted-foreground">
                Control the logo, colours, welcome copy, purchase steps, and
                optional free-access button shown to hotspot customers.
              </p>
            </div>
            <div className="grid grid-cols-3 gap-2 text-center text-xs text-muted-foreground">
              <div className="rounded-xl border bg-background p-3">
                <Globe2 className="mx-auto mb-1 h-4 w-4 text-primary" />
                Language
              </div>
              <div className="rounded-xl border bg-background p-3">
                <Palette className="mx-auto mb-1 h-4 w-4 text-primary" />
                Branding
              </div>
              <div className="rounded-xl border bg-background p-3">
                <Wifi className="mx-auto mb-1 h-4 w-4 text-primary" />
                Preview
              </div>
            </div>
          </div>
        </div>

        <form
          id="hotspot-template-form"
          onSubmit={submit}
          className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_390px]"
        >
          <div className="space-y-5">
            <Card>
              <SectionHeading
                icon={<Globe2 className="h-4 w-4" />}
                title="Template identity"
                description="Choose the portal layout, language, support phone, and public redirect behaviour."
              />
              <CardContent className="grid gap-4 p-4 md:grid-cols-2">
                <div className="space-y-2">
                  <Label htmlFor="template_key">Template</Label>
                  <Select
                    value={data.template_key}
                    onValueChange={(value) => setData("template_key", value)}
                  >
                    <SelectTrigger id="template_key">
                      <SelectValue placeholder="Template" />
                    </SelectTrigger>
                    <SelectContent>
                      {templateOptions.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                          {option.label}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  <InputError message={errors.template_key} />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="template_name">Portal Name</Label>
                  <Input
                    id="template_name"
                    value={data.template_name}
                    onChange={(event) =>
                      setData("template_name", event.target.value)
                    }
                    placeholder="e.g. StudyRoom Hotspot"
                  />
                  <InputError message={errors.template_name} />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="language">Language</Label>
                  <Select
                    value={data.language}
                    onValueChange={(value) => setData("language", value)}
                  >
                    <SelectTrigger id="language">
                      <SelectValue placeholder="Language" />
                    </SelectTrigger>
                    <SelectContent>
                      {languageOptions.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                          {option.label}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  <InputError message={errors.language} />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="care_phone">Customer Care Phone</Label>
                  <div className="relative">
                    <Phone className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                      id="care_phone"
                      value={data.care_phone}
                      onChange={(event) =>
                        setData("care_phone", event.target.value)
                      }
                      placeholder="+254..."
                      className="pl-9"
                    />
                  </div>
                  <InputError message={errors.care_phone} />
                </div>

                <div className="space-y-2 md:col-span-2">
                  <Label htmlFor="redirect_url">After Login Redirect URL</Label>
                  <Input
                    id="redirect_url"
                    type="url"
                    value={data.redirect_url}
                    onChange={(event) =>
                      setData("redirect_url", event.target.value)
                    }
                    placeholder="https://example.com"
                  />
                  <p className="text-xs text-muted-foreground">
                    Optional. Customers can be redirected here after successful
                    authentication.
                  </p>
                  <InputError message={errors.redirect_url} />
                </div>
              </CardContent>
            </Card>

            <Card>
              <SectionHeading
                icon={<ImageIcon className="h-4 w-4" />}
                title="Brand assets"
                description="Upload the logo and background artwork customers will see on the hotspot portal."
              />
              <CardContent className="grid gap-4 p-4 md:grid-cols-2">
                <div className="space-y-3 rounded-xl border bg-background p-4">
                  <div className="flex items-center justify-between gap-3">
                    <div>
                      <Label htmlFor="logo">Logo</Label>
                      <p className="mt-1 text-xs text-muted-foreground">
                        PNG or JPG. Keep it clear on mobile.
                      </p>
                    </div>
                    <Upload className="h-4 w-4 text-muted-foreground" />
                  </div>
                  <Input
                    id="logo"
                    type="file"
                    accept="image/*"
                    onChange={(event) =>
                      setData("logo", event.target.files?.[0] || null)
                    }
                  />
                  <InputError message={errors.logo} />
                  {setting.logo_url && (
                    <img
                      src={setting.logo_url}
                      alt="Current logo"
                      className="h-16 w-auto rounded-lg border bg-card object-contain p-2"
                    />
                  )}
                </div>

                <div className="space-y-3 rounded-xl border bg-background p-4">
                  <div className="flex items-center justify-between gap-3">
                    <div>
                      <Label htmlFor="background">Background Image</Label>
                      <p className="mt-1 text-xs text-muted-foreground">
                        Optional image behind the customer portal card.
                      </p>
                    </div>
                    <ImageIcon className="h-4 w-4 text-muted-foreground" />
                  </div>
                  <Input
                    id="background"
                    type="file"
                    accept="image/*"
                    onChange={(event) =>
                      setData("background", event.target.files?.[0] || null)
                    }
                  />
                  <InputError message={errors.background} />
                  {setting.background_url && (
                    <img
                      src={setting.background_url}
                      alt="Current background"
                      className="h-24 w-full rounded-lg border object-cover"
                    />
                  )}
                </div>
              </CardContent>
            </Card>

            <Card>
              <SectionHeading
                icon={<Palette className="h-4 w-4" />}
                title="Portal colours"
                description="Set calm brand colours without changing the admin dashboard theme."
              />
              <CardContent className="grid gap-4 p-4 md:grid-cols-3">
                <div className="space-y-2 rounded-xl border bg-background p-4">
                  <Label htmlFor="primary_color">Primary Colour</Label>
                  <div className="flex gap-2">
                    <Input
                      id="primary_color"
                      type="color"
                      value={data.primary_color}
                      onChange={(event) =>
                        setData("primary_color", event.target.value)
                      }
                      className="h-10 w-14 p-1"
                    />
                    <Input
                      value={data.primary_color}
                      onChange={(event) =>
                        setData("primary_color", event.target.value)
                      }
                    />
                  </div>
                  <InputError message={errors.primary_color} />
                </div>

                <div className="space-y-2 rounded-xl border bg-background p-4">
                  <Label htmlFor="secondary_color">Secondary Colour</Label>
                  <div className="flex gap-2">
                    <Input
                      id="secondary_color"
                      type="color"
                      value={data.secondary_color}
                      onChange={(event) =>
                        setData("secondary_color", event.target.value)
                      }
                      className="h-10 w-14 p-1"
                    />
                    <Input
                      value={data.secondary_color}
                      onChange={(event) =>
                        setData("secondary_color", event.target.value)
                      }
                    />
                  </div>
                  <InputError message={errors.secondary_color} />
                </div>

                <div className="space-y-2 rounded-xl border bg-background p-4">
                  <Label htmlFor="accent_color">Accent Colour</Label>
                  <div className="flex gap-2">
                    <Input
                      id="accent_color"
                      type="color"
                      value={data.accent_color}
                      onChange={(event) =>
                        setData("accent_color", event.target.value)
                      }
                      className="h-10 w-14 p-1"
                    />
                    <Input
                      value={data.accent_color}
                      onChange={(event) =>
                        setData("accent_color", event.target.value)
                      }
                    />
                  </div>
                  <InputError message={errors.accent_color} />
                </div>
              </CardContent>
            </Card>

            <Card>
              <SectionHeading
                icon={<ShieldCheck className="h-4 w-4" />}
                title="Free access button"
                description="Control the temporary free-access option customers can use before buying a package."
              />
              <CardContent className="space-y-4 p-4">
                <ToggleRow
                  checked={data.enable_datalan_free_access}
                  onChange={(checked) =>
                    setData("enable_datalan_free_access", checked)
                  }
                  title="Enable free hotspot access button"
                  description="Show a controlled free-access call-to-action on the hotspot portal."
                />

                <div className="grid gap-4 md:grid-cols-3">
                  <div className="space-y-2">
                    <Label htmlFor="free_access_duration_minutes">
                      Duration Minutes
                    </Label>
                    <Input
                      id="free_access_duration_minutes"
                      type="number"
                      min={1}
                      value={data.free_access_duration_minutes}
                      onChange={(event) =>
                        setData(
                          "free_access_duration_minutes",
                          Number(event.target.value || 60),
                        )
                      }
                    />
                    <InputError message={errors.free_access_duration_minutes} />
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="free_access_cooldown_hours">
                      Cooldown Hours
                    </Label>
                    <Input
                      id="free_access_cooldown_hours"
                      type="number"
                      min={1}
                      value={data.free_access_cooldown_hours}
                      onChange={(event) =>
                        setData(
                          "free_access_cooldown_hours",
                          Number(event.target.value || 24),
                        )
                      }
                    />
                    <InputError message={errors.free_access_cooldown_hours} />
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="free_access_identity_mode">
                      Identity Mode
                    </Label>
                    <Select
                      value={data.free_access_identity_mode}
                      onValueChange={(value) =>
                        setData("free_access_identity_mode", value)
                      }
                    >
                      <SelectTrigger id="free_access_identity_mode">
                        <SelectValue placeholder="Identity mode" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="mac">MAC</SelectItem>
                        <SelectItem value="phone">Phone</SelectItem>
                        <SelectItem value="both">MAC or Phone</SelectItem>
                      </SelectContent>
                    </Select>
                    <InputError message={errors.free_access_identity_mode} />
                  </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                  <div className="space-y-2">
                    <Label htmlFor="free_access_package_id">
                      Free Package ID
                    </Label>
                    <Input
                      id="free_access_package_id"
                      value={data.free_access_package_id}
                      onChange={(event) =>
                        setData("free_access_package_id", event.target.value)
                      }
                      placeholder="Optional package/profile ID"
                    />
                    <InputError message={errors.free_access_package_id} />
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="free_access_speed_limit">Speed Limit</Label>
                    <Input
                      id="free_access_speed_limit"
                      value={data.free_access_speed_limit}
                      onChange={(event) =>
                        setData("free_access_speed_limit", event.target.value)
                      }
                      placeholder="Optional, e.g. 2M/1M"
                    />
                    <InputError message={errors.free_access_speed_limit} />
                  </div>
                </div>

                <div className="grid gap-3 md:grid-cols-2">
                  <ToggleRow
                    checked={data.free_access_requires_phone}
                    onChange={(checked) =>
                      setData("free_access_requires_phone", checked)
                    }
                    title="Require phone"
                    description="Ask customer for phone number before free access."
                  />

                  <ToggleRow
                    checked={data.free_access_requires_name}
                    onChange={(checked) =>
                      setData("free_access_requires_name", checked)
                    }
                    title="Require name"
                    description="Ask customer for name before free access."
                  />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="free_access_button_text">Button Text</Label>
                  <Input
                    id="free_access_button_text"
                    value={data.free_access_button_text}
                    onChange={(event) =>
                      setData("free_access_button_text", event.target.value)
                    }
                  />
                  <InputError message={errors.free_access_button_text} />
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                  <div className="space-y-2">
                    <Label htmlFor="free_access_cooldown_message">
                      Cooldown Message
                    </Label>
                    <Textarea
                      id="free_access_cooldown_message"
                      value={data.free_access_cooldown_message}
                      onChange={(event) =>
                        setData(
                          "free_access_cooldown_message",
                          event.target.value,
                        )
                      }
                    />
                    <InputError message={errors.free_access_cooldown_message} />
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="free_access_success_message">
                      Success Message
                    </Label>
                    <Textarea
                      id="free_access_success_message"
                      value={data.free_access_success_message}
                      onChange={(event) =>
                        setData(
                          "free_access_success_message",
                          event.target.value,
                        )
                      }
                    />
                    <InputError message={errors.free_access_success_message} />
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card>
              <SectionHeading
                icon={<ListChecks className="h-4 w-4" />}
                title="Portal text and payment steps"
                description="Edit the words shown to customers when they select and pay for WiFi packages."
              />
              <CardContent className="space-y-4 p-4">
                <div className="grid gap-4 md:grid-cols-2">
                  <div className="space-y-2">
                    <Label htmlFor="welcome_text">Welcome Text</Label>
                    <Textarea
                      id="welcome_text"
                      value={data.welcome_text}
                      onChange={(event) =>
                        setData("welcome_text", event.target.value)
                      }
                      placeholder="Welcome to our WiFi hotspot"
                    />
                    <InputError message={errors.welcome_text} />
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="footer_text">Footer Text</Label>
                    <Textarea
                      id="footer_text"
                      value={data.footer_text}
                      onChange={(event) =>
                        setData("footer_text", event.target.value)
                      }
                      placeholder="Powered by StudyRoom WiFi Billing"
                    />
                    <InputError message={errors.footer_text} />
                  </div>
                </div>

                <div className="rounded-xl border bg-background">
                  <div className="flex items-center justify-between gap-3 border-b p-4">
                    <div>
                      <h3 className="text-sm font-semibold text-foreground">
                        Purchase Instructions
                      </h3>
                      <p className="mt-1 text-xs text-muted-foreground">
                        Shown as simple steps on the customer portal.
                      </p>
                    </div>
                    <Button
                      type="button"
                      variant="outline"
                      size="sm"
                      onClick={addInstruction}
                    >
                      <Plus className="h-4 w-4" />
                      Add Step
                    </Button>
                  </div>
                  <div className="space-y-3 p-4">
                    {data.purchase_instructions.map((instruction, index) => (
                      <div key={index} className="flex gap-2">
                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border bg-muted/30 text-sm font-semibold text-muted-foreground">
                          {index + 1}
                        </div>
                        <Input
                          value={instruction}
                          onChange={(event) =>
                            updateInstruction(index, event.target.value)
                          }
                          placeholder={`Step ${index + 1}`}
                        />
                        <Button
                          type="button"
                          variant="outline"
                          size="icon"
                          onClick={() => removeInstruction(index)}
                          disabled={data.purchase_instructions.length === 1}
                        >
                          <Minus className="h-4 w-4" />
                        </Button>
                      </div>
                    ))}
                    <InputError message={errors.purchase_instructions} />
                    {data.purchase_instructions.map((_, index) => (
                      <InputError
                        key={index}
                        message={
                          validationErrors[`purchase_instructions.${index}`]
                        }
                      />
                    ))}
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card>
              <SectionHeading
                icon={<Palette className="h-4 w-4" />}
                title="Custom CSS"
                description="Optional advanced styling for the customer-facing portal only."
              />
              <CardContent className="p-4">
                <Textarea
                  value={data.custom_css}
                  onChange={(event) =>
                    setData("custom_css", event.target.value)
                  }
                  className="min-h-40 font-mono"
                  placeholder="/* Optional portal CSS */"
                />
                <InputError className="mt-2" message={errors.custom_css} />
              </CardContent>
            </Card>
          </div>

          <div className="space-y-5 xl:sticky xl:top-5 xl:self-start">
            <Card className="overflow-hidden">
              <CardHeader className="border-b bg-muted/20 py-4">
                <CardTitle className="flex items-center gap-2 text-base">
                  <Wifi className="h-4 w-4 text-primary" />
                  Live portal preview
                </CardTitle>
                <p className="text-sm text-muted-foreground">
                  A clean sample of what customers will see on phone or desktop.
                </p>
              </CardHeader>
              <CardContent className="p-4">
                <div className="overflow-hidden rounded-2xl border bg-muted/30 p-3">
                  <div
                    className="rounded-xl border bg-card bg-cover bg-center p-4 shadow-sm"
                    style={{
                      backgroundImage: setting.background_url
                        ? `linear-gradient(rgba(255,255,255,.88), rgba(255,255,255,.94)), url(${setting.background_url})`
                        : undefined,
                    }}
                  >
                    <div className="rounded-xl border bg-background/95 p-4 shadow-sm">
                      <div className="flex items-center gap-3">
                        <div
                          className="flex h-11 w-11 items-center justify-center rounded-xl text-white"
                          style={{ backgroundColor: data.primary_color }}
                        >
                          {setting.logo_url ? (
                            <img
                              src={setting.logo_url}
                              alt="Logo"
                              className="h-8 w-8 rounded object-contain"
                            />
                          ) : (
                            <Upload className="h-5 w-5" />
                          )}
                        </div>
                        <div className="min-w-0">
                          <div className="truncate font-semibold text-foreground">
                            {data.template_name}
                          </div>
                          <div className="truncate text-xs text-muted-foreground">
                            {data.care_phone || "Customer care line"}
                          </div>
                        </div>
                      </div>

                      <div className="mt-5">
                        <h3 className="text-xl font-semibold text-foreground">
                          {data.welcome_text || "Welcome to our WiFi hotspot"}
                        </h3>
                        <p className="mt-1 text-sm text-muted-foreground">
                          Choose a package and pay securely using M-Pesa.
                        </p>
                        <div className="mt-4 rounded-xl border bg-card p-3">
                          <div className="flex items-center justify-between gap-3">
                            <div>
                              <div className="text-sm font-medium text-foreground">
                                Sample Package
                              </div>
                              <div className="mt-1 text-xs text-muted-foreground">
                                1 hour access
                              </div>
                            </div>
                            <div className="text-right">
                              <div
                                className="text-lg font-semibold"
                                style={{ color: data.primary_color }}
                              >
                                KES 50
                              </div>
                            </div>
                          </div>
                          {data.enable_datalan_free_access && (
                            <Button
                              type="button"
                              className="mt-3 w-full"
                              variant="outline"
                            >
                              {data.free_access_button_text ||
                                "Get 1 hour free access"}
                            </Button>
                          )}
                          <Button
                            type="button"
                            className="mt-3 w-full"
                            style={{ backgroundColor: data.primary_color }}
                          >
                            Pay with M-Pesa
                          </Button>
                        </div>
                      </div>

                      <ol className="mt-4 space-y-2 text-sm text-muted-foreground">
                        {data.purchase_instructions
                          .filter(Boolean)
                          .map((instruction, index) => (
                            <li key={index} className="flex gap-2">
                              <span
                                className="font-semibold"
                                style={{ color: data.accent_color }}
                              >
                                {index + 1}.
                              </span>
                              <span>{instruction}</span>
                            </li>
                          ))}
                      </ol>

                      <div className="mt-5 border-t pt-3 text-xs text-muted-foreground">
                        {data.footer_text ||
                          "Powered by StudyRoom WiFi Billing"}
                      </div>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardContent className="space-y-3 p-4">
                <div className="grid grid-cols-2 gap-3 text-xs text-muted-foreground">
                  <div className="rounded-xl border bg-background p-3">
                    <Clock className="mb-2 h-4 w-4 text-primary" />
                    {data.free_access_duration_minutes} min free access
                  </div>
                  <div className="rounded-xl border bg-background p-3">
                    <ShieldCheck className="mb-2 h-4 w-4 text-primary" />
                    {data.free_access_cooldown_hours} hr cooldown
                  </div>
                </div>
                <Button className="w-full" type="submit" disabled={processing}>
                  <Save className="h-4 w-4" />
                  Save template
                </Button>
                <p className="text-center text-xs text-muted-foreground">
                  Changes apply to the customer hotspot portal.
                </p>
              </CardContent>
            </Card>
          </div>
        </form>
      </div>
    </AuthenticatedLayout>
  );
}
