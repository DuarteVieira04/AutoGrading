<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use stdClass;

class UsersSeeder extends Seeder
{
    use SeederUtils;

    private $faker = null;
    private $files_M = [];
    private $files_F = [];
    public static $hashPasword = "";
    public static $dbUsers = null;

    public static $fixedUsers = [
        ['type' => 'board', 'name' => 'Board Member', 'email' => 'b1@mail.pt', 'gender' => 'F'],
        ['type' => 'board', 'name' => 'Second Board Member or Admininstrator', 'email' => 'b2@mail.pt', 'gender' => 'M'],
        ['type' => 'board', 'name' => 'Third Board Member', 'email' => 'b3@mail.pt', 'gender' => 'M'],
        ['type' => 'board', 'name' => 'Fourth Board Member', 'email' => 'b4@mail.pt', 'gender' => 'M'],
        ['type' => 'member', 'name' => 'Regular Member', 'email' => 'm1@mail.pt', 'gender' => 'F'],
        ['type' => 'member', 'name' => 'Second Regular Member', 'email' => 'm2@mail.pt', 'gender' => 'M'],
        ['type' => 'member', 'name' => 'Third Regular Member', 'email' => 'm3@mail.pt', 'gender' => 'M'],
        ['type' => 'member', 'name' => 'Fourth Regular Member', 'email' => 'm4@mail.pt', 'gender' => 'F'],
        ['type' => 'employee', 'name' => 'First Employee', 'email' => 'e1@mail.pt', 'gender' => 'F'],
        ['type' => 'employee', 'name' => 'Second Employee', 'email' => 'e2@mail.pt', 'gender' => 'M'],
        ['type' => 'employee', 'name' => 'Third Employee', 'email' => 'e3@mail.pt', 'gender' => 'M'],
        ['type' => 'employee', 'name' => 'Fourth Employee', 'email' => 'e4@mail.pt', 'gender' => 'F'],
    ];

    public static $userTypes = [
        'member' => 500, 
        'board' => 6,
        'employee' => 10];

    public function run(): void
    {
        self::$hashPasword = bcrypt('123');

        //$this->faker = Factory::create('pt_PT');
        $this->faker = Factory::create();

        $this->cleanStorageFolder('users');
        $this->addUsersToDatabase();
        // $this->addFixedUsers($this->faker);
        // $this->addDynamicUsers($this->faker);
        $this->addPhotoFiles();
        $this->softdeleteSomeUsers();
        self::$dbUsers = DB::table('users')->orderBy('id')->get();
    }

    private function createUserArrayToSaveOnDB($user, $createdDate)
    {
        $confirmedDate = $createdDate->copy()->addMinutes(mt_rand(10, 300));
        $updatedDate = mt_rand(1, 4) == 1 ? $confirmedDate->copy()->addMinutes(mt_rand(100000, 300000)) : $confirmedDate;
        $default_payment_type = null;
        $default_payment_reference = null;
        if ($user['type'] != 'employee') {
            $this->ramdomPaymentMethod($user['email'], $default_payment_type, $default_payment_reference);
        }
        // Ensure that one in five payment defaults is null
        $default_payment_type = ($default_payment_type && mt_rand(1, 5) == 2) ? null : $default_payment_type;
        $default_payment_reference = $default_payment_type ? $default_payment_reference : null;

        return [
            'name' => $user['name'],
            'email' => $user['email'],
            'email_verified_at' => $confirmedDate,
            'password' => self::$hashPasword,
            'remember_token' => $this->randomString(),
            'created_at' => $createdDate,
            'updated_at' => $updatedDate,
            'type' => $user['type'],
            'blocked' => 0,
            'gender' => $user['gender'],
            'photo' => null,
            // one in 10 will just have null nif
            'nif' => mt_rand(1,10) == 2 ? null : ($user['type'] == 'employee' ? null : mt_rand(100000000, 199999999)),
            // one in 10 will just have null delivery address
            'default_delivery_address' => mt_rand(1, 10) == 2 ? null : ($user['type'] == 'employee' ? null : $this->faker->address),
            'default_payment_type' => $default_payment_type,
            'default_payment_reference' => $default_payment_reference,
            'deleted_at' => null,
        ];
    }

    private function addUsersToDatabase()
    {
        $this->command->line("Adding users to the database");
        $usersToAdd = Self::$fixedUsers;
        foreach (self::$userTypes as $userType => $totalUsers) {
            for ($i = 0; $i < $totalUsers; $i++) {                
                $gender = null;
                $name = null;
                $email = null;
                $this->randomName($this->faker, $gender, $name, $email, false);
                $usersToAdd[] = [
                    'type' => $userType,
                    'name' => $name,
                    'email' => $email,
                    'gender' => $gender
                ];
            }
        }
        $arrayToStore = [];
        $createdDate = DatabaseSeeder::$startDate->copy()->addMinutes(mt_rand(20000, 100000));
        foreach ($usersToAdd as $user) {
            $createdDate = $createdDate->copy()->addMinutes(mt_rand(10, 60));
            $arrayToStore[] = $this->createUserArrayToSaveOnDB($user, $createdDate);
            if (count($arrayToStore) >= DatabaseSeeder::$dbInsertBlockSize) {
                DB::table('users')->insert($arrayToStore);
                $this->command->line("Created " . count($arrayToStore) . " users");
                $arrayToStore = [];
            }
        }
        if (count($arrayToStore) >= 1) {
            DB::table('users')->insert($arrayToStore);
            $this->command->line("Created " . count($arrayToStore) . " users");
        }

        $this->command->line("Total users created: " . DB::table('users')->count());
        self::$dbUsers = DB::table('users')->get();
    }

    private function addPhotoFiles() 
    {
        $this->command->line("Copying users' photos");
        $this->fillPhotoFilesNames();
        $total = count($this->files_M) + count($this->files_F);
        $sortedUsers = self::$dbUsers->sortBy(function (stdClass $user) { 
            if ($user->id < 10 ) {
                return $user->id;
            }
            return match($user->type) {
                'board' => 20 + $user->id,
                'employee' => 1000 + $user->id,
                default => ($user->id < 50) ? 2000 + $user->id : 3000 + mt_rand(0, 100000),
            };
        });
        $i = 0;
        foreach($sortedUsers as $user) {
            $originalFilename = $user->gender == 'M' ? array_shift($this->files_M) : array_shift($this->files_F);
            if (!$originalFilename) {
                if ((count($this->files_M) == 0) && (count($this->files_F) == 0))
                    break;
            }
            if ($originalFilename) {
                $originalFilename = basename($originalFilename);
                $newFileName = $this->copyFileToStorage('photos', $originalFilename, 'users', $user->id);
                $user->photo = $newFileName;
                DB::table('users')->where('id', $user->id)->update(['photo' => $user->photo]);
                $i++;
                if ($i % 10 == 0) {
                    $this->command->line("User photo $i/$total copied");
                }                
            }            
        }
        $this->command->line("Total of $total user's photos were copied!");
        $this->directCopyFileToStorage('null_images', 'anonymous.png', 'users');
        $this->command->line("Image for user with no associated photo was copied!");
    }

    private function fillPhotoFilesNames()
    {
        $allFiles = collect(File::files(database_path('seeders/photos')));
        foreach ($allFiles as $f) {
            if (strpos($f->getPathname(), 'm_')) {
                $this->files_M[] = $f->getPathname();
            } else {
                $this->files_F[] = $f->getPathname();
            }
        }
        shuffle($this->files_M);
        shuffle($this->files_F);
    }

    private function softdeleteSomeUsers()
    {
        $this->command->line("Soft deleting some users");
        $ids = DB::table('users')->where('id', '>=', 13)->pluck('id')->toArray();
        shuffle($ids);
        $ids = array_slice($ids, 0, 20);
        $allIdToDelete = array_merge([3, 8, 10], $ids);
        $deleteDate = DatabaseSeeder::$startDate->copy()->addMinutes(mt_rand(150000, 200000));
        foreach($allIdToDelete as $id) {
            $deleteDate = $deleteDate->copy()->addMinutes(mt_rand(1000, 5000));
            DB::table('users')->where('id', $id)->update(['deleted_at' => $deleteDate]);
        }
        $this->command->line("Total of " . count($allIdToDelete) . " users were soft deleted!");
    }

}
