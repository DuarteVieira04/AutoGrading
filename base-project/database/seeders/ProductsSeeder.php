<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory;
use Illuminate\Support\Facades\DB;

class ProductsSeeder extends Seeder
{
    use SeederUtils;

    private $faker = null;

    public function run(): void
    {
        //$this->faker = Factory::create('pt_PT');
        $this->faker = Factory::create();

        $this->cleanStorageFolder('products');
        $this->addProductsFromArray($this->faker);
        Products::$dbProducts = DB::table('products')->get();
        $this->addPhotoFiles();
        Products::$dbProducts = DB::table('products')->get();
    }

    private function addProductsFromArray($faker)
    {
        $this->command->line("Adding products on the database");
        $arrayToStore = [];
        $createdDate = DatabaseSeeder::$startDate->copy()->addMinutes(mt_rand(10000, 20000));
        foreach (Products::$names as $category => $productNames) {
            foreach ($productNames as $productName) {
                $createdDate = $createdDate->copy()->addMinutes(mt_rand(10, 60));
                $updatedDate = mt_rand(1, 4) == 1 ? $createdDate->copy()->addMinutes(mt_rand(100000, 300000)) : $createdDate;
                $arrayToStore[] = [
                    'category_id' => $category,
                    'name' => $productName,
                    'price' => $this->randomDecimal(0.5, 5, 2),
                    'stock' => 0,
                    'description' => $faker->text(200),
                    'photo' => null,
                    'discount_min_qty' => null,
                    'discount' => null,
                    'stock_lower_limit' => mt_rand(2, 5),
                    'stock_upper_limit' => mt_rand(20, 50),
                    'created_at' => $createdDate,
                    'updated_at' => $updatedDate,
                    'deleted_at' => null,
                ];
                if (count($arrayToStore) >= DatabaseSeeder::$dbInsertBlockSize) {
                    DB::table('products')->insert($arrayToStore);
                    $this->command->line("Created " . count($arrayToStore) . " products");
                    $arrayToStore = [];
                }
            }
        }
        if (count($arrayToStore) >= 1) {
            DB::table('products')->insert($arrayToStore);
            $this->command->line("Created " . count($arrayToStore) . " products");
        }        
    }

    private function addPhotoFiles()
    {
        $this->command->line("Copying product photos");
        $total = count(Products::$dbProducts);
        $i = 0;
        foreach (Products::$dbProducts as $DBproduct) {
            // Turnip (Nabo) with ID = 51 will not have an image
            $newFileName = null;
            if ($DBproduct->name != 'Turnip') {
                $originalFilename = $this->getFileNameFromString($DBproduct->name, 'jpg');
                $newFileName = $this->copyFileToStorage('products_photos', $originalFilename, 'products', $DBproduct->id);
            } else {
                $this->command->line("Product with id= $DBproduct->id (Turnip / Nabo) will not have an image!");
            }
            $DBproduct->photo = $newFileName;
            DB::table('products')->where('id', $DBproduct->id)->update(['photo' => $DBproduct->photo]);
            $i++;
            if ($i % 10 == 0) {
                $this->command->line("Product photo $i/$total copied");
            }
        }
        $this->command->line("Total of $total product photos were copied!");
        $this->directCopyFileToStorage('null_images', 'product_no_image.png', 'products');
        $this->command->line("Image for products with no associated image was copied!");
    }
}
