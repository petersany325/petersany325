<?php

namespace Plugins\ContactDesk\src\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Plugins\ContactDesk\src\Support\ContactCopy;

class ContactController extends Controller
{
    public function edit()
    {
        return view('contact-desk::admin', ['copy' => ContactCopy::get()]);
    }

    public function save(Request $request)
    {
        ContactCopy::save($request->all());

        return redirect()->to(url('/admin/contact-page'))->with('success', 'صفحه تماس ذخیره شد.');
    }
}
