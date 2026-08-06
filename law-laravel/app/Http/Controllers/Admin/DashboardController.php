<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Service;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'leadsCount' => Lead::query()->count(),
            'newLeads' => Lead::query()->where('status', 'new')->count(),
            'servicesCount' => Service::query()->count(),
            'latestLeads' => Lead::query()->latest()->limit(5)->get(),
        ]);
    }
}
