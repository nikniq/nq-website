<?php

namespace App\Http\Controllers;

use App\Support\News;
use Illuminate\View\View;

class NewsController extends Controller
{
    public function index(): View
    {
        return view('news.index', ['articles' => News::all()]);
    }

    public function show(string $slug): View
    {
        $article = News::find($slug);
        abort_unless($article, 404);

        return view('news.show', [
            'article' => $article,
            'more' => News::all()->where('slug', '!=', $slug)->take(2),
        ]);
    }
}
