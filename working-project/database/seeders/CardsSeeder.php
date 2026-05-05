<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CardsSeeder extends Seeder
{
    use SeederUtils;

    private $faker = null;
    public static $dbCards = null;
    public static $membershipFee = null;

    public function run(): void
    {
        //$this->faker = Factory::create('pt_PT');
        $this->faker = Factory::create();
        self::$membershipFee = DB::table('settings')->first()->membership_fee;
        $this->addCardsToUsers();
        $this->addMembershipFees();
        self::$dbCards = DB::table('cards')->orderBy('id')->get();
    }

    private function addCardsToUsers()
    {
        $this->command->line("Adding cards to the database");        
        $arrayToStore = [];
        $cardNumber = 100000;
        foreach (UsersSeeder::$dbUsers as $user) {
            if($user->type != 'employee') {
                $arrayToStore[] = [
                    'id' => $user->id,
                    'card_number' => $cardNumber,
                    'balance' => self::$membershipFee,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                    'deleted_at' => $user->deleted_at,
                ];
                $cardNumber++;
            }            
            if (count($arrayToStore) >= DatabaseSeeder::$dbInsertBlockSize) {
                DB::table('cards')->insert($arrayToStore);
                $this->command->line("Created " . count($arrayToStore) . " cards");
                $arrayToStore = [];
            }
        }
        if (count($arrayToStore) >= 1) {
            DB::table('cards')->insert($arrayToStore);
            $this->command->line("Created " . count($arrayToStore) . " cards");
        }
        $this->command->line("Total Cards created: " . DB::table('cards')->count());
        self::$dbCards = DB::table('cards')->orderBy('created_at', 'asc')->get();
    }

    private function addMembershipFees() 
    {
        $arrayToStore = [];
        foreach(self::$dbCards as $card) {
            $cardCreatedDate = Carbon::parse($card->created_at);
            $creditDate = $cardCreatedDate->copy()->addSeconds(mt_rand(30,500));
            $payment_type = null;
            $payment_reference = null;
            $user = UsersSeeder::$dbUsers->where('id', $card->id)->first();
            if ($user) {
                $payment_type = $user->default_payment_type;
                $payment_reference = $user->default_payment_reference;
            }
            if (!$payment_type || !$payment_reference) {
                $email = $user->email ?? $this->faker->email;
                //$email = $this->faker->email;
                $this->ramdomPaymentMethod($email, $payment_type, $payment_reference);
            }
            $valueCredited = self::$membershipFee + (mt_rand(0, 3) == 1 ? mt_rand(0, 100) * 5 : 0);
            $arrayToStore[] = [
                'card_id' => $card->id,
                'type' => 'credit',
                'value' => $valueCredited,
                'date' => $creditDate->toDateString(),
                'debit_type' => null,
                'credit_type' => 'payment',
                'payment_type' => $payment_type,
                'payment_reference' => $payment_reference,
                'order_id' => null,
                'created_at' => $creditDate,
                'updated_at' => $creditDate,
            ];
            $debitDate = $creditDate->copy()->addSeconds(mt_rand(30, 500));
            $arrayToStore[] = [
                'card_id' => $card->id,
                'type' => 'debit',
                'value' => self::$membershipFee,
                'date' => $debitDate->toDateString(),
                'debit_type' => 'membership_fee',
                'credit_type' => null,
                'payment_type' => null,
                'payment_reference' => null,
                'order_id' => null,
                'created_at' => $debitDate,
                'updated_at' => $debitDate,
            ];            
            DB::table('cards')->where('id', $card->id)->update(['balance' => round($valueCredited - self::$membershipFee, 2)]);
            if (count($arrayToStore) >= DatabaseSeeder::$dbInsertBlockSize) {
                DB::table('operations')->insert($arrayToStore);
                $this->command->line("Created " . count($arrayToStore) . " operations");
                $arrayToStore = [];
            }
        }
        if (count($arrayToStore) >= 1) {
            DB::table('operations')->insert($arrayToStore);
            $this->command->line("Created " . count($arrayToStore) . " operations");
        }
        $this->command->line("Total operations to pay the membership fee created: " . DB::table('operations')->count());
    }
}