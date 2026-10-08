<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\Product;
use App\Models\RecoveryJob;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EkStoreSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'store_name' => 'EK Electronics',
            'phone' => '+27 10 500 2140',
            'email' => 'info@ekelectronics.co.za',
            'whatsapp_number' => '27105002140',
            'whatsapp_display_name' => 'EK Operations',
            'whatsapp_enabled' => '1',
            'whatsapp_fab_enabled' => '1',
            'whatsapp_default_message' => 'Hi EK Electronics, I need help with a drive.',
            'whatsapp_order_template' => "New order {number} from {name}\nPhone: {phone}\nShip to: {city}\n\n{lines}\n\nTotal: R {total}\nPlease confirm stock and courier.",
            'whatsapp_ticket_template' => 'New ticket {number}: {subject}',
            'whatsapp_business_hours' => 'Mon–Fri 08:00–17:00 SAST',
            'whatsapp_away_message' => 'Thanks for messaging EK Electronics. We will reply during business hours.',
            'whatsapp_notify_orders' => '1',
            'whatsapp_notify_tickets' => '1',
            'whatsapp_notify_contact' => '1',
            'whatsapp_notify_invoices' => '1',
            'whatsapp_api_enabled' => '0',
            'whatsapp_api_provider' => 'wa_me',
            'whatsapp_api_mode' => 'sandbox',
            'whatsapp_api_version' => 'v21.0',
            'whatsapp_api_base_url' => 'https://graph.facebook.com',
            'whatsapp_webhook_verify_token' => 'ek-wa-verify',
            'whatsapp_webhook_enabled' => '0',
            'whatsapp_api_fallback_wame' => '1',
            'whatsapp_api_send_orders' => '1',
            'whatsapp_api_send_tickets' => '1',
            'whatsapp_api_send_contact' => '0',
            'whatsapp_chat_position' => 'bottom-right',
            'whatsapp_chat_color' => '#25d366',
            'whatsapp_chat_label' => 'WhatsApp us',
            'whatsapp_chat_subtitle' => 'Typically replies in minutes during business hours',
            'whatsapp_chat_auto_open' => '0',
            'whatsapp_chat_sound' => '1',
            'whatsapp_chat_show_agent' => '1',
            'whatsapp_chat_agent_name' => 'EK Support',
            'whatsapp_chat_prechat_enabled' => '0',
            'whatsapp_welcome_message' => 'Welcome to EK Electronics. How can we help — shop, recovery, or an existing order?',
            'whatsapp_offline_message' => 'We are offline right now. Leave your number and we will WhatsApp you back.',
            'whatsapp_timezone' => 'Africa/Johannesburg',
            'whatsapp_hours_start' => '08:00',
            'whatsapp_hours_end' => '17:00',
            'whatsapp_department_default' => 'sales',
            'whatsapp_quick_replies' => "Stock check\nCourier quote\nData recovery\nTalk to sales",
            'whatsapp_signature' => "\n— EK Electronics Midrand",
            'whatsapp_invoice_template' => "Invoice {number} for {name}\nAmount: R {amount}\nDue: {due}\nPay via EFT and reply with proof.",
            'whatsapp_recovery_template' => "Recovery job {number} update: {stage}\nClient: {name}",
            'tagline' => 'Innovation. Integrity. Impact.',
            'address' => "Unit 15, Ground floor, Lone Creek Office Building D\n21 Mac-Mac Road and Howick Close\nWaterfall Business Park, Midrand, 1685",
            'hours' => 'Mon–Fri 08:00–17:00 SAST',
            'currency' => 'ZAR',
            'vat_rate' => '15',
            'free_shipping_min' => '2500',
            'shipping_note' => 'Courier nationwide from Midrand. Collection available at Waterfall Business Park.',
            'hero_headline' => 'Hard drives & components, refurbished with integrity.',
            'hero_sub' => 'Enterprise storage, memory, boards, and professional data recovery from Midrand.',
            'hero_cta_label' => 'Shop catalogue',
            'hero_cta_url' => '/shop',
            'shop_enabled' => '1',
            'checkout_enabled' => '1',
            'show_prices' => '1',
            'low_stock_threshold' => '10',
            'footer_about' => 'Hard drive refurbishment, secure erasure, data recovery, and computer components from Midrand.',
            'footer_col1_title' => 'Customer services',
            'footer_col2_title' => 'Shop',
            'footer_col3_title' => 'Contact',
            'footer_legal' => '© {year} EK Electronics · ekelectronics.co.za',
            'footer_show_socials' => '1',
            'footer_show_whatsapp' => '1',
            'customer_login_enabled' => '1',
            'customer_register_enabled' => '1',
            'customer_portal_enabled' => '1',
            'customer_login_heading' => 'Customer sign in',
            'customer_login_blurb' => 'Track orders, open support tickets, and manage your EK account.',
            'customer_register_blurb' => 'Create a free customer account to follow purchases and recovery tickets.',
            'staff_login_enabled' => '1',
            'staff_portal_enabled' => '1',
            'staff_login_heading' => 'Staff portal',
            'staff_login_blurb' => 'Operations desk for orders, tickets, recovery jobs, and accounting.',
            'require_phone_on_register' => '0',
            'welcome_message' => 'Welcome to EK Electronics.',
        ];
        Setting::many($settings);

        $this->seedMenus();
        $this->seedPages();
        $this->seedUsersAndExpenses();

        $catalogue = [
            ['Enterprise / Server HDDs (SAS / SATA)', 'ent-hdd-8', 'Enterprise SAS HDD 8TB', 1899, 1120, 42, 'Grade A refurbished', 'assets/img/hdd.jpg', true],
            ['Desktop HDDs (3.5”)', 'desk-hdd-4', 'Desktop HDD 4TB 3.5"', 749, 420, 88, 'Grade A+', 'assets/img/hdd.jpg', true],
            ['Laptop HDDs (2.5”)', 'lap-hdd-1', 'Laptop HDD 1TB 2.5"', 429, 210, 61, 'Certified', 'assets/img/laptop.jpg', true],
            ['SATA SSDs', 'sata-ssd-960', 'SATA SSD 960GB', 1099, 640, 54, 'New / OEM', 'assets/img/ssd.jpg', true],
            ['NVMe SSDs (M.2 / PCIe)', 'nvme-2', 'NVMe SSD 2TB M.2', 2499, 1540, 27, 'Enterprise pull', 'assets/img/chips.jpg', true],
            ['External SSDs', 'ext-ssd', 'External SSD 1TB USB-C', 1649, 980, 33, 'Retail kit', 'assets/img/dock.jpg', false],
            ['Enterprise / Server SSDs', 'ent-ssd', 'Enterprise SSD 1.92TB', 3290, 2100, 19, 'Health 98%+', 'assets/img/ssd.jpg', true],
            ['Desktop Memory (DDR3 / DDR4 / DDR5)', 'ddr4-32', 'Desktop Memory 32GB DDR4', 890, 480, 70, 'Tested', 'assets/img/ram.jpg', true],
            ['Laptop Memory (SODIMM)', 'sodimm-16', 'Laptop SODIMM 16GB DDR4', 520, 260, 46, 'Tested', 'assets/img/ram.jpg', false],
            ['Server Memory (ECC / Registered)', 'ecc-64', 'Server ECC RDIMM 64GB', 2180, 1280, 14, 'Server pull', 'assets/img/ram.jpg', false],
            ['Graphics Cards (GPUs)', 'gpu-16', 'Graphics Card 16GB', 4590, 2900, 8, 'Refurbished', 'assets/img/gpu.jpg', true],
            ['Processors (CPUs)', 'cpu-8c', 'Xeon 8-core Processor', 1890, 980, 21, 'Pulled / tested', 'assets/img/chips.jpg', false],
            ['Motherboards', 'mb-atx', 'ATX Server Motherboard', 2450, 1400, 11, 'Refurbished', 'assets/img/mb.jpg', false],
            ['Power Supplies', 'psu-750', '750W Power Supply', 980, 520, 25, '80+ Gold', 'assets/img/psu.jpg', false],
            ['Network Cards (NICs)', 'nic-10g', '10Gb Network Card', 760, 360, 30, 'OEM', 'assets/img/cables.jpg', false],
            ['RAID / Controller Cards', 'raid', 'RAID Controller Card', 1420, 780, 9, 'Tested', 'assets/img/mb.jpg', false],
            ['Surveillance Drives (CCTV/NVR)', 'surv', 'Surveillance HDD 6TB', 1299, 720, 37, 'Grade A', 'assets/img/hdd.jpg', false],
            ['External Portable Drives', 'ext-hdd', 'External Portable 2TB', 980, 540, 40, 'Retail', 'assets/img/dock.jpg', false],
            ['Docking Stations (SATA/USB-C)', 'dock', 'USB-C Dual Docking Station', 690, 310, 22, 'New', 'assets/img/dock.jpg', false],
            ['HDD/SSD External Enclosures', 'encl', 'HDD/SSD External Enclosure', 310, 120, 58, 'New', 'assets/img/dock.jpg', false],
            ['SATA & Power Cables', 'cables', 'SATA & Power Cable Pack', 89, 25, 120, 'New', 'assets/img/cables.jpg', false],
            ['USB to SATA Adapters', 'usb-sata', 'USB to SATA Adapter', 149, 45, 77, 'New', 'assets/img/cables.jpg', false],
            ['Drive Mounting Kits', 'mount', 'Drive Mounting Kit', 129, 40, 64, 'New', 'assets/img/cables.jpg', false],
            ['Cooling Fans and Accessories', 'fan', 'Cooling Fan 120mm', 99, 30, 90, 'New', 'assets/img/psu.jpg', false],
        ];

        $sort = 0;
        foreach ($catalogue as [$catName, $sku, $name, $price, $cost, $stock, $grade, $image, $featured]) {
            $category = Category::query()->firstOrCreate(
                ['slug' => Str::slug($catName)],
                ['name' => $catName, 'sort_order' => $sort++, 'is_active' => true]
            );

            Product::query()->updateOrCreate(
                ['sku' => $sku],
                [
                    'category_id' => $category->id,
                    'name' => $name,
                    'slug' => Str::slug($name.'-'.$sku),
                    'description' => $name.' — tested and ready from EK Electronics Midrand.',
                    'grade' => $grade,
                    'price' => $price,
                    'cost' => $cost,
                    'stock' => $stock,
                    'image_path' => $image,
                    'is_active' => true,
                    'is_featured' => $featured,
                ]
            );
        }

        $product = Product::query()->where('sku', 'ent-hdd-8')->first();
        if ($product && ! Order::query()->where('number', 'SO-DEMO-001')->exists()) {
            $order = Order::query()->create([
                'number' => 'SO-DEMO-001',
                'customer_name' => 'Naledi IT',
                'customer_phone' => '+27820000000',
                'city' => 'Midrand',
                'status' => 'paid',
                'subtotal' => $product->price,
                'total' => $product->price,
                'whatsapp_payload' => 'Demo paid order for Enterprise SAS HDD 8TB',
                'whatsapp_sent_at' => now(),
            ]);
            OrderItem::query()->create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'sku' => $product->sku,
                'qty' => 1,
                'unit_price' => $product->price,
                'line_total' => $product->price,
            ]);
            Invoice::query()->create([
                'number' => 'INV-DEMO-001',
                'order_id' => $order->id,
                'customer_name' => 'Naledi IT',
                'customer_phone' => '+27820000000',
                'amount' => $product->price,
                'vat_amount' => round(((float) $product->price) * 15 / 115, 2),
                'status' => 'paid',
                'due_date' => now()->subDays(2),
                'paid_at' => now()->subDay(),
            ]);
        }

        Invoice::query()->firstOrCreate(
            ['number' => 'INV-1051'],
            [
                'customer_name' => 'Thabo Logistics',
                'customer_phone' => '+27821112222',
                'amount' => 12480,
                'vat_amount' => 1627.83,
                'status' => 'late',
                'due_date' => now()->subDays(7),
            ]
        );

        RecoveryJob::query()->firstOrCreate(
            ['number' => 'JOB-224'],
            [
                'client_name' => 'Legal firm PTA',
                'client_phone' => '+27123456789',
                'media' => 'RAID 5 × 4',
                'stage' => 'Imaging 62%',
                'quote' => 18500,
                'notes' => 'Confidential corporate recovery',
            ]
        );
    }

    private function seedMenus(): void
    {
        if (Menu::query()->exists()) {
            return;
        }

        $items = [
            ['header', 'Home', '/', 1],
            ['header', 'Shop', '/shop', 2],
            ['header', 'Services', '/services', 3],
            ['header', 'About', '/about', 4],
            ['header', 'Contact', '/contact', 5],
            ['header', 'Shipping', '/page/shipping', 6],
            ['header', 'Warranty', '/page/warranty', 7],
            ['header', 'Terms', '/page/terms', 8],
            ['footer', 'Shipping', '/page/shipping', 1],
            ['footer', 'Warranty policy', '/page/warranty', 2],
            ['footer', 'Terms and conditions', '/page/terms', 3],
            ['footer', 'Customer login', '/login', 4],
            ['footer', 'Staff login', '/login?portal=staff', 5],
            ['footer_shop', 'All products', '/shop', 1],
            ['footer_services', 'Refurbishment', '/services', 1],
            ['footer_services', 'Data recovery', '/services', 2],
            ['footer_legal', 'Privacy', '/page/terms', 1],
        ];

        foreach ($items as [$location, $label, $url, $sort]) {
            Menu::query()->create([
                'location' => $location,
                'label' => $label,
                'url' => $url,
                'target' => '_self',
                'sort_order' => $sort,
                'is_active' => true,
            ]);
        }
    }

    private function seedPages(): void
    {
        $pages = [
            [
                'title' => 'Shipping',
                'slug' => 'shipping',
                'blocks' => [
                    ['heading' => 'Courier from Midrand', 'body' => "We dispatch nationwide with trusted couriers. Most in-stock items leave within 24–48 hours on business days.\n\nCollection is available at Waterfall Business Park by appointment.", 'type' => 'text'],
                    ['heading' => 'Need a quote?', 'body' => 'Message us on WhatsApp with your suburb and cart contents for a courier estimate.', 'type' => 'cta', 'button_label' => 'WhatsApp shipping', 'button_url' => '/contact'],
                ],
            ],
            [
                'title' => 'Warranty policy',
                'slug' => 'warranty',
                'blocks' => [
                    ['heading' => 'Tested. Graded. Covered.', 'body' => 'Refurbished drives include a limited warranty covering functional failure under normal use. Physical damage, firmware abuse, and data recovery attempts void cover.', 'type' => 'text'],
                    ['heading' => 'How to claim', 'body' => 'Open a support ticket from your customer account or WhatsApp the order number and fault description.', 'type' => 'faq'],
                ],
            ],
            [
                'title' => 'Terms and conditions',
                'slug' => 'terms',
                'blocks' => [
                    ['heading' => 'Trading terms', 'body' => 'Prices are in ZAR and exclude courier unless stated. Stock is reserved when payment is confirmed. Data recovery quotes are estimates until imaging is complete.', 'type' => 'text'],
                ],
            ],
        ];

        foreach ($pages as $def) {
            $page = Page::query()->updateOrCreate(
                ['slug' => $def['slug']],
                [
                    'title' => $def['title'],
                    'status' => 'published',
                    'meta_title' => $def['title'].' — EK Electronics',
                    'meta_description' => $def['title'].' information for EK Electronics customers.',
                    'show_in_menu' => false,
                ]
            );
            if ($page->blocks()->exists()) {
                continue;
            }
            $sort = 0;
            foreach ($def['blocks'] as $block) {
                PageBlock::query()->create([
                    'page_id' => $page->id,
                    'type' => $block['type'],
                    'heading' => $block['heading'] ?? null,
                    'body' => $block['body'] ?? null,
                    'button_label' => $block['button_label'] ?? null,
                    'button_url' => $block['button_url'] ?? null,
                    'sort_order' => $sort++,
                    'is_active' => true,
                ]);
            }
        }
    }

    private function seedUsersAndExpenses(): void
    {
        User::query()
            ->where('is_admin', true)
            ->where(function ($q) {
                $q->whereNull('role')->orWhere('role', 'customer');
            })
            ->update(['role' => 'admin', 'is_active' => true]);

        User::query()->updateOrCreate(
            ['email' => 'staff@ekelectronics.co.za'],
            [
                'name' => 'EK Staff',
                'password' => Hash::make('StaffEK2026!'),
                'role' => 'staff',
                'is_admin' => false,
                'is_active' => true,
                'phone' => '+27105002140',
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'customer@ekelectronics.co.za'],
            [
                'name' => 'Demo Customer',
                'password' => Hash::make('CustomerEK2026!'),
                'role' => 'customer',
                'is_admin' => false,
                'is_active' => true,
                'phone' => '+27820000000',
            ]
        );

        if (! Expense::query()->exists()) {
            Expense::query()->insert([
                [
                    'spent_on' => now()->subDays(12)->toDateString(),
                    'category' => 'rent',
                    'description' => 'Waterfall Business Park unit rent',
                    'amount' => 18500,
                    'vendor' => 'Lone Creek',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'spent_on' => now()->subDays(5)->toDateString(),
                    'category' => 'courier',
                    'description' => 'Courier top-up — nationwide dispatches',
                    'amount' => 2400,
                    'vendor' => 'The Courier Guy',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'spent_on' => now()->subDays(2)->toDateString(),
                    'category' => 'stock',
                    'description' => 'Enterprise HDD intake batch',
                    'amount' => 42000,
                    'vendor' => 'Wholesale intake',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }
}
