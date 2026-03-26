<?php

namespace Database\Seeders;

use Illuminate\Container\Attributes\Database;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $createdDate = DatabaseSeeder::$startDate->copy()->addMinutes(mt_rand(10, 60));
        DB::table('settings')->insert([
            'membership_fee' => 100,
            'created_at' => $createdDate,
            'updated_at' => $createdDate,
        ]);
        $createdDate = $createdDate->copy()->addMinutes(mt_rand(10, 60));
        DB::table('settings_shipping_costs')->insert([
            [
            'min_value_threshold' => 0,
            'max_value_threshold' => 50,
            'shipping_cost' => 10,
            'created_at' => $createdDate,
            'updated_at' => $createdDate,
            ],
            [
            'min_value_threshold' => 50,
            'max_value_threshold' => 100,
            'shipping_cost' => 5,
            'created_at' => $createdDate,
            'updated_at' => $createdDate,
            ],
            [
            'min_value_threshold' => 100,
            'max_value_threshold' => 9999999.99,
            'shipping_cost' => 0,
            'created_at' => $createdDate,
            'updated_at' => $createdDate,
            ]        
        ]);


        // DB::table('settings_shipping_costs')->insert([
        //     'min_value_threshold' => 0,
        //     'max_value_threshold' => 100,
        //     'shipping_cost' => 10,
        //     'created_at' => $createdDate,
        //     'updated_at' => $createdDate,
        // ]);
        // DB::table('settings_shipping_costs')->insert([
        //     'min_value_threshold' => 100,
        //     'max_value_threshold' => 200,
        //     'shipping_cost' => 5,
        //     'created_at' => $createdDate,
        //     'updated_at' => $createdDate,
        // ]);
        // DB::table('settings_shipping_costs')->insert([
        //     'min_value_threshold' => 200,
        //     'max_value_threshold' => 9999999.99,
        //     'shipping_cost' => 0,
        //     'created_at' => $createdDate,
        //     'updated_at' => $createdDate,
        // ]);
    }
}
