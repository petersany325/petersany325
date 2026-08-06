<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Page;
use App\Models\Post;
use App\Models\TeamMember;
use Illuminate\View\View;

class SiteController extends Controller
{
    public function blog(): View
    {
        $posts = Post::query()->published()->latest('published_at')->latest('id')->paginate(9);

        return view('site.blog-index', compact('posts'));
    }

    public function blogShow(Post $post): View
    {
        abort_unless($post->is_published, 404);

        return view('site.blog-show', compact('post'));
    }

    public function team(): View
    {
        $members = TeamMember::query()->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();

        return view('site.team', compact('members'));
    }

    public function faq(): View
    {
        $faqs = Faq::query()->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();

        return view('site.faq', compact('faqs'));
    }

    public function page(Page $page): View
    {
        abort_unless($page->is_published, 404);

        return view('site.page', compact('page'));
    }
}
