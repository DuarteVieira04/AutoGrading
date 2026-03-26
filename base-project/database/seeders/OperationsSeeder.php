<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory;
use Carbon\Carbon;

class OperationsSeeder extends Seeder
{
    use SeederUtils;

    private $faker = null;
    private $cards = null;
    private $users = null;

    public function run(): void
    {
        //$this->faker = Factory::create('pt_PT');
        $this->faker = Factory::create();
        $this->cards = DB::table('cards')->get();
        $this->users = DB::table('users')->where('type', '<>', 'employee')->get();

        $this->createOperationsToUpdateCardWhenOrdering();
        $this->saveAllCardsBalance();
        $this->command->line("Created operations to update balance when ordering");
    }

    private function getCard($member_id)
    {
        return  $this->cards->where('id', $member_id)->first();
    }

    private function getUser($card)
    {
        return  $this->users->where('id', $card->id)->first();
    }

    private function createOperationArrayToSaveOnDB($card, $order, $operationDate, $operationType, $value)
    {
        // $operationType = 'debit', 'credit', 'return' (credit due to a cancelation)
        $type = $operationType == 'debit' ? 'debit' : 'credit';
        $creditType = $type == 'credit' ? ($operationType == 'return' ? 'order_cancellation' : 'payment') : null;

        $payment_type = null;
        $payment_reference = null;
        if ($creditType == 'payment') {
            $user = $this->getUser($card);
            if ($user) {
                $payment_type = $user->default_payment_type;
                $payment_reference = $user->default_payment_reference;
            }
            if (!$payment_type || !$payment_reference) {
                $email = $user->email ?? $this->faker->email;
                $this->ramdomPaymentMethod($email, $payment_type, $payment_reference);
            }
        }
        if ($type == 'credit') {
            $card->balance = round($card->balance + $value, 2);    
        } else {
            $card->balance = round($card->balance - $value, 2);
        }
        if ($card->balance < 0) {
            $this->command->error("ATENTION: Card #$card->id balance is negative ($card->balance)");
        }
        return [
            'card_id' => $card->id,
            'type' => $type,
            'value' => $value,
            'date' => $operationDate->toDateString(),
            'debit_type' => $type == 'debit' ? 'order' : null,
            'credit_type' => $creditType,
            'payment_type' => $payment_type,
            'payment_reference' => $payment_reference,
            'order_id' => $order->id,
            'created_at' => $operationDate,
            'updated_at' => $operationDate,
        ];
    }

    private function handleOrderOperations($card, $order, &$arrayToStore)
    {
        if ($card) {
            $newCreditValue = 0;
            $orderDate = Carbon::parse($order->created_at);
            // No money to pay the order - calculate a new credit value to restore card balance

            if ($card->balance < $order->total) {
                $newCreditValue = round($order->total - $card->balance, 2);
                $newCreditValue = match(mt_rand(1,5)) {
                    1 => $newCreditValue,
                    2 => round((intdiv($newCreditValue, 5) + 1) * 5, 2),
                    default => round(mt_rand(intdiv($newCreditValue, 5) + 1, intdiv($newCreditValue, 5) + 50) * 5, 2)
                };
            }
            if ($newCreditValue > 0) {
                $operationDate = $orderDate->copy()->addMinutes(mt_rand(-100, -10));
                $arrayToStore[] = $this->createOperationArrayToSaveOnDB($card, $order, $operationDate, 'credit', $newCreditValue);
            }

            $operationDate = $orderDate->copy();
            $arrayToStore[] = $this->createOperationArrayToSaveOnDB($card, $order, $operationDate, 'debit', $order->total);

            if ($order->status == 'canceled') {
                $operationDate = $orderDate->copy()->addMinutes(mt_rand(1500, 3000));
                $arrayToStore[] = $this->createOperationArrayToSaveOnDB($card, $order, $operationDate, 'return', $order->total);
            }

            if (count($arrayToStore) >= DatabaseSeeder::$dbInsertBlockSize) {
                DB::table('operations')->insert($arrayToStore);
                $this->command->line("Created " . count($arrayToStore) . " transaction operations");
                $arrayToStore = [];
            }
        }
    }

    private function createOperationsToUpdateCardWhenOrdering()
    {
        $totalOrders = DB::table('orders')->count();
        $handledOrders = 0;
        $arrayToStore = [];
        DB::table('orders')->orderBy('id')->chunk(
            1000,
            function ($orders) use (&$arrayToStore, &$handledOrders, $totalOrders) {
                foreach ($orders as $order) {
                    $card = $this->getCard($order->member_id);
                    $this->handleOrderOperations($card, $order, $arrayToStore);
                }
                $handledOrders +=  1000;
                $this->command->line("Analysed $handledOrders/$totalOrders orders and created required transaction operations!");
            }
        );
        if (count($arrayToStore) >= 1) {
            DB::table('operations')->insert($arrayToStore);
            $this->command->line("Created " . count($arrayToStore) . " transaction operation records (last block)");
            $arrayToStore = [];
        }
    }

    private function saveAllCardsBalance()      
    {
        $this->command->line("Updating 'balance' of all cards - this may take a very long time!");
        foreach ($this->cards as $card) {
            DB::table('cards')->where('id', $card->id)->update(['balance' => $card->balance]);
        }
        $this->command->line("Completed updating 'balance' of all cards");
    }        
}
