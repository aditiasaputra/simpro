<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $query = Item::query();

        // Logika Pencarian (Search)
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        }

        // Infinite Scroll membutuhkan Pagination (misal 10 data per load)
        $items = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('items/index', [
            'items' => $items,
            'filters' => $request->only(['search']) // Kirim balik keyword pencarian
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:equipment,consumable',
            'description' => 'nullable|string'
        ]);

        Item::create($request->all());
        return redirect()->back();
    }

    public function update(Request $request, Item $item)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:equipment,consumable',
            'description' => 'nullable|string'
        ]);

        $item->update($request->all());
        return redirect()->back();
    }

    public function destroy(Item $item)
    {
        $item->delete();
        return redirect()->back();
    }
}
