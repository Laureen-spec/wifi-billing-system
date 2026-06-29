<?php

namespace StudyRoomTechLab\RepairPos\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use StudyRoomTechLab\RepairPos\Models\RepairProduct;

class RepairProductController extends Controller
{
    public function index(Request $request)
    {
        $products = RepairProduct::query()
            ->when($request->filled('q'), fn ($query) => $query->where('name', 'like', '%' . $request->q . '%')->orWhere('sku', 'like', '%' . $request->q . '%'))
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->category))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('repair-pos::products.index', [
            'products' => $products,
            'categories' => RepairProduct::whereNotNull('category')->distinct()->pluck('category'),
            'filters' => $request->only(['q', 'category']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100', 'unique:repair_pos_products,sku'],
            'category' => ['nullable', 'string', 'max:100'],
            'brand' => ['nullable', 'string', 'max:100'],
            'supplier' => ['nullable', 'string', 'max:255'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'low_stock_alert' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['purchase_price'] = $data['purchase_price'] ?? 0;
        $data['stock'] = $data['stock'] ?? 0;
        $data['low_stock_alert'] = $data['low_stock_alert'] ?? 2;
        $data['status'] = 'active';

        RepairProduct::create($data);

        return back()->with('success', 'Product saved successfully.');
    }
}
