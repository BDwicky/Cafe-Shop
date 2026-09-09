<?php

namespace App\Http\Controllers;

use App\Models\Category;

class PublicMenuController extends Controller
{
    public function index()
    {
        $categories = Category::with(['menus' => fn ($q) => $q->available()->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get()
            ->filter(fn ($c) => $c->menus->isNotEmpty());

        return view('menu', compact('categories'));
    }
}
