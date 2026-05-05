<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public static $startDate;
    public static $dbInsertBlockSize = 500;
    /**
     * Seed the application's database.
     */
    public function run(): void
    {        
        $this->command->info("-----------------------------------------------");
        $this->command->info("START of database seeder");
        $this->command->info("-----------------------------------------------");

        self::$startDate = Carbon::now()->subYears(2);
        
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        } else {
            DB::statement('SET foreign_key_checks=0');
        }

        DB::table('items_orders')->delete();
        DB::table('orders')->delete();
        DB::table('products')->delete();
        DB::table('operations')->delete();
        DB::table('cards')->delete();
        DB::table('supply_orders')->delete();
        DB::table('stock_adjustments')->delete();
        DB::table('users')->delete();
        DB::table('categories')->delete();
        DB::table('settings')->delete();
        DB::table('settings_shipping_costs')->delete();

        if (DB::getDriverName() === 'sqlite') {
            DB::statement("DELETE FROM sqlite_sequence WHERE name = 'items_orders'");
            DB::statement("DELETE FROM sqlite_sequence WHERE name = 'orders'");
            DB::statement("DELETE FROM sqlite_sequence WHERE name = 'products'");
            DB::statement("DELETE FROM sqlite_sequence WHERE name = 'operations'");
            DB::statement("DELETE FROM sqlite_sequence WHERE name = 'supply_orders'");
            DB::statement("DELETE FROM sqlite_sequence WHERE name = 'stock_adjustments'");
            DB::statement("DELETE FROM sqlite_sequence WHERE name = 'users'");
            DB::statement("DELETE FROM sqlite_sequence WHERE name = 'categories'");
            DB::statement("DELETE FROM sqlite_sequence WHERE name = 'settings'");
            DB::statement("DELETE FROM sqlite_sequence WHERE name = 'settings_shipping_costs'");
        } else {
            DB::statement('ALTER TABLE items_orders AUTO_INCREMENT = 0');
            DB::statement('ALTER TABLE orders AUTO_INCREMENT = 0');
            DB::statement('ALTER TABLE products AUTO_INCREMENT = 0');
            DB::statement('ALTER TABLE operations AUTO_INCREMENT = 0');
            DB::statement('ALTER TABLE supply_orders AUTO_INCREMENT = 0');
            DB::statement('ALTER TABLE stock_adjustments AUTO_INCREMENT = 0');
            DB::statement('ALTER TABLE users AUTO_INCREMENT = 0');
            DB::statement('ALTER TABLE categories AUTO_INCREMENT = 0');
            DB::statement('ALTER TABLE settings AUTO_INCREMENT = 0');
            DB::statement('ALTER TABLE settings_shipping_costs AUTO_INCREMENT = 0');
        }

        $this->call(SettingsSeeder::class);
        $this->call(UsersSeeder::class);
        $this->call(CardsSeeder::class);
        $this->call(CategoriesSeeder::class);
        $this->call(ProductsSeeder::class);
        $this->call(OrdersSeeder::class);
        $this->call(StocksSeeder::class);
        $this->call(OperationsSeeder::class);

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            DB::statement('SET foreign_key_checks=1');
        }


        $this->command->info("-----------------------------------------------");
        $this->command->info("END of database seeder");
        $this->command->info("-----------------------------------------------");
    }
}
