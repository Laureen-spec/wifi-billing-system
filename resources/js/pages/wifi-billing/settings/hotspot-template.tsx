import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { InputError } from '@/components/ui/input-error';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { PageHeader } from '../components';
import { Head, useForm } from '@inertiajs/react';
import { Minus, Plus, Save, Upload, Wifi } from 'lucide-react';
import { FormEvent } from 'react';

declare function route(name: string, params?: string | number | Record<string, unknown>): string;

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
    'Choose a package',
    'Enter your M-Pesa phone number',
    'Confirm the STK prompt',
    'Internet activates after payment confirmation',
];

export default function HotspotTemplateSettings({ isp, setting, templateOptions, languageOptions }: Props) {
    const { data, setData, post, processing, errors, recentlySuccessful, transform } = useForm<FormData>({
        template_key: setting.template_key || 'modern',
        template_name: setting.template_name || 'Modern Hotspot',
        logo: null,
        background: null,
        primary_color: setting.primary_color || '#0f766e',
        secondary_color: setting.secondary_color || '#0f172a',
        accent_color: setting.accent_color || '#f59e0b',
        welcome_text: setting.welcome_text || '',
        footer_text: setting.footer_text || '',
        care_phone: setting.care_phone || '',
        redirect_url: setting.redirect_url || '',
        language: setting.language || 'en',
        purchase_instructions: setting.purchase_instructions?.length ? setting.purchase_instructions : defaultInstructions,
        custom_css: setting.custom_css || '',
        enable_datalan_free_access: Boolean(setting.enable_datalan_free_access),
        free_access_duration_minutes: Number(setting.free_access_duration_minutes || 60),
        free_access_cooldown_hours: Number(setting.free_access_cooldown_hours || 24),
        free_access_package_id: setting.free_access_package_id ? String(setting.free_access_package_id) : '',
        free_access_speed_limit: setting.free_access_speed_limit || '',
        free_access_identity_mode: setting.free_access_identity_mode || 'mac',
        free_access_requires_phone: Boolean(setting.free_access_requires_phone),
        free_access_requires_name: Boolean(setting.free_access_requires_name),
        free_access_button_text: setting.free_access_button_text || 'Get 1 hour free access',
        free_access_cooldown_message: setting.free_access_cooldown_message || 'You already used free access. Come back after @time_remaining.',
        free_access_success_message: setting.free_access_success_message || 'Free access is active for @duration minutes.',
    });

    const validationErrors = errors as Record<string, string | undefined>;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        transform((formData) => ({
            ...formData,
            enable_datalan_free_access: formData.enable_datalan_free_access ? '1' : '0',
            free_access_requires_phone: formData.free_access_requires_phone ? '1' : '0',
            free_access_requires_name: formData.free_access_requires_name ? '1' : '0',
        }));
        post(route('wifi-billing.settings.hotspot-template.update'), {
            preserveScroll: true,
            forceFormData: true,
        });
    };

    const updateInstruction = (index: number, value: string) => {
        const instructions = [...data.purchase_instructions];
        instructions[index] = value;
        setData('purchase_instructions', instructions);
    };

    const addInstruction = () => {
        setData('purchase_instructions', [...data.purchase_instructions, '']);
    };

    const removeInstruction = (index: number) => {
        setData('purchase_instructions', data.purchase_instructions.filter((_, itemIndex) => itemIndex !== index));
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: 'WiFi Billing', url: route('wifi-billing.dashboard') },
                { label: 'Settings', url: route('wifi-billing.settings.index') },
                { label: 'Hotspot Template' },
            ]}
            pageTitle="Hotspot Template"
        >
            <Head title="Hotspot Template" />

            <div className="space-y-5">
                <PageHeader
                    title="Hotspot Template"
                    description={`Customize the hotspot portal theme settings for ${isp.name}.`}
                    actions={
                        <Button form="hotspot-template-form" type="submit" disabled={processing}>
                            <Save className="h-4 w-4" />
                            Save Changes
                        </Button>
                    }
                />

                <form id="hotspot-template-form" onSubmit={submit} className="grid gap-5 xl:grid-cols-3">
                    <div className="space-y-5 xl:col-span-2">
                        <Card>
                            <CardHeader className="border-b py-4">
                                <CardTitle className="text-base">Template</CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-4 p-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="template_key">Template</Label>
                                    <Select value={data.template_key} onValueChange={(value) => setData('template_key', value)}>
                                        <SelectTrigger id="template_key">
                                            <SelectValue placeholder="Template" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {templateOptions.map((option) => (
                                                <SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.template_key} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="template_name">Template Name</Label>
                                    <Input
                                        id="template_name"
                                        value={data.template_name}
                                        onChange={(event) => setData('template_name', event.target.value)}
                                    />
                                    <InputError message={errors.template_name} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="language">Language</Label>
                                    <Select value={data.language} onValueChange={(value) => setData('language', value)}>
                                        <SelectTrigger id="language">
                                            <SelectValue placeholder="Language" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {languageOptions.map((option) => (
                                                <SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.language} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="care_phone">Care Phone</Label>
                                    <Input
                                        id="care_phone"
                                        value={data.care_phone}
                                        onChange={(event) => setData('care_phone', event.target.value)}
                                        placeholder="+254..."
                                    />
                                    <InputError message={errors.care_phone} />
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="border-b py-4">
                                <CardTitle className="text-base">Brand Assets</CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-4 p-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="logo">Logo</Label>
                                    <Input
                                        id="logo"
                                        type="file"
                                        accept="image/*"
                                        onChange={(event) => setData('logo', event.target.files?.[0] || null)}
                                    />
                                    <InputError message={errors.logo} />
                                    {setting.logo_url && (
                                        <img src={setting.logo_url} alt="Current logo" className="mt-2 h-14 w-auto rounded-md border bg-background object-contain p-2" />
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="background">Background Image</Label>
                                    <Input
                                        id="background"
                                        type="file"
                                        accept="image/*"
                                        onChange={(event) => setData('background', event.target.files?.[0] || null)}
                                    />
                                    <InputError message={errors.background} />
                                    {setting.background_url && (
                                        <img src={setting.background_url} alt="Current background" className="mt-2 h-24 w-full rounded-md border object-cover" />
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="border-b py-4">
                                <CardTitle className="text-base">DataLAN Free Access</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4 p-4">
                                <label className="flex items-center gap-3 rounded-md border p-3">
                                    <input
                                        type="checkbox"
                                        checked={data.enable_datalan_free_access}
                                        onChange={(event) => setData('enable_datalan_free_access', event.target.checked)}
                                        className="h-4 w-4"
                                    />
                                    <span className="text-sm font-medium">Enable free hotspot access button</span>
                                </label>

                                <div className="grid gap-4 md:grid-cols-3">
                                    <div className="space-y-2">
                                        <Label htmlFor="free_access_duration_minutes">Duration Minutes</Label>
                                        <Input
                                            id="free_access_duration_minutes"
                                            type="number"
                                            min={1}
                                            value={data.free_access_duration_minutes}
                                            onChange={(event) => setData('free_access_duration_minutes', Number(event.target.value || 60))}
                                        />
                                        <InputError message={errors.free_access_duration_minutes} />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="free_access_cooldown_hours">Cooldown Hours</Label>
                                        <Input
                                            id="free_access_cooldown_hours"
                                            type="number"
                                            min={1}
                                            value={data.free_access_cooldown_hours}
                                            onChange={(event) => setData('free_access_cooldown_hours', Number(event.target.value || 24))}
                                        />
                                        <InputError message={errors.free_access_cooldown_hours} />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="free_access_identity_mode">Identity Mode</Label>
                                        <Select value={data.free_access_identity_mode} onValueChange={(value) => setData('free_access_identity_mode', value)}>
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
                                        <Label htmlFor="free_access_package_id">Free Package ID</Label>
                                        <Input
                                            id="free_access_package_id"
                                            value={data.free_access_package_id}
                                            onChange={(event) => setData('free_access_package_id', event.target.value)}
                                            placeholder="Optional"
                                        />
                                        <InputError message={errors.free_access_package_id} />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="free_access_speed_limit">Speed Limit</Label>
                                        <Input
                                            id="free_access_speed_limit"
                                            value={data.free_access_speed_limit}
                                            onChange={(event) => setData('free_access_speed_limit', event.target.value)}
                                            placeholder="Optional, e.g. 2M/1M"
                                        />
                                        <InputError message={errors.free_access_speed_limit} />
                                    </div>
                                </div>

                                <div className="grid gap-3 md:grid-cols-2">
                                    <label className="flex items-center gap-3 rounded-md border p-3">
                                        <input
                                            type="checkbox"
                                            checked={data.free_access_requires_phone}
                                            onChange={(event) => setData('free_access_requires_phone', event.target.checked)}
                                            className="h-4 w-4"
                                        />
                                        <span className="text-sm font-medium">Require phone</span>
                                    </label>

                                    <label className="flex items-center gap-3 rounded-md border p-3">
                                        <input
                                            type="checkbox"
                                            checked={data.free_access_requires_name}
                                            onChange={(event) => setData('free_access_requires_name', event.target.checked)}
                                            className="h-4 w-4"
                                        />
                                        <span className="text-sm font-medium">Require name</span>
                                    </label>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="free_access_button_text">Button Text</Label>
                                    <Input
                                        id="free_access_button_text"
                                        value={data.free_access_button_text}
                                        onChange={(event) => setData('free_access_button_text', event.target.value)}
                                    />
                                    <InputError message={errors.free_access_button_text} />
                                </div>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="free_access_cooldown_message">Cooldown Message</Label>
                                        <Textarea
                                            id="free_access_cooldown_message"
                                            value={data.free_access_cooldown_message}
                                            onChange={(event) => setData('free_access_cooldown_message', event.target.value)}
                                        />
                                        <InputError message={errors.free_access_cooldown_message} />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="free_access_success_message">Success Message</Label>
                                        <Textarea
                                            id="free_access_success_message"
                                            value={data.free_access_success_message}
                                            onChange={(event) => setData('free_access_success_message', event.target.value)}
                                        />
                                        <InputError message={errors.free_access_success_message} />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="border-b py-4">
                                <CardTitle className="text-base">Colors and Copy</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4 p-4">
                                <div className="grid gap-4 md:grid-cols-3">
                                    <div className="space-y-2">
                                        <Label htmlFor="primary_color">Primary Color</Label>
                                        <Input
                                            id="primary_color"
                                            type="color"
                                            value={data.primary_color}
                                            onChange={(event) => setData('primary_color', event.target.value)}
                                            className="h-10 p-1"
                                        />
                                        <InputError message={errors.primary_color} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="secondary_color">Secondary Color</Label>
                                        <Input
                                            id="secondary_color"
                                            type="color"
                                            value={data.secondary_color}
                                            onChange={(event) => setData('secondary_color', event.target.value)}
                                            className="h-10 p-1"
                                        />
                                        <InputError message={errors.secondary_color} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="accent_color">Accent Color</Label>
                                        <Input
                                            id="accent_color"
                                            type="color"
                                            value={data.accent_color}
                                            onChange={(event) => setData('accent_color', event.target.value)}
                                            className="h-10 p-1"
                                        />
                                        <InputError message={errors.accent_color} />
                                    </div>
                                </div>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="welcome_text">Welcome Text</Label>
                                        <Textarea
                                            id="welcome_text"
                                            value={data.welcome_text}
                                            onChange={(event) => setData('welcome_text', event.target.value)}
                                        />
                                        <InputError message={errors.welcome_text} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="footer_text">Footer</Label>
                                        <Textarea
                                            id="footer_text"
                                            value={data.footer_text}
                                            onChange={(event) => setData('footer_text', event.target.value)}
                                        />
                                        <InputError message={errors.footer_text} />
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="redirect_url">Redirect URL</Label>
                                    <Input
                                        id="redirect_url"
                                        type="url"
                                        value={data.redirect_url}
                                        onChange={(event) => setData('redirect_url', event.target.value)}
                                        placeholder="https://example.com"
                                    />
                                    <InputError message={errors.redirect_url} />
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between border-b py-4">
                                <CardTitle className="text-base">Purchase Instructions</CardTitle>
                                <Button type="button" variant="outline" size="sm" onClick={addInstruction}>
                                    <Plus className="h-4 w-4" />
                                    Add Step
                                </Button>
                            </CardHeader>
                            <CardContent className="space-y-3 p-4">
                                {data.purchase_instructions.map((instruction, index) => (
                                    <div key={index} className="flex gap-2">
                                        <Input
                                            value={instruction}
                                            onChange={(event) => updateInstruction(index, event.target.value)}
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
                                    <InputError key={index} message={validationErrors[`purchase_instructions.${index}`]} />
                                ))}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="border-b py-4">
                                <CardTitle className="text-base">Custom CSS</CardTitle>
                            </CardHeader>
                            <CardContent className="p-4">
                                <Textarea
                                    value={data.custom_css}
                                    onChange={(event) => setData('custom_css', event.target.value)}
                                    className="min-h-40 font-mono"
                                />
                                <InputError className="mt-2" message={errors.custom_css} />
                            </CardContent>
                        </Card>
                    </div>

                    <div className="space-y-5">
                        <Card>
                            <CardHeader className="border-b py-4">
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <Wifi className="h-4 w-4" />
                                    Preview
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="p-4">
                                <div
                                    className="overflow-hidden rounded-lg border"
                                    style={{
                                        backgroundColor: data.secondary_color,
                                    }}
                                >
                                    <div
                                        className="min-h-80 bg-cover bg-center p-5"
                                        style={{
                                            backgroundImage: setting.background_url ? `linear-gradient(rgba(15,23,42,.58), rgba(15,23,42,.72)), url(${setting.background_url})` : undefined,
                                        }}
                                    >
                                        <div className="rounded-lg bg-background/95 p-4 shadow-sm">
                                            <div className="flex items-center gap-3">
                                                <div
                                                    className="flex h-10 w-10 items-center justify-center rounded-md text-white"
                                                    style={{ backgroundColor: data.primary_color }}
                                                >
                                                    {setting.logo_url ? (
                                                        <img src={setting.logo_url} alt="Logo" className="h-8 w-8 rounded object-contain" />
                                                    ) : (
                                                        <Upload className="h-5 w-5" />
                                                    )}
                                                </div>
                                                <div>
                                                    <div className="font-semibold">{data.template_name}</div>
                                                    <div className="text-xs text-muted-foreground">{data.care_phone || 'Customer care'}</div>
                                                </div>
                                            </div>

                                            <div className="mt-5">
                                                <h3 className="text-xl font-semibold">{data.welcome_text || 'Welcome to our WiFi hotspot'}</h3>
                                                    <div className="mt-3 rounded-md border p-3">
                                                        <div className="text-sm font-medium">Sample Package</div>
                                                        <div className="mt-1 text-2xl font-semibold" style={{ color: data.primary_color }}>KES 50</div>
                                                        {data.enable_datalan_free_access && (
                                                            <Button type="button" className="mt-3 w-full" variant="outline">
                                                                {data.free_access_button_text || 'Get 1 hour free access'}
                                                            </Button>
                                                        )}
                                                        <Button type="button" className="mt-3 w-full" style={{ backgroundColor: data.primary_color }}>
                                                            Pay with M-Pesa
                                                        </Button>
                                                    </div>
                                            </div>

                                            <ol className="mt-4 space-y-2 text-sm text-muted-foreground">
                                                {data.purchase_instructions.filter(Boolean).map((instruction, index) => (
                                                    <li key={index} className="flex gap-2">
                                                        <span className="font-medium" style={{ color: data.accent_color }}>{index + 1}.</span>
                                                        <span>{instruction}</span>
                                                    </li>
                                                ))}
                                            </ol>

                                            <div className="mt-5 border-t pt-3 text-xs text-muted-foreground">
                                                {data.footer_text || 'Powered by StudyRoom WiFi Billing'}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent className="p-4">
                                <Button className="w-full" type="submit" disabled={processing}>
                                    <Save className="h-4 w-4" />
                                    Save Changes
                                </Button>
                                {recentlySuccessful && (
                                    <p className="mt-3 text-center text-sm text-emerald-600">Saved.</p>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
