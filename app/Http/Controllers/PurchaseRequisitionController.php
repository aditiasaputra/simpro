<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\PurchaseRequisition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PurchaseRequisitionController extends Controller
{
    public function index()
    {
        $mprs = PurchaseRequisition::latest()->paginate(10);
        return Inertia::render('PurchaseRequisition/Index', ['mprs' => $mprs]);
    }

    public function show(PurchaseRequisition $purchaseRequisition)
    {
        $purchaseRequisition->load('details.item');

        return Inertia::render('PurchaseRequisition/Show', [
            'mpr' => $purchaseRequisition
        ]);
    }

    public function create()
    {
        $items = Item::select('id', 'name', 'type')->get();

        $initialDetails = [];
        // Tangkap session dari halaman checkout keranjang
        $cartItemIds = session('checkout_cart_ids', []);

        if (!empty($cartItemIds)) {
            // Jika datang dari keranjang, isikan baris otomatis
            $cartItems = \App\Models\CartItem::with('item')->whereIn('id', $cartItemIds)->get();
            foreach ($cartItems as $cart) {
                $initialDetails[] = [
                    'item_id' => (string) $cart->item_id,
                    'item_name' => $cart->item->name,
                    'type' => $cart->item->type,
                    'quantity' => $cart->quantity,
                    'purpose' => '', // Kosong, agar diisi manual oleh user di form MPR
                    'description' => ''
                ];
            }
        } else {
            // Jika buka menu dari Sidebar biasa, beri 1 baris kosong
            $initialDetails[] = [
                'item_id' => '',
                'item_name' => '',
                'type' => '',
                'quantity' => 1,
                'purpose' => '',
                'description' => ''
            ];
        }

        return Inertia::render('PurchaseRequisition/Create', [
            'items' => $items,
            'initialDetails' => $initialDetails,
            'cartItemIds' => $cartItemIds
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'request_date' => 'required|date',
            'mpr_no' => 'required|string|unique:purchase_requisitions,mpr_no',
            'name' => 'required|string',
            'code' => 'required|string',
            'location' => 'required|string',

            'details' => 'required|array|min:1',
            'details.*.item_id' => 'required|exists:items,id',
            'details.*.quantity' => 'required|integer|min:1',
            'details.*.type' => 'required|in:equipment,consumable',
            'details.*.purpose' => 'nullable|string',
            'details.*.description' => 'nullable|string',

            // Validasi ID keranjang (opsional)
            'cart_item_ids' => 'nullable|array'
        ]);

        DB::transaction(function () use ($validated) {
            $pr = PurchaseRequisition::create([
                'request_date' => $validated['request_date'],
                'mpr_no' => $validated['mpr_no'],
                'name' => $validated['name'],
                'code' => $validated['code'],
                'location' => $validated['location'],
            ]);

            $pr->details()->createMany($validated['details']);

            // EKSEKUSI CHECKOUT: Jika membawa ID keranjang, bersihkan dari keranjang!
            if (!empty($validated['cart_item_ids'])) {
                \App\Models\CartItem::whereIn('id', $validated['cart_item_ids'])->delete();
            }
        });

        return redirect()->route('purchase-requisitions.index')
            ->with('success', 'Purchase Requisition berhasil dibuat!');
    }
}
