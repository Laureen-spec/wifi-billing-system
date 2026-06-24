<?php

namespace Workdo\Pos\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\Pos\Models\PosDiscount;
use Workdo\ProductService\Models\ProductServiceItem;
use Workdo\ProductService\Models\ProductServiceCategory;

class DemoPosDiscountSeeder extends Seeder
{
    public function run($userId): void
    {
        if (PosDiscount::where('created_by', $userId)->exists()) {
            return;
        }

        $products = ProductServiceItem::where('created_by', $userId)->limit(5)->get();
        $categories = ProductServiceCategory::where('created_by', $userId)->limit(4)->get();

        if ($categories->isEmpty() && $products->isEmpty()) {
            return;
        }

        $now = now();
        $startDate = $now->copy();
        $endDate = $now->copy()->addMonths(3);

        // Category-based discounts
        if ($categories->isNotEmpty()) {
            $categoryDiscounts = [
                ['name' => 'Summer Sale', 'discount_type' => 'percentage', 'discount_value' => 10, 'category_id' => $categories[0]->id, 'min_quantity' => 1, 'is_active' => 1],
                ['name' => 'Winter Clearance', 'discount_type' => 'percentage', 'discount_value' => 25, 'category_id' => $categories->count() > 1 ? $categories[1]->id : $categories[0]->id, 'min_quantity' => 2, 'is_active' => 1],
                ['name' => 'Festival Offer', 'discount_type' => 'percentage', 'discount_value' => 30, 'category_id' => $categories->count() > 2 ? $categories[2]->id : $categories[0]->id, 'min_quantity' => 1, 'is_active' => 1],
                ['name' => 'Monsoon Mega Sale', 'discount_type' => 'percentage', 'discount_value' => 35, 'category_id' => $categories[0]->id, 'min_quantity' => 1, 'is_active' => 1],
                ['name' => 'New Year Special', 'discount_type' => 'fixed', 'discount_value' => 200, 'category_id' => $categories->count() > 1 ? $categories[1]->id : $categories[0]->id, 'min_quantity' => 3, 'is_active' => 1],
                ['name' => 'Diwali Dhamaka', 'discount_type' => 'percentage', 'discount_value' => 40, 'category_id' => $categories->count() > 2 ? $categories[2]->id : $categories[0]->id, 'min_quantity' => 2, 'is_active' => 1],
                ['name' => 'Inactive Deal', 'discount_type' => 'percentage', 'discount_value' => 20, 'category_id' => $categories->count() > 3 ? $categories[3]->id : $categories[0]->id, 'min_quantity' => 1, 'is_active' => 0],
            ];

            foreach ($categoryDiscounts as $discount) {
                PosDiscount::create(array_merge($discount, [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'creator_id' => $userId,
                    'created_by' => $userId,
                ]));
            }
        }

        // Product-based discounts
        if ($products->isNotEmpty()) {
            $productDiscounts = [
                ['name' => 'Bulk Discount', 'discount_type' => 'percentage', 'discount_value' => 15, 'min_quantity' => 5, 'is_active' => 1, 'products' => [$products[0]->id]],
                ['name' => 'Fixed Promo', 'discount_type' => 'fixed', 'discount_value' => 50, 'min_quantity' => 1, 'is_active' => 1, 'products' => [$products->count() > 1 ? $products[1]->id : $products[0]->id]],
                ['name' => 'Buy More Save More', 'discount_type' => 'percentage', 'discount_value' => 20, 'min_quantity' => 10, 'is_active' => 1, 'products' => [$products->count() > 2 ? $products[2]->id : $products[0]->id]],
                ['name' => 'Special Deal', 'discount_type' => 'fixed', 'discount_value' => 100, 'min_quantity' => 3, 'is_active' => 1, 'products' => [$products->count() > 3 ? $products[3]->id : $products[0]->id]],
                ['name' => 'Combo Offer', 'discount_type' => 'percentage', 'discount_value' => 18, 'min_quantity' => 4, 'is_active' => 1, 'products' => [$products->count() > 4 ? $products[4]->id : $products[0]->id]],
                ['name' => 'Flash Sale', 'discount_type' => 'fixed', 'discount_value' => 150, 'min_quantity' => 2, 'is_active' => 1, 'products' => [$products[0]->id]],
                ['name' => 'Weekend Special', 'discount_type' => 'percentage', 'discount_value' => 12, 'min_quantity' => 1, 'is_active' => 1, 'products' => [$products->count() > 1 ? $products[1]->id : $products[0]->id]],
            ];

            foreach ($productDiscounts as $discount) {
                $productIds = $discount['products'];
                unset($discount['products']);

                $posDiscount = PosDiscount::create(array_merge($discount, [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'creator_id' => $userId,
                    'created_by' => $userId,
                ]));

                $posDiscount->products()->attach($productIds);
            }
        }
    }
}
