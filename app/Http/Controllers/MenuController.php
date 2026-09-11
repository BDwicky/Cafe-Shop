<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MenuController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $categoryId = $request->query('category_id');
        $status = $request->query('status', 'all');

        $query = Menu::with('category');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($categoryId && $categoryId !== 'all') {
            $query->where('category_id', $categoryId);
        }

        if ($status === 'available') {
            $query->where('is_available', true);
        } elseif ($status === 'sold_out') {
            $query->where('is_available', false);
        }

        $menus = $query->orderBy('sort_order')->orderBy('id')->paginate(50)->withQueryString();
        $categories = Category::withCount('menus')->orderBy('sort_order')->orderBy('name')->get();

        // Metrik statistik Menu
        $stats = [
            'total' => Menu::count(),
            'available' => Menu::where('is_available', true)->count(),
            'sold_out' => Menu::where('is_available', false)->count(),
            'categories_count' => $categories->count(),
        ];

        return view('kasir.menu.index', compact('menus', 'categories', 'stats', 'search', 'categoryId', 'status'));
    }

    public function create()
    {
        $menu = new Menu;
        $categories = Category::orderBy('name')->get();

        return view('kasir.menu.form', ['menu' => $menu, 'categories' => $categories]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('menus', 'public');
        }

        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['is_available'] = $request->boolean('is_available');

        Menu::create($data);

        return redirect('/kasir/menu')->with('status', 'Menu "'.$data['name'].'" ditambahkan.');
    }

    public function edit(Menu $menu)
    {
        $categories = Category::orderBy('name')->get();

        return view('kasir.menu.form', ['menu' => $menu, 'categories' => $categories]);
    }

    public function update(Request $request, Menu $menu)
    {
        $data = $this->validated($request);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('menus', 'public');
        }

        if ($data['name'] !== $menu->name) {
            $data['slug'] = $this->uniqueSlug($data['name'], $menu->id);
        }

        $data['is_available'] = $request->boolean('is_available');

        $menu->update($data);

        return redirect('/kasir/menu')->with('status', 'Menu "'.$menu->name.'" diperbarui.');
    }

    public function toggle(Request $request, Menu $menu)
    {
        $menu->update(['is_available' => ! $menu->is_available]);

        $statusText = $menu->is_available ? 'Tersedia' : 'Habis (Stok Kosong)';

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'is_available' => (bool) $menu->is_available,
                'status_text' => $statusText,
                'message' => 'Menu "'.$menu->name.'" sekarang '.$statusText.'.',
            ]);
        }

        return redirect()->route('kasir.menu.index')
            ->with('status', 'Menu "'.$menu->name.'" sekarang '.$statusText.'.');
    }

    public function destroy(Menu $menu)
    {
        $menu->delete();

        return redirect('/kasir/menu')->with('status', 'Menu dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'integer', 'min:0', 'max:100000000'],
            'description' => ['nullable', 'string', 'max:500'],
            'image' => ['nullable', 'image', 'max:2048'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 2;

        while (Menu::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
