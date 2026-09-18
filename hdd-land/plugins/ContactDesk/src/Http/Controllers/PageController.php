<?php

namespace Plugins\ContactDesk\src\Http\Controllers;

use Illuminate\Routing\Controller;
use Plugins\ContactDesk\src\Support\ContactCopy;

class PageController extends Controller
{
    public function show()
    {
        return view('contact-desk::page', ['copy' => ContactCopy::get()]);
    }
}
