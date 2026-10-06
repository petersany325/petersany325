<?php

namespace App\Http\Controllers;

use App\Support\StaffSmsTemplates;
use Illuminate\Http\Request;

class StaffSmsTemplateController extends Controller
{
    public function edit()
    {
        return view('staff-sms.templates', [
            'employeeTemplate' => StaffSmsTemplates::employeeTemplate(),
            'internTemplate' => StaffSmsTemplates::internTemplate(),
            'employeeDefault' => StaffSmsTemplates::employeeDefault(),
            'internDefault' => StaffSmsTemplates::internDefault(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'employee_template' => ['required', 'string', 'max:1000'],
            'intern_template' => ['required', 'string', 'max:1000'],
        ], [
            'employee_template.required' => 'متن پیامک کارمند الزامی است.',
            'intern_template.required' => 'متن پیامک کارآموز الزامی است.',
        ]);

        StaffSmsTemplates::save($data['employee_template'], $data['intern_template']);

        return back()->with('success', 'متن پیامک‌های خوش‌آمدگویی ذخیره شد.');
    }
}
