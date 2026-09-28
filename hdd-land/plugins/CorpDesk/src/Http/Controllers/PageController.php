<?php

namespace Plugins\CorpDesk\src\Http\Controllers;

use App\Http\Controllers\Controller;
use Plugins\CorpDesk\src\Support\CorpCopy;

class PageController extends Controller
{
    public function enterprise()
    {
        return $this->show('enterprise');
    }

    public function cctv()
    {
        return $this->show('cctv');
    }

    protected function show(string $page)
    {
        try {
            \Plugins\CorpDesk\Plugin::registerViews();
        } catch (\Throwable) {
        }
        $copy = CorpCopy::get($page);

        return view('corp-desk::page', [
            'page' => $page,
            'copy' => $copy,
            'features' => CorpCopy::lines((string) ($copy['features'] ?? '')),
            'steps' => CorpCopy::lines((string) ($copy['steps'] ?? '')),
            'stats' => CorpCopy::lines((string) ($copy['stats'] ?? '')),
            'cases' => CorpCopy::lines((string) ($copy['cases'] ?? '')),
            'faq' => CorpCopy::lines((string) ($copy['faq'] ?? '')),
            'brands' => CorpCopy::list((string) ($copy['brands'] ?? '')),
            'intro' => CorpCopy::list((string) ($copy['intro'] ?? '')),
        ]);
    }
}
