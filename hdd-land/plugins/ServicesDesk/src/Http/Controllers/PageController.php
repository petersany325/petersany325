<?php

namespace Plugins\ServicesDesk\src\Http\Controllers;

use Illuminate\Routing\Controller;
use Plugins\ServicesDesk\src\Support\ServicesCopy;

class PageController extends Controller
{
    public function show()
    {
        $copy = ServicesCopy::get();

        return view('services-desk::page', [
            'copy' => $copy,
            'cards' => ServicesCopy::cards($copy),
            'tools' => ServicesCopy::tools($copy),
        ]);
    }
}
