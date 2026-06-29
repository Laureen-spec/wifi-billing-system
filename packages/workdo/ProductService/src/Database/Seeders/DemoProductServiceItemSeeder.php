<?php

namespace Workdo\ProductService\Database\Seeders;

use Workdo\ProductService\Models\ProductServiceItem;
use Workdo\ProductService\Models\ProductServiceCategory;
use Workdo\ProductService\Models\ProductServiceTax;
use Workdo\ProductService\Models\ProductServiceUnit;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Workdo\ProductService\Models\WarehouseStock;

class DemoProductServiceItemSeeder extends Seeder
{
    public function run($userId): void
    {
        if (ProductServiceItem::where('created_by', $userId)->exists()) {
            return;
        }

        if (!empty($userId)) {
            // Get only Item type categories
            $itemCategories = ProductServiceCategory::where('created_by', $userId)->pluck('id', 'name')->toArray();
            $taxes = ProductServiceTax::where('created_by', $userId)->pluck('id')->toArray();
            $units = ProductServiceUnit::where('created_by', $userId)->pluck('id')->toArray();
            $warehouses = Warehouse::where('created_by', $userId)->pluck('id')->toArray();

            if (empty($itemCategories) || empty($taxes) || empty($units)) {
                return;
            }

            // 35 Items: 15 Products, 4 Services, 6 Parts across all 10 categories
            $categoryItems = [
                'Electronics & Technology' => [
                    ['name' => 'Laptop', 'sku' => 'ELEC-PROD-001', 'type' => 'product', 'unit' => 'Piece', 'has_tax' => true, 'image' => true, 'images' => 5, 'description' => 'High performance business laptop with advanced graphics processing power for professionals', 'sale_price' => 899.99, 'purchase_price' => 650.00],
                    ['name' => 'Smartphone', 'sku' => 'ELEC-PROD-002', 'type' => 'product', 'unit' => 'Piece', 'has_tax' => true, 'image' => true, 'images' => 4, 'description' => 'Latest model smartphone with advanced camera system and high speed processing', 'sale_price' => 699.99, 'purchase_price' => 450.00],
                    ['name' => 'IT Support Service', 'sku' => 'ELEC-SERV-003', 'type' => 'service', 'unit' => 'Hour', 'has_tax' => true, 'image' => true, 'images' => 0, 'description' => 'Comprehensive technical support and system maintenance service package for business operations', 'sale_price' => 75.00, 'purchase_price' => 0.00],
                    ['name' => 'Laptop Battery', 'sku' => 'ELEC-PART-004', 'type' => 'part', 'unit' => 'Piece', 'has_tax' => true, 'image' => true, 'images' => 2, 'description' => 'High capacity replacement lithium battery compatible with multiple laptop models', 'sale_price' => 89.99, 'purchase_price' => 45.00],
                ],
                'Fashion & Apparel' => [
                    ['name' => 'T-Shirt', 'sku' => 'FASH-PROD-005', 'type' => 'product', 'unit' => 'Piece', 'has_tax' => true, 'image' => true, 'images' => 5, 'description' => 'Organic cotton comfortable casual wear t-shirt available in multiple colors', 'sale_price' => 24.99, 'purchase_price' => 12.00],
                    ['name' => 'Jeans', 'sku' => 'FASH-PROD-006', 'type' => 'product', 'unit' => 'Pair', 'has_tax' => true, 'image' => true, 'images' => 3, 'description' => 'Classic fit premium denim jeans with stretch comfort technology for everyday', 'sale_price' => 59.99, 'purchase_price' => 35.00],
                    ['name' => 'Tailoring Service', 'sku' => 'FASH-SERV-007', 'type' => 'service', 'unit' => 'Service Call', 'has_tax' => true, 'image' => true, 'images' => 0, 'description' => 'Professional clothing alteration and custom tailoring service for perfect fit garments', 'sale_price' => 45.00, 'purchase_price' => 0.00],
                    ['name' => 'Belt', 'sku' => 'FASH-PART-008', 'type' => 'part', 'unit' => 'Piece', 'has_tax' => true, 'image' => true, 'images' => 2, 'description' => 'Leather belt for casual and formal wear', 'sale_price' => 250.00, 'purchase_price' => 120.00],
                ],
                'Books & Stationery' => [
                    ['name' => 'Notebook', 'sku' => 'BOOK-PROD-009', 'type' => 'product', 'unit' => 'Book', 'has_tax' => true, 'image' => true, 'images' => 4, 'description' => 'Premium handcrafted leather bound notebook with high quality lined pages for professionals', 'sale_price' => 34.99, 'purchase_price' => 18.00],
                    ['name' => 'Ink Cartridge', 'sku' => 'BOOK-PART-011', 'type' => 'part', 'unit' => 'Piece', 'has_tax' => true, 'image' => true, 'images' => 1, 'description' => 'Compatible ink cartridge replacement for various printer models and brands available', 'sale_price' => 25.99, 'purchase_price' => 12.00],
                ],
                'Home & Garden' => [
                    ['name' => 'Plant Pot', 'sku' => 'HOME-PROD-012', 'type' => 'product', 'unit' => 'Pot', 'has_tax' => true, 'image' => true, 'images' => 3, 'description' => 'Decorative ceramic plant pot perfect for indoor plant cultivation with drainage', 'sale_price' => 24.99, 'purchase_price' => 12.00],
                    ['name' => 'Light Bulb', 'sku' => 'HOME-PART-014', 'type' => 'part', 'unit' => 'Piece', 'has_tax' => true, 'image' => true, 'images' => 2, 'description' => 'Standard household light bulb for daily lighting needs', 'sale_price' => 300.00, 'purchase_price' => 250.00],
                ],
                'Sports & Fitness' => [
                    ['name' => 'Football', 'sku' => 'SPORT-PROD-015', 'type' => 'product', 'unit' => 'Ball', 'has_tax' => true, 'image' => true, 'images' => 3, 'description' => 'Official regulation size football for competitive games and training with leather', 'sale_price' => 39.99, 'purchase_price' => 22.00],
                    ['name' => 'Yoga Mat', 'sku' => 'SPORT-PART-017', 'type' => 'part', 'unit' => 'Piece', 'has_tax' => true, 'image' => true, 'images' => 1, 'description' => 'Everyday yoga mat for home or gym workouts', 'sale_price' => 150.00, 'purchase_price' => 70.00],
                ],
                'Health & Beauty' => [
                    ['name' => 'Shampoo', 'sku' => 'BEAUTY-PROD-018', 'type' => 'product', 'unit' => 'Bottle', 'has_tax' => true, 'image' => true, 'images' => 3, 'description' => 'Natural organic shampoo for all hair types with essential oils and formula', 'sale_price' => 18.99, 'purchase_price' => 9.00],
                    ['name' => 'Face Cream', 'sku' => 'BEAUTY-PROD-019', 'type' => 'product', 'has_tax' => true, 'image' => true, 'images' => 0, 'description' => 'Anti aging moisturizing face cream with vitamin E and hyaluronic acid', 'sale_price' => 35.99, 'purchase_price' => 18.00],
                    ['name' => 'Massage Therapy Service', 'sku' => 'BEAUTY-SERV-020', 'type' => 'service', 'unit' => 'Session', 'has_tax' => true, 'image' => true, 'images' => 0, 'description' => 'Professional full-body massage therapy for relaxation and stress relief', 'sale_price' => 120.00, 'purchase_price' => 0.00],
                ],
                'Fruits & Vegetables' => [
                    ['name' => 'Apple', 'sku' => 'FRUIT-PROD-022', 'type' => 'product', 'unit' => 'Kilogram', 'has_tax' => true, 'image' => true, 'images' => 5, 'description' => 'Fresh organic red apples packed with natural vitamins and minerals from orchards', 'sale_price' => 3.99, 'purchase_price' => 2.00],
                    ['name' => 'Raspberry', 'sku' => 'FRUIT-PROD-023', 'type' => 'product', 'unit' => 'Basket', 'has_tax' => true, 'image' => true, 'images' => 3, 'description' => 'Fresh organic raspberries packed with antioxidants and natural sweetness for eating', 'sale_price' => 6.99, 'purchase_price' => 4.00],
                ],
                'Food & Beverages' => [
                    ['name' => 'Coffee', 'sku' => 'FOOD-PROD-026', 'type' => 'product', 'unit' => 'Kilogram', 'has_tax' => true, 'image' => true, 'images' => 4, 'description' => 'Premium arabica coffee beans roasted to perfection with rich aroma and taste', 'sale_price' => 15.99, 'purchase_price' => 8.00],
                ],
                'Automotive & Tools' => [
                    ['name' => 'Car Engine Oil', 'sku' => 'AUTO-PROD-029', 'type' => 'product', 'unit' => 'Bottle', 'has_tax' => true, 'image' => true, 'images' => 3, 'description' => 'High-performance synthetic engine oil designed for modern cars and extended engine life', 'sale_price' => 45.00, 'purchase_price' => 25.00],
                    ['name' => 'Cordless Drill Machine', 'sku' => 'AUTO-PROD-030', 'type' => 'product', 'unit' => 'Piece', 'has_tax' => true, 'image' => true, 'images' => 2, 'description' => 'Rechargeable cordless drill with variable speed and multiple drill bit attachments', 'sale_price' => 120.00, 'purchase_price' => 70.00],
                    ['name' => 'Brake Pad Set', 'sku' => 'AUTO-PART-032', 'type' => 'part', 'unit' => 'Set', 'has_tax' => true, 'image' => true, 'images' => 1, 'description' => 'Durable replacement brake pads compatible with most mid-size cars and SUVs', 'sale_price' => 80.00, 'purchase_price' => 40.00],
                ],
                'Jewelry & Accessories' => [
                    ['name' => 'Watch', 'sku' => 'JEWEL-PROD-033', 'type' => 'product', 'unit' => 'Piece', 'has_tax' => true, 'image' => true, 'images' => 3, 'description' => 'Elegant stainless steel watch with water resistance and precision quartz movement', 'sale_price' => 199.99, 'purchase_price' => 120.00],
                    ['name' => 'Jewelry Repair Service', 'sku' => 'JEWEL-SERV-034', 'type' => 'service', 'has_tax' => true, 'image' => true, 'images' => 0, 'description' => 'Professional jewelry repair and restoration service for watches rings and necklaces', 'sale_price' => 35.00, 'purchase_price' => 0.00],
                ],
            ];

            $items = [];
            foreach ($categoryItems as $categoryName => $categoryProducts) {
                if (isset($itemCategories[$categoryName])) {
                    foreach ($categoryProducts as $product) {
                        $product['category_name'] = $categoryName;
                        $items[] = $product;
                    }
                }
            }

            if (!empty($items)) {
                $items = collect($items)->shuffle()->values()->toArray(); // random select from array
            }

            if (!empty($warehouses)) {
                foreach ($items as $itemData) {
                    // Generate item name based image paths
                    $itemName = strtolower(str_replace([' ', '-'], '_', $itemData['name']));
                    $imagePath = "{$itemName}_image.png";

                    // Use predefined image count from categoryItems
                    $imageCount = $itemData['images'];
                    $imagesPaths = [];
                    if ($itemData['image'] && $imageCount > 0) {
                        for ($i = 1; $i <= $imageCount; $i++) {
                            $imagesPaths[] = "{$itemName}_images_{$i}.png";
                        }
                    }

                    // Handle tax assignment - 5 items without tax, rest with tax
                    $selectedTaxes = null;
                    if ($itemData['has_tax']) {
                        $taxCount = rand(1, min(3, count($taxes)));
                        $randomTaxes = array_slice($taxes, 0, $taxCount);
                        $selectedTaxes = array_map('intval', $randomTaxes);
                    }

                    // Get unit ID by name
                    $selectedUnit = null;
                    if (isset($itemData['unit'])) {
                        $unitId = ProductServiceUnit::where('created_by', $userId)
                            ->where('unit_name', $itemData['unit'])
                            ->value('id');
                        $selectedUnit = $unitId ?: null;
                    }

                    $item = ProductServiceItem::create([
                        'name' => $itemData['name'],
                        'sku' => $itemData['sku'],
                        'type' => $itemData['type'],
                        'description' => $itemData['description'] ?? null,
                        'sale_price' => $itemData['sale_price'],
                        'purchase_price' => $itemData['purchase_price'],
                        'tax_ids' => $selectedTaxes,
                        'category_id' => $itemCategories[$itemData['category_name']],
                        'unit' => $selectedUnit,
                        'image' => $itemData['image'] ? $imagePath : null,
                        'images' => !empty($imagesPaths) ? json_encode($imagesPaths) : null,
                        'is_active' => 1,
                        'creator_id' => $userId,
                        'created_by' => $userId,
                    ]);

                    // Only create warehouse stock for products and parts, not services
                    if ($itemData['type'] !== 'service') {
                        $warehouseCount = rand(1, min(3, count($warehouses)));
                        $selectedWarehouses = array_rand($warehouses, $warehouseCount);
                        if (!is_array($selectedWarehouses)) {
                            $selectedWarehouses = [$selectedWarehouses];
                        }

                        foreach ($selectedWarehouses as $warehouseIndex) {
                            WarehouseStock::create([
                                'product_id' => $item->id,
                                'warehouse_id' => $warehouses[$warehouseIndex],
                                'quantity' => rand(10, 150),
                            ]);
                        }
                    }
                }
            }
        }
    }
}
