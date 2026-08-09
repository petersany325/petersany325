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
        $services = Setting::applyLimit(
            Service::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id'),
            'home_services_limit',
            0
        )->get();

        $members = Setting::applyLimit(
            TeamMember::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id'),
            'home_team_limit',
            0
        )->get();

        $faqs = Setting::applyLimit(
            Faq::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id'),
            'home_faq_limit',
            0
        )->get();

        $posts = Setting::applyLimit(
            Post::query()
                ->published()
                ->latest('published_at')
                ->latest('id'),
            'home_posts_limit',
            0
        )->get();

        $testimonials = Setting::applyLimit(
            Testimonial::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id'),
            'home_testimonials_limit',
            0
        )->get();

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
