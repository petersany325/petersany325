<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Faq;
use App\Models\Lead;
use App\Models\Post;
use App\Models\Service;
use App\Models\Setting;
use App\Models\TeamMember;
use App\Models\Testimonial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
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

        $testimonials = Testimonial::query()
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

        return view('site.home', compact(
            'services', 'settings', 'members', 'faqs', 'testimonials', 'posts'
        ));
    }

    public function contact(Request $request): RedirectResponse
    {
        if (filled($request->input('website'))) {
            return redirect()->to(url('/').'#contact')->with('success', 'درخواست شما ثبت شد. به‌زودی با شما تماس می‌گیریم.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'phone' => ['required', 'string', 'min:8', 'max:30'],
            'topic' => ['required', 'string', 'max:120'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        Lead::query()->create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'topic' => $data['topic'],
            'message' => $data['message'] ?? null,
            'ip' => $request->ip(),
            'status' => 'new',
        ]);

        return redirect()->to(url('/').'#contact')->with('success', 'درخواست شما ثبت شد. به‌زودی با شما تماس می‌گیریم.');
    }

    public function appointment(Request $request): RedirectResponse
    {
        if (filled($request->input('website'))) {
            return redirect()->to(url('/').'#appointment')->with('success', 'نوبت شما ثبت شد.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'phone' => ['required', 'string', 'min:8', 'max:30'],
            'email' => ['nullable', 'email', 'max:160'],
            'topic' => ['nullable', 'string', 'max:120'],
            'preferred_date' => ['nullable', 'date'],
            'preferred_time' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        Appointment::query()->create($data + ['status' => 'pending']);

        return redirect()->to(url('/').'#appointment')->with('success', 'نوبت مشاوره ثبت شد. برای تأیید با شما تماس می‌گیریم.');
    }
}
