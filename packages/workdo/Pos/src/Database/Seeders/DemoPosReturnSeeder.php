<?php

namespace Workdo\Pos\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\Pos\Models\Pos;
use Workdo\Pos\Models\PosReturn;
use Workdo\Pos\Models\PosReturnItem;
use Workdo\Pos\Models\PosReturnItemTax;
use Workdo\ProductService\Models\ProductServiceTax;
use Carbon\Carbon;

class DemoPosReturnSeeder extends Seeder
{
    public function run($userId): void
    {
        if (!empty($userId)) {
            // Get completed POS sales that are eligible for returns (at least 7 days old)
            $eligiblePosSales = Pos::with(['items.product', 'customer', 'warehouse'])
                ->where('created_by', $userId)
                ->where('status', 'completed')
                ->whereDate('created_at', '<=', Carbon::now()->subDays(7))
                ->whereHas('items')
                ->limit(15) // Create returns for 15 sales
                ->get();

            if ($eligiblePosSales->isEmpty()) {
                return;
            }

            // Return scenarios with realistic business cases
            $returnScenarios = [
                [
                    'reason' => 'Product received damaged during delivery',
                    'status' => 'draft',
                    'return_percentage' => 100, // Full return
                    'days_after_sale' => rand(1, 3),
                    'item_reason' => 'Damaged packaging and product'
                ],
                [
                    'reason' => 'Manufacturing defect found after opening',
                    'status' => 'draft',
                    'return_percentage' => 100,
                    'days_after_sale' => rand(2, 5),
                    'item_reason' => 'Product not working properly'
                ],
                [
                    'reason' => 'Item broken during shipping',
                    'status' => 'draft',
                    'return_percentage' => 100,
                    'days_after_sale' => rand(1, 2),
                    'item_reason' => 'Physical damage to product'
                ],
                
                // Wrong Item/Size (25%)
                [
                    'reason' => 'Wrong size delivered',
                    'status' => 'draft',
                    'return_percentage' => 50, // Partial return
                    'days_after_sale' => rand(1, 4),
                    'item_reason' => 'Size does not fit'
                ],
                [
                    'reason' => 'Received wrong color/variant',
                    'status' => 'draft',
                    'return_percentage' => 100,
                    'days_after_sale' => rand(1, 3),
                    'item_reason' => 'Wrong product variant'
                ],
                [
                    'reason' => 'Item does not match description',
                    'status' => 'draft',
                    'return_percentage' => 100,
                    'days_after_sale' => rand(2, 5),
                    'item_reason' => 'Product specifications mismatch'
                ],
                
                // Customer Changed Mind (20%)
                [
                    'reason' => 'Customer changed mind',
                    'status' => 'draft',
                    'return_percentage' => 100,
                    'days_after_sale' => rand(3, 7),
                    'item_reason' => 'No longer needed'
                ],
                [
                    'reason' => 'Found better alternative',
                    'status' => 'draft',
                    'return_percentage' => 50,
                    'days_after_sale' => rand(2, 6),
                    'item_reason' => 'Customer preference changed'
                ],
                [
                    'reason' => 'Ordered by mistake',
                    'status' => 'draft',
                    'return_percentage' => 100,
                    'days_after_sale' => rand(1, 2),
                    'item_reason' => 'Duplicate order'
                ],
                
                // Quality Issues (10%)
                [
                    'reason' => 'Poor quality material',
                    'status' => 'draft',
                    'return_percentage' => 100,
                    'days_after_sale' => rand(3, 7),
                    'item_reason' => 'Quality below expectations'
                ],
                [
                    'reason' => 'Product expired/near expiry',
                    'status' => 'draft',
                    'return_percentage' => 100,
                    'days_after_sale' => rand(1, 2),
                    'item_reason' => 'Expiry date issue'
                ],
                
                // Other Reasons (5%)
                [
                    'reason' => 'Incomplete product/Missing parts',
                    'status' => 'draft',
                    'return_percentage' => 100,
                    'days_after_sale' => rand(1, 3),
                    'item_reason' => 'Missing accessories'
                ],
                [
                    'reason' => 'Better price available elsewhere',
                    'status' => 'draft',
                    'return_percentage' => 100,
                    'days_after_sale' => rand(4, 7),
                    'item_reason' => 'Price difference'
                ],
                [
                    'reason' => 'Delivery delayed too much',
                    'status' => 'draft',
                    'return_percentage' => 50,
                    'days_after_sale' => rand(5, 10),
                    'item_reason' => 'Late delivery'
                ],
                [
                    'reason' => 'Product not as expected',
                    'status' => 'draft',
                    'return_percentage' => 100,
                    'days_after_sale' => rand(2, 5),
                    'item_reason' => 'Expectations not met'
                ],
            ];

            foreach ($eligiblePosSales as $index => $posSale) {
                if ($index >= count($returnScenarios)) {
                    break;
                }

                $scenario = $returnScenarios[$index];
                
                // Calculate return date based on scenario
                $returnDate = Carbon::parse($posSale->created_at)
                    ->addDays($scenario['days_after_sale']);
                
                // Skip if return date is in future
                if ($returnDate->isFuture()) {
                    continue;
                }

                // Calculate totals for return
                $subtotal = 0;
                $taxAmount = 0;
                $discountAmount = 0;
                
                // Select items to return based on return percentage
                $itemsToReturn = [];
                $totalItems = $posSale->items->count();
                $returnItemCount = max(1, ceil($totalItems * ($scenario['return_percentage'] / 100)));
                
                $selectedItems = $posSale->items->random(min($returnItemCount, $totalItems));
                
                foreach ($selectedItems as $originalItem) {
                    // Calculate return quantity (50-100% of original quantity)
                    $returnPercentage = rand(50, 100);
                    $returnQuantity = max(1, ceil($originalItem->quantity * ($returnPercentage / 100)));
                    
                    // Calculate amounts following controller logic
                    $lineTotal = $returnQuantity * $originalItem->price;
                    $itemDiscountAmount = 0;
                    $afterDiscount = $lineTotal - $itemDiscountAmount;
                    
                    // Calculate tax proportionally
                    $itemTaxAmount = 0;
                    if ($originalItem->subtotal > 0) {
                        $itemTaxAmount = $afterDiscount * ($originalItem->tax_amount / $originalItem->subtotal);
                    }
                    
                    $itemTotal = $afterDiscount + $itemTaxAmount;
                    
                    $subtotal += $lineTotal;
                    $taxAmount += $itemTaxAmount;
                    $discountAmount += $itemDiscountAmount;
                    
                    $itemsToReturn[] = [
                        'original_item' => $originalItem,
                        'return_quantity' => $returnQuantity,
                        'unit_price' => $originalItem->price,
                        'discount_amount' => $itemDiscountAmount,
                        'tax_amount' => $itemTaxAmount,
                        'total_amount' => $itemTotal,
                    ];
                }
                
                $totalAmount = $subtotal + $taxAmount - $discountAmount;
                
                // Create POS Return following exact controller structure
                $posReturn = PosReturn::create([
                    'return_date' => $returnDate->toDateString(),
                    'customer_id' => $posSale->customer_id,
                    'warehouse_id' => $posSale->warehouse_id,
                    'original_pos_id' => $posSale->id,
                    'reason' => $scenario['reason'],
                    'notes' => $this->generateNotes($scenario['status']),
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'discount_amount' => $discountAmount,
                    'total_amount' => $totalAmount,
                    'status' => $scenario['status'],
                    'creator_id' => $userId,
                    'created_by' => $userId,
                    'created_at' => $returnDate,
                    'updated_at' => $returnDate,
                ]);
                
                // Create return items following exact controller structure
                foreach ($itemsToReturn as $itemData) {
                    $originalItem = $itemData['original_item'];
                    
                    $returnItem = PosReturnItem::create([
                        'return_id' => $posReturn->id,
                        'product_id' => $originalItem->product_id,
                        'original_pos_item_id' => $originalItem->id,
                        'original_quantity' => $originalItem->quantity,
                        'return_quantity' => $itemData['return_quantity'],
                        'unit_price' => $itemData['unit_price'],
                        'discount_percentage' => 0,
                        'discount_amount' => $itemData['discount_amount'],
                        'tax_amount' => $itemData['tax_amount'],
                        'total_amount' => $itemData['total_amount'],
                        'reason' => $scenario['item_reason'],
                        'created_at' => $returnDate,
                        'updated_at' => $returnDate,
                    ]);
                    
                    // Store individual taxes from original POS item following controller logic
                    if ($originalItem->tax_ids && is_array($originalItem->tax_ids) && !empty($originalItem->tax_ids)) {
                        $taxes = ProductServiceTax::whereIn('id', $originalItem->tax_ids)
                            ->where('created_by', $userId)
                            ->get();
                        
                        foreach ($taxes as $tax) {
                            PosReturnItemTax::create([
                                'item_id' => $returnItem->id,
                                'tax_name' => $tax->tax_name,
                                'tax_rate' => $tax->rate,
                                'created_at' => $returnDate,
                                'updated_at' => $returnDate,
                            ]);
                        }
                    }
                }
                

            }
        }
    }
    
    /**
     * Generate realistic notes based on return status
     */
    private function generateNotes(string $status): ?string
    {
        $notes = [
            'draft' => [
                'Customer requested return, pending approval',
                'Return initiated by customer, awaiting verification',
                'Return request submitted, under review',
            ],
            'approved' => [
                'Return approved by manager, awaiting product receipt',
                'Approved for return, customer notified',
                'Return authorized, processing refund after product inspection',
            ],
            'completed' => [
                'Return completed successfully, refund processed',
                'Product received and inspected, refund issued',
                'Return finalized, amount credited to customer account',
            ],
            'cancelled' => [
                'Return cancelled as per customer request',
                'Return request withdrawn by customer',
                'Cancelled - customer decided to keep the product',
            ],
        ];
        
        if (isset($notes[$status])) {
            return $notes[$status][array_rand($notes[$status])];
        }
        
        return null;
    }
}
