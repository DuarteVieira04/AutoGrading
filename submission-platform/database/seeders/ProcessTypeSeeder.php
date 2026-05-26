<?php

namespace Database\Seeders;

use App\Models\ProcessType;
use Illuminate\Database\Seeder;

class ProcessTypeSeeder extends Seeder
{
    public function run(): void
    {
        if (ProcessType::query()->exists()) {
            return;
        }

        ProcessType::create([
            'name' => 'Past',
        ]);
    }
}
