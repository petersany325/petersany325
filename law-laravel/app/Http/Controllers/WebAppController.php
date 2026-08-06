<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Post;
use App\Models\Service;
use App\Models\Setting;
use App\Models\TeamMember;
use App\Models\Testimonial;
use Illuminate\View\View;

class WebAppController extends Controller
{
    public function index(): View
    {
        $services = Service::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $members = TeamMember::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(6)
            ->get();

        $faqs = Faq::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(6)
            ->get();

        $posts = Post::query()
            ->published()
            ->latest('published_at')
            ->latest('id')
            ->limit(3)
            ->get();

        $testimonials = Testimonial::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(4)
            ->get();

        $settings = [
            'site_name' => Setting::get('site_name', 'مؤسسه حقوقی آریان'),
            'site_tagline' => Setting::get('site_tagline', 'وکالت · مشاوره · دفاع'),
            'phone' => Setting::get('phone', ''),
            'address' => Setting::get('address', ''),
            'hours' => Setting::get('hours', ''),
            'about_title' => Setting::get('about_title', ''),
            'about_text' => Setting::get('about_text', ''),
            'hero_lead' => Setting::get('hero_lead', ''),
        ];

        return view('app.index', compact(
            'services', 'settings', 'members', 'faqs', 'testimonials', 'posts'
        ));
    }
}
