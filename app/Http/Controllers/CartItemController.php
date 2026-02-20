<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CartItemController extends Controller
{
    public function index()
    {
        // Ambil data keranjang milik user yang sedang login, beserta detail itemnya
        $cartItems = CartItem::with('item')
            ->where('user_id',Auth::id())
            ->latest()
            ->get();

        return Inertia::render('Cart', [
            'cartItems' => $cartItems
        ]);
    }

    // Menambahkan item ke keranjang
    public function store(Request $request)
    {
        $request->validate([
            'item_id' => 'required|exists:items,id',
        ]);

        // Gunakan firstOrNew untuk mencegah data ganda di keranjang
        $cartItem = CartItem::firstOrNew([
            'user_id' => Auth::id(),
            'item_id' => $request->item_id,
        ]);

        // Jika data baru, otomatis quantity-nya 0 (sebelum ditambah bawahnya),
        // jika sudah ada, pakai quantity lama
        $cartItem->quantity = ($cartItem->quantity ?? 0) + $request->input('quantity', 1);
        $cartItem->save();

        return redirect()->back()->with('success', 'Item ditambahkan ke keranjang');
    }

    // Mengubah jumlah (quantity) item di keranjang
    public function update(Request $request, CartItem $cartItem)
    {
        // Pastikan hanya pemilik keranjang yang bisa mengubah
        if ($cartItem->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate(['quantity' => 'required|integer|min:1']);

        $cartItem->update(['quantity' => $request->quantity]);

        return redirect()->back();
    }

    // Menghapus item dari keranjang
    public function destroy(CartItem $cartItem)
    {
        if ($cartItem->user_id === Auth::id()) {
            $cartItem->delete();
        }

        return redirect()->back();
    }
}
