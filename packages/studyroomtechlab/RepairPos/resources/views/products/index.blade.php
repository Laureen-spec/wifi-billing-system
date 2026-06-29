@extends('layouts.app')
@section('title', 'Repair POS Inventory')
@section('page_heading', 'Repair POS Inventory')
@section('content')
@include('repair-pos::partials-style')
<div class="rp-page">
@if(session('success'))<div class="rp-alert">{{ session('success') }}</div>@endif
<div class="rp-hero"><div><h1>Inventory</h1><p>Manage spares, accessories, products, prices and stock.</p></div><div class="rp-actions"><a class="rp-btn secondary" href="{{ route('repair-pos.dashboard') }}">Dashboard</a><a class="rp-btn" href="{{ route('repair-pos.sales.index') }}">New Sale</a></div></div>
<div class="rp-card"><form class="rp-form" method="POST" action="{{ route('repair-pos.products.store') }}">@csrf<input name="name" placeholder="Product/spare name" required><input name="sku" placeholder="SKU"><input name="category" placeholder="Category"><input name="brand" placeholder="Brand"><input name="supplier" placeholder="Supplier"><input type="number" step="0.01" name="purchase_price" placeholder="Purchase price"><input type="number" step="0.01" name="selling_price" placeholder="Selling price" required><input type="number" name="stock" placeholder="Stock"><input type="number" name="low_stock_alert" placeholder="Low stock alert" value="2"><textarea name="notes" placeholder="Notes"></textarea><button class="rp-btn" type="submit">Add Product</button></form>
<table class="rp-table"><thead><tr><th>Product</th><th>Category</th><th>Supplier</th><th>Purchase</th><th>Selling</th><th>Stock</th></tr></thead><tbody>@forelse($products as $product)<tr><td><strong>{{ $product->name }}</strong><br><span class="rp-muted">{{ $product->sku ?? '-' }}</span></td><td>{{ $product->category ?? '-' }}<br><span class="rp-muted">{{ $product->brand }}</span></td><td>{{ $product->supplier ?? '-' }}</td><td>KES {{ number_format($product->purchase_price, 2) }}</td><td>KES {{ number_format($product->selling_price, 2) }}</td><td><span class="rp-badge {{ $product->stock <= $product->low_stock_alert ? 'cancelled' : 'active' }}">{{ $product->stock }}</span></td></tr>@empty<tr><td colspan="6" class="rp-empty">No products yet.</td></tr>@endforelse</tbody></table>@if(method_exists($products,'links'))<div class="rp-pagination">{{ $products->links() }}</div>@endif</div>
</div>
@endsection
