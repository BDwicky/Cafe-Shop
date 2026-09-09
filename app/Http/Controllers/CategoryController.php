<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:60']]);

        $slug = Str::slug($data['name']);
        $i = 2;
        while (Category::where('slug', $slug)->exists()) {
            $slug = Str::slug($data['name']) . '-' . $i++;
        }

        Category::create(['name' => $data['name'], 'slug' => $slug]);

        return back()->with('status', 'Kategori "' . $data['name'] . '" ditambahkan.');
    }

    public function destroy(Category $category)
    {
        abort_unless($category->menus()->count() === 0, 422, 'Kategori masih memiliki menu.');

        $category->delete();

        return back()->with('status', 'Kategori dihapus.');
    }
}
