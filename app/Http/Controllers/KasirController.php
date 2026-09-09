<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class KasirController extends Controller
{
    public function index(Request $request)
    {
        $menus = \App\Models\Menu::with('category')->orderBy('sort_order')->get();

        return view('kasir.terminal', compact('menus'));
    }
}
