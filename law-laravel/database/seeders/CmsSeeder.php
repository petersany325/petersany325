<?php

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\Page;
use App\Models\Post;
use App\Models\Service;
use App\Models\Setting;
use App\Models\TeamMember;
use App\Models\Testimonial;
use Illuminate\Database\Seeder;

class CmsSeeder extends Seeder
{
    public function run(): void
    {
        Setting::many([
            'site_name' => Setting::get('site_name') ?: 'مؤسسه حقوقی آریان',
            'site_tagline' => Setting::get('site_tagline') ?: 'وکالت · مشاوره · دفاع',
            'phone' => Setting::get('phone') ?: '۰۲۱−۸۸۷۷۶۶۵۵',
            'address' => Setting::get('address') ?: 'تهران، خیابان ولیعصر، برج حقوقی آریان',
            'hours' => Setting::get('hours') ?: 'شنبه تا چهارشنبه · ۹ تا ۱۸',
            'about_title' => Setting::get('about_title') ?: 'دفتری که پرونده را تا نتیجه همراهی می‌کند',
            'about_text' => Setting::get('about_text') ?: 'آریان ترکیبی از تجربه وکالت و مشاوره شفاف است. از همان جلسه اول، مسیر پرونده، ریسک‌ها و گزینه‌های واقعی را با شما مرور می‌کنیم.',
            'hero_lead' => Setting::get('hero_lead') ?: 'همراهی دقیق و حرفه‌ای در پرونده‌های حقوقی، کیفری و تجاری — با زبانی ساده و دفاعی قوی.',
        ]);

        if (Service::query()->count() === 0) {
            foreach ([
                ['sort_order' => 1, 'title' => 'حقوق خانواده', 'description' => 'طلاق، حضانت، مهریه، نفقه و توافقات خانوادگی با رویکرد حمایتی و واقع‌بینانه.'],
                ['sort_order' => 2, 'title' => 'کیفری و دفاع', 'description' => 'دفاع تخصصی در مراحل تحقیقات، دادگاه و تجدیدنظر با تمرکز بر حقوق متهم.'],
                ['sort_order' => 3, 'title' => 'قرارداد و تجارت', 'description' => 'تنظیم، بررسی و حل اختلاف قراردادهای تجاری، شرکتی و ملکی.'],
                ['sort_order' => 4, 'title' => 'ملک و ثبت', 'description' => 'دعاوی ملکی، خلع ید، الزام به تنظیم سند و پیگیری امور ثبتی.'],
                ['sort_order' => 5, 'title' => 'کار و بیمه', 'description' => 'اختلافات کارگر و کارفرما، مطالبات بیمه و شکایت در مراجع حل اختلاف.'],
                ['sort_order' => 6, 'title' => 'داوری و میانجیگری', 'description' => 'حل اختلاف خارج از دادگاه با تمرکز بر سرعت و کاهش هزینه.'],
            ] as $row) {
                Service::query()->create($row + ['is_active' => true]);
            }
        }

        if (TeamMember::query()->count() === 0) {
            foreach ([
                ['name' => 'دکتر نازنین راد', 'role' => 'وکیل پایه یک · حقوق خانواده', 'bio' => 'بیش از ۱۲ سال تجربه در پرونده‌های خانواده و داوری.'],
                ['name' => 'محمد امین کیانی', 'role' => 'وکیل پایه یک · کیفری', 'bio' => 'تخصص در دفاع کیفری، تجدیدنظر و پرونده‌های پیچیده.'],
                ['name' => 'سارا بهشتی', 'role' => 'مشاور قراردادهای تجاری', 'bio' => 'تنظیم و بررسی قراردادهای شرکتی و سرمایه‌گذاری.'],
            ] as $i => $row) {
                TeamMember::query()->create($row + ['sort_order' => $i + 1, 'is_active' => true]);
            }
        }

        if (Faq::query()->count() === 0) {
            foreach ([
                ['question' => 'هزینه مشاوره چطور محاسبه می‌شود؟', 'answer' => 'جلسه اول ارزیابی معمولاً کوتاه و شفاف است؛ پس از بررسی موضوع، برآورد هزینه اعلام می‌شود.', 'category' => 'عمومی'],
                ['question' => 'آیا امکان پیگیری آنلاین پرونده وجود دارد؟', 'answer' => 'بله، وضعیت پرونده به‌صورت منظم به شما گزارش می‌شود.', 'category' => 'عمومی'],
                ['question' => 'برای تنظیم قرارداد چه مدارکی لازم است؟', 'answer' => 'شناسه طرفین، موضوع معامله، شرایط مالی و هر سند مرتبط با تعهدات.', 'category' => 'قرارداد'],
                ['question' => 'چقدر طول می‌کشد تا پرونده به نتیجه برسد؟', 'answer' => 'بسته به نوع دعوا و مرجع رسیدگی متفاوت است؛ در جلسه اول بازه زمانی واقع‌بینانه اعلام می‌شود.', 'category' => 'عمومی'],
            ] as $i => $row) {
                Faq::query()->create($row + ['sort_order' => $i + 1, 'is_active' => true]);
            }
        }

        if (Testimonial::query()->count() === 0) {
            foreach ([
                ['client_name' => 'رضا م.', 'client_role' => 'موکل پرونده ملکی', 'content' => 'مسیر پرونده شفاف توضیح داده شد و تا صدور رأی همراهی کامل داشتیم.', 'rating' => 5],
                ['client_name' => 'مینا ک.', 'client_role' => 'موکل حقوق خانواده', 'content' => 'برخورد حرفه‌ای و واقع‌بینانه؛ بدون وعده غیرواقعی.', 'rating' => 5],
                ['client_name' => 'شرکت آفاق', 'client_role' => 'قرارداد تجاری', 'content' => 'قراردادها دقیق و قابل اتکا تنظیم شد.', 'rating' => 5],
            ] as $i => $row) {
                Testimonial::query()->create($row + ['sort_order' => $i + 1, 'is_active' => true]);
            }
        }

        if (Post::query()->count() === 0) {
            foreach ([
                ['title' => 'چطور قبل از امضای قرارداد ریسک را کم کنیم؟', 'excerpt' => 'چک‌لیست کوتاه برای بررسی تعهدات، ضمانت‌ها و خروج از قرارداد.', 'body' => "قبل از امضا، موضوع معامله، مبلغ، زمان‌بندی و ضمانت اجرا را شفاف کنید.\n\nهمچنین بند حل اختلاف و شرایط فسخ را جدی بگیرید."],
                ['title' => 'مراحل دفاع کیفری از نگاه وکیل', 'excerpt' => 'از تحقیقات تا دادگاه و تجدیدنظر؛ چه نکاتی اهمیت دارد.', 'body' => "دفاع مؤثر با جمع‌آوری مدارک و حضور به‌موقع در مراحل تحقیقات آغاز می‌شود.\n\nاستراتژی دفاع باید با واقعیت پرونده هماهنگ باشد."],
                ['title' => 'حضانت فرزند؛ پرسش‌های پرتکرار', 'excerpt' => 'پاسخ به سوالات رایج درباره حضانت و ملاقات.', 'body' => "مصلحت کودک معیار اصلی تصمیم‌گیری است.\n\nتوافق والدین در بسیاری موارد مسیر را کوتاه‌تر می‌کند."],
            ] as $i => $row) {
                Post::query()->create($row + [
                    'slug' => 'post-'.($i + 1),
                    'is_published' => true,
                    'published_at' => now()->subDays(3 - $i),
                ]);
            }
        }

        if (Page::query()->count() === 0) {
            Page::query()->create([
                'title' => 'حریم خصوصی',
                'slug' => 'privacy',
                'body' => 'اطلاعات تماس و پرونده‌های شما محرمانه نگهداری می‌شود و فقط برای ارائه خدمات حقوقی استفاده خواهد شد.',
                'is_published' => true,
                'sort_order' => 1,
            ]);
            Page::query()->create([
                'title' => 'شرایط استفاده',
                'slug' => 'terms',
                'body' => 'محتوای سایت جنبه اطلاع‌رسانی دارد و جایگزین مشاوره اختصاصی نیست.',
                'is_published' => true,
                'sort_order' => 2,
            ]);
        }
    }
}
