<?php

namespace Workdo\ProductService\Listeners;

use Workdo\ProductService\Models\WarehouseStock;
use Workdo\Retainer\Events\ConvertSalesRetainer;

class ConvertSalesRetainerListener
{
    public function handle(ConvertSalesRetainer $event)
    {
        $salesInvoice = $event->invoice;

        if ($salesInvoice->type === 'product') {
            foreach ($salesInvoice->items()->get() as $item) {
                $stock = WarehouseStock::where('warehouse_id', $salesInvoice->warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->first();
                if ($stock) {
                    $stock->decrement('quantity', $item->quantity);
                }
            }
        }
    }
}
