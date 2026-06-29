<?php

namespace StudyRoomTechLab\RepairPos\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use StudyRoomTechLab\RepairPos\Models\RepairCustomer;
use StudyRoomTechLab\RepairPos\Models\RepairProduct;
use StudyRoomTechLab\RepairPos\Models\RepairSale;
use StudyRoomTechLab\RepairPos\Models\RepairSaleItem;

class RepairSaleController extends Controller
{
    public function index()
    {
        return view('repair-pos::sales.index', [
            'sales' => RepairSale::with(['customer', 'items'])->latest()->paginate(25),
            'customers' => RepairCustomer::orderBy('name')->get(),
            'products' => RepairProduct::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => ['nullable', 'integer', 'exists:repair_pos_customers,id'],
            'product_id' => ['nullable', 'integer', 'exists:repair_pos_products,id'],
            'item_name' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
        ]);

        return DB::transaction(function () use ($data) {
            $product = ! empty($data['product_id']) ? RepairProduct::find($data['product_id']) : null;
            $itemName = $data['item_name'] ?: $product?->name ?: 'Custom Item';
            $unitPrice = (float) ($data['unit_price'] ?? $product?->selling_price ?? 0);
            $quantity = (int) $data['quantity'];
            $subtotal = $unitPrice * $quantity;
            $discount = (float) ($data['discount'] ?? 0);
            $total = max(0, $subtotal - $discount);

            $sale = RepairSale::create([
                'sale_number' => 'SALE-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4)),
                'customer_id' => $data['customer_id'] ?? null,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => 0,
                'total' => $total,
                'amount_paid' => $total,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'payment_reference' => $data['payment_reference'] ?? null,
                'status' => 'paid',
                'sold_at' => now(),
                'created_by' => auth()->id(),
            ]);

            RepairSaleItem::create([
                'sale_id' => $sale->id,
                'product_id' => $product?->id,
                'item_name' => $itemName,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $subtotal,
            ]);

            if ($product) {
                $product->decrement('stock', min($quantity, (int) $product->stock));
            }

            return back()->with('success', 'Sale recorded successfully.');
        });
    }
}
