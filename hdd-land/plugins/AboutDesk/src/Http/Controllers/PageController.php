<?php

namespace Plugins\AboutDesk\src\Http\Controllers;

use Illuminate\Routing\Controller;
use Plugins\AboutDesk\src\Support\AboutCopy;

class PageController extends Controller
{
    public function show()
    {
        return view('about-desk::page', [
            'copy' => AboutCopy::get(),
            'tools' => AboutCopy::tools(),
            'paragraphs' => AboutCopy::paragraphs(),
        ]);
    }
}
