<?php

namespace StudyRoomTechLab\LandingPage\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use StudyRoomTechLab\LandingPage\Models\LandingPageSetting;

class LandingPageSettingSeeder extends Seeder
{
    public function run(): void
    {
        if (LandingPageSetting::exists()) {
            return;
        }

        try {
            LandingPageSetting::create($this->settings());
        } catch (\Throwable $e) {
            Log::error('Failed to seed StudyRoom landing page settings: ' . $e->getMessage());
            throw $e;
        }
    }

    public static function defaultSettings(): array
    {
        return (new self())->settings();
    }

    private function settings(): array
    {
        return [
            'company_name' => 'StudyRoom TechLab WiFi Billing',
            'contact_email' => 'support@studyroomtechlab.com',
            'contact_phone' => '0713 910 353',
            'contact_address' => 'Kenya',
            'config_sections' => [
                'sections' => $this->sections(),
                'section_visibility' => $this->visibility(),
                'section_order' => $this->order(),
                'colors' => $this->colors(),
            ],
        ];
    }

    private function sections(): array
    {
        return [
            'header' => [
                'variant' => 'header1',
                'company_name' => 'StudyRoom WiFi Billing',
                'cta_text' => 'Get Started',
                'enable_addon_link' => true,
                'enable_pricing_link' => true,
                'navigation_items' => [
                    ['text' => 'Home', 'href' => url('/')],
                    ['text' => 'Features', 'href' => '#features'],
                    ['text' => 'Modules', 'href' => '#modules'],
                    ['text' => 'Pricing', 'href' => url('/pricing')],
                    ['text' => 'Add-ons', 'href' => url('/addons')],
                ],
            ],
            'hero' => [
                'variant' => 'hero1',
                'title' => 'ISP Billing, MikroTik Automation & M-Pesa WiFi Sales in One Platform',
                'subtitle' => 'StudyRoom TechLab WiFi Billing helps ISPs sell hotspot packages, manage customers, trigger M-Pesa payments, provision MikroTik users, track wallets, and control subscriptions from one clean dashboard.',
                'primary_button_text' => 'Start Managing ISP',
                'primary_button_link' => url('/register'),
                'secondary_button_text' => 'Admin Login',
                'secondary_button_link' => url('/login'),
                'highlight_text' => 'Built for Kenyan ISPs, hotspot businesses, cyber cafes, landlords, schools, and managed WiFi providers.',
                'image' => '/packages/StudyRoomTechLab/LandingPage/src/marketplace/hero.png',
            ],
            'stats' => [
                'variant' => 'stats1',
                'stats' => [
                    ['label' => 'MikroTik Router Agent', 'value' => 'Auto'],
                    ['label' => 'M-Pesa STK Ready', 'value' => 'KES'],
                    ['label' => 'Hotspot & PPPoE', 'value' => '2-in-1'],
                    ['label' => 'Multi-ISP SaaS Ready', 'value' => 'Cloud'],
                ],
            ],
            'features' => [
                'variant' => 'features1',
                'title' => 'Everything Needed to Run a Modern WiFi Billing Business',
                'subtitle' => 'A focused ISP system for customer management, router provisioning, packages, M-Pesa payments, wallets, and add-on growth.',
                'features' => $this->features(),
            ],
            'modules' => [
                'variant' => 'modules1',
                'title' => 'Built Around the Real ISP Workflow',
                'subtitle' => 'From public landing page to M-Pesa checkout, router command pull, customer activation, and admin reporting.',
                'modules' => $this->modules(),
            ],
            'benefits' => [
                'variant' => 'benefits1',
                'title' => 'Why ISPs Choose StudyRoom WiFi Billing',
                'benefits' => [
                    [
                        'title' => 'Customer Pays, Router Activates',
                        'description' => 'Customers can buy WiFi packages through the hotspot portal. After payment, the system marks the transaction paid and triggers MikroTik provisioning.',
                    ],
                    [
                        'title' => 'No Manual MikroTik Customer Setup',
                        'description' => 'The router agent pulls commands and creates or updates hotspot users with the correct package profile, speed limit, and shared-user settings.',
                    ],
                    [
                        'title' => 'M-Pesa, Wallets & Settlements',
                        'description' => 'Track customer payments, ISP wallet balances, platform commission, ledger records, and settlement workflows from the dashboard.',
                    ],
                    [
                        'title' => 'Safe Router Admin Separation',
                        'description' => 'The system manages /ip hotspot user accounts for customers and does not touch MikroTik /user admin accounts.',
                    ],
                    [
                        'title' => 'Multi-ISP Ready',
                        'description' => 'Super Admin can manage platform modules while ISP admins manage their own routers, packages, customers, and payments.',
                    ],
                    [
                        'title' => 'Add-on Friendly',
                        'description' => 'Extend the system with M-Pesa, Landing Page, HRM, POS, accounts, and future ISP operation modules without rebuilding the core.',
                    ],
                ],
            ],
            'gallery' => [
                'variant' => 'gallery1',
                'title' => 'A Practical Dashboard for ISP Work',
                'subtitle' => 'Manage routers, packages, hotspot customers, transactions, wallets, settlements, and provisioning status in one place.',
                'images' => [
                    '/packages/StudyRoomTechLab/LandingPage/src/marketplace/image1.png',
                    '/packages/StudyRoomTechLab/LandingPage/src/marketplace/image2.png',
                    '/packages/StudyRoomTechLab/LandingPage/src/marketplace/image3.png',
                    '/packages/StudyRoomTechLab/LandingPage/src/marketplace/image4.png',
                ],
            ],
            'cta' => [
                'variant' => 'cta1',
                'title' => 'Ready to Launch Your WiFi Billing Platform?',
                'subtitle' => 'Use StudyRoom TechLab WiFi Billing to manage packages, customers, MikroTik provisioning, and M-Pesa payment activation from one system.',
                'primary_button' => 'Create Account',
                'secondary_button' => 'Talk to StudyRoom TechLab',
            ],
            'addons' => [
                'title' => 'ISP Add-ons & Modules',
                'subtitle' => 'Enable extra modules for payments, landing pages, accounts, field teams, POS, and ISP operations.',
                'per_page' => 20,
                'default_price_type' => 'monthly',
                'card_variant' => 'card1',
                'show_search' => true,
                'show_category' => true,
                'show_price' => true,
                'show_sort' => true,
                'empty_message' => 'No add-ons available yet. Enable modules from Super Admin to show them here.',
            ],
            'pricing' => [
                'title' => 'Simple Plans for ISP Teams',
                'subtitle' => 'Choose a plan based on your router count, customers, modules, and support needs.',
                'default_subscription_type' => 'pre-package',
                'default_price_type' => 'monthly',
                'show_pre_package' => true,
                'show_usage_subscription' => true,
                'show_monthly_yearly_toggle' => true,
                'empty_message' => 'No plans available yet. Add plans from Super Admin to show pricing here.',
            ],
            'footer' => [
                'variant' => 'footer1',
                'description' => 'StudyRoom TechLab WiFi Billing is a MikroTik-ready ISP billing platform for hotspot packages, customer subscriptions, M-Pesa payments, wallets, and router provisioning.',
                'email' => 'support@studyroomtechlab.com',
                'phone' => '0713 910 353',
                'newsletter_title' => 'Get ISP System Updates',
                'newsletter_description' => 'Subscribe for WiFi billing, MikroTik, M-Pesa, and StudyRoom TechLab product updates.',
                'newsletter_button_text' => 'Subscribe',
                'copyright_text' => '© ' . date('Y') . ' StudyRoom TechLab. All rights reserved.',
                'navigation_sections' => [
                    [
                        'title' => 'Platform',
                        'links' => [
                            ['text' => 'Features', 'href' => '#features'],
                            ['text' => 'Modules', 'href' => '#modules'],
                            ['text' => 'Pricing', 'href' => url('/pricing')],
                        ],
                    ],
                    [
                        'title' => 'ISP Tools',
                        'links' => [
                            ['text' => 'MikroTik Provisioning', 'href' => '#modules'],
                            ['text' => 'M-Pesa Payments', 'href' => '#features'],
                            ['text' => 'Add-ons', 'href' => url('/addons')],
                        ],
                    ],
                    [
                        'title' => 'Account',
                        'links' => [
                            ['text' => 'Login', 'href' => url('/login')],
                            ['text' => 'Register', 'href' => url('/register')],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function features(): array
    {
        return [
            [
                'title' => 'MikroTik Hotspot & PPPoE Automation',
                'description' => 'Create customer commands, assign package profiles, update users, and let the router agent pull provisioning automatically.',
                'icon' => 'Wifi',
            ],
            [
                'title' => 'M-Pesa STK Push Payments',
                'description' => 'Customers can pay for WiFi packages using M-Pesa. Successful payments update transactions and trigger activation.',
                'icon' => 'CreditCard',
            ],
            [
                'title' => 'Customer & Package Management',
                'description' => 'Manage customers, router links, hotspot packages, shared users, billing status, connection status, and due dates.',
                'icon' => 'Users',
            ],
            [
                'title' => 'ISP Wallet & Platform Commission',
                'description' => 'Track ISP earnings, platform fees, ledger entries, wallet balances, and settlement requests from one dashboard.',
                'icon' => 'Wallet',
            ],
            [
                'title' => 'Router Agent Pull Mode',
                'description' => 'Routers pull pending commands from Laravel, which is safer for networks where the router is behind NAT or not publicly reachable.',
                'icon' => 'Router',
            ],
            [
                'title' => 'Modular Add-on System',
                'description' => 'Enable Landing Page, M-Pesa Payment, HRM, POS, accounts, and future ISP add-ons without changing the core workflow.',
                'icon' => 'Package',
            ],
        ];
    }

    private function modules(): array
    {
        return [
            [
                'key' => 'wifi-billing-core',
                'label' => 'Core Billing',
                'title' => 'WiFi & ISP Billing Core',
                'description' => 'Create internet packages, assign routers, manage customers, track billing status, connection status, and provisioning status.',
                'image' => '/packages/StudyRoomTechLab/LandingPage/src/marketplace/image1.png',
            ],
            [
                'key' => 'mikrotik-agent',
                'label' => 'Router Agent',
                'title' => 'MikroTik Agent Mode Provisioning',
                'description' => 'RouterOS pulls queued commands from Laravel and provisions hotspot users safely without exposing the router publicly.',
                'image' => '/packages/StudyRoomTechLab/LandingPage/src/marketplace/image2.png',
            ],
            [
                'key' => 'mpesa-payment',
                'label' => 'M-Pesa',
                'title' => 'M-Pesa Payment Add-on',
                'description' => 'Accept STK push payments, simulate payments locally, process callbacks, post wallet balances, and trigger paid customer provisioning.',
                'image' => '/packages/StudyRoomTechLab/LandingPage/src/marketplace/image3.png',
            ],
            [
                'key' => 'hotspot-portal',
                'label' => 'Hotspot Portal',
                'title' => 'Customer Self-Service WiFi Portal',
                'description' => 'Let customers choose packages, validate phone numbers, pay with M-Pesa, and get activated automatically on MikroTik.',
                'image' => '/packages/StudyRoomTechLab/LandingPage/src/marketplace/image4.png',
            ],
            [
                'key' => 'wallet-settlements',
                'label' => 'Wallets',
                'title' => 'ISP Wallets & Settlements',
                'description' => 'Track ISP revenue, platform commission, wallet ledger entries, payout approval, paid status, and failed settlement workflows.',
                'image' => '/packages/StudyRoomTechLab/LandingPage/src/marketplace/image5.png',
            ],
            [
                'key' => 'superadmin-saas',
                'label' => 'SaaS Control',
                'title' => 'Super Admin & ISP Admin Control',
                'description' => 'Separate Super Admin platform control from ISP admin operations so each provider manages their own customers and routers.',
                'image' => '/packages/StudyRoomTechLab/LandingPage/src/marketplace/image6.png',
            ],
        ];
    }

    private function visibility(): array
    {
        return [
            'header' => true,
            'hero' => true,
            'stats' => true,
            'features' => true,
            'modules' => true,
            'benefits' => true,
            'gallery' => true,
            'cta' => true,
            'footer' => true,
            'addons' => true,
            'pricing' => true,
        ];
    }

    private function order(): array
    {
        return ['header', 'hero', 'stats', 'features', 'modules', 'benefits', 'gallery', 'cta', 'footer'];
    }

    private function colors(): array
    {
        return [
            'primary' => '#0f766e',
            'secondary' => '#0ea5e9',
            'accent' => '#f59e0b',
        ];
    }
}
