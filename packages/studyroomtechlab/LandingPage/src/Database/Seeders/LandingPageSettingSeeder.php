<?php

namespace StudyRoomTechLab\LandingPage\Database\Seeders;

use Illuminate\Database\Seeder;
use StudyRoomTechLab\LandingPage\Models\LandingPageSetting;
use Illuminate\Support\Facades\Log;

class LandingPageSettingSeeder extends Seeder
{
    public function run()
    {
        if (LandingPageSetting::exists()) {
            return;
        }

        try {
            LandingPageSetting::create($this->getDefaultSettings());
        } catch (\Exception $e) {
            Log::error('Failed to seed landing page settings: ' . $e->getMessage());
            throw $e;
        }
    }

    private function getDefaultSettings(): array
    {
        return [
            'company_name' => 'StudyRoomTechLab WiFi Billing',
            'contact_email' => 'support@StudyRoomTechLabStudyRoomTechLab.com',
            'contact_phone' => '+1 (555) 123-4567',
            'contact_address' => '123 Business Ave, City, State 12345',
            'config_sections' => $this->getDefaultConfigSections()
        ];
    }

    private function getDefaultConfigSections(): array
    {
        return [
            'sections' => $this->getDefaultSections(),
            'section_visibility' => $this->getDefaultVisibility(),
            'section_order' => $this->getDefaultOrder(),
            'colors' => $this->getDefaultColors()
        ];
    }

    private function getDefaultSections(): array
    {
        return [
            'hero' => [
                'variant' => 'hero1',
                'title' => 'Run Your ISP with StudyRoomTechLab WiFi Billing',
                'subtitle' => 'The complete WiFi and ISP billing platform that combines WiFi billing, ISP billing, MikroTik billing, customer subscriptions, and M-Pesa-ready payments into a single powerful platform. Streamline operations, boost productivity, and grow your business with our integrated suite of tools.',
                'primary_button_text' => 'Start Free Trial',
                'primary_button_link' => route('register'),
                'secondary_button_text' => 'Login',
                'secondary_button_link' => route('login'),
                'highlight_text' => 'StudyRoomTechLab WiFi Billing',
                'image' => '/packages/StudyRoomTechLab/LandingPage/src/marketplace/hero.png'
            ],
            'header' => [
                'variant' => 'header1',
                'company_name' => 'StudyRoomTechLab WiFi Billing',
                'cta_text' => 'Get Started',
                'enable_addon_link' => true,
                'enable_pricing_link' => true,
                'navigation_items' => [
                    ['text' => 'Home', 'href' => route('landing.page')]
                ]
            ],
            'stats' => [
                'variant' => 'stats1',
                'stats' => [
                    ['label' => 'Businesses Trust Us', 'value' => '10,000+'],
                    ['label' => 'Uptime Guarantee', 'value' => '99.9%'],
                    ['label' => 'Customer Support', 'value' => '24/7'],
                    ['label' => 'Countries Worldwide', 'value' => '50+']
                ]
            ],
            'features' => [
                'variant' => 'features1',
                'title' => 'Powerful Features',
                'subtitle' => 'Everything your business needs in one integrated platform',
                'features' => $this->getDefaultFeatures()
            ],
            'modules' => [
                'variant' => 'modules1',
                'title' => 'Complete Business Solutions',
                'subtitle' => 'Discover our comprehensive modules designed to streamline every aspect of your ISP operations',
                'modules' => [
                    [
                        'key' => 'taskly',
                        'label' => 'Project',
                        'title' => 'ISP Billing Dashboard',
                        'description' => 'Organize and track projects efficiently with comprehensive project management tools. Manage tasks, milestones, and deadlines with team collaboration in one centralized platform. Track progress with Gantt charts and Kanban boards, assign tasks and set priorities, monitor project timelines and deliverables, and generate detailed project reports. Perfect for teams of any size.',
                        'image' => '/packages/StudyRoomTechLab/LandingPage/src/marketplace/image1.png'
                    ],
                    [
                        'key' => 'account',
                        'label' => 'ISP Billing',
                        'title' => 'M-Pesa Ready Billing',
                        'description' => 'Streamline your financial operations with our comprehensive accounting system. Manage invoices, bills, and payments, track income and expenses, perform bank account reconciliation, and generate detailed financial reports. Professional invoice generation, vendor and customer management, tax calculations and compliance, with real-time financial analytics.',
                        'image' => '/packages/StudyRoomTechLab/LandingPage/src/marketplace/image2.png'
                    ],
                    [
                        'key' => 'hrm',
                        'label' => 'Field Staff',
                        'title' => 'Human Resource Management System',
                        'description' => 'Complete employee management solution for modern businesses. Manage employee records and profiles, attendance and leave management, payroll processing and automation, and performance evaluations. Handle department and designation management, recruitment process handling, employee benefits management, and comprehensive HR reporting.',
                        'image' => '/packages/StudyRoomTechLab/LandingPage/src/marketplace/image3.png'
                    ],
                    [
                        'key' => 'lead',
                        'label' => 'Customer Accounts',
                        'title' => 'Customer Relationship Management',
                        'description' => 'Build stronger customer relationships and boost sales with our powerful Customer Accounts system. Manage leads and contacts, track sales pipeline, handle deal and opportunity management, and monitor customer interaction tracking. Automate follow-ups, analyze sales performance, forecast revenue, and maintain customer communication history.',
                        'image' => '/packages/StudyRoomTechLab/LandingPage/src/marketplace/image4.png'
                    ],
                    [
                        'key' => 'pos',
                        'label' => 'M-Pesa Ready',
                        'title' => 'Point of Sale System',
                        'description' => 'Fast, reliable point-of-sale solution for retail and service businesses. Process transactions quickly, manage inventory in real-time, handle multiple payment methods, and generate instant receipts. Track product stock, support barcode scanning, handle returns and exchanges, and generate comprehensive sales reports.',
                        'image' => '/packages/StudyRoomTechLab/LandingPage/src/marketplace/image5.png'
                    ],
                    [
                        'key' => 'productservice',
                        'label' => 'Product & Service',
                        'title' => 'Product & Service Management',
                        'description' => 'Efficiently manage your complete products and services catalog. Organize product categories, manage inventory levels, implement pricing strategies and variations, and handle product attributes. Manage stock across multiple locations, set up automated reorder points, track product performance, and maintain detailed product specifications.',
                        'image' => '/packages/StudyRoomTechLab/LandingPage/src/marketplace/image6.png'
                    ]
                ]
            ],
            'benefits' => [
                'variant' => 'benefits1',
                'title' => 'Why Choose StudyRoomTechLab WiFi Billing?',
                'benefits' => [
                    ['title' => 'Complete ISP Billing', 'description' => 'Manage ISP packages, customer accounts, billing status, and service workflows from one focused dashboard.'],
                    ['title' => 'Integrated Financial System', 'description' => 'Manage your finances seamlessly with comprehensive accounting, invoicing, expense tracking, and real-time financial reporting.'],
                    ['title' => 'Efficient HR Management', 'description' => 'Streamline employee management with automated payroll, attendance tracking, leave management, and performance evaluation tools.'],
                    ['title' => 'Powerful Customer Accounts Tools', 'description' => 'Track customer subscriptions, connection status, due dates, and support follow-ups from one place.'],
                    ['title' => 'Modern M-Pesa Ready Solution', 'description' => 'Prepare for M-Pesa payments, receipts, and subscription activation in a modular add-on flow.'],
                    ['title' => 'Scalable & Secure', 'description' => 'Enterprise-grade security with cloud-based infrastructure that grows with your business needs and ensures data protection.']
                ]
            ],
            'gallery' => [
                'variant' => 'gallery1',
                'title' => 'See StudyRoomTechLab WiFi Billing in Action',
                'subtitle' => 'Explore our intuitive interface and powerful features through real screenshots of our platform',
                'images' => ['/packages/StudyRoomTechLab/LandingPage/src/marketplace/image1.png', '/packages/StudyRoomTechLab/LandingPage/src/marketplace/image2.png', '/packages/StudyRoomTechLab/LandingPage/src/marketplace/image3.png', '/packages/StudyRoomTechLab/LandingPage/src/marketplace/image4.png']
            ],
            'cta' => [
                'variant' => 'cta1',
                'title' => 'Ready to Transform Your Business?',
                'subtitle' => 'Join ISP teams using StudyRoomTechLab WiFi Billing to streamline packages, customers, and payments.',
                'primary_button' => 'Start Free Trial',
                'secondary_button' => 'Contact Sales'
            ],
            'addons' => [
                'title' => 'Premium Addons',
                'subtitle' => 'Extend StudyRoomTechLab WiFi Billing with installable modules for payments, field work, and ISP operations',
                'per_page' => 20,
                'default_price_type' => 'monthly',
                'card_variant' => 'card1',
                'show_search' => true,
                'show_category' => true,
                'show_price' => true,
                'show_sort' => true,
                'empty_message' => 'No addons available. Check back later for new premium addons and modules.'
            ],
            'pricing' => [
                'title' => 'Subscription Setting',
                'subtitle' => 'Choose the perfect subscription plan for your business needs',
                'default_subscription_type' => 'pre-package',
                'default_price_type' => 'monthly',
                'show_pre_package' => true,
                'show_usage_subscription' => true,
                'show_monthly_yearly_toggle' => true,
                'empty_message' => 'No plans available. Check back later for new pricing plans.'
            ],
            'footer' => [
                'variant' => 'footer1',
                'description' => 'The complete ISP billing solution for modern enterprisesThe complete ISP billing solution for modern enterprises.',
                'email' => 'support@StudyRoomTechLabStudyRoomTechLab.com',
                'phone' => '+1 (555) 123-4567',
                'newsletter_title' => 'Join Our Community',
                'newsletter_description' => 'We build modern web tools to help you jump-start your daily business work.',
                'newsletter_button_text' => 'Subscribe',
                'copyright_text' => '',
                'navigation_sections' => [
                    [
                        'title' => 'Product',
                        'links' => [
                            ['text' => 'Features', 'href' => '#features'],
                            ['text' => 'Pricing', 'href' => '#pricing'],
                            ['text' => 'Demo', 'href' => '#demo']
                        ]
                    ],
                    [
                        'title' => 'Company',
                        'links' => [
                            ['text' => 'About', 'href' => '#about'],
                            ['text' => 'Contact', 'href' => '#contact'],
                            ['text' => 'Support', 'href' => '#support']
                        ]
                    ]
                ]
            ]
        ];
    }

    private function getDefaultFeatures(): array
    {
        return [
            ['title' => 'Project Management', 'description' => 'Organize and track projects efficiently. Manage tasks, milestones, and deadlines with team collaboration. Track progress with Gantt charts and Kanban boards.', 'icon' => 'FolderOpen'],
            ['title' => 'ISP Billing', 'description' => 'Manage finances with ease and accuracy. Handle invoices, bills, and payments. Track income and expenses and generate detailed financial reports.', 'icon' => 'Calculator'],
            ['title' => 'Field Staff', 'description' => 'Simplify employee management and payroll. Manage employee records and profiles, attendance and leave management, and payroll processing automation.', 'icon' => 'UserCheck'],
            ['title' => 'Customer Accounts', 'description' => 'Strengthen customer relationships and improve sales. Manage leads and contacts, track sales pipeline, and handle deal and opportunity management.', 'icon' => 'Users'],
            ['title' => 'M-Pesa Ready', 'description' => 'Fast and reliable point-of-sale solution. Process transactions quickly, manage inventory in real-time, and handle multiple payment methods.', 'icon' => 'CreditCard'],
            ['title' => 'Product & Service', 'description' => 'Manage your products and services catalog efficiently. Organize product categories, manage inventory levels, and implement pricing strategies.', 'icon' => 'Package']
        ];
    }

    private function getDefaultVisibility(): array
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
            'pricing' => true
        ];
    }

    private function getDefaultOrder(): array
    {
        return ['header', 'hero', 'stats', 'features', 'modules', 'benefits', 'gallery', 'cta', 'footer'];
    }

    private function getDefaultColors(): array
    {
        return [
            'primary' => '#10b981',
            'secondary' => '#059669',
            'accent' => '#065f46'
        ];
    }
}
