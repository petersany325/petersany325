<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Service;
use App\Models\Setting;
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

        return view('site.home', compact('services', 'settings'));
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
}
