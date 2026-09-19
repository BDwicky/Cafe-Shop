<?php

namespace App\Http\Controllers;

use App\Models\Promo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PromoController extends Controller
{
    /**
     * Tampilkan halaman kelola kupon & diskon kasir.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status', 'all');

        $query = Promo::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $promos = $query->orderByDesc('is_active')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total' => Promo::count(),
            'active' => Promo::where('is_active', true)->count(),
            'total_used' => Promo::sum('used_count'),
        ];

        return view('kasir.promos.index', compact('promos', 'stats', 'search', 'status'));
    }

    /**
     * Simpan kode kupon diskon baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:promos,code'],
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:percentage,fixed'],
            'discount_value' => [
                'required',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->input('type') === 'percentage' && $value > 100) {
                        $fail('Nilai diskon persentase tidak boleh melebihi 100%.');
                    }
                },
            ],
            'max_discount' => ['nullable', 'integer', 'min:0'],
            'min_order' => ['nullable', 'integer', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['is_active'] = true;
        $validated['used_count'] = 0;
        $validated['max_discount'] = ! empty($validated['max_discount']) ? (int) $validated['max_discount'] : null;
        $validated['min_order'] = ! empty($validated['min_order']) ? (int) $validated['min_order'] : 0;
        $validated['usage_limit'] = ! empty($validated['usage_limit']) ? (int) $validated['usage_limit'] : null;

        $promo = Promo::create($validated);

        return redirect()->route('kasir.promos.index')
            ->with('success', "Kupon diskon \"{$promo->code}\" berhasil dibuat dan aktif.");
    }

    /**
     * Toggle status aktif kupon diskon.
     */
    public function toggle(Request $request, Promo $promo): JsonResponse|RedirectResponse
    {
        $promo->update(['is_active' => ! $promo->is_active]);

        $statusText = $promo->is_active ? 'Diaktifkan' : 'Dinonaktifkan';

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'is_active' => (bool) $promo->is_active,
                'message' => "Kupon {$promo->code} berhasil {$statusText}.",
            ]);
        }

        return back()->with('success', "Kupon {$promo->code} berhasil {$statusText}.");
    }

    /**
     * Hapus kupon diskon.
     */
    public function destroy(Promo $promo): RedirectResponse
    {
        $code = $promo->code;
        $promo->delete();

        return redirect()->route('kasir.promos.index')
            ->with('success', "Kupon \"{$code}\" berhasil dihapus.");
    }
}
