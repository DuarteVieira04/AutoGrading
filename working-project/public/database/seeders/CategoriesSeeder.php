<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesSeeder extends Seeder
{
    use SeederUtils;

    public static $categories = [
        1 => 'Fruits', 
        2 => 'Vegetables',
        3 => 'Dairy',
        4 => 'Meat',
        5 => 'Seafood',
        6 => 'Bakery',
        7 => 'Baby food',
        8 => 'Frozen foods',
        9 => 'Snacks',
        10 => 'Miscellaneous',
        11 => 'Grains',
    ];    
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->line("Copying categories and category images");
        $this->cleanStorageFolder('categories');
        $createdDate = DatabaseSeeder::$startDate->copy()->addMinutes(mt_rand(360, 9999));
        foreach (self::$categories as $newId => $category) {
            $createdDate = $createdDate->copy()->addMinutes(mt_rand(10, 60));
            // Miscellaneous will not have an image
            $newFileName = null;
            if ($category != 'Miscellaneous') {
                $originalFilename = $this->getFileNameFromString($category, 'png');
                $newFileName = $this->copyFileToStorage('categories_images', $originalFilename, 'categories', $newId);
            }
            DB::table('categories')->insert([
                'name' => $category,
                'image' => $newFileName,
                'created_at' => $createdDate,
                'updated_at' => $createdDate,
                'deleted_at' => null,
            ]);
        }
        $deletedDate = $createdDate->copy()->addMinutes(mt_rand(360, 9999));
        DB::table('categories')->where('name', 'Grains')->update(['deleted_at' => $deletedDate]);
        $this->command->line("All categories and category images were copied!");
        $this->directCopyFileToStorage('null_images', 'category_no_image.png', 'categories');
        $this->command->line("Image for categories with no associated image was copied!");
    }


}
