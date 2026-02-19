<?php
namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ItemController extends Controller
{
    public function index() {
        return Inertia::render('items/index', [
            'items' => Item::all()
        ]);
    }

    public function store(Request $request) {
        $request->validate(['name' => 'required', 'type' => 'nullable', 'description' => 'nullable']);
        Item::create([
            'name' => $request->post('name'),
            'description' => $request->post('description'),
            'type' => $request->post('type') ?? 'equipment',
        ]);
        return redirect()->back();
    }

    public function update(Request $request, Item $item) {
        $item->update($request->all());
        return redirect()->back();
    }

    public function destroy(Item $item) {
        $item->delete();
        return redirect()->back();
    }
}
