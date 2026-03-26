<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OrdersSeeder extends Seeder
{
    use SeederUtils;

    private $faker = null;

    private $avgOrdersDay = [8, 15, 12, 14, 19, 30, 50]; // Sunday, Monday, Tuesday , ...
    private $deltaAvgOrdersDay = [5, 7, 5, 5, 10, 15, 30]; // Delta (minus or plus) to the average orders per day
                                                           // maximum orders per week = 282 (30000 order in 2 years)
    private $differentProducts = [1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 2, 2, 2, 2, 2, 2, 2, 2, 2, 3, 3, 3, 4, 4, 5, 6, 7, 8, 9, 10];
    private $qtys = [1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 
                     1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 
                     2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 
                     3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 
                     4, 4, 4, 4, 4, 4, 4, 
                     5, 5, 5, 5,  
                     6, 6,
                     7,
                     8,
                     9,
                     10];
    private $weightedProducts = null;
    private $totalProductsWeight = 0;

    private $orders_ids = [];
    
    public function run(): void
    {
        //$this->faker = Factory::create('pt_PT');
        $this->faker = Factory::create();

        $this->cleanStorageFolder('receipts', false);        
        $this->initProductsWeigth();
        $startDate = $this->latestCardDate()->copy()->addMinutes(mt_rand(1000, 3000));
        $startDate->addDay();
        $this->addOrdersToDB($startDate);
        $this->addItemOrdersToDB();
        $this->updateTotalItems();
        $this->updateShippingCosts();
        $this->updateTotal();
        $this->updateStatusOrders();
        $this->addCustomDataToHandleStockSeeder();
        $this->addReceiptsFiles();
    }

    private function addOrdersToDB($startDate)
    {
        $this->command->line("Adding Orders to the database. Starting date = " . $startDate->toDateString());
        $members = UsersSeeder::$dbUsers->where('type', '<>', 'employee');
        $d = $startDate->copy()->startOfDay();
        $yesterday = Carbon::yesterday()->startOfDay();
        $totalOrders = 0;
        $nextChangeDate = $d->copy()->addDays(mt_rand(15,25));
        $arrayToStore = [];
        while($d->lessThanOrEqualTo($yesterday)) {
            $totalOrdersForDay = $this->avgOrdersDay[$d->dayOfWeek] + 
                mt_rand(-1*$this->deltaAvgOrdersDay[$d->dayOfWeek],$this->deltaAvgOrdersDay[$d->dayOfWeek]);
            $totalOrders += $totalOrdersForDay;
            // From 6:00 to 24:00 (typical usage) = 18 hours = 64800 seconds    
            $maxIntervalBetweenOrdersInSeconds = max(2, intdiv(64800 , $totalOrdersForDay));
            $orderDate = $d->copy()->addHours(6)->addSeconds(mt_rand(1, $maxIntervalBetweenOrdersInSeconds));
            for($i=1; $i <= $totalOrdersForDay; $i++) {
                $user = $members->random();
                $arrayToStore[] = $this->createOrderArrayToSaveOnDB($user, $orderDate);
                $orderDate = $orderDate->copy()->addSeconds(mt_rand(1, $maxIntervalBetweenOrdersInSeconds));
            }
            if ($nextChangeDate->equalTo($d)) {
                $nextChangeDate = $d->copy()->addDays(mt_rand(15, 25));
                $this->changeAvgsPerDay($d->month);
            }
            $d->addDay();
            if (count($arrayToStore) >= DatabaseSeeder::$dbInsertBlockSize) {
                DB::table('orders')->insert($arrayToStore);
                $this->command->line("Created " . count($arrayToStore) . " orders");
                $arrayToStore = [];
            }
        }
        if (count($arrayToStore) >= 1) {
            DB::table('orders')->insert($arrayToStore);
            $this->command->line("Created " . count($arrayToStore) . " orders");
        }
        $totalOrders = DB::table('orders')->count();
        $this->orders_ids = DB::table('orders')->pluck('id')->toArray();
        $this->command->line("A total of $totalOrders were added to the database.");
    }

    private function createOrderArrayToSaveOnDB($user, $orderDate)
    {
        return [
            'member_id' => $user->id,
            'status' => 'completed',
            'date' => $orderDate->copy()->startOfDay(),
            'total_items' => 0,
            'shipping_cost' => 0,
            'total' => 0,
            'nif' => $user->nif ?? (mt_rand(1,3) == 2 ? mt_rand(100000000, 199999999): null),
            'delivery_address' => $user->default_delivery_address ?? $this->faker->address,
            'pdf_receipt' => null,
            'cancel_reason' => null,
            'created_at' => $orderDate,
            'updated_at' => $orderDate,
        ];
    }    

    private function addItemOrdersToDB()
    {
        $this->command->line("Adding Item Orders to the database.");
        
        $arrayToStore = [];
        foreach($this->orders_ids as $order_id) {
            $arrayToStore = array_merge($arrayToStore, $this->createItemsOrderArrayToSaveOnDB($order_id));
            if (count($arrayToStore) >= DatabaseSeeder::$dbInsertBlockSize) {
                DB::table('items_orders')->insert($arrayToStore);
                $this->command->line("Created " . count($arrayToStore) . " items orders");
                $arrayToStore = [];
            }
        }
        if (count($arrayToStore) >= 1) {
            DB::table('items_orders')->insert($arrayToStore);
            $this->command->line("Created " . count($arrayToStore) . " items orders");
        }
        $totalItemsOrders = DB::table('items_orders')->count();
        $this->command->line("A total of $totalItemsOrders items orders were added to the database.");
    }

    private function createItemsOrderArrayToSaveOnDB($orderId)
    {
        $numberOfItems = $this->faker->randomElement($this->differentProducts);
        $selectedProducts = [];
        $arrayToStore = [];
        for ($i = 1; $i <= $numberOfItems; $i++) {
            $product = $this->randomProduct();            
            $j = 0;
            while (in_array($product->id, $selectedProducts, false)) {
                $product = $this->randomProduct();
                $j++;
                if ($j > 20) {
                    // To many attempts to get a random product!!!
                    // We'll ignore this and continue after the cycle
                    $this->command->warn("Order #$orderId ignored an item order because random product #$product->id was repeated to many times!!!!");
                    $product = null;
                }
            }
            if ($product) {
                $selectedProducts[] = $product->id;
                // Here we have selected the product and ensured it was not used previously
                $qty = $this->faker->randomElement($this->qtys); //$qty = 1 .. 10
                $discount = mt_rand(1, 25 - 2 * $qty) == 2 ? 1 : 0;
                if ($discount > 0) {
                    $discount = round($product->price * mt_rand(1, 40) / 100, 2);
                }
                $subTotal = $qty * ($product->price - $discount);
                $arrayToStore[] = [
                    'order_id' => $orderId,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_price' => $product->price,
                    'discount' => $discount,
                    'subtotal' => $subTotal
                ];
            } 
        }
        return $arrayToStore;
    }

    private function randomProduct()
    {
        $randomNumber = mt_rand(0, $this->totalProductsWeight - 1);
        foreach($this->weightedProducts as $product) {
            if (($product->weightLowerLimit <= $randomNumber) && ($randomNumber < $product->weightUpperLimit)) {
                return $product;
            }
        }
        return $this->weightedProducts->last();
    }

    private function initProductsWeigth()
    {
        $this->weightedProducts =  Products::$dbProducts;
        $this->totalProductsWeight = 0;
        foreach($this->weightedProducts as $product) {
            $weight = $product->stock_upper_limit * mt_rand(1, 4) - mt_rand(1, 9);
            $product->weight = $weight;
            $product->weightLowerLimit = $this->totalProductsWeight;
            $product->weightUpperLimit = $product->weightLowerLimit + $weight;
            $this->totalProductsWeight += $weight;
        }
    }


    private function changeAvgsPerDay($month) 
    {
        $randomIncrements = [1, 2, 3, 2, 2, 3, 4, 4, 1, 2, 8, 10];
        foreach ($this->avgOrdersDay as $key => $value) {
            $this->avgOrdersDay[$key] = $value + mt_rand(-1, $randomIncrements[$month-1]);
            if ($this->avgOrdersDay[$key] <= $this->deltaAvgOrdersDay[$key]) {
                $this->avgOrdersDay[$key] = $this->deltaAvgOrdersDay[$key] + 1;
            }
        }
    }
    
    private function latestCardDate() 
    {
        return Carbon::parse(DB::select('select max(created_at) as m from cards')[0]->m);
    }

    private function updateTotalItems()
    {
        $this->command->line("Updating 'total_items' of all orders - this may take a very long time!");
        DB::update('update orders set total_items = (select sum(subtotal) from items_orders where items_orders.order_id = orders.id)');
        $this->command->line("Completed updating 'total_items' of all orders");
    }

    private function updateShippingCosts() {
        $this->command->line("Updating 'shipping_costs' of all orders");
        $shippingCosts = DB::table('settings_shipping_costs')->get();
        foreach($shippingCosts as $shippingCostRule) {
            DB::update("update orders set shipping_cost = ? 
                            where (total_items >= ?) and (total_items < ?)",
                            [
                                $shippingCostRule->shipping_cost,
                                $shippingCostRule->min_value_threshold,
                                $shippingCostRule->max_value_threshold
                            ]);
        }
        $this->command->line("Completed updating 'shipping_costs' of all orders");        
    }

    private function updateTotal() {
        $this->command->line("Updating 'total' of all orders");
        DB::update('update orders set total = total_items + shipping_cost');
        $this->command->line("Completed updating 'total' of all orders");
    }


    private function addReceiptsFiles() 
    {
        $totalOrders = DB::table('orders')->count();
        $i = 0;
        foreach ($this->orders_ids as $order_id) {
            $newFileName = $this->copyFileToStorage('receipts', "receipt_$order_id.pdf", 'receipts', $order_id, false);
            DB::table('orders')->where('id', $order_id)->update(['pdf_receipt' => $newFileName]);
            $i++;
            if ($i % 100 == 0) {
                $this->command->line("Copied $i of $totalOrders PDF receipts and updated associated orders");
            }
        }
        $this->command->line("Copied a total of $i PDF receipts for a total of $totalOrders orders!");
    }

    private function updateStatusOrders()
    {
        $lastId = DB::table('orders')->max('id');
        $lastCancellable = $lastId - 100;
        $randomIDs = array_map(fn() => mt_rand(1, $lastCancellable), range(1, 200));
        $this->command->line("Creating 200 random canceled orders");
        DB::table('orders')->whereIntegerInRaw('id', $randomIDs)->update(['status'=> 'canceled']);
        // DB::update(
        //     'update orders set status = ? where id in (' . implode(',', array_fill(0, count($randomIDs), '?')) . ')',
        //     array_merge(['canceled'], $randomIDs)
        // );
        $this->command->line("Created 200 random canceled orders");

        $firstPending = $lastId - 20;
        $lastId--;
        $randomIDs = array_map(fn() => mt_rand($firstPending, $lastId), range(1, 5));
        $this->command->line("Creating 5 random pending orders");
        DB::table('orders')->whereIntegerInRaw('id', $randomIDs)->update(['status' => 'pending']);
        // DB::update(
        //     'update orders set status = ? where id in (' . implode(',', array_fill(0, count($randomIDs), '?')) . ')',
        //     array_merge(['pending'], $randomIDs)
        // );
        $this->command->line("Created 5 random pending orders");
    }

    private function addCustomDataToHandleStockSeeder()
    {
        // We'll add the order status and created_at deate to the custom field of items_orders
        // This will simplify filling up the stock of the products
        // When ending the stock seeder, the custom field should reverts to null 
        $this->command->line("Updating 'items orders' custom field to simplify stock handling. This may take a very long time!");
        if (DB::getDriverName() === 'sqlite') {
            DB::update('UPDATE items_orders SET custom = (SELECT status || "," || created_at FROM orders WHERE items_orders.order_id = orders.id)');
        } else {
            DB::update('update items_orders set custom = JSON_OBJECT("value", (select concat(status, ",", created_at) from orders where items_orders.order_id = orders.id))');
        }
        $this->command->line("Completed updating 'items orders' custom field to simplify stock handling!");
    }
}
