<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory;
use Carbon\Carbon;

class StocksSeeder extends Seeder
{
    private $boardEmployeeUserIds = [];

    private $faker = null;
    
    public function run(): void
    {
        //$this->faker = Factory::create('pt_PT');
        $this->faker = Factory::create();

        $this->boardEmployeeUserIds = DB::table('users')->where('type', '<>', 'member')->pluck('id')->toArray();

        Products::$dbProducts = DB::table('products')->get();

        $this->setInitialStock();
        $this->createSupplyOrdersToUpdateInventoryWhenOrdering();
        $this->saveAllProductsStocks();
        $this->cleanItemsOrdersCustomField();
        $this->command->line("Created supply orders to update inventory when ordering");
    }

    private function setInitialStock()
    {
        $this->command->line("Setting Initial Stock for Products");
        $arrayToStore = [];
        foreach(Products::$dbProducts as $product) {
            $productCreatedDate = Carbon::parse($product->created_at);
            $stockCreatedDate = $productCreatedDate->copy()->addMinutes(mt_rand(10, 1000));
            $quantity = max($product->stock_lower_limit, $product->stock_upper_limit + mt_rand(-5, 10));

            $product->stock = $quantity;
            $arrayToStore[] = [
                'product_id' => $product->id,
                'registered_by_user_id' => $this->faker->randomElement($this->boardEmployeeUserIds),
                'status' => 'completed',
                'quantity' => $quantity,
                'created_at' => $stockCreatedDate,
                'updated_at' => $stockCreatedDate,
            ];
            if (count($arrayToStore) >= DatabaseSeeder::$dbInsertBlockSize) {
                DB::table('supply_orders')->insert($arrayToStore);
                $this->command->line("Created " . count($arrayToStore) . " supply stock records");
                $arrayToStore = [];
            }
        }
        if (count($arrayToStore) >= 1) {
            DB::table('supply_orders')->insert($arrayToStore);
            $this->command->line("Created " . count($arrayToStore) . " supply stock records");
            $arrayToStore = [];
        }
    }

    private function getProduct($productId)
    {
        return  Products::$dbProducts->where('id', $productId)->first();
    }

    private function handleStockUpdate($product, $orderedQuantity, $orderCreatedDate, &$arrayToStore)
    {
        $resupplyQuantity = 0;
        if ($product) {
            if (($product->stock - $orderedQuantity) <= $product->stock_lower_limit) {
                $resupplyQuantity = max(
                    $orderedQuantity - $product->stock, 
                    $product->stock_lower_limit, 
                    $product->stock_upper_limit + mt_rand(-2, 10));
            }
            if ($resupplyQuantity > 0) {
                $createdData = $orderCreatedDate->copy()->addMinutes(mt_rand(-100, -10));
                $arrayToStore[] = [
                    'product_id' => $product->id,
                    'registered_by_user_id' => $this->faker->randomElement($this->boardEmployeeUserIds),
                    'status' => 'completed',
                    'quantity' => $resupplyQuantity,
                    'created_at' => $createdData,
                    'updated_at' => $createdData,
                ];
                $product->stock = $product->stock + $resupplyQuantity;
                if (count($arrayToStore) >= DatabaseSeeder::$dbInsertBlockSize) {
                    DB::table('supply_orders')->insert($arrayToStore);
                    $this->command->line("Created " . count($arrayToStore) . " supply stock records");
                    $arrayToStore = [];
                }
            }
            $product->stock = $product->stock - $orderedQuantity;
        }
    }

    private function createSupplyOrdersToUpdateInventoryWhenOrdering()
    {
        $totalItemOrders = DB::table('items_orders')->count();
        $handledItems = 0;
        $arrayToStore = [];
        DB::table('items_orders')->orderBy('id')->chunk(
            1000,
            function ($itemsOrders) use (&$arrayToStore, &$handledItems, $totalItemOrders) {
                foreach ($itemsOrders as $itemOrder) {
                    $product = $this->getProduct($itemOrder->product_id);
                    if (DB::getDriverName() === 'sqlite') {
                        $orderValues = explode(',', $itemOrder->custom);
                    } else {
                        $orderValues = explode(',', json_decode($itemOrder->custom, true)['value']);
                    }
                    $orderStatus = $orderValues[0];
                    $orderCreatedAt = Carbon::parse($orderValues[1]);
                    if ($orderStatus == 'completed') {
                        $this->handleStockUpdate($product, $itemOrder->quantity, $orderCreatedAt, $arrayToStore);
                    }                    
                }
                $handledItems +=  1000;
                $this->command->line("Analysed $handledItems/$totalItemOrders order items and restock the products if necessary!");
            }
        );
        if (count($arrayToStore) >= 1) {
            DB::table('supply_orders')->insert($arrayToStore);
            $this->command->line("Created " . count($arrayToStore) . " supply stock records (last block)");
            $arrayToStore = [];
        }
    }
    private function cleanItemsOrdersCustomField() 
    {
        DB::table('items_orders')->update(['custom' => null]);
        $this->command->line("Cleaned all items_orders custom field");
    }

    private function saveAllProductsStocks()
    {
        $this->command->line("Updating 'stock' of all products - this may take a very long time!");
        // DB::update('update orders set total_items = (select sum(subtotal) from items_orders where items_orders.order_id = orders.id)');
        foreach (Products::$dbProducts as $product) {
            DB::table('products')->where('id', $product->id)->update(['stock' => $product->stock]);
        }
        $this->command->line("Completed updating 'stock' of all products");
    }
}
